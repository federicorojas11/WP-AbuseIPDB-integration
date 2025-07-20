<?php
/**
 * Plugin Name: WP AbuseIPDB Integration
 * Plugin URI: https://federicorojas.dev/wp-abuseipdb-integration
 * Description: Integración completa entre WordPress y AbuseIPDB para análisis automático de IPs maliciosas con geolocalización, reglas de seguridad personalizables y monitoreo de detecciones.
 * Version: 1.0.0
 * Author: Federico Rojas
 * Author URI: https://federicorojas.dev
 * License: GPL v2 or later
 * Text Domain: wp-abuseipdb-integration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Network: false
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('AIPDB_VERSION', '1.0.0');
define('AIPDB_PLUGIN_FILE', __FILE__);
define('AIPDB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIPDB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AIPDB_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Incluir archivos principales
require_once AIPDB_PLUGIN_PATH . 'includes/class-core.php';

// Hooks de activación/desactivación
register_activation_hook(__FILE__, array('AIPDB_Core', 'activate'));
register_deactivation_hook(__FILE__, array('AIPDB_Core', 'deactivate'));

// Inicializar el plugin
add_action('plugins_loaded', array('AIPDB_Core', 'init'));

// Hook de desinstalación
if (function_exists('register_uninstall_hook')) {
    register_uninstall_hook(__FILE__, array('AIPDB_Core', 'uninstall'));
}
