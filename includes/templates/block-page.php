<?php
/**
 * Block page rendered by AIPDB_Firewall when a visitor is denied access.
 *
 * Available variables (set by the firewall before include):
 *   $blocked_ip   string  The IP being blocked.
 *   $block_reason string  Reason shown to the visitor (intentionally generic).
 */
if (!defined('ABSPATH')) {
    exit;
}

$site_name = function_exists('get_bloginfo') ? get_bloginfo('name') : 'This site';
$contact_email = get_option('admin_email');
$blocked_ip = isset($blocked_ip) ? $blocked_ip : '';
$block_reason = isset($block_reason) ? $block_reason : '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html__('Access Denied', 'wp-abuseipdb-integration'); ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f6f7f7;
            color: #1d2327;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .aipdb-block {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            max-width: 520px;
            width: 100%;
            padding: 40px 32px;
            text-align: center;
        }
        .aipdb-block-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #fcf0f1;
            color: #d63638;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
        }
        .aipdb-block h1 {
            font-size: 24px;
            margin: 0 0 8px;
            color: #1d2327;
        }
        .aipdb-block p {
            margin: 8px 0;
            color: #50575e;
            line-height: 1.5;
        }
        .aipdb-block .aipdb-details {
            background: #f6f7f7;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 12px 16px;
            margin: 20px 0;
            text-align: left;
            font-size: 13px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            color: #2c3338;
            word-break: break-all;
        }
        .aipdb-block .aipdb-details strong {
            color: #1d2327;
        }
        .aipdb-block .aipdb-contact {
            margin-top: 24px;
            font-size: 13px;
            color: #646970;
        }
        .aipdb-block .aipdb-contact a {
            color: #2271b1;
            text-decoration: none;
        }
        .aipdb-block .aipdb-contact a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="aipdb-block" role="alert">
        <div class="aipdb-block-icon" aria-hidden="true">!</div>
        <h1><?php echo esc_html__('Access Denied', 'wp-abuseipdb-integration'); ?></h1>
        <p>
            <?php
            printf(
                /* translators: %s: site name */
                esc_html__('Your access to %s has been blocked for security reasons.', 'wp-abuseipdb-integration'),
                '<strong>' . esc_html($site_name) . '</strong>'
            );
            ?>
        </p>

        <div class="aipdb-details">
            <div><strong><?php echo esc_html__('Your IP:', 'wp-abuseipdb-integration'); ?></strong> <?php echo esc_html($blocked_ip); ?></div>
            <?php if (!empty($block_reason)) : ?>
                <div><strong><?php echo esc_html__('Reason:', 'wp-abuseipdb-integration'); ?></strong> <?php echo esc_html($block_reason); ?></div>
            <?php endif; ?>
            <div><strong><?php echo esc_html__('Time:', 'wp-abuseipdb-integration'); ?></strong> <?php echo esc_html(gmdate('Y-m-d H:i:s')); ?> UTC</div>
        </div>

        <?php if (!empty($contact_email)) : ?>
            <p class="aipdb-contact">
                <?php esc_html_e('If you believe this is a mistake, contact the site administrator:', 'wp-abuseipdb-integration'); ?>
                <br>
                <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a>
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
