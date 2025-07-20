<?php
if (!defined('ABSPATH')) exit;

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'options';
?>

<div class="aipdb-options-page">
    <!-- Navegación de tabs -->
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=aipdb-options&tab=options'); ?>" 
           class="nav-tab <?php echo $current_tab === 'options' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Options', 'wp-abuseipdb-integration'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=aipdb-options&tab=advanced'); ?>" 
           class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Advanced', 'wp-abuseipdb-integration'); ?>
        </a>
    </h2>
    
    <!-- Contenido del tab -->
    <div class="aipdb-tab-content">
        <?php if ($current_tab === 'options'): ?>
            <!-- Tab Options -->
            <form method="post" action="options.php" class="aipdb-options-form">
                <?php settings_fields('AIPDB_Configuration_general'); ?>
                
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
                                <?php _e('IPs with abuse confidence score equal to or higher than this threshold will be flagged as malicious.', 'wp-abuseipdb-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Auto-Report IPs', 'wp-abuseipdb-integration'); ?></th>
                        <td>
                            <label for="aipdb_auto_report">
                                <input type="checkbox" 
                                       id="aipdb_auto_report" 
                                       name="aipdb_auto_report" 
                                       value="1" 
                                       <?php checked(1, get_option('aipdb_auto_report')); ?> />
                                <?php _e('Automatically report malicious IPs to AbuseIPDB', 'wp-abuseipdb-integration'); ?>
                            </label>
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
                                <?php _e('How long to cache API results to reduce API calls.', 'wp-abuseipdb-integration'); ?>
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
                                <?php _e('Maximum API calls per day (free accounts: 1000, paid accounts: higher).', 'wp-abuseipdb-integration'); ?>
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
                                <?php _e('Enable detailed logging for debugging and monitoring', 'wp-abuseipdb-integration'); ?>
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
                                <?php _e('How many days to keep log files and detection records.', 'wp-abuseipdb-integration'); ?>
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
                                      rows="5" 
                                      cols="50"><?php echo esc_textarea(get_option('aipdb_whitelist_ips', '')); ?></textarea>
                            <p class="description">
                                <?php _e('One IP address per line. These IPs will never be checked or blocked. Supports CIDR notation (e.g., 192.168.1.0/24).', 'wp-abuseipdb-integration'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Options', 'wp-abuseipdb-integration')); ?>
            </form>
            
        <?php elseif ($current_tab === 'advanced'): ?>
            <!-- Tab Advanced -->
            <form method="post" action="options.php" class="aipdb-options-form">
                <?php settings_fields('AIPDB_Configuration_advanced'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Emergency Mode', 'wp-abuseipdb-integration'); ?></th>
                        <td>
                            <label for="aipdb_emergency_mode">
                                <input type="checkbox" 
                                       id="aipdb_emergency_mode" 
                                       name="aipdb_emergency_mode" 
                                       value="1" 
                                       <?php checked(1, get_option('aipdb_emergency_mode')); ?> />
                                <?php _e('Enable emergency mode (stricter detection rules)', 'wp-abuseipdb-integration'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, the plugin will use more aggressive detection rules and lower thresholds.', 'wp-abuseipdb-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'wp-abuseipdb-integration'); ?></th>
                        <td>
                            <label for="aipdb_debug_mode">
                                <input type="checkbox" 
                                       id="aipdb_debug_mode" 
                                       name="aipdb_debug_mode" 
                                       value="1" 
                                       <?php checked(1, get_option('aipdb_debug_mode')); ?> />
                                <?php _e('Enable verbose debug logging', 'wp-abuseipdb-integration'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Only enable for troubleshooting. This will generate extensive logs.', 'wp-abuseipdb-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="aipdb_custom_user_agent"><?php _e('Custom User Agent', 'wp-abuseipdb-integration'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="aipdb_custom_user_agent" 
                                   name="aipdb_custom_user_agent" 
                                   value="<?php echo esc_attr(get_option('aipdb_custom_user_agent', '')); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('Custom User-Agent string for API requests (leave blank for default).', 'wp-abuseipdb-integration'); ?>
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
                                <?php _e('Remove all plugin data when uninstalling', 'wp-abuseipdb-integration'); ?>
                            </label>
                            <p class="description">
                                <?php _e('WARNING: This will permanently delete all detections, logs, and settings when the plugin is uninstalled.', 'wp-abuseipdb-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Sección de integración con otros plugins -->
                    <tr>
                        <th colspan="2">
                            <h3><?php _e('Third-Party Integrations', 'wp-abuseipdb-integration'); ?></h3>
                        </th>
                    </tr>
                    
                    <tr>
                        <td colspan="2">
                            <div class="aipdb-integration-info">
                                <p><?php _e('This section will contain integration settings for other security plugins and services.', 'wp-abuseipdb-integration'); ?></p>
                                
                                <div class="aipdb-integration-placeholder">
                                    <h4><?php _e('Available Integrations:', 'wp-abuseipdb-integration'); ?></h4>
                                    <ul>
                                        <li><?php _e('WP-Cerber Security (Coming Soon)', 'wp-abuseipdb-integration'); ?></li>
                                        <li><?php _e('Wordfence Security (Planned)', 'wp-abuseipdb-integration'); ?></li>
                                        <li><?php _e('Sucuri Security (Planned)', 'wp-abuseipdb-integration'); ?></li>
                                        <li><?php _e('iThemes Security (Planned)', 'wp-abuseipdb-integration'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Advanced Settings', 'wp-abuseipdb-integration')); ?>
            </form>
        <?php endif; ?>
    </div>
</div>
