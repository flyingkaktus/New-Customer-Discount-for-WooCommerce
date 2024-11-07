<?php
/**
 * Classic Email Template
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}

// Stelle sicher dass settings definiert ist
if (!isset($settings)) {
    $settings = [];
}

// Stelle sicher dass settings ein Array ist
if (!is_array($settings)) {
    $settings = [];
}

return [
    'name' => 'Classic',
    'description' => __('Ein zeitlos-elegantes Design', 'newcustomer-discount'),
    'settings' => $settings,
    'styles' => <<<CSS
    .classic-template {
        --primary-color: {$settings['primary_color']};
        --secondary-color: {$settings['secondary_color']};
        --text-color: {$settings['text_color']};
        --background-color: {$settings['background_color']};
        --font-family: {$settings['font_family']};
    }

    /* Button Styles */
    .classic-template .button.rounded {
        border-radius: 4px;
        background: var(--primary-color);
        color: #ffffff;
        font-family: var(--font-family);
        border: none;
    }

    .classic-template .button.pill {
        border-radius: 25px;
        background: var(--primary-color);
        color: #ffffff;
        font-family: var(--font-family);
        border: none;
    }

    .classic-template .button.square {
        border-radius: 0;
        background: var(--primary-color);
        color: #ffffff;
        font-family: var(--font-family);
        border: none;
        position: relative;
        padding: 16px 32px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .classic-template .button.square::after {
        content: '';
        position: absolute;
        top: 4px;
        right: 4px;
        bottom: 4px;
        left: 4px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Layout Styles */
    .classic-template .email-wrapper.centered {
        margin: 0 auto;
        max-width: 600px;
        padding: 40px 0;
    }

    .classic-template .email-wrapper.full-width {
        margin: 0;
        max-width: none;
        padding: 40px 20px;
    }

    .classic-template.ncd-email {
        font-family: var(--font-family);
        line-height: 1.8;
        color: var(--text-color);
        margin: 0;
        padding: 0;
    }

    .classic-template .email-wrapper {
        background-color: var(--background-color);
        border: 1px solid transparent;
        box-shadow: none;
    }

    .classic-template .header {
        text-align: center;
        padding: 40px 20px;
        border-bottom: 2px solid var(--primary-color);
    }

    .classic-template .logo {
        max-width: 200px;
        height: auto;
    }

    .classic-template .content {
        padding: 60px 40px;
    }

    .classic-template h1 {
        color: var(--text-color);
        font-size: 28px;
        font-weight: normal;
        text-align: center;
        margin: 0 0 40px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--text-color);
        font-family: var(--font-family);
    }

    .classic-template .coupon-code {
        text-align: center;
        font-size: 32px;
        font-weight: normal;
        letter-spacing: 4px;
        margin: 40px auto;
        padding: 30px;
        max-width: 400px;
        border: 2px dashed var(--primary-color);
        color: var(--text-color);
        position: relative;
    }

    .classic-template .coupon-code::before,
    .classic-template .coupon-code::after {
        content: '✦';
        position: absolute;
        color: var(--primary-color);
        font-size: 20px;
    }

    .classic-template .coupon-code::before {
        left: 20px;
    }

    .classic-template .coupon-code::after {
        right: 20px;
    }

    .classic-template .details {
        margin: 40px auto;
        max-width: 500px;
        padding: 30px;
        border: 1px solid var(--text-color);
    }

    .classic-template .details h3 {
        margin: 0 0 20px;
        color: var(--text-color);
        font-size: 20px;
        font-weight: normal;
        text-align: center;
        font-family: var(--font-family);
    }

    .classic-template .details ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .classic-template .details li {
        padding: 12px 0;
        border-bottom: 1px solid var(--text-color);
        text-align: center;
        font-style: italic;
        color: var(--text-color);
    }

    .classic-template .details li:last-child {
        border-bottom: none;
    }

    .classic-template .button {
        display: inline-block;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
        margin: 20px 0;
    }

    .classic-template .button:hover {
        background: var(--secondary-color);
    }

    .classic-template .footer {
        text-align: center;
        font-size: 14px;
        color: var(--text-color);
        margin-top: 0;
        padding: 30px;
        border-top: 1px solid var(--text-color);
    }

    @media (prefers-color-scheme: dark) {
        .classic-template .email-wrapper {
            background-color: var(--background-color);
            border-color: transparent;
        }

        .classic-template .header {
            border-bottom-color: var(--primary-color);
        }

        .classic-template h1 {
            color: var(--text-color);
            border-bottom-color: var(--text-color);
        }

        .classic-template .coupon-code {
            border-color: var(--primary-color);
            color: var(--text-color);
        }

        .classic-template .details {
            border-color: var(--text-color);
        }

        .classic-template .details li {
            border-bottom-color: var(--text-color);
            color: var(--text-color);
        }

        .classic-template .footer {
            border-top-color: var(--text-color);
            color: var(--text-color);
        }
    }

    @media only screen and (max-width: 600px) {
        .classic-template .email-wrapper.centered {
            padding: 20px 0;
        }

        .classic-template .content {
            padding: 30px 20px;
        }

        .classic-template h1 {
            font-size: 24px;
            margin-bottom: 30px;
        }

        .classic-template .coupon-code {
            font-size: 24px;
            padding: 20px;
            margin: 30px auto;
        }

        .classic-template .details {
            padding: 20px;
        }
    }
CSS,
    'html' => <<<HTML
    <div class="ncd-email classic-template" style="font-family: {$settings['font_family']}">
        <div class="email-wrapper {$settings['layout_type']}">
            <div class="header">
                <img src="{logo_url}" alt="{shop_name}" class="logo">
            </div>

            <div class="content">
                <h1>{email_heading}</h1>
                
                <p style="text-align: center;">{email_greeting}</p>
                
                <p style="text-align: center;">{email_intro}</p>

                <div class="coupon-code">
                    {coupon_code}
                </div>

                <div class="details">
                    <h3>Gutschein-Details</h3>
                    <ul>
                        <li>⚜️ {discount_amount}% Rabatt auf Ihren Einkauf</li>
                        <li>⌛ Einlösbar bis {expiry_date}</li>
                        <li>✧ Ab einem Bestellwert von {min_order_amount}</li>
                    </ul>
                </div>

                <center>
                    <a href="{shop_url}" class="button {$settings['button_style']}">Zum Online-Shop</a>
                </center>

                <p style="text-align: center;">
                    {email_coupon_info}
                </p>
            </div>

            <div class="footer">
                <p>&copy; {current_year} {shop_name}</p>
                <p>{email_footer}</p>
            </div>
        </div>
    </div>
HTML
];