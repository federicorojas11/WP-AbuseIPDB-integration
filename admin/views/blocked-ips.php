<?php
/**
 * View: Blocked IPs
 *
 * Lists the manual blocklist and allows adding/removing entries.
 */
if (!defined('ABSPATH')) {
    exit;
}

$blocklist = aipdb_get_manual_blocklist();

// Sort by most recent blocks first.
uasort($blocklist, function ($a, $b) {
    return ((int) ($b['blocked_at'] ?? 0)) <=> ((int) ($a['blocked_at'] ?? 0));
});
?>

<div class="aipdb-blocked-ips-page">

    <!-- Add IP manually -->
    <div class="aipdb-card">
        <h2><?php _e('Block an IP manually', 'wp-abuseipdb-integration'); ?></h2>
        <p class="description">
            <?php _e('Add an IP to the manual blocklist. Blocked IPs are denied access to the front-end of the site immediately, without consulting AbuseIPDB.', 'wp-abuseipdb-integration'); ?>
        </p>

        <form id="aipdb-block-ip-form" class="aipdb-inline-form">
            <div class="aipdb-form-row">
                <input type="text"
                       id="aipdb-block-ip-input"
                       name="ip"
                       placeholder="192.0.2.1"
                       class="regular-text"
                       required />

                <input type="text"
                       id="aipdb-block-ip-reason"
                       name="reason"
                       placeholder="<?php esc_attr_e('Reason (optional)', 'wp-abuseipdb-integration'); ?>"
                       class="regular-text" />

                <button type="submit" class="button button-primary">
                    <?php _e('Block IP', 'wp-abuseipdb-integration'); ?>
                </button>
            </div>
            <div id="aipdb-block-ip-message"></div>
        </form>
    </div>

    <!-- Blocklist table -->
    <div class="aipdb-card">
        <h2>
            <?php _e('Manually blocked IPs', 'wp-abuseipdb-integration'); ?>
            <span class="aipdb-count-badge"><?php echo count($blocklist); ?></span>
        </h2>

        <table class="wp-list-table widefat fixed striped aipdb-blocklist-table">
            <thead>
                <tr>
                    <th><?php _e('IP Address', 'wp-abuseipdb-integration'); ?></th>
                    <th><?php _e('Reason', 'wp-abuseipdb-integration'); ?></th>
                    <th><?php _e('Blocked at', 'wp-abuseipdb-integration'); ?></th>
                    <th><?php _e('Expires', 'wp-abuseipdb-integration'); ?></th>
                    <th><?php _e('Actions', 'wp-abuseipdb-integration'); ?></th>
                </tr>
            </thead>
            <tbody id="aipdb-blocklist-body">
                <?php if (empty($blocklist)) : ?>
                    <tr class="no-items">
                        <td colspan="5" style="text-align:center; padding: 20px;">
                            <?php _e('No IPs are currently blocked.', 'wp-abuseipdb-integration'); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($blocklist as $ip => $entry) : ?>
                        <tr data-ip="<?php echo esc_attr($ip); ?>">
                            <td>
                                <strong><?php echo esc_html($ip); ?></strong>
                                <div class="row-actions">
                                    <span>
                                        <a href="https://www.abuseipdb.com/check/<?php echo urlencode($ip); ?>"
                                           target="_blank" rel="noopener noreferrer">
                                            <?php _e('Check AbuseIPDB', 'wp-abuseipdb-integration'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo esc_html($entry['reason'] ?? ''); ?></td>
                            <td>
                                <?php
                                $blocked_at = (int) ($entry['blocked_at'] ?? 0);
                                echo $blocked_at
                                    ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $blocked_at))
                                    : '-';
                                ?>
                            </td>
                            <td>
                                <?php
                                if (empty($entry['expires_at'])) {
                                    echo '<em>' . esc_html__('Permanent', 'wp-abuseipdb-integration') . '</em>';
                                } else {
                                    echo esc_html(date_i18n(get_option('date_format'), (int) $entry['expires_at']));
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button"
                                        class="button button-small button-link-delete aipdb-unblock-ip"
                                        data-ip="<?php echo esc_attr($ip); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
                                    <?php _e('Unblock', 'wp-abuseipdb-integration'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
