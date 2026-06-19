<?php
if (!defined('ABSPATH')) {
    exit;
}

class AIPDB_Core
{
    private static $instance = null;

    /** @var AIPDB_Admin|null */
    public $admin = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function init()
    {
        $instance = self::get_instance();
        $instance->load_dependencies();
        $instance->init_hooks();
        $instance->load_textdomain();
    }

    public static function activate()
    {
        // Crear opciones por defecto
        $default_options = array(
            'aipdb_api_key' => '',
            'aipdb_enabled' => false,
            'aipdb_abuse_threshold' => 70,
            'aipdb_auto_report' => false,

            // Filtro por país (IP2Location → bypass de AbuseIPDB para países confiables)
            'aipdb_enable_country_blocking' => 0,
            'aipdb_trusted_countries' => array(),

            // Reglas de seguridad (eventos monitoreados)
            'aipdb_enabled_events' => array(),

            // Configuración general
            'aipdb_cache_duration' => 24,
            'aipdb_rate_limit_daily' => 900,
            'aipdb_whitelist_ips' => '',
            'aipdb_enable_logging' => true,
            'aipdb_log_retention_days' => 30,
            'aipdb_remove_data_on_uninstall' => false,

            // Blocklist manual
            'aipdb_manual_blocklist' => array(),
        );

        foreach ($default_options as $option_name => $default_value) {
            add_option($option_name, $default_value);
        }

        // Crear directorios de logs/data y protegerlos del acceso público
        $upload_dir = wp_upload_dir();
        self::protect_directory($upload_dir['basedir'] . '/aipdb-logs');
        self::protect_directory($upload_dir['basedir'] . '/aipdb-data');

        // Crear tabla para detecciones
        self::create_detections_table();

        // Programar tareas cron
        if (!wp_next_scheduled('aipdb_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'aipdb_daily_maintenance');
        }
    }

    /**
     * Crea un directorio dentro de uploads y lo bloquea para el acceso público
     * vía .htaccess (Apache) e index.php (fallback para listings).
     */
    private static function protect_directory($path)
    {
        if (!file_exists($path)) {
            wp_mkdir_p($path);
        }

        $htaccess = $path . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents(
                $htaccess,
                "# WP AbuseIPDB Integration: deny direct access\n" .
                "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
                "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n"
            );
        }

        $index = $path . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }
    }

    private static function create_detections_table()
    {
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

    public static function deactivate()
    {
        wp_clear_scheduled_hook('aipdb_daily_maintenance');
    }

    public static function uninstall()
    {
        if (get_option('aipdb_remove_data_on_uninstall', false)) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'aipdb_detections';
            $wpdb->query("DROP TABLE IF EXISTS $table_name");

            $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'aipdb_%'");
        }
    }

    private function load_dependencies()
    {
        require_once AIPDB_PLUGIN_PATH . 'includes/helper-functions.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-abuseipdb-api.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-geolocation.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-security-rules.php';
        require_once AIPDB_PLUGIN_PATH . 'includes/class-firewall.php';

        if (is_admin()) {
            require_once AIPDB_PLUGIN_PATH . 'admin/class-admin.php';
        }
    }

    private function init_hooks()
    {
        if (is_admin()) {
            $this->admin = new AIPDB_Admin();
        }

        add_action('aipdb_daily_maintenance', array($this, 'daily_maintenance'));

        // Inicializar Firewall (decide internamente si engancha o no).
        AIPDB_Firewall::get_instance();
    }

    private function load_textdomain()
    {
        load_plugin_textdomain(
            'wp-abuseipdb-integration',
            false,
            dirname(AIPDB_PLUGIN_BASENAME) . '/languages/'
        );
    }

    public function daily_maintenance()
    {
        // Limpiar logs antiguos
        $retention_days = get_option('aipdb_log_retention_days', 30);
        $logs_dir = wp_upload_dir()['basedir'] . '/aipdb-logs';
        $files = glob($logs_dir . '/*.log');
        $cutoff = time() - ($retention_days * DAY_IN_SECONDS);

        if (is_array($files)) {
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                }
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

        // Limpiar manual blocklist de entradas expiradas (Fase 1)
        $blocklist = get_option('aipdb_manual_blocklist', array());
        if (is_array($blocklist) && !empty($blocklist)) {
            $now = time();
            $changed = false;
            foreach ($blocklist as $ip => $entry) {
                if (!empty($entry['expires_at']) && (int) $entry['expires_at'] < $now) {
                    unset($blocklist[$ip]);
                    $changed = true;
                }
            }
            if ($changed) {
                update_option('aipdb_manual_blocklist', $blocklist, false);
            }
        }
    }
}
