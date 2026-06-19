<?php
/**
 * Vista: Dashboard – WP AbuseIPDB Integration
 *
 * Resumen de actividad: KPIs, chequeos recientes, lookup manual de IP
 * y estado de la API. Inspirado en el layout "AbuseIPDB Guardian".
 */
if (!defined('ABSPATH')) {
    exit;
}

$dashboard = new AIPDB_Dashboard();
$stats     = $dashboard->get_dashboard_stats();

// Chequeos recientes (reutiliza el handler de detecciones).
$detections_handler = new AIPDB_Detections();
$recent = $detections_handler->get_detections(array('per_page' => 6, 'page' => 1));
$recent_rows = $recent['data'];

// Cálculos de presentación.
$api_limit  = max(1, (int) $stats['api_limit']);
$api_used   = (int) $stats['api_calls_today'];
$api_pct    = min(100, (int) round($api_used / $api_limit * 100));
$now_ts     = current_time('timestamp');
$resets_in  = human_time_diff($now_ts, strtotime('tomorrow', $now_ts));

$api_key    = (string) get_option('aipdb_api_key', '');
$key_mask   = $api_key !== '' ? '&bull;&bull;&bull;&bull;' . esc_html(substr($api_key, -4)) : '&mdash;';
$api_status = $stats['api_status']; // ok | error | unknown
$status_cls = $api_status === 'ok' ? 'is-ok' : ($api_status === 'error' ? 'is-error' : '');
$status_txt = $api_status === 'ok'
    ? __('Connected', 'wp-abuseipdb-integration')
    : ($api_status === 'error' ? __('Error', 'wp-abuseipdb-integration') : __('Not verified', 'wp-abuseipdb-integration'));
$quota_cls  = $api_pct >= 100 ? 'is-full' : ($api_pct >= 80 ? 'is-high' : '');

/**
 * Helper local: nivel a partir del score.
 */
