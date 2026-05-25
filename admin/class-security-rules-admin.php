<?php
if (!defined('ABSPATH')) exit;

/**
 * Administración de reglas de seguridad con interfaz tipo tabla
 * Basado en los Event Types definidos en el sistema
 */
class AIPDB_Security_Rules_Admin {
    
    private $event_types;
    
    public function __construct() {
        // Definir tipos de eventos basados en detections.php
        $this->event_types = array(
            'login_failure' => array(
                'title' => __('Login Failure', 'wp-abuseipdb-integration'),
                'description' => __('Detectar intentos fallidos de autenticación', 'wp-abuseipdb-integration'),
                'default' => true,
                'has_threshold' => true,
                'threshold_config' => array(
                    'attempts' => 3,
                    'duration' => 60 // minutos
                )
            ),
            'suspicious_request' => array(
                'title' => __('Suspicious Request', 'wp-abuseipdb-integration'),
                'description' => __('Monitorear requests con patrones sospechosos', 'wp-abuseipdb-integration'),
                'default' => true,
                'has_threshold' => false
            ),
            'comment_spam' => array(
                'title' => __('Comment Spam', 'wp-abuseipdb-integration'),
                'description' => __('Detectar comentarios marcados como spam', 'wp-abuseipdb-integration'),
                'default' => false,
                'has_threshold' => false
            ),
            'rest_api' => array(
                'title' => __('REST API', 'wp-abuseipdb-integration'),
                'description' => __('Monitorear abuso de la API REST', 'wp-abuseipdb-integration'),
                'default' => false,
                'has_threshold' => true,
                'threshold_config' => array(
                    'requests' => 100,
                    'duration' => 60 // minutos
                )
            ),
            'xmlrpc' => array(
                'title' => __('XML-RPC', 'wp-abuseipdb-integration'),
                'description' => __('Detectar accesos a xmlrpc.php', 'wp-abuseipdb-integration'),
                'default' => false,
                'has_threshold' => false
            ),
            '404_error' => array(
                'title' => __('404 Error', 'wp-abuseipdb-integration'),
                'description' => __('Monitorear errores 404 en rutas sensibles', 'wp-abuseipdb-integration'),
                'default' => false,
                'has_threshold' => true,
                'threshold_config' => array(
                    'errors' => 10,
                    'duration' => 30 // minutos
                )
            )
        );
        
        add_action('admin_init', array($this, 'register_security_settings'));
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_security_settings() {
        register_setting(
            'aipdb_security_rules',
            'aipdb_enabled_events',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_enabled_events'),
                'default' => array()
            )
        );
        
        // Registrar configuraciones de umbrales
        foreach ($this->event_types as $event_key => $event_config) {
            if ($event_config['has_threshold']) {
                register_setting('aipdb_security_rules', "aipdb_threshold_{$event_key}");
            }
        }
    }
    
