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
    'description' => __('Ein modernes, dynamisches Design mit klaren Linien', 'newcustomer-discount'),
    'settings' => [
        'primary_color' => '#4F46E5',
        'secondary_color' => '#818CF8',
        'text_color' => '#1F2937',
        'background_color' => '#F9FAFB',
        'button_style' => 'rounded',
        'layout_type' => 'centered',
        'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ],
    'styles' => <<<CSS
        .modern-template {
            --primary-color: {$settings['primary_color']};
            --secondary-color: {$settings['secondary_color']};
            --text-color: {$settings['text_color']};
            --background-color: {$settings['background_color']};
            --font-family: {$settings['font_family']};
        }

        /* Button Styles */
        .modern-template .button.rounded {
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #ffffff;
            font-weight: 500;
            padding: 14px 28px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .modern-template .button.pill {
            border-radius: 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #ffffff;
            font-weight: 500;
            padding: 14px 32px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .modern-template .button.minimal {
            border-radius: 6px;
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
            padding: 12px 26px;
        }

        /* Layout Styles */
        .modern-template .email-wrapper.centered {
            margin: 0 auto;
            max-width: 600px;
        }

        .modern-template .email-wrapper.full-width {
            margin: 0;
            max-width: none;
        }

        .modern-template.ncd-email {
            font-family: var(--font-family);
            line-height: 1.6;
            color: var(--text-color);
            margin: 0;
            padding: 30px 20px;
        }

        .modern-template .email-wrapper {
            background-color: var(--background-color);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .modern-template .header {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
            overflow: hidden;
        }

        .modern-template .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.2), transparent 70%);
        }

        .modern-template .logo {
            max-width: 180px;
            height: auto;
            position: relative
        }

        .modern-template .content {
            padding: 40px;
        }

        .modern-template h1 {
            color: var(--text-color);
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin: 0 0 30px;
            line-height: 1.3;
        }

        .modern-template .coupon-code {
            background: var(--background-color);
            padding: 24px;
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 3px;
            border-radius: 12px;
            margin: 30px 0;
            color: var(--text-color);
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .modern-template .coupon-code::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .modern-template .details {
            background: var(--background-color);
            padding: 24px;
            border-radius: 12px;
            margin: 30px 0;
        }

        .modern-template .details h3 {
            margin: 0 0 16px;
            color: var(--text-color);
            font-size: 18px;
            font-weight: 600;
        }

        .modern-template .details ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 12px;
        }

        .modern-template .details li {
            padding: 12px 16px;
            background: var(--background-color);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            color: var(--text-color);
        }

        .modern-template .button {
            display: inline-block;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            margin: 20px 0;
        }

        .modern-template .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .modern-template .footer {
            text-align: center;
            font-size: 14px;
            color: var(--text-color);
            margin-top: 40px;
            padding: 20px 40px;
            background: var(--background-color);
            border-top: 1px solid var(--text-color);
        }

        @media (prefers-color-scheme: dark) {
            .modern-template .email-wrapper {
                background-color: var(--background-color);
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            }

            .modern-template .details li {
                background: var(--background-color);
            }

            .modern-template .button.minimal {
                border-color: var(--text-color);
                color: var(--text-color);
            }
        }

        @media only screen and (max-width: 600px) {
            .modern-template.ncd-email {
                padding: 16px;
            }

            .modern-template .content {
                padding: 24px;
            }

            .modern-template h1 {
                font-size: 24px;
            }

            .modern-template .coupon-code {
                font-size: 24px;
                padding: 20px;
                letter-spacing: 2px;
            }

            .modern-template .details {
                padding: 20px;
            }

            .modern-template .footer {
                padding: 20px;
            }
        }
CSS,
    'html' => <<<HTML
    <div class="ncd-email modern-template" style="font-family: {$settings['font_family']}">
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
                    <h3>Ihre Vorteile</h3>
                    <ul>
                        <li>✨ {discount_amount}% Rabatt auf Ihre Bestellung</li>
                        <li>⏰ Gültig bis {expiry_date}</li>
                        <li>💰 Mindestbestellwert: {min_order_amount}</li>
                    </ul>
                </div>

                <center>
                    <a href="{shop_url}" class="button {$settings['button_style']}">Jetzt einlösen</a>
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