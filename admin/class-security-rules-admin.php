<?php
if (!defined('ABSPATH')) exit;

/**
 * Controlador de reglas de seguridad en el panel admin
 */
class AIPDB_Security_Rules_Admin
{
    private $rules;

    public function __construct()
    {
        // Definir reglas disponibles, escalable por estructura de array
        $this->rules = [
            [
                'key'        => 'aipdb_rule_login_failures',
                'title'      => __('Bloqueo por fallos de login', 'wp-abuseipdb-integration'),
                'desc'       => __('Bloquear IP tras X intentos fallidos de autenticación.', 'wp-abuseipdb-integration'),
                'type'       => 'threshold',
                'default'    => 3,
                'options'    => [
                    'threshold' => 3,
                    'duration'  => 60 // min
                ]
            ],
            [
                'key'        => 'aipdb_rule_suspicious_uri',
                'title'      => __('Bloqueo por URLs sospechosas', 'wp-abuseipdb-integration'),
                'desc'       => __('Detecta patrones comunes de ataque en la URI.', 'wp-abuseipdb-integration'),
                'type'       => 'toggle',
                'default'    => true
            ],
            [
                'key'        => 'aipdb_rule_country_blocking',
                'title'      => __('Bloqueo por país', 'wp-abuseipdb-integration'),
                'desc'       => __('Permite bloquear o permitir tráfico según el país de origen de la IP.', 'wp-abuseipdb-integration'),
                'type'       => 'country',
                'default'    => 'disabled'
            ],
            [
                'key'        => 'aipdb_rule_rest_api_abuse',
                'title'      => __('Abuso en API REST', 'wp-abuseipdb-integration'),
                'desc'       => __('Bloquea IPs con exceso de peticiones al endpoint REST.', 'wp-abuseipdb-integration'),
                'type'       => 'threshold',
                'default'    => 200, // peticiones/hora
                'options'    => [
                    'threshold' => 200,
                    'window'    => 3600 // segundos
                ]
            ],
        ];

        add_action('admin_init', [$this, 'register_security_rules_settings']);
    }

    /**
     * Registrar las reglas de seguridad como opciones WordPress
     */
    public function register_security_rules_settings()
    {
        foreach ($this->rules as $rule) {
            register_setting('aipdb_security_rules', $rule['key']);
        }
    }

    /**
     * Renderizar la vista con todas las reglas activables/configurables
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Reglas de Seguridad', 'wp-abuseipdb-integration'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('aipdb_security_rules'); ?>
                <table class="form-table">
                <?php foreach ($this->rules as $rule): ?>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($rule['key']); ?>">
                                <?php echo esc_html($rule['title']); ?>
                            </label>
                        </th>
                        <td>
                        <?php if ($rule['type'] === 'toggle'): ?>
                            <input type="checkbox"
                                   name="<?php echo esc_attr($rule['key']); ?>"
                                   id="<?php echo esc_attr($rule['key']); ?>"
                                   value="1"
                                   <?php checked(1, (int)get_option($rule['key'], $rule['default']), true); ?> />
                            <span><?php echo esc_html($rule['desc']); ?></span>
                        <?php elseif ($rule['type'] === 'threshold'): ?>
                            <input type="number"
                                   name="<?php echo esc_attr($rule['key']); ?>"
                                   id="<?php echo esc_attr($rule['key']); ?>"
                                   value="<?php echo esc_attr(get_option($rule['key'], $rule['default'])); ?>"
                                   min="1"
                                   class="small-text" />
                            <span><?php echo esc_html($rule['desc']); ?></span>
                        <?php elseif ($rule['type'] === 'country'): ?>
                            <select name="<?php echo esc_attr($rule['key']); ?>" id="<?php echo esc_attr($rule['key']); ?>">
                                <option value="disabled" <?php selected('disabled', get_option($rule['key'], 'disabled'), true); ?>>
                                    <?php _e('Deshabilitado', 'wp-abuseipdb-integration'); ?>
                                </option>
                                <option value="whitelist" <?php selected('whitelist', get_option($rule['key'], 'disabled'), true); ?>>
                                    <?php _e('Permitidos (whitelist)', 'wp-abuseipdb-integration'); ?>
                                </option>
                                <option value="blacklist" <?php selected('blacklist', get_option($rule['key'], 'disabled'), true); ?>>
                                    <?php _e('Bloqueados (blacklist)', 'wp-abuseipdb-integration'); ?>
                                </option>
                            </select>
                            <span><?php echo esc_html($rule['desc']); ?></span>
                        <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </table>
                <?php submit_button(__('Guardar Reglas', 'wp-abuseipdb-integration')); ?>
            </form>
        </div>
        <?php
    }

    public function get_rules() {
        return $this->rules;
    }
}