    /**
     * Sanitizar eventos habilitados
     */
    public function sanitize_enabled_events($input) {
        if (!is_array($input)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($input as $event_type) {
            if (array_key_exists($event_type, $this->event_types)) {
                $sanitized[] = sanitize_text_field($event_type);
            }
        }
        
        return array_unique($sanitized);
    }
    
    /**
     * Renderizar página de configuración
     */
    public function render_page() {
        $enabled_events = get_option('aipdb_enabled_events', array());
        
        // Aplicar valores por defecto si es la primera vez
        if (empty($enabled_events)) {
            $default_events = array();
            foreach ($this->event_types as $event_key => $event_config) {
                if ($event_config['default']) {
                    $default_events[] = $event_key;
                }
            }
            $enabled_events = $default_events;
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Reglas de Seguridad', 'wp-abuseipdb-integration'); ?></h1>
            <p><?php _e('Configure qué tipos de eventos deben ser monitoreados y detectados por el sistema. Active o desactive cada tipo de detección según sus necesidades.', 'wp-abuseipdb-integration'); ?></p>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Configuración guardada correctamente.', 'wp-abuseipdb-integration'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('aipdb_security_rules'); ?>
                
                <div class="aipdb-security-table">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%;"><?php _e('Tipo de Evento', 'wp-abuseipdb-integration'); ?></th>
                                <th style="width: 15%;"><?php _e('Estado', 'wp-abuseipdb-integration'); ?></th>
                                <th style="width: 15%;"><?php _e('Activar/Desactivar', 'wp-abuseipdb-integration'); ?></th>
                                <th style="width: 30%;"><?php _e('Configuración', 'wp-abuseipdb-integration'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->event_types as $event_key => $event_config): ?>
                                <tr>
                                    <td>
                                        <div class="aipdb-event-title"><?php echo esc_html($event_config['title']); ?></div>
                                        <div class="aipdb-event-description"><?php echo esc_html($event_config['description']); ?></div>
                                    </td>
                                    <td>
                                        <?php $is_enabled = in_array($event_key, $enabled_events); ?>
                                        <span class="aipdb-status-badge <?php echo $is_enabled ? 'aipdb-status-enabled' : 'aipdb-status-disabled'; ?>">
                                            <?php echo $is_enabled ? 'ENABLED' : 'DISABLED'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <label class="aipdb-toggle-switch">
                                            <input type="checkbox" 
                                                   name="aipdb_enabled_events[]" 
                                                   value="<?php echo esc_attr($event_key); ?>"
                                                   class="aipdb-event-toggle"
                                                   data-event="<?php echo esc_attr($event_key); ?>"
                                                   <?php checked(in_array($event_key, $enabled_events)); ?>>
                                            <span class="aipdb-toggle-slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <?php if ($event_config['has_threshold']): ?>
                                            <?php $threshold_config = get_option("aipdb_threshold_{$event_key}", $event_config['threshold_config']); ?>
                                            <div class="aipdb-threshold-config">
                                                <?php if (isset($threshold_config['attempts'])): ?>
                                                    <label>
                                                        <?php _e('Max intentos:', 'wp-abuseipdb-integration'); ?>
                                                        <input type="number" 
                                                               name="aipdb_threshold_<?php echo esc_attr($event_key); ?>[attempts]"
                                                               value="<?php echo esc_attr($threshold_config['attempts']); ?>"
                                                               min="1" max="100" step="1">
                                                    </label>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($threshold_config['requests'])): ?>
                                                    <label>
                                                        <?php _e('Max requests:', 'wp-abuseipdb-integration'); ?>
                                                        <input type="number" 
                                                               name="aipdb_threshold_<?php echo esc_attr($event_key); ?>[requests]"
                                                               value="<?php echo esc_attr($threshold_config['requests']); ?>"
                                                               min="10" max="1000" step="10">
                                                    </label>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($threshold_config['errors'])): ?>
                                                    <label>
                                                        <?php _e('Max errores:', 'wp-abuseipdb-integration'); ?>
                                                        <input type="number" 
                                                               name="aipdb_threshold_<?php echo esc_attr($event_key); ?>[errors]"
                                                               value="<?php echo esc_attr($threshold_config['errors']); ?>"
                                                               min="1" max="50" step="1">
                                                    </label>
                                                <?php endif; ?>
                                                
                                                <label>
                                                    <?php _e('en', 'wp-abuseipdb-integration'); ?>
                                                    <input type="number" 
                                                           name="aipdb_threshold_<?php echo esc_attr($event_key); ?>[duration]"
                                                           value="<?php echo esc_attr($threshold_config['duration']); ?>"
                                                           min="1" max="1440" step="1">
                                                    <?php _e('minutos', 'wp-abuseipdb-integration'); ?>
                                                </label>
                                            </div>
                                        <?php else: ?>
                                            <em><?php _e('Sin configuración adicional', 'wp-abuseipdb-integration'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="aipdb-save-notice">
                    <strong><?php _e('Nota:', 'wp-abuseipdb-integration'); ?></strong>
                    <?php _e('Los cambios se aplicarán inmediatamente después de guardar. Las reglas desactivadas no generarán nuevas detecciones.', 'wp-abuseipdb-integration'); ?>
                </div>
                
                <?php submit_button(__('Guardar Configuración', 'wp-abuseipdb-integration'), 'primary', 'submit', true, array('style' => 'margin-top: 20px;')); ?>
            </form>
            
            <?php $this->render_statistics_section(); ?>
        </div>
        <?php
    }
    
    /**
     * Renderizar sección de estadísticas
     */
    private function render_statistics_section() {
        $enabled_events = get_option('aipdb_enabled_events', array());
        $stats = aipdb_get_detection_stats();
        
        ?>
        <div class="aipdb-security-table" style="margin-top: 30px;">
            <h3 style="padding: 15px; margin: 0; background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                <?php _e('Estadísticas de Detección (Últimos 30 días)', 'wp-abuseipdb-integration'); ?>
            </h3>
            
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Tipo de Evento', 'wp-abuseipdb-integration'); ?></th>
                        <th><?php _e('Estado', 'wp-abuseipdb-integration'); ?></th>
                        <th><?php _e('Detecciones', 'wp-abuseipdb-integration'); ?></th>
                        <th><?php _e('% del Total', 'wp-abuseipdb-integration'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_detections = array_sum(array_column($stats['by_event_type'], 'count'));
                    
                    foreach ($this->event_types as $event_key => $event_config): 
                        $event_count = 0;
                        foreach ($stats['by_event_type'] as $stat) {
                            if ($stat->event_type === $event_key) {
                                $event_count = $stat->count;
                                break;
                            }
                        }
                        $percentage = $total_detections > 0 ? round(($event_count / $total_detections) * 100, 1) : 0;
                        $is_enabled = in_array($event_key, $enabled_events);
                    ?>
                        <tr>
                            <td>
                                <div class="aipdb-event-title"><?php echo esc_html($event_config['title']); ?></div>
                            </td>
                            <td>
                                <span class="aipdb-status-badge <?php echo $is_enabled ? 'aipdb-status-enabled' : 'aipdb-status-disabled'; ?>">
                                    <?php echo $is_enabled ? 'ACTIVO' : 'INACTIVO'; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo number_format($event_count); ?></strong>
                            </td>
                            <td>
                                <?php echo $percentage; ?>%
                                <?php if ($percentage > 0): ?>
                                    <div style="background: #007cba; height: 4px; width: <?php echo min($percentage * 2, 100); ?>%; margin-top: 2px; border-radius: 2px;"></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Obtener eventos habilitados (para usar en class-security-rules.php)
     */
    public static function get_enabled_events() {
        return get_option('aipdb_enabled_events', array());
    }
}
