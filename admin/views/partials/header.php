<?php
if (!defined('ABSPATH')) exit;

$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'aipdb-dashboard';

// Subtítulo contextual: estado de protección + última verificación.
$is_enabled = (bool) get_option('aipdb_enabled', false);
$last_check = (int) get_option('aipdb_last_api_check', 0);
if ($last_check) {
    $sync = sprintf(
        /* translators: %s: human-readable time difference */
        __('last sync %s ago', 'wp-abuseipdb-integration'),
        human_time_diff($last_check, current_time('timestamp'))
    );
} else {
    $sync = __('no API checks yet', 'wp-abuseipdb-integration');
}
$state = $is_enabled
    ? __('Protection active', 'wp-abuseipdb-integration')
    : __('Protection disabled', 'wp-abuseipdb-integration');
?>

<div class="wrap aipdb-admin-wrap">
    <h1 class="aipdb-main-title">
        <span class="aipdb-icon dashicons dashicons-shield-alt"></span>
        <?php _e('WP AbuseIPDB Integration', 'wp-abuseipdb-integration'); ?>
        <span class="aipdb-version">v<?php echo esc_html(AIPDB_VERSION); ?></span>
        <a href="https://www.buymeacoffee.com/federicorojas"
           target="_blank"
           rel="noopener noreferrer"
           class="aipdb-donate-link"
           title="<?php esc_attr_e('Support the development of this plugin', 'wp-abuseipdb-integration'); ?>">
            <span class="dashicons dashicons-heart"></span>
            <?php _e('Donate', 'wp-abuseipdb-integration'); ?>
        </a>
    </h1>

    <p class="aipdb-subtitle">
        <?php echo esc_html($state); ?> &middot; <?php echo esc_html($sync); ?>
    </p>

    <div class="aipdb-content-wrapper">
