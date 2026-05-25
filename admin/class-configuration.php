<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Configuration {
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_configuration_settings'));
    }

    public function register_configuration_settings() {
        // Tab Options (General)
        $general_options = array(
            'aipdb_api_key',
            'aipdb_enabled',
            'aipdb_abuse_threshold',
            'aipdb_auto_report',
            'aipdb_enable_logging',
            'aipdb_log_retention_days',
            'aipdb_cache_duration',
            'aipdb_rate_limit_daily',
            'aipdb_whitelist_ips',
        );
        foreach ($general_options as $option) {
            register_setting('aipdb_configuration_general', $option);
        }

        // Tab Advanced
        $advanced_options = array(
            'aipdb_emergency_mode',
            'aipdb_remove_data_on_uninstall',
            'aipdb_debug_mode',
            'aipdb_custom_user_agent',
            'aipdb_proxy_settings',
        );
        foreach ($advanced_options as $option) {
            register_setting('aipdb_configuration_advanced', $option);
        }
    }
}
