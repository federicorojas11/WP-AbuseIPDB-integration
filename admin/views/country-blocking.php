<?php
if (!defined('ABSPATH')) exit;

function aipdb_get_country_list() {
    return array(
        'AR' => 'Argentina',
        'AU' => 'Australia',
        'BR' => 'Brazil',
        'CA' => 'Canada',
        'CL' => 'Chile',
        'CN' => 'China',
        'CO' => 'Colombia',
        'DE' => 'Germany',
        'ES' => 'Spain',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'IN' => 'India',
        'IT' => 'Italy',
        'JP' => 'Japan',
        'KR' => 'South Korea',
        'MX' => 'Mexico',
        'PE' => 'Peru',
        'RU' => 'Russia',
        'US' => 'United States',
        'UY' => 'Uruguay',
        'VE' => 'Venezuela',
    );
}
?>

<div class="aipdb-options-page">
    <h2 class="aipdb-page-heading" style="font-size:18px;margin:0 0 16px;"><?php _e('Country Filter', 'wp-abuseipdb-integration'); ?></h2>

    <form method="post" action="options.php" class="aipdb-options-form">
        <?php settings_fields('aipdb_country_blocking'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Country Filter', 'wp-abuseipdb-integration'); ?></th>
                <td>
                    <label for="aipdb_enable_country_blocking">
                        <input type="checkbox"
                               id="aipdb_enable_country_blocking"
                               name="aipdb_enable_country_blocking"
                               value="1"
                               <?php checked(1, get_option('aipdb_enable_country_blocking', 0)); ?> />
                        <?php _e('Enable country-based AbuseIPDB filtering', 'wp-abuseipdb-integration'); ?>
                    </label>
                    <p class="description">
                        <?php _e('When enabled, IPs from trusted countries bypass the AbuseIPDB check. IPs from all other countries are verified before being allowed in.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aipdb_trusted_countries"><?php _e('Trusted Countries', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <?php
                        $selected  = get_option('aipdb_trusted_countries', []);
                        if (!is_array($selected)) $selected = [];
                        $countries = aipdb_get_country_list();
                    ?>
                    <select id="aipdb_trusted_countries"
                            name="aipdb_trusted_countries[]"
                            multiple="multiple"
                            size="10"
                            style="width: 300px;">
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>"
                                <?php selected(in_array($code, $selected), true); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('IPs from these countries will skip the AbuseIPDB check. Hold Ctrl/Cmd to select multiple.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Options', 'wp-abuseipdb-integration')); ?>
    </form>
</div>
