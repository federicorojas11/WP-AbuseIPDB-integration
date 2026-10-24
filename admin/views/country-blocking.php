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
    <h2 class="aipdb-page-heading" style="font-size:18px;margin:0 0 16px;"><?php _e('Country Filtering', 'wp-abuseipdb-integration'); ?></h2>
    <p class="description" style="margin:0 0 20px;color:#555;"><?php _e('Optional policy to skip AbuseIPDB checks for traffic coming from trusted countries.', 'wp-abuseipdb-integration'); ?></p>

    <form method="post" action="options.php" class="aipdb-options-form">
        <?php settings_fields('aipdb_country_blocking'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable country-based filtering', 'wp-abuseipdb-integration'); ?></th>
                <td>
                    <label for="aipdb_enable_country_blocking">
                        <input type="checkbox"
                               id="aipdb_enable_country_blocking"
                               name="aipdb_enable_country_blocking"
                               value="1"
                               <?php checked(1, get_option('aipdb_enable_country_blocking', 0)); ?> />
                        <?php _e('Skip AbuseIPDB checks for trusted countries', 'wp-abuseipdb-integration'); ?>
                    </label>
                    <p class="description">
                        <?php _e('This filter only applies to IPs whose country can be resolved. It does not block countries by default. Instead, it can be used to bypass AbuseIPDB checks for countries you trust, reducing API usage while keeping the firewall focused on IP reputation.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row" style="vertical-align:top;padding-top:18px;">
                    <label><?php _e('Trusted Countries / Bypass Countries', 'wp-abuseipdb-integration'); ?></label>
                </th>
                <td>
                    <?php
                        $selected  = get_option('aipdb_trusted_countries', []);
                        if (!is_array($selected)) $selected = [];
                        $countries = aipdb_get_country_list();
                        $available = array_diff_key($countries, array_flip($selected));
                        $trusted   = array_intersect_key($countries, array_flip($selected));
                    ?>

                    <!-- Hidden select — submitted to WordPress on save -->
                    <select id="aipdb_trusted_countries"
                            name="aipdb_trusted_countries[]"
                            multiple="multiple"
                            style="display:none;" aria-hidden="true">
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>"
                                <?php selected(in_array($code, $selected), true); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Dual-panel picker -->
                    <div class="aipdb-country-picker" id="aipdb-country-picker">

                        <!-- LEFT: Available -->
                        <div class="aipdb-cp-panel">
                            <div class="aipdb-cp-header">
                                <span class="aipdb-cp-label"><?php _e('Available Countries', 'wp-abuseipdb-integration'); ?></span>
                                <input type="text"
                                       class="aipdb-cp-search"
                                       id="aipdb-search-available"
                                       placeholder="<?php esc_attr_e('Filter…', 'wp-abuseipdb-integration'); ?>"
                                       autocomplete="off" />
                            </div>
                            <ul class="aipdb-cp-list" id="aipdb-available-list"
                                data-side="available"
                                aria-label="<?php esc_attr_e('Available countries', 'wp-abuseipdb-integration'); ?>">
                                <?php foreach ($available as $code => $name): ?>
                                    <li class="aipdb-cp-item"
                                        data-code="<?php echo esc_attr($code); ?>"
                                        data-name="<?php echo esc_attr(strtolower($name)); ?>"
                                        draggable="true"
                                        title="<?php echo esc_attr($code); ?>">
                                        <span class="aipdb-cp-flag"><?php echo esc_html($code); ?></span>
                                        <span class="aipdb-cp-name"><?php echo esc_html($name); ?></span>
                                        <span class="aipdb-cp-action aipdb-cp-add" title="<?php esc_attr_e('Add to trusted', 'wp-abuseipdb-integration'); ?>">&#x2192;</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- CENTER: arrow buttons -->
                        <div class="aipdb-cp-controls">
                            <button type="button" class="button aipdb-cp-btn" id="aipdb-move-all-right" title="<?php esc_attr_e('Trust all', 'wp-abuseipdb-integration'); ?>">&#x00BB;</button>
                            <button type="button" class="button aipdb-cp-btn" id="aipdb-move-selected-right" title="<?php esc_attr_e('Trust selected', 'wp-abuseipdb-integration'); ?>">&#x203A;</button>
                            <button type="button" class="button aipdb-cp-btn" id="aipdb-move-selected-left" title="<?php esc_attr_e('Remove selected', 'wp-abuseipdb-integration'); ?>">&#x2039;</button>
                            <button type="button" class="button aipdb-cp-btn" id="aipdb-move-all-left" title="<?php esc_attr_e('Remove all', 'wp-abuseipdb-integration'); ?>">&#x00AB;</button>
                        </div>

                        <!-- RIGHT: Trusted -->
                        <div class="aipdb-cp-panel">
                            <div class="aipdb-cp-header">
                                <span class="aipdb-cp-label"><?php _e('Trusted / Bypass', 'wp-abuseipdb-integration'); ?></span>
                                <input type="text"
                                       class="aipdb-cp-search"
                                       id="aipdb-search-trusted"
                                       placeholder="<?php esc_attr_e('Filter…', 'wp-abuseipdb-integration'); ?>"
                                       autocomplete="off" />
                            </div>
                            <ul class="aipdb-cp-list" id="aipdb-trusted-list"
                                data-side="trusted"
                                aria-label="<?php esc_attr_e('Trusted countries', 'wp-abuseipdb-integration'); ?>">
                                <?php foreach ($trusted as $code => $name): ?>
                                    <li class="aipdb-cp-item"
                                        data-code="<?php echo esc_attr($code); ?>"
                                        data-name="<?php echo esc_attr(strtolower($name)); ?>"
                                        draggable="true"
                                        title="<?php echo esc_attr($code); ?>">
                                        <span class="aipdb-cp-flag"><?php echo esc_html($code); ?></span>
                                        <span class="aipdb-cp-name"><?php echo esc_html($name); ?></span>
                                        <span class="aipdb-cp-action aipdb-cp-remove" title="<?php esc_attr_e('Remove from trusted', 'wp-abuseipdb-integration'); ?>">&#x2190;</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                    </div><!-- /.aipdb-country-picker -->

                    <p class="description" style="margin-top:8px;">
                        <?php _e('Drag countries between panels, or use the arrow buttons. Countries in the right panel bypass the AbuseIPDB check. This is a policy filter, not a hard geopolitical block.', 'wp-abuseipdb-integration'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Options', 'wp-abuseipdb-integration')); ?>
    </form>
</div>
