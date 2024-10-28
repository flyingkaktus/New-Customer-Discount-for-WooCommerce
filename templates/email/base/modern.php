<?php
/**
 * Modern Email Template
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name' => 'Modern',
    'description' => __('Ein modernes, klares Design mit akzentuierten Farben', 'newcustomer-discount'),
    'settings' => [
        'primary_color' => '#4CAF50',
        'secondary_color' => '#45a049',
        'text_color' => '#333333',
        'background_color' => '#f5f5f5',
        'button_style' => 'rounded',
        'layout_type' => 'centered',
        'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif'
    ],
    'styles' => <<<CSS
        .ncd-email {
            font-family: var(--font-family);
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }

        .content {
            padding: 40px;
        }

        h1 {
            color: var(--text-color);
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            margin: 0 0 30px;
        }

        .coupon-code {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            border: 2px dashed #ddd;
            margin: 30px 0;
            border-radius: 6px;
            color: var(--text-color);
        }

        .details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .details h3 {
            margin-top: 0;
            color: var(--text-color);
            font-size: 18px;
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
            padding: 14px 28px;
            background: var(--primary-color);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
            text-align: center;
            transition: all 0.3s ease;
        }

        .button:hover {
            background: var(--secondary-color);
        }

        .footer {
            text-align: center;
            font-size: 13px;
            color: #666666;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        @media (prefers-color-scheme: dark) {
            .details {
                background: #333;
            }

            .coupon-code {
                background: #333;
                border-color: #444;
                color: #f5f5f5;
            }

            .details li {
                border-bottom-color: #444;
            }

            .footer {
                color: #999;
                border-top-color: #444;
            }
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }

            .content {
                padding: 20px;
            }

            .coupon-code {
                font-size: 24px;
            }

            .button {
                display: block;
                text-align: center;
            }
        }
    CSS,
    'html' => <<<HTML
    <div class="ncd-email modern-template">
        <div class="email-wrapper">
            <div class="header">
                <img src="{logo_url}" alt="{shop_name}" class="logo">
            </div>

            <div class="content">
                <h1>Exklusiver Rabattgutschein</h1>
                
                <p>Vielen Dank für Ihr Interesse an {shop_name}! Als Zeichen unserer Wertschätzung haben wir einen exklusiven Rabattgutschein für Sie erstellt.</p>

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
                    <a href="{shop_url}" class="button">Jetzt einlösen</a>
                </center>

                <p style="text-align: center;">
                    Besuchen Sie unseren Shop und geben Sie Ihren Gutscheincode beim Checkout ein.
                </p>
            </div>

            <div class="footer">
                <p>&copy; {current_year} {shop_name}. Alle Rechte vorbehalten.</p>
                <p>Dies ist eine automatisch generierte E-Mail.</p>
            </div>
        </div>
    </div>
HTML
];