<?php
if (!defined('ABSPATH')) exit;

class AIPDB_License_Manager {

    private $option_key = 'aipdb_license_key';
    private $status_key = 'aipdb_license_status';

    /**
     * Verifica si la licencia está activa
     */
    public function is_license_valid() {
        $status = get_option($this->status_key, 'inactive');
        $license = trim(get_option($this->option_key, ''));

        // Lógica básica: considera válida si hay key y está marcada como válida
        return ($status === 'valid' && !empty($license));
    }

    /**
     * Activa una nueva licencia ingresada desde el admin
     */
    public function activate_license($license_key) {
        // Lógica simple: acepta cualquier string no vacío como válido
        $license_key = trim(sanitize_text_field($license_key));

        if (!$license_key || strlen($license_key) < 6) {
            update_option($this->status_key, 'invalid');
            return array('success' => false, 'message' => 'Licencia inválida o muy corta.');
        }

        update_option($this->option_key, $license_key);
        update_option($this->status_key, 'valid');

        return array('success' => true, 'message' => 'Licencia activada correctamente.');
    }

    /**
     * Desactiva la licencia actual
     */
    public function deactivate_license() {
        delete_option($this->option_key);
        update_option($this->status_key, 'inactive');
    }

    /**
     * Consulta la licencia almacenada
     */
    public function get_license_key() {
        return get_option($this->option_key, '');
    }

    /**
     * Consulta el estatus de la activación
     */
    public function get_license_status() {
        return get_option($this->status_key, 'inactive');
    }
}
