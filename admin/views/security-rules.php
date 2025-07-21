<?php
if (!defined('ABSPATH')) exit;

// Crear instancia de la clase de administración de reglas de seguridad
$security_rules_admin = new AIPDB_Security_Rules_Admin();

// Renderizar la página
$security_rules_admin->render_page();
    