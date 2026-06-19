<?php
if (!defined('ABSPATH')) exit;

$threshold  = (int) get_option('aipdb_abuse_threshold', 70);
$api_key    = (string) get_option('aipdb_api_key', '');
$api_status = get_option('aipdb_api_status', 'unknown');
$api_limit  = max(1, (int) get_option('aipdb_rate_limit_daily', 900));
$api_used   = (int) (get_transient('aipdb_daily_calls_' . date('Y-m-d')) ?: 0);

$verified_cls = $api_status === 'ok' ? '' : 'is-unknown';
$verified_txt = $api_status === 'ok'
    ? __('Verified', 'wp-abuseipdb-integration')
    : ($api_status === 'error' ? __('Invalid key', 'wp-abuseipdb-integration') : __('Not verified', 'wp-abuseipdb-integration'));
?>

<div class="aipdb-settings">
    <h2 class="aipdb-page-heading" style="font-size:18px;margin:0 0 16px;"><?php _e('Settings', 'wp-abuseipdb-integration'); ?></h2>

    <form method="post" action="options.php" class="aipdb-options-form">
        <?php settings_fields('aipdb_configuration_general'); ?>

        <!-- API key -->
        <div class="aipdb-card">
            <h2><?php _e('AbuseIPDB API key', 'wp-abuseipdb-integration'); ?></h2>
            <p class="description" style="margin-top:-6px;">
                <?php _e('Generate a key from your AbuseIPDB account → API settings.', 'wp-abuseipdb-integration'); ?>
            </p>
            <div class="aipdb-api-key-row" style="margin-top:12px;">
                <input type="password" id="aipdb_api_key" name="aipdb_api_key"
                       value="<?php echo esc_attr($api_key); ?>" class="regular-text"
                       autocomplete="off" />
                <button type="button" class="button aipdb-test-api"><?php _e('Test Connection', 'wp-abuseipdb-integration'); ?></button>
                <span class="aipdb-verified <?php echo esc_attr($verified_cls); ?>"><?php echo esc_html($verified_txt); ?></span>
            </div>
            <div id="aipdb-api-status-message"></div>
            <p class="aipdb-plan-line">
                <?php printf(
                    /* translators: 1: daily limit, 2: used today */
                    __('Daily limit: %1$s checks · %2$s used today', 'wp-abuseipdb-integration'),
                    number_format_i18n($api_limit),
                    number_format_i18n($api_used)
                ); ?>
                &mdash; <a href="https://www.abuseipdb.com/account/api" target="_blank" rel="noopener noreferrer">AbuseIPDB.com</a>
            </p>
        </div>

        <!-- Detection threshold -->
        <div class="aipdb-card">
            <h2><?php _e('Detection threshold', 'wp-abuseipdb-integration'); ?></h2>
            <div class="aipdb-field">
                <label class="aipdb-field-label" for="aipdb_abuse_threshold">
                    <?php _e('Block IPs with confidence score ≥', 'wp-abuseipdb-integration'); ?>
                </label>
                <div class="aipdb-slider-row">
                    <input type="range" id="aipdb_abuse_threshold" name="aipdb_abuse_threshold"
                           class="aipdb-slider" min="1" max="100" value="<?php echo esc_attr($threshold); ?>" />
                    <span class="aipdb-slider-value"><?php echo (int) $threshold; ?>%</span>
                </div>
                <div class="aipdb-slider-scale">
                    <span><?php _e('1 · Safe', 'wp-abuseipdb-integration'); ?></span>
                    <span>50</span>
                    <span><?php _e('100 · Malicious', 'wp-abuseipdb-integration'); ?></span>
                </div>
                <p class="description">
                    <?php _e('Requests from IPs scoring at or above this value are blocked at request time.', 'wp-abuseipdb-integration'); ?>
                </p>
            </div>

            <div class="aipdb-toggle-row">
                <div class="aipdb-toggle-copy">
                    <strong><?php _e('Auto-report blocked IPs to AbuseIPDB', 'wp-abuseipdb-integration'); ?></strong>
                    <span><?php _e('Contribute your blocks back to the community database.', 'wp-abuseipdb-integration'); ?></span>
                </div>
                <label class="aipdb-switch">
                    <input type="checkbox" name="aipdb_auto_report" value="1" <?php checked(1, get_option('aipdb_auto_report')); ?> />
                    <span class="aipdb-slider-toggle"></span>
                </label>
            </div>
        </div>

        <!-- Protection & logging -->
        <div class="aipdb-card">
            <h2><?php _e('Protection & logging', 'wp-abuseipdb-integration'); ?></h2>

            <div class="aipdb-toggle-row">
                <div class="aipdb-toggle-copy">
                    <strong><?php _e('Enable request-time protection', 'wp-abuseipdb-integration'); ?></strong>
                    <span><?php _e('Check visitor IPs against AbuseIPDB and block abusive ones.', 'wp-abuseipdb-integration'); ?></span>
                </div>
                <label class="aipdb-switch">
                    <input type="checkbox" name="aipdb_enabled" value="1" <?php checked(1, get_option('aipdb_enabled')); ?> />
                    <span class="aipdb-slider-toggle"></span>
                </label>
            </div>

            <div class="aipdb-toggle-row">
                <div class="aipdb-toggle-copy">
                    <strong><?php _e('Enable logging', 'wp-abuseipdb-integration'); ?></strong>
                    <span><?php _e('Write detection and debug logs to a protected uploads folder.', 'wp-abuseipdb-integration'); ?></span>
                </div>
                <label class="aipdb-switch">
                    <input type="checkbox" name="aipdb_enable_logging" value="1" <?php checked(1, get_option('aipdb_enable_logging', true)); ?> />
                    <span class="aipdb-slider-toggle"></span>
                </label>
            </div>

            <table class="form-table" style="margin-top:6px;">
                <tr>
                    <th scope="row"><label for="aipdb_cache_duration"><?php _e('Cache duration (hours)', 'wp-abuseipdb-integration'); ?></label></th>
                    <td>
                        <input type="number" id="aipdb_cache_duration" name="aipdb_cache_duration"
                               value="<?php echo esc_attr(get_option('aipdb_cache_duration', 24)); ?>" min="1" max="168" class="small-text" />
                        <p class="description"><?php _e('How long to cache each AbuseIPDB result to reduce API calls.', 'wp-abuseipdb-integration'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="aipdb_rate_limit_daily"><?php _e('Daily API limit', 'wp-abuseipdb-integration'); ?></label></th>
                    <td>
                        <input type="number" id="aipdb_rate_limit_daily" name="aipdb_rate_limit_daily"
                               value="<?php echo esc_attr(get_option('aipdb_rate_limit_daily', 900)); ?>" min="100" max="100000" class="small-text" />
                        <p class="description"><?php _e('Once reached, the firewall fails open (allows traffic) until the next day.', 'wp-abuseipdb-integration'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="aipdb_log_retention_days"><?php _e('Log retention (days)', 'wp-abuseipdb-integration'); ?></label></th>
                    <td>
                        <input type="number" id="aipdb_log_retention_days" name="aipdb_log_retention_days"
                               value="<?php echo esc_attr(get_option('aipdb_log_retention_days', 30)); ?>" min="7" max="365" class="small-text" />
                        <p class="description"><?php _e('Detections and log files older than this are pruned daily.', 'wp-abuseipdb-integration'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Whitelist -->
        <div class="aipdb-card">
            <h2><?php _e('IP whitelist', 'wp-abuseipdb-integration'); ?></h2>
            <div class="aipdb-field">
                <textarea id="aipdb_whitelist_ips" name="aipdb_whitelist_ips" rows="5" class="large-text code"><?php echo esc_textarea(get_option('aipdb_whitelist_ips', '')); ?></textarea>
                <p class="description">
                    <?php _e('One IP per line. These IPs are never checked or blocked.', 'wp-abuseipdb-integration'); ?>
                </p>
            </div>
        </div>

        <!-- Data -->
        <div class="aipdb-card">
            <h2><?php _e('Data', 'wp-abuseipdb-integration'); ?></h2>
            <div class="aipdb-toggle-row">
                <div class="aipdb-toggle-copy">
                    <strong><?php _e('Remove all data on uninstall', 'wp-abuseipdb-integration'); ?></strong>
                    <span><?php _e('Permanently delete detections, logs and settings when the plugin is deleted.', 'wp-abuseipdb-integration'); ?></span>
                </div>
                <label class="aipdb-switch">
                    <input type="checkbox" name="aipdb_remove_data_on_uninstall" value="1" <?php checked(1, get_option('aipdb_remove_data_on_uninstall')); ?> />
                    <span class="aipdb-slider-toggle"></span>
                </label>
            </div>
        </div>

        <div class="aipdb-settings-actions">
            <?php submit_button(__('Save changes', 'wp-abuseipdb-integration'), 'primary', 'submit', false); ?>
        </div>
    </form>
</div>
