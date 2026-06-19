<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Country_Blocking {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        register_setting('aipdb_country_blocking', 'aipdb_enable_country_blocking');
        register_setting('aipdb_country_blocking', 'aipdb_trusted_countries');
    }
}
