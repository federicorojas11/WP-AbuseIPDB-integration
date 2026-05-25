<?php
if (!defined('ABSPATH')) exit;

// Reutilizar la instancia ya creada en AIPDB_Admin para evitar
// doble registro de settings y hooks.
$core = AIPDB_Core::get_instance();

if (isset($core->admin) && isset($core->admin->security_rules_admin)) {
    $core->admin->security_rules_admin->render_page();
} else {
    // Fallback defensivo (no debería ocurrir en runtime normal).
    $security_rules_admin = new AIPDB_Security_Rules_Admin();
    $security_rules_admin->render_page();
}
