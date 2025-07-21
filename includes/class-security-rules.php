<?php
if (!defined('ABSPATH')) exit;

/**
 * Clase de detección de eventos de seguridad
 * Lee configuración de admin/class-security-rules-admin.php
 */
class AIPDB_Security_Rules {
    
    private static $instance = null;
    private $enabled_events = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
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
    
    aipdb_add_detection(
        $ip,
        'login_failure',
        $threat_level,
        null,
        null,
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['REQUEST_URI'] ?? '',
        $details,
        $action_taken
    );
    
    aipdb_debug_log("Login failure #{$failure_count} from IP: {$ip}, User: {$username}", 'security');
    }
    
    /**
     * Monitorear requests sospechosos
     */
    public function monitor_suspicious_requests() {
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
                aipdb_add_detection(
                    $ip,
                    'suspicious_request',
                    'high',
                    null,
                    null,
                    $user_agent,
                    $uri,
                    $description . ': ' . $uri,
                    'logged'
                );
                
                aipdb_debug_log("Suspicious request detected: {$pattern} from IP: {$ip}", 'security');
                break;
            }
        }
        
        // User agents sospechosos
        $suspicious_agents = array('sqlmap', 'nikto', 'nmap', 'masscan');
        foreach ($suspicious_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                aipdb_add_detection(
                    $ip,
                    'suspicious_request',
                    'high',
                    null,
                    null,
                    $user_agent,
                    $uri,
                    "User-Agent sospechoso detectado: {$agent}",
                    'logged'
                );
                break;
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
        
        aipdb_add_detection(
            $ip,
            'comment_spam',
            'medium',
            null,
            null,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REQUEST_URI'] ?? '',
            $details,
            'logged'
        );
        
        aipdb_debug_log("Comment spam detected from IP: {$ip}", 'security');
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
        
        aipdb_add_detection(
            $ip,
            'rest_api',
            $request_count >= $threshold_config['requests'] ? 'high' : 'medium',
            null,
            null,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $request->get_route(),
            $details,
            $action_taken
        );
        
        aipdb_debug_log("REST API request #{$request_count} from IP: {$ip}", 'security');
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
        
        aipdb_add_detection(
            $ip,
            'xmlrpc',
            'medium',
            null,
            null,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $uri,
            'Acceso a xmlrpc.php detectado',
            'logged'
        );
        
        aipdb_debug_log("XML-RPC access detected from IP: {$ip}", 'security');
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
        
        aipdb_add_detection(
            $ip,
            '404_error',
            $threat_level,
            null,
            null,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $uri,
            $details,
            $action_taken
        );
        
        aipdb_debug_log("404 error #{$error_count} from IP: {$ip}, URI: {$uri}", 'security');
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
}

// Inicializar solo si hay eventos habilitados
if (!empty(get_option('aipdb_enabled_events', array()))) {
    AIPDB_Security_Rules::get_instance();
}
