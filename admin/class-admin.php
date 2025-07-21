<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Admin {
    
    private $pages = array();
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        $this->load_admin_pages();
    }
    
    public function add_admin_menu() {
        // Página principal
        $main_page = add_menu_page(
            'WP AbuseIPDB Integration',
            'AbuseIPDB',
            'manage_options',
            'aipdb-dashboard',
            array($this, 'display_admin_page'),
            'dashicons-shield-alt',
            30
        );
        
        // Subpáginas
        $subpages = array(
            array(
                'parent_slug' => 'aipdb-dashboard',
                'page_title' => 'Dashboard - AbuseIPDB',
                'menu_title' => 'Dashboard',
                'capability' => 'manage_options',
                'menu_slug' => 'aipdb-dashboard',
                'function' => array($this, 'display_admin_page')
            ),
            array(
                'parent_slug' => 'aipdb-dashboard',
                'page_title' => 'Country Blocking - AbuseIPDB',
                'menu_title' => 'Country Blocking',
                'capability' => 'manage_options',
                'menu_slug' => 'aipdb-country-blocking',
                'function' => array($this, 'display_admin_page')
            ),
            array(
                'parent_slug' => 'aipdb-dashboard',
                'page_title' => 'Security Rules - AbuseIPDB',
                'menu_title' => 'Security Rules',
                'capability' => 'manage_options',
                'menu_slug' => 'aipdb-security-rules',
                'function' => array($this, 'display_admin_page')
            ),
            array(
                'parent_slug' => 'aipdb-dashboard',
                'page_title' => 'Detections - AbuseIPDB',
                'menu_title' => 'Detections',
                'capability' => 'manage_options',
                'menu_slug' => 'aipdb-detections',
                'function' => array($this, 'display_admin_page')
            ),
            array(
                'parent_slug' => 'aipdb-dashboard',
                'page_title' => 'Configuration - AbuseIPDB',
                'menu_title' => 'Configuration',
                'capability' => 'manage_options',
                'menu_slug' => 'aipdb-configuration',
                'function' => array($this, 'display_admin_page')
            )
        );
        
        foreach ($subpages as $subpage) {
            add_submenu_page(
                $subpage['parent_slug'],
                $subpage['page_title'],
                $subpage['menu_title'],
                $subpage['capability'],
                $subpage['menu_slug'],
                $subpage['function']
            );
        }
    }
    
    public function register_settings() {
        // Configuraciones generales
        register_setting('aipdb_general', 'aipdb_api_key');
        register_setting('aipdb_general', 'aipdb_enabled');
        register_setting('aipdb_general', 'aipdb_abuse_threshold');
        register_setting('aipdb_general', 'aipdb_auto_report');
        
        // Configuraciones de países
        register_setting('aipdb_countries', 'aipdb_country_mode');
        register_setting('aipdb_countries', 'aipdb_allowed_countries');
        register_setting('aipdb_countries', 'aipdb_blocked_countries');
        register_setting('aipdb_countries', 'aipdb_geo_provider');
        register_setting('aipdb_countries', 'aipdb_geo_api_key');
        
        // Configuraciones de monitoreo
        $monitor_options = array(
            'aipdb_monitor_login_failures', 'aipdb_monitor_suspicious_requests',
            'aipdb_monitor_comment_spam', 'aipdb_monitor_rest_api',
            'aipdb_monitor_xmlrpc', 'aipdb_monitor_user_registration',
            'aipdb_monitor_404_errors'
        );
        
        foreach ($monitor_options as $option) {
            register_setting('aipdb_security_rules', $option);
        }
        
        // Configuraciones avanzadas
        register_setting('AIPDB_Configuration', 'aipdb_cache_duration');
        register_setting('AIPDB_Configuration', 'aipdb_rate_limit_daily');
        register_setting('AIPDB_Configuration', 'aipdb_emergency_mode');
        register_setting('AIPDB_Configuration', 'aipdb_whitelist_ips');
        register_setting('AIPDB_Configuration', 'aipdb_enable_logging');
        register_setting('AIPDB_Configuration', 'aipdb_log_retention_days');
    }
    
    public function enqueue_admin_scripts($hook) {
        // Solo cargar en nuestras páginas
        if (strpos($hook, 'aipdb-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'aipdb-admin-css',
            AIPDB_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            AIPDB_VERSION
        );
        
        wp_enqueue_script(
            'aipdb-admin-js',
            AIPDB_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'wp-util'),
            AIPDB_VERSION,
            true
        );
        
        // Variables para JavaScript
        wp_localize_script('aipdb-admin-js', 'aipdb_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aipdb_admin_nonce'),
            'current_page' => $this->get_current_page(),
            'is_premium' => AIPDB_Core::is_premium_active(),
            'strings' => array(
                'saving' => __('Guardando...', 'wp-abuseipdb-integration'),
                'saved' => __('Guardado', 'wp-abuseipdb-integration'),
                'error' => __('Error al guardar', 'wp-abuseipdb-integration'),
            )
        ));
    }
    
    public function display_admin_page() {
        $current_page = $this->get_current_page();
        
        // Header común
        include AIPDB_PLUGIN_PATH . 'admin/views/partials/header.php';
        
        // Contenido específico de la página
        switch ($current_page) {
            case 'aipdb-dashboard':
                include AIPDB_PLUGIN_PATH . 'admin/views/dashboard.php';
                break;
            case 'aipdb-country-blocking':
                include AIPDB_PLUGIN_PATH . 'admin/views/country-blocking.php';
                break;
            case 'aipdb-security-rules':
                include AIPDB_PLUGIN_PATH . 'admin/views/security-rules.php';
                break;
            case 'aipdb-detections':
                include AIPDB_PLUGIN_PATH . 'admin/views/detections.php';
                break;
            case 'aipdb-configuration':
                include AIPDB_PLUGIN_PATH . 'admin/views/configuration.php';
                break;
            default:
                include AIPDB_PLUGIN_PATH . 'admin/views/dashboard.php';
        }
        
        // Footer común
        include AIPDB_PLUGIN_PATH . 'admin/views/partials/footer.php';
    }
    
    private function get_current_page() {
        return isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'aipdb-dashboard';
    }
    
    private function load_admin_pages() {
        require_once AIPDB_PLUGIN_PATH . 'admin/class-dashboard.php';
        require_once AIPDB_PLUGIN_PATH . 'admin/class-country-blocking.php';
        require_once AIPDB_PLUGIN_PATH . 'admin/class-security-rules-admin.php';
        require_once AIPDB_PLUGIN_PATH . 'admin/class-detections.php';
        require_once AIPDB_PLUGIN_PATH . 'admin/class-configuration.php';
        
        // Inicializar páginas
        new AIPDB_Dashboard();
        new AIPDB_Country_Blocking();
        new AIPDB_Security_Rules_Admin();
        new AIPDB_Detections();
        new AIPDB_Configuration();
    }
    
    public function admin_notices() {
        // Verificar si la API key está configurada
        if (!get_option('aipdb_api_key') && $this->is_aipdb_page()) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('WP AbuseIPDB Integration:', 'wp-abuseipdb-integration'); ?></strong>
                    <?php _e('Para comenzar, configura tu API key de AbuseIPDB.', 'wp-abuseipdb-integration'); ?>
                    <a href="<?php echo admin_url('admin.php?page=aipdb-configuration'); ?>" class="button button-secondary">
                        <?php _e('Configurar ahora', 'wp-abuseipdb-integration'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    private function is_aipdb_page() {
        $current_screen = get_current_screen();
        return $current_screen && strpos($current_screen->id, 'aipdb-') !== false;
    }
}
