<?php
/**
 * Uninstall — WP AbuseIPDB Integration
 *
 * WordPress ejecuta este archivo (y NO el hook registrado) al borrar el
 * plugin. El plugin no está cargado en este punto: no usar constantes
 * ni clases propias, solo API de WordPress.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!get_option('aipdb_remove_data_on_uninstall', false)) {
    return;
}

global $wpdb;

// Tabla de detecciones.
$table_name = $wpdb->prefix . 'aipdb_detections';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Opciones y transients del plugin (los transients viven en options).
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE 'aipdb\\_%'
        OR option_name LIKE '\\_transient\\_aipdb\\_%'
        OR option_name LIKE '\\_transient\\_timeout\\_aipdb\\_%'"
);

// Directorios de logs y datos en uploads.
$upload_dir = wp_upload_dir();
foreach (array('/aipdb-logs', '/aipdb-data') as $dir) {
    $path = $upload_dir['basedir'] . $dir;
    if (!is_dir($path)) {
        continue;
    }
    // GLOB_BRACE no es portable (falta en musl); listar con scandir.
    $entries = scandir($path);
    if (is_array($entries)) {
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $file = $path . '/' . $entry;
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    @rmdir($path);
}
