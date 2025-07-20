<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Geolocation {

    private $db_path;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->db_path = $upload_dir['basedir'] . '/aipdb-data/IP2LOCATION-LITE-DB1.BIN';

        // Descargar si necesario
        if (!file_exists($this->db_path)) {
            $this->download_database();
        }
    }

    public function get_country_code_by_ip($ip) {
        if (!file_exists($this->db_path)) {
            return null;
        }

        if (!class_exists('IP2Location\Database')) {
            require_once AIPDB_PLUGIN_PATH . 'vendor/ip2location/ip2location-php/src/IP2Location.php';
        }

        try {
            $db = new \IP2Location\Database($this->db_path, \IP2Location\Database::FILE_IO);
            $record = $db->lookup($ip, \IP2Location\Database::ALL);
            return $record->countryCode ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function download_database() {
        $zip_url = 'https://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.BIN.ZIP';
        $zip_path = $this->db_path . '.zip';

        // Descargar el ZIP
        $response = wp_remote_get($zip_url, ['timeout' => 60]);
        if (is_wp_error($response)) return false;

        file_put_contents($zip_path, wp_remote_retrieve_body($response));

        $zip = new ZipArchive;
        if ($zip->open($zip_path) === true) {
            $zip->extractTo(dirname($this->db_path));
            $zip->close();
            unlink($zip_path);
        }
    }
}
