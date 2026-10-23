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

    // Construir columnas dinámicamente: wpdb convierte null con formato
    // en 0/'' — omitir las columnas nulas preserva el NULL de la DB
    // (un score NULL es "N/A", no 0%).
    $data = array(
        'ip_address'   => array($ip_address, '%s'),
        'event_type'   => array($event_type, '%s'),
        'threat_level' => array($threat_level, '%s'),
        'abuseipdb_score'   => array($abuseipdb_score, '%d'),
        'country_code'      => array($country_code, '%s'),
        'user_agent'        => array($user_agent, '%s'),
        'request_uri'       => array($request_uri, '%s'),
        'detection_details' => array($detection_details, '%s'),
        'action_taken'      => array($action_taken, '%s'),
        'created_at'        => array(current_time('mysql'), '%s'),
    );

    $columns = array();
    $formats = array();
    foreach ($data as $column => $pair) {
        if ($pair[0] === null) {
            continue;
        }
        $columns[$column] = $pair[0];
        $formats[] = $pair[1];
    }

    $result = $wpdb->insert($table_name, $columns, $formats);
    
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
 * Return the real client IP safely.
 *
 * Rules:
 * - REMOTE_ADDR is always the base source of truth.
 * - Forwarded headers (X-Forwarded-For, CF-Connecting-IP, etc.) are only
 *   trusted when the immediate peer (REMOTE_ADDR) is in the configured
 *   trusted proxy list. This prevents attackers from spoofing their IP by
 *   injecting arbitrary forwarded headers.
 * - Trusted proxies can be configured as exact IPs or CIDR ranges.
 */
function aipdb_get_client_ip() {
    $remote_addr = aipdb_normalize_ip($_SERVER['REMOTE_ADDR'] ?? '');

    $trusted_proxies = aipdb_get_trusted_proxies();

    // No trusted proxies configured: use REMOTE_ADDR only and ignore all
    // forwarded headers. Log a debug notice if forwarded headers are present.
    if (empty($trusted_proxies)) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) || !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            aipdb_debug_log(
                'Forwarded IP headers ignored: no trusted proxies configured. REMOTE_ADDR=' . ($remote_addr ?: 'unknown'),
                'security'
            );
        }
        return $remote_addr ?: '0.0.0.0';
    }

    // If the immediate peer is NOT a trusted proxy, ignore all forwarded headers.
    if (!empty($remote_addr) && !aipdb_is_trusted_proxy_ip($remote_addr, $trusted_proxies)) {
        aipdb_debug_log(
            'Forwarded IP headers ignored: REMOTE_ADDR=' . $remote_addr . ' is not a trusted proxy.',
            'security'
        );
        return $remote_addr;
    }

    // The immediate peer is a trusted proxy — read forwarded headers.
    $headers_to_check = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
    );

    foreach ($headers_to_check as $header) {
        if (empty($_SERVER[$header])) {
            continue;
        }

        $value = $_SERVER[$header];

        if ($header === 'HTTP_X_FORWARDED_FOR' || $header === 'HTTP_CLIENT_IP') {
            // X-Forwarded-For can be a comma-separated list: client, proxy1, proxy2 ...
            // The left-most non-proxy IP is the real client.
            $parts = array_map('trim', explode(',', $value));
            foreach ($parts as $part) {
                $normalized = aipdb_normalize_ip($part);
                if (!empty($normalized) && aipdb_is_valid_ip($normalized)) {
                    return $normalized;
                }
            }
            continue;
        }

        // Single-value header (CF-Connecting-IP, X-Real-IP)
        $normalized = aipdb_normalize_ip($value);
        if (!empty($normalized) && aipdb_is_valid_ip($normalized)) {
            return $normalized;
        }
    }

    return $remote_addr ?: '0.0.0.0';
}

/**
 * Normalize an IP string: trim whitespace, strip ports, strip IPv6 zone IDs.
 * Returns an empty string if the result is not a valid IP.
 */
