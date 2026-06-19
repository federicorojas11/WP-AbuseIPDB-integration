<?php
/**
 * AIPDB_Firewall
 *
 * Request-time firewall that intercepts visitors early in the WordPress
 * lifecycle and blocks IPs that are either in the manual blocklist or
 * have an AbuseIPDB confidence score >= the configured threshold.
 *
 * Policy: fail-open. If the API errors, the daily quota is exhausted,
 * or any other unexpected condition is hit, the visitor is allowed
 * through and the incident is logged. This avoids breaking the site
 * when an upstream dependency fails.
 */
if (!defined('ABSPATH')) {
    exit;
}

class AIPDB_Firewall
{
    private static $instance = null;

    /** @var AIPDB_AbuseIPDB_API|null */
    private $api_client = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        if (!$this->should_attach()) {
            return;
        }

        // plugins_loaded prio 1 = WP core ready, options accessible,
        // but runs before most other plugins / theme code.
        add_action('plugins_loaded', array($this, 'check_request'), 1);
    }

    /**
     * Decide whether the firewall should attach to this request at all.
     * Anything that returns false here means "do not even hook" — useful
     * to keep CLI/cron/admin overhead at zero.
     */
    private function should_attach()
    {
        if (!get_option('aipdb_enabled', false)) {
            return false;
        }
        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }
        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }
        return true;
    }

    /**
     * Main firewall entrypoint. Runs once per front-end request.
     */
    public function check_request()
    {
        // Skip admin area entirely.
        if (is_admin()) {
            return;
        }

        // Skip AJAX (admin-ajax is is_admin() but defensive).
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // Safety net: never block a user that can already manage the site.
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            if (function_exists('current_user_can') && current_user_can('manage_options')) {
                return;
            }
        }

        $ip = aipdb_get_client_ip();
        if (!aipdb_is_valid_ip($ip)) {
            return;
        }

        // 1. Whitelist short-circuits everything.
        if ($this->is_whitelisted($ip)) {
            return;
        }

        // 2. Manual blocklist — instant block, no API call.
        $manual = aipdb_is_ip_manually_blocked($ip);
        if ($manual !== false) {
            $this->log_block($ip, 'manual_block', null, null, $manual['reason'] ?? '');
            $this->render_block_page($ip, $manual['reason'] ?? '');
            return; // render_block_page exits, but keep the guard.
        }

        // 3. Local cache hit — avoid redundant API calls.
        $cached = $this->get_cached_score($ip);
        if (is_array($cached)) {
            $this->bump_cache_stat('hits');
            if ((int) $cached['score'] >= $this->get_threshold()) {
                $this->log_block(
                    $ip,
                    'firewall_block',
                    (int) $cached['score'],
                    $cached['country'] ?? null,
                    sprintf('Cached score %d >= threshold %d', $cached['score'], $this->get_threshold())
                );
                $this->render_block_page($ip, __('Suspicious activity detected.', 'wp-abuseipdb-integration'));
            }
            return;
        }

        // 4. Country filter — trusted countries skip the AbuseIPDB call entirely.
        if (get_option('aipdb_enable_country_blocking', 0)) {
            $geo     = new AIPDB_Geolocation();
            $country = $geo->get_country_code_by_ip($ip);
            $trusted = get_option('aipdb_trusted_countries', []);
            if (!empty($trusted) && !empty($country) && in_array($country, (array) $trusted, true)) {
                aipdb_debug_log("IP {$ip} ({$country}) is from trusted country, skipping AbuseIPDB check.", 'firewall');
                return;
            }
        }

        // 5. Quota guard — if we've hit the daily limit, fail-open.
        if (!$this->within_quota()) {
            aipdb_debug_log("Quota exhausted, allowing IP without check: {$ip}", 'firewall');
            return;
        }

        // 6. Live AbuseIPDB check.
        if ($this->api_client === null) {
            $this->api_client = new AIPDB_AbuseIPDB_API();
        }

        $this->bump_cache_stat('misses');
        $response = $this->api_client->check_ip($ip);
        $this->increment_quota();

        if (is_wp_error($response)) {
            aipdb_debug_log(
                "API error for IP {$ip}: " . $response->get_error_message(),
                'firewall'
            );
            return; // fail-open
        }

        if (!isset($response['data']['abuseConfidenceScore'])) {
            return;
        }

        $score = (int) $response['data']['abuseConfidenceScore'];
        $country = isset($response['data']['countryCode']) ? $response['data']['countryCode'] : null;

        $this->cache_score($ip, $score, $country);

        if ($score >= $this->get_threshold()) {
            $this->log_block(
                $ip,
                'firewall_block',
                $score,
                $country,
                sprintf('Score %d >= threshold %d', $score, $this->get_threshold())
            );
            $this->render_block_page($ip, __('Suspicious activity detected.', 'wp-abuseipdb-integration'));
        }
    }

    /**
     * Manual, admin-triggered IP lookup for the dashboard tool.
     * Reuses the cache when available; otherwise hits AbuseIPDB and caches
     * the result. Returns a structured array or WP_Error.
     */
    public function check_ip_manually($ip)
    {
        if (!aipdb_is_valid_ip($ip)) {
            return new WP_Error('invalid_ip', __('Invalid IP address.', 'wp-abuseipdb-integration'));
        }

        $score         = null;
        $country       = null;
        $total_reports = null;
        $last_reported = null;
        $source        = 'cache';

        $cached = $this->get_cached_score($ip);
        if (is_array($cached)) {
            $score   = (int) $cached['score'];
            $country = $cached['country'] ?? null;
        } else {
            $source = 'api';
            if ($this->api_client === null) {
                $this->api_client = new AIPDB_AbuseIPDB_API();
            }
            $response = $this->api_client->check_ip($ip);
            if (is_wp_error($response)) {
                return $response;
            }
            if (isset($response['data']['abuseConfidenceScore'])) {
                $score         = (int) $response['data']['abuseConfidenceScore'];
                $country       = $response['data']['countryCode'] ?? null;
                $total_reports = isset($response['data']['totalReports']) ? (int) $response['data']['totalReports'] : null;
                $last_reported = $response['data']['lastReportedAt'] ?? null;
                $this->cache_score($ip, $score, $country);
                $this->increment_quota();
            }
        }

        $threshold = $this->get_threshold();

        return array(
            'ip'             => $ip,
            'score'          => $score,
            'country'        => $country,
            'country_name'   => $country ? aipdb_get_country_name($country) : null,
            'threat_level'   => $score !== null ? $this->score_to_threat($score) : null,
            'threshold'      => $threshold,
            'would_block'    => ($score !== null && $score >= $threshold),
            'is_whitelisted' => $this->is_whitelisted($ip),
            'is_blocked'     => (aipdb_is_ip_manually_blocked($ip) !== false),
            'total_reports'  => $total_reports,
            'last_reported_at' => $last_reported,
            'source'         => $source,
        );
    }

    private function is_whitelisted($ip)
    {
        $whitelist = get_option('aipdb_whitelist_ips', '');
        if (empty($whitelist)) {
            return false;
        }
        $ips = array_filter(array_map('trim', preg_split('/[\s,]+/', $whitelist)));
        return in_array($ip, $ips, true);
    }

    private function get_threshold()
    {
        return (int) get_option('aipdb_abuse_threshold', 70);
    }

    private function get_cached_score($ip)
    {
        return get_transient('aipdb_score_' . md5($ip));
    }

    private function cache_score($ip, $score, $country)
    {
        $hours = (int) get_option('aipdb_cache_duration', 24);
        if ($hours < 1) {
            $hours = 24;
        }
        set_transient(
            'aipdb_score_' . md5($ip),
            array(
                'score' => $score,
                'country' => $country,
                'checked_at' => time(),
            ),
            $hours * HOUR_IN_SECONDS
        );
    }

    private function within_quota()
    {
        $limit = (int) get_option('aipdb_rate_limit_daily', 900);
        if ($limit <= 0) {
            return true; // no limit configured
        }
        $key = 'aipdb_daily_calls_' . date('Y-m-d');
        $current = (int) get_transient($key);
        return $current < $limit;
    }

    private function increment_quota()
    {
        $key = 'aipdb_daily_calls_' . date('Y-m-d');
        $current = (int) get_transient($key);
        // Keep the counter alive for the rest of the day. Using
        // DAY_IN_SECONDS is a slight overshoot but harmless.
        set_transient($key, $current + 1, DAY_IN_SECONDS);
    }

    /**
     * Track cache effectiveness. Counters feed the dashboard's
     * cache-hit-ratio stat and reset daily with the maintenance purge.
     */
    private function bump_cache_stat($which)
    {
        $key = 'aipdb_cache_' . $which; // 'hits' | 'misses'
        set_transient($key, ((int) get_transient($key)) + 1, DAY_IN_SECONDS);
    }

    private function score_to_threat($score)
    {
        if ($score >= 85) {
            return 'high';
        }
        if ($score >= 50) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Persist the block in the detections table for the admin UI.
     */
    private function log_block($ip, $event_type, $score, $country, $details)
    {
        aipdb_add_detection(
            $ip,
            $event_type,
            $score !== null ? $this->score_to_threat($score) : 'high',
            $score,
            $country,
            isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
            isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 500) : null,
            $details,
            'blocked'
        );
    }

    /**
     * Render the 403 page and terminate the request.
     */
    private function render_block_page($ip, $reason)
    {
        if (!headers_sent()) {
            status_header(403);
            nocache_headers();
            header('Content-Type: text/html; charset=utf-8');
        }

        $template = AIPDB_PLUGIN_PATH . 'includes/templates/block-page.php';
        if (file_exists($template)) {
            // Variables consumed by the template.
            $blocked_ip = $ip;
            $block_reason = $reason;
            include $template;
        } else {
            // Minimal fallback — should never happen in normal installs.
            echo '<h1>403 Forbidden</h1><p>Access denied.</p>';
        }
        exit;
    }
}
