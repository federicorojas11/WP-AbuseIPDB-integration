<?php
if (!defined('ABSPATH')) exit;

/**
 * Log debug messages
 */
function aipdb_debug_log($message, $context = 'general') {
    if (!get_option('aipdb_enable_logging', true)) {
        return;
    }
    
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/aipdb-logs';

    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    // Asegurar protección contra acceso público incluso si el directorio
    // fue creado fuera del flujo de activación.
    if (!file_exists($log_dir . '/.htaccess')) {
        @file_put_contents(
            $log_dir . '/.htaccess',
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n"
        );
    }
    if (!file_exists($log_dir . '/index.php')) {
        @file_put_contents($log_dir . '/index.php', "<?php\n// Silence is golden.\n");
    }

    $log_file = $log_dir . '/debug-' . date('Y-m-d') . '.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $log_entry = "{$timestamp} [{$context}] {$message}\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Add detection to database
 */
function aipdb_add_detection($ip_address, $event_type, $threat_level, $abuseipdb_score = null, $country_code = null, $user_agent = null, $request_uri = null, $detection_details = null, $action_taken = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'aipdb_detections';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'ip_address' => $ip_address,
            'event_type' => $event_type,
            'threat_level' => $threat_level,
            'abuseipdb_score' => $abuseipdb_score,
            'country_code' => $country_code,
            'user_agent' => $user_agent,
            'request_uri' => $request_uri,
            'detection_details' => $detection_details,
            'action_taken' => $action_taken,
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result !== false) {
        aipdb_debug_log("Detection added: IP {$ip_address}, Type: {$event_type}, Threat: {$threat_level}", 'detection');
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Validate IP address
 */
function aipdb_is_valid_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

/**
 * Get client IP address
 */
function aipdb_get_client_ip() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (aipdb_is_valid_ip($ip)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Format country name from code
 */
function aipdb_get_country_name($country_code) {
    $countries = array(
        'US' => 'Estados Unidos',
        'CA' => 'Canadá',
        'MX' => 'México',
        'BR' => 'Brasil',
        'AR' => 'Argentina',
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'PE' => 'Perú',
        'VE' => 'Venezuela',
        'UY' => 'Uruguay',
        'ES' => 'España',
        'FR' => 'Francia',
        'DE' => 'Alemania',
        'IT' => 'Italia',
        'GB' => 'Reino Unido',
        'RU' => 'Rusia',
        'CN' => 'China',
        'JP' => 'Japón',
        'KR' => 'Corea del Sur',
        'IN' => 'India',
        'AU' => 'Australia',
        // Agregar más según necesidad
    );
    
    return isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
}

/**
 * Check if current user can manage plugin
 */
function aipdb_current_user_can_manage() {
    return current_user_can('manage_options');
}

/**
 * Sanitize array of values
 */
function aipdb_sanitize_array($array) {
    if (!is_array($array)) {
        return array();
    }
    
    return array_map('sanitize_text_field', $array);
}

/**
 * Get plugin status summary
 */
function aipdb_get_status_summary() {
    global $wpdb;
    $detections_table = $wpdb->prefix . 'aipdb_detections';
    
    $total_detections = $wpdb->get_var("SELECT COUNT(*) FROM $detections_table");
    $recent_detections = $wpdb->get_var(
        "SELECT COUNT(*) FROM $detections_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    
    return array(
        'api_configured' => !empty(get_option('aipdb_api_key')),
        'plugin_enabled' => get_option('aipdb_enabled', false),
        'api_calls_today' => get_transient('aipdb_daily_calls_' . date('Y-m-d')) ?: 0,
        'total_detections' => $total_detections,
        'recent_detections' => $recent_detections,
    );
}

/**
 * Manual blocklist helpers.
 *
 * Storage: option `aipdb_manual_blocklist`, keyed by IP. Each entry:
 *   array(
 *     'reason'      => string,
 *     'blocked_at'  => int (unix timestamp),
 *     'expires_at'  => int|null (null = permanent),
 *     'blocked_by'  => int (user ID, 0 if system),
 *   )
 */
function aipdb_get_manual_blocklist() {
    $list = get_option('aipdb_manual_blocklist', array());
    return is_array($list) ? $list : array();
}

function aipdb_block_ip($ip, $reason = '', $expires_at = null) {
    if (!aipdb_is_valid_ip($ip)) {
        return false;
    }
    $list = aipdb_get_manual_blocklist();
    $list[$ip] = array(
        'reason'     => sanitize_text_field($reason),
        'blocked_at' => time(),
        'expires_at' => $expires_at, // null = permanent
        'blocked_by' => function_exists('get_current_user_id') ? (int) get_current_user_id() : 0,
    );
    update_option('aipdb_manual_blocklist', $list, false);
    aipdb_debug_log("Manually blocked IP: {$ip} (reason: {$reason})", 'firewall');
    return true;
}

function aipdb_unblock_ip($ip) {
    $list = aipdb_get_manual_blocklist();
    if (!isset($list[$ip])) {
        return false;
    }
    unset($list[$ip]);
    update_option('aipdb_manual_blocklist', $list, false);
    aipdb_debug_log("Manually unblocked IP: {$ip}", 'firewall');
    return true;
}

/**
 * Returns the blocklist entry if the IP is currently blocked, false otherwise.
 * Auto-cleans expired entries on access.
 */
function aipdb_is_ip_manually_blocked($ip) {
    $list = aipdb_get_manual_blocklist();
    if (!isset($list[$ip])) {
        return false;
    }
    $entry = $list[$ip];
    if (!empty($entry['expires_at']) && (int) $entry['expires_at'] < time()) {
        aipdb_unblock_ip($ip);
        return false;
    }
    return $entry;
}

/**
 * Get detection statistics for dashboard
 */
function aipdb_get_detection_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aipdb_detections';
    
    // Estadísticas por tipo de evento (últimos 30 días)
    $event_stats = $wpdb->get_results(
        "SELECT event_type, COUNT(*) as count 
         FROM $table_name 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
         GROUP BY event_type 
         ORDER BY count DESC"
    );
    
    // Estadísticas por nivel de amenaza (últimos 30 días)
    $threat_stats = $wpdb->get_results(
        "SELECT threat_level, COUNT(*) as count 
         FROM $table_name 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
         GROUP BY threat_level 
         ORDER BY count DESC"
    );
    
    // Top IPs más detectadas (últimos 30 días)
    $top_ips = $wpdb->get_results(
        "SELECT ip_address, country_code, COUNT(*) as count 
         FROM $table_name 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
         GROUP BY ip_address 
         ORDER BY count DESC 
         LIMIT 10"
    );
    
    return array(
        'by_event_type' => $event_stats,
        'by_threat_level' => $threat_stats,
        'top_ips' => $top_ips
    );
}
