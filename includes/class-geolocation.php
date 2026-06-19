<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Geolocation {

    private $db_path;

    public function __construct() {
        $this->db_path = AIPDB_PLUGIN_PATH . 'vendor/ip2location/ip2location-php/data/IP2LOCATION-LITE-DB1.BIN';
    }

    public function get_country_code_by_ip($ip) {
        if (!file_exists($this->db_path)) {
            return null;
        }

        if (!class_exists('IP2Location\Database')) {
            require_once AIPDB_PLUGIN_PATH . 'vendor/ip2location/ip2location-php/src/IP2Location.php';
        }

        try {
            $db     = new \IP2Location\Database($this->db_path, \IP2Location\Database::FILE_IO);
            $record = $db->lookup($ip, \IP2Location\Database::ALL);
            return $record->countryCode ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
}
