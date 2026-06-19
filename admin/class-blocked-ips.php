<?php
/**
 * Admin handler for the manual blocklist.
 * Backed by the option-based helpers in includes/helper-functions.php
 * (aipdb_block_ip / aipdb_unblock_ip / aipdb_get_manual_blocklist).
 */
if (!defined('ABSPATH')) {
    exit;
}

class AIPDB_Blocked_IPs
{
    public function __construct()
    {
        add_action('wp_ajax_aipdb_block_ip', array($this, 'ajax_block_ip'));
        add_action('wp_ajax_aipdb_unblock_ip', array($this, 'ajax_unblock_ip'));
    }

    /**
     * AJAX: add an IP to the manual blocklist.
     */
    public function ajax_block_ip()
    {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-abuseipdb-integration')));
        }

        $ip = isset($_POST['ip']) ? trim(sanitize_text_field(wp_unslash($_POST['ip']))) : '';
        $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';

        if (!aipdb_is_valid_ip($ip)) {
            wp_send_json_error(array('message' => __('Invalid IP address.', 'wp-abuseipdb-integration')));
        }

        if (aipdb_is_ip_manually_blocked($ip) !== false) {
            wp_send_json_error(array('message' => __('This IP is already blocked.', 'wp-abuseipdb-integration')));
        }

        $ok = aipdb_block_ip($ip, $reason);
        if (!$ok) {
            wp_send_json_error(array('message' => __('Could not block the IP.', 'wp-abuseipdb-integration')));
        }

        wp_send_json_success(array(
            'message' => sprintf(__('IP %s blocked.', 'wp-abuseipdb-integration'), $ip),
            'ip' => $ip,
            'reason' => $reason,
            'blocked_at' => time(),
        ));
    }

    /**
     * AJAX: remove an IP from the manual blocklist.
     */
    public function ajax_unblock_ip()
    {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-abuseipdb-integration')));
        }

        $ip = isset($_POST['ip']) ? trim(sanitize_text_field(wp_unslash($_POST['ip']))) : '';

        if (!aipdb_is_valid_ip($ip)) {
            wp_send_json_error(array('message' => __('Invalid IP address.', 'wp-abuseipdb-integration')));
        }

        $ok = aipdb_unblock_ip($ip);
        if (!$ok) {
            wp_send_json_error(array('message' => __('IP was not in the blocklist.', 'wp-abuseipdb-integration')));
        }

        wp_send_json_success(array(
            'message' => sprintf(__('IP %s unblocked.', 'wp-abuseipdb-integration'), $ip),
            'ip' => $ip,
        ));
    }
}