$aipdb_level = function ($score) {
    $s = (int) $score;
    return $s >= 75 ? 'high' : ($s >= 25 ? 'medium' : 'low');
};
?>
<div class="aipdb-dashboard">

    <!-- KPIs -->
    <div class="aipdb-stats-grid">
        <div class="aipdb-stat-card">
            <p class="aipdb-stat-label"><?php _e('IPs checked today', 'wp-abuseipdb-integration'); ?></p>
            <div class="aipdb-stat-value"><?php echo number_format_i18n($api_used); ?></div>
            <p class="aipdb-stat-sub"><?php _e('live AbuseIPDB lookups', 'wp-abuseipdb-integration'); ?></p>
        </div>

        <div class="aipdb-stat-card">
            <p class="aipdb-stat-label"><?php _e('Threats blocked', 'wp-abuseipdb-integration'); ?></p>
            <div class="aipdb-stat-value is-danger"><?php echo number_format_i18n($stats['blocked_count']); ?></div>
            <p class="aipdb-stat-sub is-up">
                <?php printf(
                    /* translators: %s: number of blocks in the last 24h */
                    __('+%s in last 24h', 'wp-abuseipdb-integration'),
                    number_format_i18n($stats['blocked_today'])
                ); ?>
            </p>
        </div>

        <div class="aipdb-stat-card">
            <p class="aipdb-stat-label"><?php _e('Avg confidence', 'wp-abuseipdb-integration'); ?></p>
            <div class="aipdb-stat-value"><?php echo (int) $stats['avg_confidence']; ?><span class="aipdb-stat-unit">%</span></div>
            <p class="aipdb-stat-sub"><?php _e('of blocked traffic', 'wp-abuseipdb-integration'); ?></p>
        </div>

        <div class="aipdb-stat-card">
            <p class="aipdb-stat-label"><?php _e('API requests', 'wp-abuseipdb-integration'); ?></p>
            <div class="aipdb-stat-value">
                <?php echo number_format_i18n($api_used); ?><span class="aipdb-stat-unit"> / <?php echo number_format_i18n($api_limit); ?></span>
            </div>
            <p class="aipdb-stat-sub">
                <?php printf(
                    /* translators: %s: human-readable time until reset */
                    __('resets in %s', 'wp-abuseipdb-integration'),
                    $resets_in
                ); ?>
            </p>
            <div class="aipdb-stat-bar"><span style="width: <?php echo (int) $api_pct; ?>%;"></span></div>
        </div>
    </div><!-- .aipdb-stats-grid -->

    <div class="aipdb-dash-grid">

        <!-- Chequeos recientes -->
        <div class="aipdb-card">
            <div class="aipdb-card-head">
                <h2><?php _e('Recent IP checks', 'wp-abuseipdb-integration'); ?></h2>
                <a class="aipdb-card-link" href="<?php echo esc_url(admin_url('admin.php?page=aipdb-detections')); ?>">
                    <?php _e('View firewall log →', 'wp-abuseipdb-integration'); ?>
                </a>
            </div>

            <?php if (empty($recent_rows)) : ?>
                <p class="aipdb-muted"><?php _e('No activity yet. Once protection is enabled, checked IPs will appear here.', 'wp-abuseipdb-integration'); ?></p>
            <?php else : ?>
                <table class="aipdb-table">
                    <thead>
                        <tr>
                            <th><?php _e('IP address', 'wp-abuseipdb-integration'); ?></th>
                            <th><?php _e('Confidence', 'wp-abuseipdb-integration'); ?></th>
                            <th><?php _e('Event', 'wp-abuseipdb-integration'); ?></th>
                            <th><?php _e('Action', 'wp-abuseipdb-integration'); ?></th>
                            <th><?php _e('Checked', 'wp-abuseipdb-integration'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_rows as $row) :
                            $score = $row->abuseipdb_score;
                            $lvl   = $aipdb_level($score);
                        ?>
                            <tr>
                                <td>
                                    <a class="aipdb-ip" href="https://www.abuseipdb.com/check/<?php echo urlencode($row->ip_address); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html($row->ip_address); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($score !== null) : ?>
                                        <div class="aipdb-conf">
                                            <span class="aipdb-conf-track">
                                                <span class="aipdb-conf-fill aipdb-fill-<?php echo esc_attr($lvl); ?>" style="width: <?php echo (int) $score; ?>%;"></span>
                                            </span>
                                            <span class="aipdb-conf-pct aipdb-lvl-<?php echo esc_attr($lvl); ?>"><?php echo (int) $score; ?>%</span>
                                        </div>
                                    <?php else : ?>
                                        <span class="aipdb-score-na">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="aipdb-event-type"><?php echo esc_html(ucfirst(str_replace('_', ' ', $row->event_type))); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row->action_taken)) : ?>
                                        <span class="aipdb-action aipdb-action-<?php echo esc_attr($row->action_taken); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $row->action_taken))); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="aipdb-action aipdb-action-none"><?php _e('Logged', 'wp-abuseipdb-integration'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="aipdb-muted">
                                    <?php echo esc_html(human_time_diff(strtotime($row->created_at), $now_ts)); ?> <?php _e('ago', 'wp-abuseipdb-integration'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Columna lateral -->
        <div class="aipdb-dash-side">

            <!-- Live IP lookup -->
            <div class="aipdb-card">
                <h2><?php _e('Live IP lookup', 'wp-abuseipdb-integration'); ?></h2>
                <p class="aipdb-lookup-hint"><?php _e('Check any address against AbuseIPDB.', 'wp-abuseipdb-integration'); ?></p>
                <form id="aipdb-ip-check-form" class="aipdb-ip-check-form">
                    <div class="aipdb-lookup-field">
                        <input type="text" id="aipdb-check-ip-input" name="ip"
                               placeholder="<?php esc_attr_e('e.g. 185.220.101.47', 'wp-abuseipdb-integration'); ?>" />
                        <button type="submit" class="button button-primary">
                            <?php _e('Check IP', 'wp-abuseipdb-integration'); ?>
                        </button>
                    </div>
                    <div id="aipdb-ip-check-result"></div>
                </form>
            </div>

            <!-- API status -->
            <div class="aipdb-card">
                <h2><?php _e('API status', 'wp-abuseipdb-integration'); ?></h2>
                <div class="aipdb-api-status-row">
                    <span class="aipdb-status-dot <?php echo esc_attr($status_cls); ?>"><?php echo esc_html($status_txt); ?></span>
                    <span class="aipdb-api-key-mask"><?php echo $key_mask; // ya escapado ?></span>
                </div>
                <p class="aipdb-quota-label">
                    <?php printf(
                        /* translators: %s%% used */
                        __('Daily quota · %s%% used', 'wp-abuseipdb-integration'),
                        (int) $api_pct
                    ); ?>
                </p>
                <div class="aipdb-quota-bar"><span class="<?php echo esc_attr($quota_cls); ?>" style="width: <?php echo (int) $api_pct; ?>%;"></span></div>
                <p class="aipdb-quota-foot">
                    <?php printf(
                        /* translators: 1: used requests, 2: daily limit */
                        __('%1$s of %2$s requests', 'wp-abuseipdb-integration'),
                        number_format_i18n($api_used),
                        number_format_i18n($api_limit)
                    ); ?>
                </p>
            </div>

        </div><!-- .aipdb-dash-side -->
    </div><!-- .aipdb-dash-grid -->

</div><!-- .aipdb-dashboard -->
