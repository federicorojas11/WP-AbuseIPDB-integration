<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Configuration {
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_configuration_settings'));
    }

    public function register_configuration_settings() {
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
            'aipdb_remove_data_on_uninstall',
        );
        foreach ($general_options as $option) {
            register_setting('aipdb_configuration_general', $option);
        }
    }
}
