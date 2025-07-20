<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Dashboard {
    
    public function __construct() {
        add_action('wp_ajax_aipdb_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_aipdb_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_aipdb_save_quick_settings', array($this, 'ajax_save_quick_settings'));
    }
    
    /**
     * Obtener estadísticas para AJAX
     */
    public function ajax_get_stats() {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'wp-abuseipdb-integration'));
        }
        
        $stats = $this->get_dashboard_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * Test API connection via AJAX
     */
    public function ajax_test_api() {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'wp-abuseipdb-integration'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('API key is required', 'wp-abuseipdb-integration')));
        }
        
        // Probar la API con una IP conocida
        $test_result = $this->test_api_connection($api_key);
        
        if ($test_result['success']) {
            // Actualizar estado de la API
            update_option('aipdb_api_status', 'ok');
            update_option('aipdb_last_api_check', time());
            
            wp_send_json_success(array(
                'message' => __('API connection successful', 'wp-abuseipdb-integration'),
                'data' => $test_result['data']
            ));
        } else {
            update_option('aipdb_api_status', 'error');
            update_option('aipdb_last_api_check', time());
            
            wp_send_json_error(array('message' => $test_result['message']));
        }
    }
    
    /**
     * Guardar configuración rápida via AJAX
     */
    public function ajax_save_quick_settings() {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'wp-abuseipdb-integration'));
        }
        
        // Obtener y sanitizar datos
        $settings = array(
            'aipdb_api_key' => sanitize_text_field($_POST['aipdb_api_key'] ?? ''),
            'aipdb_enabled' => isset($_POST['aipdb_enabled']) ? 1 : 0,
            'aipdb_abuse_threshold' => intval($_POST['aipdb_abuse_threshold'] ?? 70),
            'aipdb_auto_report' => isset($_POST['aipdb_auto_report']) ? 1 : 0
        );
        
        // Validar umbral
        if ($settings['aipdb_abuse_threshold'] < 1 || $settings['aipdb_abuse_threshold'] > 100) {
            wp_send_json_error(array('message' => __('Abuse threshold must be between 1 and 100', 'wp-abuseipdb-integration')));
        }
        
        // Guardar configuraciones
        $saved = 0;
        foreach ($settings as $option_name => $value) {
            if (update_option($option_name, $value)) {
                $saved++;
            }
        }
        
        if ($saved > 0) {
            wp_send_json_success(array(
                'message' => __('Settings saved successfully', 'wp-abuseipdb-integration'),
                'saved_count' => $saved
            ));
        } else {
            wp_send_json_error(array('message' => __('No changes to save', 'wp-abuseipdb-integration')));
        }
    }
    
    /**
     * Obtener estadísticas para el dashboard
     */
    public function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array(
            'api_calls_today' => get_transient('aipdb_daily_calls_' . date('Y-m-d')) ?: 0,
            'api_calls_this_month' => $this->get_monthly_api_calls(),
            'total_detections' => 0,
            'recent_detections' => 0,
            'blocked_ips' => 0,
            'api_status' => get_option('aipdb_api_status', 'unknown'),
            'last_check' => get_option('aipdb_last_api_check', 0),
            'plugin_enabled' => get_option('aipdb_enabled', false),
            'cache_hit_ratio' => $this->calculate_cache_hit_ratio(),
        );
        
        // Estadísticas de detecciones si la tabla existe
        $table_name = $wpdb->prefix . 'aipdb_detections';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $stats['total_detections'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $stats['recent_detections'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $stats['blocked_ips'] = $wpdb->get_var(
                "SELECT COUNT(DISTINCT ip_address) FROM $table_name WHERE action_taken = 'blocked'"
            );
            
            // Top países esta semana
            $stats['top_countries'] = $wpdb->get_results(
                "SELECT country_code, COUNT(*) as count 
                 FROM $table_name 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                 AND country_code IS NOT NULL 
                 GROUP BY country_code 
                 ORDER BY count DESC 
                 LIMIT 5",
                ARRAY_A
            );
            
            // Tipos de eventos más comunes
            $stats['top_event_types'] = $wpdb->get_results(
                "SELECT event_type, COUNT(*) as count 
                 FROM $table_name 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                 GROUP BY event_type 
                 ORDER BY count DESC 
                 LIMIT 5",
                ARRAY_A
            );
        }
        
        return $stats;
    }
    
    /**
     * Obtener llamadas API del mes actual
     */
    private function get_monthly_api_calls() {
        $total = 0;
        $days_in_month = date('t');
        $current_month = date('Y-m');
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date_key = $current_month . '-' . sprintf('%02d', $day);
            $daily_calls = get_transient('aipdb_daily_calls_' . $date_key);
            if ($daily_calls !== false) {
                $total += intval($daily_calls);
            }
        }
        
        return $total;
    }
    
    /**
     * Calcular ratio de cache hits
     */
    private function calculate_cache_hit_ratio() {
        $hits = get_transient('aipdb_cache_hits') ?: 0;
        $misses = get_transient('aipdb_cache_misses') ?: 0;
        $total = $hits + $misses;
        
        if ($total == 0) {
            return 0;
        }
        
        return round(($hits / $total) * 100, 1);
    }
    
    /**
     * Test API connection
     */
    private function test_api_connection($api_key) {
        $api_url = 'https://api.abuseipdb.com/api/v2/check';
        
        $response = wp_remote_get(add_query_arg(array(
            'ipAddress' => '127.0.0.1', // Test con localhost
            'maxAgeInDays' => 30,
            'verbose' => ''
        ), $api_url), array(
            'headers' => array(
                'Key' => $api_key,
                'Accept' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code === 200 && isset($data['data'])) {
            return array(
                'success' => true,
                'data' => $data['data'],
                'message' => __('Connection successful', 'wp-abuseipdb-integration')
            );
        } else if ($response_code === 401) {
            return array(
                'success' => false,
                'message' => __('Invalid API key', 'wp-abuseipdb-integration')
            );
        } else if ($response_code === 429) {
            return array(
                'success' => false,
                'message' => __('API rate limit exceeded', 'wp-abuseipdb-integration')
            );
        } else {
            $error_message = isset($data['errors']) ? 
                implode(', ', $data['errors']) : 
                __('Unknown API error', 'wp-abuseipdb-integration');
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    /**
     * Obtener actividad reciente
     */
    public function get_recent_activity($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aipdb_detections';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
        
        $activity = array();
        foreach ($results as $detection) {
            $activity[] = array(
                'type' => $this->get_activity_type($detection->event_type),
                'message' => $this->format_activity_message($detection),
                'ip' => $detection->ip_address,
                'score' => $detection->abuseipdb_score ?: 0,
                'timestamp' => strtotime($detection->created_at),
                'threat_level' => $detection->threat_level,
                'action_taken' => $detection->action_taken
            );
        }
        
        return $activity;
    }
    
    /**
     * Obtener tipo de actividad para iconos
     */
    private function get_activity_type($event_type) {
        $types = array(
            'login_failure' => 'warning',
            'suspicious_request' => 'shield',
            'comment_spam' => 'admin-comments',
            'rest_api' => 'rest-api',
            'xmlrpc' => 'admin-tools',
            '404_error' => 'dismiss',
            'user_registration' => 'admin-users'
        );
        
        return isset($types[$event_type]) ? $types[$event_type] : 'info';
    }
    
    /**
     * Formatear mensaje de actividad
     */
    private function format_activity_message($detection) {
        $messages = array(
            'login_failure' => __('Failed login attempt detected', 'wp-abuseipdb-integration'),
            'suspicious_request' => __('Suspicious request intercepted', 'wp-abuseipdb-integration'),
            'comment_spam' => __('Potential comment spam detected', 'wp-abuseipdb-integration'),
            'rest_api' => __('Suspicious REST API activity', 'wp-abuseipdb-integration'),
            'xmlrpc' => __('XML-RPC abuse attempt detected', 'wp-abuseipdb-integration'),
            '404_error' => __('Multiple 404 errors from same IP', 'wp-abuseipdb-integration'),
            'user_registration' => __('Suspicious user registration attempt', 'wp-abuseipdb-integration')
        );
        
        $base_message = isset($messages[$detection->event_type]) ? 
            $messages[$detection->event_type] : 
            __('Security event detected', 'wp-abuseipdb-integration');
        
        if ($detection->action_taken) {
            $base_message .= ' - ' . ucfirst(str_replace('_', ' ', $detection->action_taken));
        }
        
        return $base_message;
    }
    
    /**
     * Obtener estadísticas de rendimiento del sistema
     */
    public function get_performance_stats() {
        return array(
            'memory_usage' => $this->format_bytes(memory_get_peak_usage()),
            'cache_size' => $this->get_cache_size(),
            'database_size' => $this->get_database_size(),
            'log_files_size' => $this->get_log_files_size(),
            'average_response_time' => get_option('aipdb_avg_response_time', 0)
        );
    }
    
    /**
     * Formatear bytes en unidades legibles
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Obtener tamaño del cache
     */
    private function get_cache_size() {
        global $wpdb;
        
        $cache_options = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) 
             FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_aipdb_%'"
        );
        
        return $this->format_bytes($cache_options ?: 0);
    }
    
    /**
     * Obtener tamaño de la base de datos del plugin
     */
    private function get_database_size() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aipdb_detections';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return $this->format_bytes(0);
        }
        
        $size = $wpdb->get_var(
            "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) 
             FROM information_schema.TABLES 
             WHERE table_schema = DATABASE() 
             AND table_name = '$table_name'"
        );
        
        return $this->format_bytes(($size ?: 0) * 1024 * 1024);
    }
    
    /**
     * Obtener tamaño de archivos de log
     */
    private function get_log_files_size() {
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/aipdb-logs';
        
        if (!file_exists($logs_dir)) {
            return $this->format_bytes(0);
        }
        
        $total_size = 0;
        $files = glob($logs_dir . '/*.log');
        
        foreach ($files as $file) {
            $total_size += filesize($file);
        }
        
        return $this->format_bytes($total_size);
    }
    
    /**
     * Obtener configuración del sistema
     */
    public function get_system_info() {
        return array(
            'php_version' => phpversion(),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => AIPDB_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'curl_enabled' => extension_loaded('curl'),
            'openssl_enabled' => extension_loaded('openssl'),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'multisite' => is_multisite(),
        );
    }
}
