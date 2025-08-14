<?php
if (!defined('ABSPATH')) exit;

class AIPDB_AbuseIPDB_API {

    private $api_url = 'https://api.abuseipdb.com/api/v2/';
    private $api_key;

    public function __construct() {
        $this->api_key = get_option('aipdb_api_key');
    }

    /**
     * Check an IP address using the /check endpoint.
     *
     * @param string $ip The IP address to check.
     * @param int $max_age_in_days The maximum age of reports to return.
     * @return array|WP_Error The API response or a WP_Error on failure.
     */
    public function check_ip($ip, $max_age_in_days = 90) {
        return $this->request('check', 'GET', [
            'ipAddress' => $ip,
            'maxAgeInDays' => $max_age_in_days,
            'verbose' => ''
        ]);
    }

    /**
     * Report an IP address using the /report endpoint.
     *
     * @param string $ip The IP address to report.
     * @param array $categories An array of category IDs.
     * @param string $comment A comment detailing the abuse.
     * @return array|WP_Error The API response or a WP_Error on failure.
     */
    public function report_ip($ip, $categories, $comment = '') {
        if (is_array($categories)) {
            $categories = implode(',', $categories);
        }

        return $this->request('report', 'POST', [
            'ip' => $ip,
            'categories' => $categories,
            'comment' => $comment
        ]);
    }

    /**
     * Perform an API request.
     *
     * @param string $endpoint The API endpoint to call.
     * @param string $method The HTTP method (GET or POST).
     * @param array $data The data to send with the request.
     * @return array|WP_Error The decoded JSON response or a WP_Error on failure.
     */
    private function request($endpoint, $method = 'GET', $data = []) {
        if (empty($this->api_key)) {
            return new WP_Error('api_key_missing', __('AbuseIPDB API key is not configured.', 'wp-abuseipdb-integration'));
        }

        $url = $this->api_url . $endpoint;
        $headers = [
            'Key' => $this->api_key,
            'Accept' => 'application/json',
        ];

        $args = [
            'headers' => $headers,
            'timeout' => 20,
        ];

        if ($method === 'GET') {
            $url = add_query_arg($data, $url);
            $response = wp_remote_get($url, $args);
        } else {
            $args['body'] = $data;
            $response = wp_remote_post($url, $args);
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);

        $decoded_body = json_decode($body, true);

        if ($http_code >= 400) {
            $error_message = __('Unknown API error.', 'wp-abuseipdb-integration');
            if (isset($decoded_body['errors'][0]['detail'])) {
                $error_message = $decoded_body['errors'][0]['detail'];
            }
            return new WP_Error('api_error', $error_message, ['status' => $http_code]);
        }

        return $decoded_body;
    }
}
