<?php
if (!defined('ABSPATH')) exit;

// Admin notice: detect forwarded headers with no trusted proxies configured.
$has_forwarded_headers = !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !empty($_SERVER['HTTP_CF_CONNECTING_IP'])
    || !empty($_SERVER['HTTP_X_REAL_IP']);
$trusted_proxies_empty = empty(get_option('aipdb_trusted_proxies', ''));
?>

<div class="aipdb-options-page">

    <?php if ($has_forwarded_headers && $trusted_proxies_empty): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('WP AbuseIPDB Integration:', 'wp-abuseipdb-integration'); ?></strong>
            <?php _e(
                'Your site is receiving forwarded IP headers (X-Forwarded-For or CF-Connecting-IP), but no trusted proxy IPs are configured. The plugin is currently ignoring those headers and using REMOTE_ADDR directly, which may not reflect the real visitor IP if your site is behind a reverse proxy or CDN.',
                'wp-abuseipdb-integration'
            ); ?>
            <?php _e('Add your proxy or CDN IP ranges in the <strong>Trusted Proxies</strong> field below.', 'wp-abuseipdb-integration'); ?>
        </p>
    </div>
    <?php endif; ?>

    <form method="post" action="options.php" class="aipdb-options-form">
        <?php settings_fields('aipdb_configuration_general'); ?>

        <table class="form-table">

            <tr>
                <th scope="row">
                    <label for="aipdb_api_key"><?php _e('AbuseIPDB API Key', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <div class="aipdb-input-group">
                        <input type="password"
                               id="aipdb_api_key"
                               name="aipdb_api_key"
                               value="<?php echo esc_attr(get_option('aipdb_api_key')); ?>"
                               class="regular-text" />
                        <button type="button" class="button aipdb-test-api" data-testfor="api_key">
                            <?php _e('Test Connection', 'wp-abuseipdb-integration'); ?>
                        </button>
                    </div>
                    <p class="description">
                        <?php _e('Get your free API key at', 'wp-abuseipdb-integration'); ?>
                        <a href="https://www.abuseipdb.com/account/api" target="_blank">AbuseIPDB.com</a>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Enable Protection', 'wp-abuseipdb-integration'); ?></th>
                <td>
                    <label for="aipdb_enabled">
                        <input type="checkbox"
                               id="aipdb_enabled"
                               name="aipdb_enabled"
                               value="1"
                               <?php checked(1, get_option('aipdb_enabled')); ?> />
                        <?php _e('Enable automatic IP verification with AbuseIPDB', 'wp-abuseipdb-integration'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aipdb_abuse_threshold"><?php _e('Abuse Threshold', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <input type="range"
                           id="aipdb_abuse_threshold"
                           name="aipdb_abuse_threshold"
                           min="1"
                           max="100"
                           value="<?php echo esc_attr(get_option('aipdb_abuse_threshold', 70)); ?>"
                           class="aipdb-threshold-slider" />
                    <output for="aipdb_abuse_threshold" class="aipdb-threshold-value">
                        <?php echo get_option('aipdb_abuse_threshold', 70); ?>%
                    </output>
                    <p class="description">
                        <?php _e('IPs with an abuse confidence score equal to or higher than this threshold will be blocked.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aipdb_cache_duration"><?php _e('Cache Duration (hours)', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <input type="number"
                           id="aipdb_cache_duration"
                           name="aipdb_cache_duration"
                           value="<?php echo esc_attr(get_option('aipdb_cache_duration', 24)); ?>"
                           min="1"
                           max="168"
                           class="small-text" />
                    <p class="description">
                        <?php _e('How long to cache API results per IP to reduce API calls.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aipdb_rate_limit_daily"><?php _e('Daily API Limit', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <input type="number"
                           id="aipdb_rate_limit_daily"
                           name="aipdb_rate_limit_daily"
                           value="<?php echo esc_attr(get_option('aipdb_rate_limit_daily', 900)); ?>"
                           min="100"
                           max="100000"
                           class="small-text" />
                    <p class="description">
                        <?php _e('Maximum API calls per day. Free accounts: 1000/day. The plugin stops checking new IPs once this limit is reached (fail-open).', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Enable Logging', 'wp-abuseipdb-integration'); ?></th>
                <td>
                    <label for="aipdb_enable_logging">
                        <input type="checkbox"
                               id="aipdb_enable_logging"
                               name="aipdb_enable_logging"
                               value="1"
                               <?php checked(1, get_option('aipdb_enable_logging', true)); ?> />
                        <?php _e('Write detailed logs to /uploads/aipdb-logs/ for debugging and monitoring', 'wp-abuseipdb-integration'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aipdb_log_retention_days"><?php _e('Log Retention (days)', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <input type="number"
                           id="aipdb_log_retention_days"
                           name="aipdb_log_retention_days"
                           value="<?php echo esc_attr(get_option('aipdb_log_retention_days', 30)); ?>"
                           min="7"
                           max="365"
                           class="small-text" />
                    <p class="description">
                        <?php _e('How many days to keep log files and detection records before they are automatically deleted.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aipdb_whitelist_ips"><?php _e('IP Whitelist', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <textarea id="aipdb_whitelist_ips"
                              name="aipdb_whitelist_ips"
                              rows="4"
                              cols="50"><?php echo esc_textarea(get_option('aipdb_whitelist_ips', '')); ?></textarea>
                    <p class="description">
                        <?php _e('One IP per line. These IPs are never checked or blocked. Supports CIDR ranges (e.g. 192.168.1.0/24).', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aipdb_trusted_proxies"><?php _e('Trusted Proxy IPs', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <textarea id="aipdb_trusted_proxies"
                              name="aipdb_trusted_proxies"
                              rows="4"
                              cols="50"><?php echo esc_textarea(get_option('aipdb_trusted_proxies', '')); ?></textarea>
                    <p class="description">
                        <?php _e(
                            'One IP or CIDR range per line. Only when the immediate connection (REMOTE_ADDR) matches one of these entries will the plugin read forwarded headers like X-Forwarded-For or CF-Connecting-IP. Leave empty to always use the direct connection IP.',
                            'wp-abuseipdb-integration'
                        ); ?>
                        <br>
                        <?php _e('<strong>Cloudflare:</strong> add their published IP ranges (cloudflare.com/ips). <strong>Nginx/Apache:</strong> add your load balancer IP.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Remove Data on Uninstall', 'wp-abuseipdb-integration'); ?></th>
                <td>
                    <label for="aipdb_remove_data_on_uninstall">
                        <input type="checkbox"
                               id="aipdb_remove_data_on_uninstall"
                               name="aipdb_remove_data_on_uninstall"
                               value="1"
                               <?php checked(1, get_option('aipdb_remove_data_on_uninstall')); ?> />
                        <?php _e('Delete all plugin data (detections, logs, settings) when the plugin is uninstalled', 'wp-abuseipdb-integration'); ?>
                    </label>
                    <p class="description">
                        <?php _e('<strong>Warning:</strong> This action is irreversible.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

        </table>

        <?php submit_button(__('Save Options', 'wp-abuseipdb-integration')); ?>
    </form>
</div>
