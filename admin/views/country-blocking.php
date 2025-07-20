<?php
if (!defined('ABSPATH')) exit;

$current_tab = $_GET['tab'] ?? 'blocked';

function aipdb_get_country_list() {
    return array(
        'US' => 'United States',
        'CA' => 'Canada',
        'BR' => 'Brazil',
        'GB' => 'United Kingdom',
        'DE' => 'Germany',
        'AR' => 'Argentina',
        'FR' => 'France',
        'JP' => 'Japan',
        'CN' => 'China',
        'IN' => 'India',
        'RU' => 'Russia',
        'IT' => 'Italy',
        'KR' => 'South Korea',
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'MX' => 'Mexico',
        'ES' => 'Spain',
        'VE' => 'Venezuela',
        'UY' => 'Uruguay',
        'PE' => 'Peru',
        'AU' => 'Australia'
    );
}
?>

<h2 class="nav-tab-wrapper">
    <a href="<?php echo admin_url('admin.php?page=aipdb-country-blocking&tab=blocked'); ?>" 
       class="nav-tab <?php echo $current_tab === 'blocked' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Blocked Countries', 'wp-abuseipdb-integration'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=aipdb-country-blocking&tab=options'); ?>" 
       class="nav-tab <?php echo $current_tab === 'options' ? 'nav-tab-active' : ''; ?>">
        <?php _e('Behavior Options', 'wp-abuseipdb-integration'); ?>
    </a>
</h2>

<div class="aipdb-tab-content">

<?php if ($current_tab === 'blocked'): ?>
    <form method="post" action="options.php">
        <?php settings_fields('aipdb_country_blocking'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Country Blocking', 'wp-abuseipdb-integration'); ?></th>
                <td>
                    <input type="checkbox"
                           id="aipdb_enable_country_blocking"
                           name="aipdb_enable_country_blocking"
                           value="1"
                           <?php checked(1, get_option('aipdb_enable_country_blocking', 0)); ?> />
                    <label for="aipdb_enable_country_blocking">
                        <?php _e('Only block users based on IP2Location country', 'wp-abuseipdb-integration'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Blocked Countries', 'wp-abuseipdb-integration'); ?></th>
                <td>
                    <?php
                        $selected = get_option('aipdb_blocked_countries', []);
                        if (!is_array($selected)) $selected = [];
                        $countries = aipdb_get_country_list();
                    ?>
                    <select id="aipdb_blocked_countries"
                            name="aipdb_blocked_countries[]"
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
                    <p class="description"><?php _e('Users accessing from selected countries will be blocked.', 'wp-abuseipdb-integration'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Options', 'wp-abuseipdb-integration')); ?>
    </form>

<?php elseif ($current_tab === 'options'): ?>

    <form method="post" action="options.php">
        <?php settings_fields('aipdb_country_behavior'); ?>

        <table class="form-table">
            <tr>
                <th><?php _e('Coming Soon', 'wp-abuseipdb-integration'); ?></th>
                <td>
                    <p><?php _e('This section will contain advanced behavior options for handling country-level rules.', 'wp-abuseipdb-integration'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Options', 'wp-abuseipdb-integration')); ?>
    </form>

<?php endif; ?>

</div>