function aipdb_normalize_ip($ip) {
    if (!is_string($ip)) {
        return '';
    }

    $ip = trim($ip);
    if ($ip === '') {
        return '';
    }

    // Handle bracketed IPv6 with optional port: [::1]:1234
    if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $ip, $m)) {
        $ip = $m[1];
    }

    // Strip IPv6 zone ID: fe80::1%eth0
    $ip = preg_replace('/%.*$/', '', $ip);

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

/**
 * Return the trusted proxy list from options.
 * Entries can be exact IPs or CIDR ranges (e.g. "198.51.100.0/24").
 */
function aipdb_get_trusted_proxies() {
    $raw = get_option('aipdb_trusted_proxies', '');

    if (empty($raw)) {
        return array();
    }

    // Support both newline-separated (textarea) and array storage.
    if (is_array($raw)) {
        $lines = $raw;
    } else {
        $lines = preg_split('/[\r\n,]+/', (string) $raw);
    }

    $clean = array();
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        $clean[] = $line;
    }

    return array_values(array_unique($clean));
}

/**
 * Return true when $ip matches any entry in the trusted-proxy list.
 * Supports exact IP matches and CIDR ranges.
 */
function aipdb_is_trusted_proxy_ip($ip, $trusted_proxies = array()) {
    $ip = aipdb_normalize_ip($ip);
    if ($ip === '') {
        return false;
    }

    if (empty($trusted_proxies)) {
        $trusted_proxies = aipdb_get_trusted_proxies();
    }

    foreach ($trusted_proxies as $entry) {
        $entry = trim((string) $entry);
        if ($entry === '') {
            continue;
        }

        if (strpos($entry, '/') !== false) {
            if (aipdb_ip_in_cidr($ip, $entry)) {
                return true;
            }
        } elseif (aipdb_normalize_ip($entry) === $ip) {
            return true;
        }
    }

    return false;
}

/**
 * Check whether an IP address falls within a CIDR block.
 * Supports both IPv4 and IPv6.
 */
function aipdb_ip_in_cidr($ip, $cidr) {
    $ip    = aipdb_normalize_ip($ip);
    $cidr  = trim((string) $cidr);

    if ($ip === '' || $cidr === '' || strpos($cidr, '/') === false) {
        return false;
    }

    list($subnet, $mask_bits) = explode('/', $cidr, 2);
    $subnet    = aipdb_normalize_ip($subnet);
    $mask_bits = (int) $mask_bits;

    if ($subnet === '') {
        return false;
    }

    $ip_bin     = @inet_pton($ip);
    $subnet_bin = @inet_pton($subnet);

    if ($ip_bin === false || $subnet_bin === false) {
        return false;
    }

    // Must be the same address family.
    if (strlen($ip_bin) !== strlen($subnet_bin)) {
        return false;
    }

    $addr_len = strlen($ip_bin); // 4 for IPv4, 16 for IPv6
    $bits     = $addr_len * 8;

    if ($mask_bits < 0 || $mask_bits > $bits) {
        return false;
    }

    // Build binary mask.
    $mask_str = str_repeat('\xff', (int) floor($mask_bits / 8));
    $remainder = $mask_bits % 8;
    if ($remainder > 0) {
        $mask_str .= chr((0xff << (8 - $remainder)) & 0xff);
    }
    $mask_str = str_pad($mask_str, $addr_len, "\x00");

    return ($ip_bin & $mask_str) === ($subnet_bin & $mask_str);
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
 * Daily API quota — shared by the firewall and the security rules engine
 * so every AbuseIPDB call counts against the same budget.
 */
function aipdb_within_quota() {
    $limit = (int) get_option('aipdb_rate_limit_daily', 900);
    if ($limit <= 0) {
        return true; // sin límite configurado
    }
    $current = (int) get_transient('aipdb_daily_calls_' . date('Y-m-d'));
    return $current < $limit;
}

function aipdb_increment_quota() {
    $key = 'aipdb_daily_calls_' . date('Y-m-d');
    $current = (int) get_transient($key);
    // Mantener el contador vivo el resto del día; DAY_IN_SECONDS
    // sobrepasa un poco pero es inofensivo.
    set_transient($key, $current + 1, DAY_IN_SECONDS);
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
