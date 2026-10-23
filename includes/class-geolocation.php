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
            $autoload = AIPDB_PLUGIN_PATH . 'vendor/autoload.php';
            if (!file_exists($autoload)) {
                return null;
            }
            require_once $autoload;
        }

        try {
            $db     = new \IP2Location\Database($this->db_path, \IP2Location\Database::FILE_IO);
            $record = $db->lookup($ip, \IP2Location\Database::ALL);

            // Según la versión de la librería, lookup() devuelve array u objeto.
            if (is_array($record)) {
                $code = $record['countryCode'] ?? null;
            } elseif (is_object($record)) {
                $code = $record->countryCode ?? null;
            } else {
                $code = null;
            }

            // La librería devuelve strings de error ("INVALID IP ADDRESS", "-")
            // en el campo en lugar de lanzar; solo aceptar códigos ISO de 2 letras.
            if (is_string($code) && preg_match('/^[A-Z]{2}$/', $code)) {
                return $code;
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
