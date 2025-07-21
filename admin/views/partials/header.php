<?php
if (!defined('ABSPATH')) exit;

$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'aipdb-dashboard';
$is_premium = AIPDB_Core::is_premium_active();
?>

<div class="wrap aipdb-admin-wrap">
    <h1 class="aipdb-main-title">
        <span class="aipdb-icon dashicons dashicons-shield-alt"></span>
        <?php _e('WP AbuseIPDB Integration', 'wp-abuseipdb-integration'); ?>
        <span class="aipdb-version">v<?php echo AIPDB_VERSION; ?></span>
        <?php if (!$is_premium): ?>
            <span class="aipdb-free-badge"><?php _e('FREE', 'wp-abuseipdb-integration'); ?></span>
        <?php else: ?>
            <span class="aipdb-premium-badge"><?php _e('PREMIUM', 'wp-abuseipdb-integration'); ?></span>
        <?php endif; ?>
    </h1>
    
    <?php if (!$is_premium): ?>
        <div class="aipdb-upgrade-notice">
            <p>
                <strong>🚀 <?php _e('Upgrade to Premium', 'wp-abuseipdb-integration'); ?></strong>
                <?php _e('Unlock advanced geolocation, enhanced rules, and priority support.', 'wp-abuseipdb-integration'); ?>
                <a href="https://tu-sitio.com/premium" target="_blank" class="button button-primary">
                    <?php _e('View Premium', 'wp-abuseipdb-integration'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
    
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
            <?php if (!$is_premium): ?>
                <span class="aipdb-premium-feature">PREMIUM</span>
            <?php endif; ?>
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
