<?php
if (!defined('ABSPATH')) exit;

$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'aipdb-dashboard';
?>

<div class="wrap aipdb-admin-wrap">
    <h1 class="aipdb-main-title">
        <span class="aipdb-icon dashicons dashicons-shield-alt"></span>
        <?php _e('WP AbuseIPDB Integration', 'wp-abuseipdb-integration'); ?>
        <span class="aipdb-version">v<?php echo AIPDB_VERSION; ?></span>
        <a href="https://www.buymeacoffee.com/federicorojas"
           target="_blank"
           rel="noopener noreferrer"
           class="aipdb-donate-link"
           title="<?php esc_attr_e('Support the development of this plugin', 'wp-abuseipdb-integration'); ?>">
            <span class="dashicons dashicons-heart"></span>
            <?php _e('Donate', 'wp-abuseipdb-integration'); ?>
        </a>
    </h1>

    <!-- Navegación por tabs -->
    <nav class="nav-tab-wrapper aipdb-nav-tabs">
        <a href="<?php echo admin_url('admin.php?page=aipdb-dashboard'); ?>"
           class="nav-tab <?php echo $current_page === 'aipdb-dashboard' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-dashboard"></span>
            <?php _e('Dashboard', 'wp-abuseipdb-integration'); ?>
        </a>

        <a href="<?php echo admin_url('admin.php?page=aipdb-country-blocking'); ?>"
           class="nav-tab <?php echo $current_page === 'aipdb-country-blocking' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-site"></span>
            <?php _e('Country Blocking', 'wp-abuseipdb-integration'); ?>
        </a>

        <a href="<?php echo admin_url('admin.php?page=aipdb-security-rules'); ?>"
           class="nav-tab <?php echo $current_page === 'aipdb-security-rules' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('Security Rules', 'wp-abuseipdb-integration'); ?>
        </a>

        <a href="<?php echo admin_url('admin.php?page=aipdb-detections'); ?>"
           class="nav-tab <?php echo $current_page === 'aipdb-detections' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-list-view"></span>
            <?php _e('Detections', 'wp-abuseipdb-integration'); ?>
        </a>

        <a href="<?php echo admin_url('admin.php?page=aipdb-configuration'); ?>"
           class="nav-tab <?php echo $current_page === 'aipdb-configuration' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php _e('Configuration', 'wp-abuseipdb-integration'); ?>
        </a>
    </nav>

    <div class="aipdb-content-wrapper">
