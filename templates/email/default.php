<?php
/**
 * Default Email Template
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}

return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{shop_name} - Rabattgutschein</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
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
            background: linear-gradient(135deg, #4CAF50, #45a049);
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
            color: #2c3338;
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
            color: #333;
        }

        .details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .details h3 {
            margin-top: 0;
            color: #2c3338;
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
            background: #4CAF50;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
            text-align: center;
            transition: all 0.3s ease;
        }

        .button:hover {
            background: #45a049;
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
            body {
                background-color: #1a1a1a;
                color: #f5f5f5;
            }

            .email-wrapper {
                background-color: #2d2d2d;
            }

            h1, .details h3 {
                color: #f5f5f5;
            }

            .coupon-code {
                background: #333;
                border-color: #444;
                color: #f5f5f5;
            }

            .details {
                background: #333;
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
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <img src="{logo_url}" alt="{shop_name}" class="logo">
        </div>

        <div class="content">
            <h1>Exklusiver Rabattgutschein</h1>

            <p>Ein Rabattgutschein wurde für Sie erstellt.</p>

            <div class="coupon-code">
                {coupon_code}
            </div>

            <div class="details">
                <h3>Gutschein-Details:</h3>
                <ul>
                    <li>Rabatt: {discount_amount}%</li>
                    <li>Gültig bis: {expiry_date}</li>
                    <li>{min_order_amount}</li>
                </ul>
            </div>

            <center>
                <a href="{shop_url}" class="button">Zum Shop</a>
            </center>
        </div>

        <div class="footer">
            <p>&copy; {current_year} {shop_name}. Alle Rechte vorbehalten.</p>
            <p>Dies ist eine automatisch generierte E-Mail.</p>
        </div>
    </div>
</body>
</html>';