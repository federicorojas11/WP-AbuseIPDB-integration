<?php
// Archivo: /includes/class-detection-logger.php

class Detection_Logger {
    
    // Guardar nueva detección
    public static function log_detection($event_type, $ip_address, $url = '', $extra_info = '') {
        global $wpdb;

        // Nombre de la tabla personalizada
        $table_name = $wpdb->prefix . 'abuseipdb_detections';

        // Obtener timestamp actual
        $timestamp = current_time('mysql');

        // Insertar detección en base de datos
        $wpdb->insert(
            $table_name,
            array(
                'event_type'   => sanitize_text_field($event_type),
                'ip_address'   => sanitize_text_field($ip_address),
                'url'          => esc_url_raw($url),
                'extra_info'   => sanitize_text_field($extra_info),
                'logged_at'    => $timestamp
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
}
