<?php
/**
 * Classic Email Template
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name' => 'Classic',
    'description' => __('Ein klassisches, zeitloses Design', 'newcustomer-discount'),
    'settings' => [
        'primary_color' => '#2C3338',
        'secondary_color' => '#505050',
        'text_color' => '#333333',
        'background_color' => '#ffffff',
        'button_style' => 'square',
        'layout_type' => 'full-width',
        'font_family' => 'Georgia, serif'
    ],
    'styles' => <<<CSS
        .ncd-email {
            font-family: var(--font-family);
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background_color);
            margin: 0;
            padding: 20px;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
        }

        .header {
            text-align: center;
            padding: 30px 20px;
            background: var(--primary-color);
            border-bottom: 3px solid var(--secondary-color);
        }

        .logo {
            max-width: 200px;
            height: auto;
        }

        .content {
            padding: 40px;
        }

        h1 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: normal;
            text-align: center;
            margin: 0 0 30px;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }

        .coupon-code {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            border: 2px solid var(--primary-color);
            margin: 30px 0;
        }

        .details {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
        }

        .details h3 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 18px;
            font-weight: normal;
        }

        .details ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .details li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .details li:last-child {
            border-bottom: none;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-color);
            color: #ffffff;
            text-decoration: none;
            font-weight: normal;
            margin: 20px 0;
            text-align: center;
            border: 2px solid var(--primary-color);
        }

        .button:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #666666;
            margin-top: 40px;
            padding: 20px;
            border-top: 1px solid #ddd;
            background: #f8f9fa;
        }

        @media (prefers-color-scheme: dark) {
            .email-wrapper {
                border-color: #444;
            }

            .details {
                background: #333;
                border-color: #444;
            }

            .details li {
                border-bottom-color: #444;
            }

            .footer {
                background: #333;
                border-top-color: #444;
                color: #999;
            }
        }

        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px;
            }

            .coupon-code {
                font-size: 24px;
            }

            .button {
                display: block;
            }
        }
    CSS,
    'html' => <<<HTML
    <div class="ncd-email classic-template">
        <div class="email-wrapper">
            <div class="header">
                <img src="{logo_url}" alt="{shop_name}" class="logo">
            </div>

            <div class="content">
                <h1>Exklusiver Rabattgutschein</h1>
                
                <p>{email_greeting}</p>
                
                <p>{email_intro}</p>

                <div class="coupon-code">
                    {coupon_code}
                </div>

                <div class="details">
                    <h3>Gutschein-Details:</h3>
                    <ul>
                        <li>Rabatt: {discount_amount}%</li>
                        <li>Gültig bis: {expiry_date}</li>
                        <li>Mindestbestellwert: {min_order_amount}</li>
                    </ul>
                </div>

                <center>
                    <a href="{shop_url}" class="button">Jetzt im Shop einlösen</a>
                </center>

                <p style="text-align: center;">
                    {email_coupon_info}
                </p>
            </div>

            <div class="footer">
                <p>&copy; {current_year} {shop_name}. Alle Rechte vorbehalten.</p>
                <p>{email_footer}</p>
            </div>
        </div>
    </div>
HTML
];