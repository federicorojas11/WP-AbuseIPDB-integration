<?php
if (!defined('ABSPATH')) exit;

/**
 * Clase de detección de eventos de seguridad
 * Lee configuración de admin/class-security-rules-admin.php
 */
class AIPDB_Security_Rules {
    
    private static $instance = null;
    private $enabled_events = array();
    private $api_client;

    private $event_to_category_map = [
        'login_failure' => [18], // Brute-Force
        'suspicious_request' => [15], // Hacking
        'comment_spam' => [4], // Spam
        'rest_api' => [21], // Web App Attack
        'xmlrpc' => [5], // XML-RPC Attack
        '404_error' => [15], // Hacking
    ];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->api_client = new AIPDB_AbuseIPDB_API();
        $this->load_configuration();
        $this->init_hooks();
    }
    
    /**
     * Cargar configuración desde opciones de WordPress
     */
    private function load_configuration() {
        $this->enabled_events = get_option('aipdb_enabled_events', array());
        aipdb_debug_log('Security rules loaded: ' . implode(', ', $this->enabled_events), 'security');
    }
    
    /**
     * Inicializar hooks solo para eventos habilitados
     */
    private function init_hooks() {
        if (empty($this->enabled_events)) {
            return;
        }
        
        // Login Failure
        if (in_array('login_failure', $this->enabled_events)) {
            add_action('wp_login_failed', array($this, 'handle_login_failure'));
        }
        
        // Suspicious Request
        if (in_array('suspicious_request', $this->enabled_events)) {
            add_action('init', array($this, 'monitor_suspicious_requests'), 1);
        }
        
        // Comment Spam
        if (in_array('comment_spam', $this->enabled_events)) {
            add_action('comment_post', array($this, 'handle_comment_spam'), 10, 3);
        }
        
        // REST API
        if (in_array('rest_api', $this->enabled_events)) {
            add_action('rest_api_init', array($this, 'init_rest_api_monitoring'));
        }
        
        // XML-RPC
        if (in_array('xmlrpc', $this->enabled_events)) {
            add_action('init', array($this, 'monitor_xmlrpc_access'));
        }
        
        // 404 Error
        if (in_array('404_error', $this->enabled_events)) {
            add_action('wp', array($this, 'monitor_404_errors'));
        }
    }
    
    /**
     * Manejar fallo de login
     */
    public function handle_login_failure($username) {
        $ip = aipdb_get_client_ip();

        if ($this->is_ip_whitelisted($ip)) {
            return;
        }

        // Obtener configuración de umbral
        $threshold_config = get_option('aipdb_threshold_login_failure', array(
            'attempts' => 3,
            'duration' => 60
        ));

        // Incrementar contador
        $failure_count = $this->increment_counter($ip, 'login_failure', $threshold_config['duration']);

        // Determinar nivel de amenaza
        $threat_level = $this->calculate_threat_level($failure_count, $threshold_config['attempts']);

        // Registrar detección con información del contador
        $details = sprintf(
            'Login fallido para usuario: %s. Intento #%d en %d min.',
            sanitize_text_field($username),
            $failure_count,
            $threshold_config['duration']
        );

        // Definir acción tomada con contador
        $action_taken = 'logged';
        if ($failure_count >= $threshold_config['attempts']) {
            $action_taken = sprintf('threshold_exceeded (%d/%d)', $failure_count, $threshold_config['attempts']);
        }

        aipdb_debug_log("Login failure #{$failure_count} from IP: {$ip}, User: {$username}", 'security');

        $this->process_detection($ip, 'login_failure', $threat_level, $details, $action_taken);
    }
    
    /**
     * Monitorear requests sospechosos
     */
    public function monitor_suspicious_requests() {
        static $processed = false;
        if ($processed) {
            return;
        }

        $ip = aipdb_get_client_ip();
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($this->is_ip_whitelisted($ip)) {
            return;
        }
        
        // Patrones sospechosos
        $suspicious_patterns = array(
            '/.env' => 'Intento de acceso a archivo .env',
            '/wp-config.php' => 'Intento de acceso a wp-config.php',
            '/admin/' => 'Sondeo de rutas de administración',
            '/phpmyadmin/' => 'Intento de acceso a phpMyAdmin',
            'union select' => 'Posible inyección SQL',
            'drop table' => 'Posible inyección SQL destructiva',
            '<?php' => 'Intento de inyección de código PHP'
        );
        
        foreach ($suspicious_patterns as $pattern => $description) {
            if (stripos($uri, $pattern) !== false) {
                $details = $description . ': ' . $uri;
                aipdb_debug_log("Suspicious request detected: {$pattern} from IP: {$ip}", 'security');
                $this->process_detection($ip, 'suspicious_request', 'high', $details, 'logged');
                $processed = true;
                return;
            }
        }
        
        // User agents sospechosos
        $suspicious_agents = array('sqlmap', 'nikto', 'nmap', 'masscan');
        foreach ($suspicious_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                $details = "User-Agent sospechoso detectado: {$agent}";
                aipdb_debug_log("Suspicious user agent detected: {$agent} from IP: {$ip}", 'security');
                $this->process_detection($ip, 'suspicious_request', 'high', $details, 'logged');
                $processed = true;
                return;
            }
        }
    }
    
    /**
     * Manejar spam en comentarios
     */
    public function handle_comment_spam($comment_ID, $comment_approved, $commentdata) {
        if ($comment_approved !== 'spam') {
            return;
        }
        
        $ip = aipdb_get_client_ip();
        
        if ($this->is_ip_whitelisted($ip)) {
            return;
        }
        
        $details = sprintf(
            'Comentario spam detectado. Autor: %s, Email: %s',
            sanitize_text_field($commentdata['comment_author']),
            sanitize_email($commentdata['comment_author_email'])
        );
        
        aipdb_debug_log("Comment spam detected from IP: {$ip}", 'security');

        $this->process_detection($ip, 'comment_spam', 'medium', $details, 'logged');
    }
    
    /**
     * Inicializar monitoreo de REST API
     */
    public function init_rest_api_monitoring() {
        add_filter('rest_pre_dispatch', array($this, 'monitor_rest_api_requests'), 10, 3);
    }
    
    /**
     * Monitorear requests a REST API
     */
    public function monitor_rest_api_requests($result, $server, $request) {
        $ip = aipdb_get_client_ip();
        
        if ($this->is_ip_whitelisted($ip)) {
            return $result;
        }
        
        // Obtener configuración de umbral
        $threshold_config = get_option('aipdb_threshold_rest_api', array(
            'requests' => 100,
            'duration' => 60
        ));

        // Incrementar contador
        $request_count = $this->increment_counter($ip, 'rest_api', $threshold_config['duration']);

        // Definir acción tomada con contador
        $action_taken = 'logged';
        if ($request_count >= $threshold_config['requests']) {
            $action_taken = sprintf('threshold_exceeded (%d/%d)', $request_count, $threshold_config['requests']);
        }

        // Solo registrar si supera un cierto número para evitar spam de logs
        if ($request_count >= $threshold_config['requests'] || $request_count % 10 == 0) {
            $details = sprintf(
                'Request #%d a REST API en %d minutos. Ruta: %s',
                $request_count,
                $threshold_config['duration'],
                $request->get_route()
            );

            $threat_level = $request_count >= $threshold_config['requests'] ? 'high' : 'medium';

            aipdb_debug_log("REST API request #{$request_count} from IP: {$ip}", 'security');

            $this->process_detection($ip, 'rest_api', $threat_level, $details, $action_taken);
        }

        return $result;
    }
    
    /**
     * Monitorear acceso a XML-RPC
     */
    public function monitor_xmlrpc_access() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (stripos($uri, 'xmlrpc.php') === false) {
            return;
        }
        
        $ip = aipdb_get_client_ip();
        
        if ($this->is_ip_whitelisted($ip)) {
            return;
        }
        
        $details = 'Acceso a xmlrpc.php detectado';
        aipdb_debug_log("XML-RPC access detected from IP: {$ip}", 'security');

        $this->process_detection($ip, 'xmlrpc', 'medium', $details, 'logged');
    }
    
    /**
     * Monitorear errores 404
     */
    public function monitor_404_errors() {
        if (!is_404()) {
            return;
        }
        
        $ip = aipdb_get_client_ip();
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if ($this->is_ip_whitelisted($ip)) {
            return;
        }
        
        // Solo detectar 404s en rutas sensibles
        $sensitive_patterns = array('admin', 'config', '.php', '.sql', '.env', 'backup');
        $is_sensitive = false;
        
        foreach ($sensitive_patterns as $pattern) {
            if (stripos($uri, $pattern) !== false) {
                $is_sensitive = true;
                break;
            }
        }
        
        if ($is_sensitive) {
            // Obtener configuración de umbral
            $threshold_config = get_option('aipdb_threshold_404_error', array(
                'errors' => 10,
                'duration' => 30
            ));

            // Incrementar contador
            $error_count = $this->increment_counter($ip, '404_error', $threshold_config['duration']);

            // Determinar nivel de amenaza
            $threat_level = $this->calculate_threat_level($error_count, $threshold_config['errors']);

            // Definir acción tomada con contador
            $action_taken = 'logged';
            if ($error_count >= $threshold_config['errors']) {
                $action_taken = sprintf('threshold_exceeded (%d/%d)', $error_count, $threshold_config['errors']);
            }

            $details = sprintf(
                'Error 404 sospechoso #%d en %d min. URI: %s',
                $error_count,
                $threshold_config['duration'],
                $uri
            );

            aipdb_debug_log("404 error #{$error_count} from IP: {$ip}, URI: {$uri}", 'security');

            $this->process_detection($ip, '404_error', $threat_level, $details, $action_taken);
        }
    }
    
    /**
     * Incrementar contador de eventos
     */
    private function increment_counter($ip, $event_type, $duration_minutes) {
        $transient_key = 'aipdb_counter_' . md5($ip . $event_type);
        $current_count = get_transient($transient_key) ?: 0;
        $new_count = $current_count + 1;
        
        set_transient($transient_key, $new_count, $duration_minutes * MINUTE_IN_SECONDS);
        
        return $new_count;
    }
    
    /**
     * Calcular nivel de amenaza
     */
    private function calculate_threat_level($count, $threshold) {
        if ($count >= $threshold * 2) {
            return 'high';
        } elseif ($count >= $threshold) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Verificar si IP está en whitelist
     */
    private function is_ip_whitelisted($ip) {
        $whitelist = get_option('aipdb_whitelist_ips', '');
        if (empty($whitelist)) {
            return false;
        }
        
        $whitelist_ips = array_map('trim', explode(',', $whitelist));
        return in_array($ip, $whitelist_ips);
    }

    private function process_detection($ip, $event_type, $threat_level, $details, $action_taken) {
        $abuse_score = null;
        $country_code = null;

        $cached = get_transient('aipdb_score_' . md5($ip));
        if ($cached !== false && isset($cached['score'])) {
            $abuse_score  = $cached['score'];
            $country_code = $cached['country'] ?? null;
            aipdb_debug_log("IP {$ip} score {$abuse_score} served from cache", 'api');
        } else {
            $api_response = $this->api_client->check_ip($ip);
            if (!is_wp_error($api_response) && isset($api_response['data'])) {
                $abuse_score  = $api_response['data']['abuseConfidenceScore'];
                $country_code = $api_response['data']['countryCode'] ?? null;
                $hours = max(1, (int) get_option('aipdb_cache_duration', 24));
                set_transient(
                    'aipdb_score_' . md5($ip),
                    array('score' => $abuse_score, 'country' => $country_code, 'checked_at' => time()),
                    $hours * HOUR_IN_SECONDS
                );
                aipdb_debug_log("IP {$ip} has abuse score of {$abuse_score}", 'api');
            }
        }

        // Add detection to local DB
        $detection_id = aipdb_add_detection(
            $ip,
            $event_type,
            $threat_level,
            $abuse_score,
            $country_code,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REQUEST_URI'] ?? '',
            $details,
            $action_taken
        );

        // Auto-report if enabled and score is high enough
        $auto_report_enabled = get_option('aipdb_auto_report', false);
        $abuse_threshold = get_option('aipdb_abuse_threshold', 70);

        if ($auto_report_enabled && $abuse_score !== null && $abuse_score >= $abuse_threshold) {
            $categories = $this->event_to_category_map[$event_type] ?? [];
            if (!empty($categories)) {
                $this->api_client->report_ip($ip, $categories, $details);
                aipdb_debug_log("IP {$ip} auto-reported for event: {$event_type}", 'api');
            }
        }
    }
}

// Inicializar solo si hay eventos habilitados
if (!empty(get_option('aipdb_enabled_events', array()))) {
    AIPDB_Security_Rules::get_instance();
}
