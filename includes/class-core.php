<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Core {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function init() {
        $instance = self::get_instance();
        $instance->load_dependencies();
        $instance->init_hooks();
        $instance->load_textdomain();
    }
    
    public static function activate() {
        // Crear opciones por defecto
        $default_options = array(
            'aipdb_api_key' => '',
            'aipdb_enabled' => false,
            'aipdb_abuse_threshold' => 70,
            'aipdb_auto_report' => false,
            'aipdb_country_mode' => 'disabled',
            'aipdb_allowed_countries' => array(),
            'aipdb_blocked_countries' => array(),
            'aipdb_geo_provider' => 'ip2location_lite',
            
            // Eventos monitoreables 
            'aipdb_monitor_login_failures' => true,
            'aipdb_monitor_suspicious_requests' => true,
            'aipdb_monitor_comment_spam' => false,
            'aipdb_monitor_rest_api' => false,
            'aipdb_monitor_xmlrpc' => false,
            'aipdb_monitor_user_registration' => false,
            'aipdb_monitor_404_errors' => false,
            
            // Configuraciones avanzadas
            'aipdb_cache_duration' => 24,
            'aipdb_rate_limit_daily' => 900,
            'aipdb_emergency_mode' => false,
            'aipdb_whitelist_ips' => '',
            'aipdb_enable_logging' => true,
            'aipdb_log_retention_days' => 30,
        );
        
        foreach ($default_options as $option_name => $default_value) {
            add_option($option_name, $default_value);
        }
        
        // Crear directorio de logs
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/aipdb-logs';
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
        
        // Crear directorio de datos
        $data_dir = $upload_dir['basedir'] . '/aipdb-data';
        if (!file_exists($data_dir)) {
            wp_mkdir_p($data_dir);
        }
        
        // Crear tabla para detecciones
        self::create_detections_table();
        
        // Programar tareas cron
        if (!wp_next_scheduled('aipdb_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'aipdb_daily_maintenance');
        }
    }
    
    private static function create_detections_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aipdb_detections';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            event_type varchar(50) NOT NULL,
            threat_level varchar(20) NOT NULL,
            abuseipdb_score int(3) DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            request_uri text DEFAULT NULL,
            detection_details text DEFAULT NULL,
            action_taken varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY threat_level (threat_level)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public static function deactivate() {
        // Limpiar tareas cron
        wp_clear_scheduled_hook('aipdb_daily_maintenance');
    }
    
    public static function uninstall() {
        // Eliminar opciones si el usuario lo desea
        if (get_option('aipdb_remove_data_on_uninstall', false)) {
            global $wpdb;
            
            // Eliminar tabla de detecciones
            $table_name = $wpdb->prefix . 'aipdb_detections';
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
            
            // Eliminar opciones
            $options_to_delete = array(
                'aipdb_api_key', 'aipdb_enabled', 'aipdb_abuse_threshold',
                'aipdb_auto_report', 'aipdb_country_mode', 'aipdb_allowed_countries',
                'aipdb_blocked_countries', 'aipdb_geo_provider',
                // ... todas las opciones
            );
            
            foreach ($options_to_delete as $option) {
                delete_option($option);
            }
        }
    }
    
    private function load_dependencies() {
        require_once AIPDB_PLUGIN_PATH . 'includes/helper-functions.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-abuseipdb-api.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-security-monitor.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-geolocation.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-security-rules.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-license-manager.php';
        
        // Cargar admin solo en admin
        if (is_admin()) {
            require_once AIPDB_PLUGIN_PATH . 'admin/class-admin.php';
        }
    }
    
    private function init_hooks() {
        // Inicializar admin
        if (is_admin()) {
            new AIPDB_Admin();
        }
        
        // Inicializar monitor de seguridad independiente
        if (get_option('aipdb_enabled', false)) {
            new AIPDB_Security_Monitor();
        }
        
        // Mantenimiento diario
        add_action('aipdb_daily_maintenance', array($this, 'daily_maintenance'));
    }
    
    private function load_textdomain() {
        load_plugin_textdomain(
            'wp-abuseipdb-integration',
            false,
            dirname(AIPDB_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    public function daily_maintenance() {
        // Limpiar logs antiguos
        $retention_days = get_option('aipdb_log_retention_days', 30);
        $logs_dir = wp_upload_dir()['basedir'] . '/aipdb-logs';
        $files = glob($logs_dir . '/*.log');
        $cutoff = time() - ($retention_days * DAY_IN_SECONDS);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
        
        // Limpiar detecciones antiguas
        global $wpdb;
        $table_name = $wpdb->prefix . 'aipdb_detections';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
        
        // Limpiar transients antiguos
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_aipdb_%' 
             OR option_name LIKE '_transient_timeout_aipdb_%'"
        );
    }
    
    public static function is_premium_active() {
        $license_manager = new AIPDB_License_Manager();
        return $license_manager->is_license_valid();
    }
}
