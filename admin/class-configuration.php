<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Configuration {
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_configuration_settings'));
        add_action('wp_ajax_aipdb_test_api', array($this, 'ajax_test_api'));
    }
    
    public function register_configuration_settings() {
        // Tab Options
        register_setting('AIPDB_Configuration_general', 'aipdb_api_key');
        register_setting('AIPDB_Configuration_general', 'aipdb_enabled');
        register_setting('AIPDB_Configuration_general', 'aipdb_abuse_threshold');
        register_setting('AIPDB_Configuration_general', 'aipdb_auto_report');
        register_setting('AIPDB_Configuration_general', 'aipdb_enable_logging');
        register_setting('AIPDB_Configuration_general', 'aipdb_log_retention_days');
        register_setting('AIPDB_Configuration_general', 'aipdb_cache_duration');
        register_setting('AIPDB_Configuration_general', 'aipdb_rate_limit_daily');
        register_setting('AIPDB_Configuration_general', 'aipdb_whitelist_ips');
        
        // Tab Advanced
        register_setting('aipdb_configuration_advanced', 'aipdb_emergency_mode');
        register_setting('aipdb_configuration_advanced', 'aipdb_remove_data_on_uninstall');
        register_setting('aipdb_configuration_advanced', 'aipdb_debug_mode');
        register_setting('aipdb_configuration_advanced', 'aipdb_custom_user_agent');
        register_setting('aipdb_configuration_advanced', 'aipdb_proxy_settings');
    }
    
    public function ajax_test_api() {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'wp-abuseipdb-integration'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('API key is required', 'wp-abuseipdb-integration')));
        }
        
        // Probar la API con una IP conocida (127.0.0.1)
        $api_url = 'https://api.abuseipdb.com/api/v2/check';
        $response = wp_remote_get(add_query_arg(array(
            'ipAddress' => '127.0.0.1',
            'maxAgeInDays' => 30,
            'verbose' => ''
        ), $api_url), array(
            'headers' => array(
                'Key' => $api_key,
                'Accept' => 'application/json'
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code === 200 && isset($data['data'])) {
            wp_send_json_success(array(
                'message' => __('API connection successful', 'wp-abuseipdb-integration'),
                'data' => $data['data']
            ));
        } else {
            $error_message = isset($data['errors']) ? implode(', ', $data['errors']) : __('Unknown error', 'wp-abuseipdb-integration');
            wp_send_json_error(array('message' => $error_message));
        }
    }
}
