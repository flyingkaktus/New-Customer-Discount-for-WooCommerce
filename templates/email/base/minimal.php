<?php
/**
 * Minimal Email Template
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
    'name' => 'Minimal',
    'description' => __('Ein reduziertes, minimalistisches Design', 'newcustomer-discount'),
    'settings' => $settings,
    'styles' => <<<CSS
        .minimal-template {
            --primary-color: {$settings['primary_color']};
            --secondary-color: {$settings['secondary_color']};
            --text-color: {$settings['text_color']};
            --background-color: {$settings['background_color']};
            --font-family: {$settings['font_family']};
        }

        /* Button Styles - mit Scope */
        .minimal-template .button.rounded {
            border-radius: 4px;
        }

        .minimal-template .button.pill {
            border-radius: 25px;
        }

        .minimal-template .button.minimal {
            border-radius: 0;
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 8px 16px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 12px;
        }

        /* Layout Styles - mit Scope */
        .minimal-template .email-wrapper.centered {
            margin: 0 auto;
            max-width: 500px;
        }

        .minimal-template .email-wrapper.full-width {
            margin: 0;
            max-width: none;
        }

        .minimal-template.ncd-email {
            font-family: var(--font-family);
            line-height: 1.6;
            color: var(--text-color);
            margin: 0;
            padding: 40px 20px;
        }

        .minimal-template .email-wrapper {
            background-color: var(--background-color);
        }

        .minimal-template .header {
            text-align: center;
            padding: 20px 0 40px;
            border-bottom: 1px solid var(--text-color);
        }

        .minimal-template .logo {
            max-width: 120px;
            height: auto;
        }

        .minimal-template .content {
            padding: 40px 0;
        }

        .minimal-template h1 {
            color: var(--text-color);
            font-size: 18px;
            font-weight: 400;
            text-align: center;
            margin: 0 0 30px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .minimal-template .coupon-code {
            text-align: center;
            font-size: 24px;
            font-weight: 300;
            letter-spacing: 5px;
            margin: 30px 0;
            padding: 20px;
            border-top: 1px solid var(--text-color);
            border-bottom: 1px solid var(--text-color);
            color: var(--text-color);
        }

        .minimal-template .details {
            margin: 30px 0;
            text-align: center;
            font-size: 14px;
        }

        .minimal-template .details h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .minimal-template .details ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .minimal-template .details li {
            padding: 8px 0;
            color: var(--text-color);
        }

        .minimal-template .button {
            display: inline-block;
            padding: 12px 24px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            margin: 20px 0;
        }

        .minimal-template .footer {
            text-align: center;
            font-size: 12px;
            color: var(--text-color);
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--text-color);
        }

        @media (prefers-color-scheme: dark) {
            .minimal-template .email-wrapper {
                background-color: var(--background-color);
            }

            .minimal-template .header,
            .minimal-template .coupon-code,
            .minimal-template .footer {
                border-color: var(--text-color);
            }

            .minimal-template h1, 
            .minimal-template .coupon-code {
                color: var(--text-color);
            }

            .minimal-template .button.minimal {
                border-color: var(--text-color);
                color: var(--text-color);
            }
        }

        @media only screen and (max-width: 500px) {
            .minimal-template.ncd-email {
                padding: 20px;
            }

            .minimal-template .content {
                padding: 20px 0;
            }

            .minimal-template .coupon-code {
                font-size: 20px;
                letter-spacing: 3px;
            }
        }
CSS,
    'html' => <<<HTML
    <div class="ncd-email minimal-template" style="font-family: {$settings['font_family']}">
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
                    <h3>Details</h3>
                    <ul>
                        <li>{discount_amount}% Rabatt auf Ihre Bestellung</li>
                        <li>Gültig bis {expiry_date}</li>
                        <li>Mindestbestellwert: {min_order_amount}</li>
                    </ul>
                </div>

                <center>
                    <a href="{shop_url}" class="button {$settings['button_style']}">Zum Shop</a>
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