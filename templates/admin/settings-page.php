<?php
/**
 * Admin Settings Page Template
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_logo = NCD_Logo_Manager::get_logo();
$max_file_size = size_format(NCD_Logo_Manager::get_max_file_size());
$allowed_types = implode(', ', array_map(function($type) {
    return strtoupper(str_replace('image/', '', $type));
}, NCD_Logo_Manager::get_allowed_types()));
$email_sender = new NCD_Email_Sender();
?>

<div class="wrap ncd-wrap">
    <h1><?php _e('Neukunden-Gutschein Einstellungen', 'newcustomer-discount'); ?></h1>
    <?php settings_errors(); ?>
    <div class="ncd-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#logo-settings" class="nav-tab nav-tab-active">
                <span class="dashicons dashicons-format-image"></span>
                <?php _e('Logo', 'newcustomer-discount'); ?>
            </a>
            <a href="#email-settings" class="nav-tab">
                <span class="dashicons dashicons-email"></span>
                <?php _e('E-Mail', 'newcustomer-discount'); ?>
            </a>
            <a href="#Gutschein-settings" class="nav-tab">
                <span class="dashicons dashicons-tickets-alt"></span>
                <?php _e('Gutschein', 'newcustomer-discount'); ?>
            </a>
            <a href="#customer-settings" class="nav-tab">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Neukunden-Definition', 'newcustomer-discount'); ?>
            </a>
            <a href="#reset-settings" class="nav-tab">
                <span class="dashicons dashicons-image-rotate"></span>
                <?php _e('Reset', 'newcustomer-discount'); ?>
            </a>
            <a href="#donate-settings" class="nav-tab">
                <span class="dashicons dashicons-heart"></span>
                <?php _e('Unterstützen', 'newcustomer-discount'); ?>
            </a>
            <a href="#feedback-settings" class="nav-tab">
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('Feedback', 'newcustomer-discount'); ?>
            </a>
        </nav>

        <!-- Logo Settings -->
        <div id="logo-settings" class="ncd-tab-content active">
            <div class="ncd-card">
                <h2><?php _e('Logo-Einstellungen', 'newcustomer-discount'); ?></h2>
                
                <form method="post" enctype="multipart/form-data" class="ncd-logo-form">
                    <?php wp_nonce_field('ncd_settings', 'ncd_settings_nonce'); ?>

                    <table class="form-table ncd-form-table">
                        <tr>
                            <th scope="row">
                                <label for="logo_file">
                                    <?php _e('Logo hochladen', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="file" 
                                       name="logo_file" 
                                       id="ncd-logo-file" 
                                       accept="image/png,image/jpeg" 
                                       class="ncd-file-input">
                                <p class="description">
                                    <?php printf(
                                        __('Erlaubte Dateitypen: %s. Maximale Größe: %s', 'newcustomer-discount'),
                                        $allowed_types,
                                        $max_file_size
                                    ); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="logo_base64">
                                    <?php _e('ODER Base64-String', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <textarea name="logo_base64" 
                                          id="logo_base64" 
                                          rows="5" 
                                          class="large-text code ncd-textarea"
                                          placeholder="data:image/png;base64,..."><?php echo esc_textarea($current_logo); ?></textarea>
                                <p class="description">
                                    <?php _e('Fügen Sie hier Ihren Base64-codierten Bildstring ein (beginnt mit \'data:image/...\')', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <?php if ($current_logo): ?>
                        <tr>
                            <th scope="row">
                                <?php _e('Aktuelles Logo', 'newcustomer-discount'); ?>
                            </th>
                            <td>
                                <div class="ncd-logo-preview-wrapper">
                                    <img src="<?php echo esc_attr($current_logo); ?>" 
                                         alt="<?php _e('Aktuelles Logo', 'newcustomer-discount'); ?>"
                                         class="ncd-logo-preview">
                                </div>
                                <div class="ncd-logo-actions">
                                    <button type="submit" 
                                            name="delete_logo" 
                                            class="button button-secondary ncd-delete-logo">
                                        <?php _e('Logo löschen', 'newcustomer-discount'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <p class="submit">
                        <button type="submit" 
                                name="update_logo" 
                                class="button button-primary">
                            <?php _e('Logo speichern', 'newcustomer-discount'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Email Settings -->
        <div id="email-settings" class="ncd-tab-content">
            <div class="ncd-card">
                <h2><?php _e('E-Mail-Einstellungen', 'newcustomer-discount'); ?></h2>
                
                <form method="post" class="ncd-email-settings-form">
                    <?php wp_nonce_field('ncd_settings', 'ncd_settings_nonce'); ?>
                    <?php $email_texts = $email_sender->get_email_texts(); ?>

                    <table class="form-table ncd-form-table">
                        <tr>
                            <th scope="row">
                                <label for="email_subject">
                                    <?php _e('E-Mail-Betreff', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                    name="email_subject" 
                                    id="email_subject" 
                                    value="<?php echo esc_attr(get_option('ncd_email_subject', __('Dein persönlicher Neukundenrabatt', 'newcustomer-discount'))); ?>" 
                                    class="regular-text">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="email_heading">
                                    <?php _e('E-Mail-Überschrift', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                    name="email_texts[heading]" 
                                    id="email_heading" 
                                    value="<?php echo esc_attr($email_texts['heading']); ?>" 
                                    class="regular-text">
                                <p class="description">
                                    <?php _e('Die Hauptüberschrift der E-Mail.', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="email_greeting">
                                    <?php _e('Begrüßung', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <textarea name="email_texts[greeting]" 
                                        id="email_greeting" 
                                        class="large-text" 
                                        rows="2"><?php echo esc_textarea($email_texts['greeting']); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="email_intro">
                                    <?php _e('Einleitungstext', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <textarea name="email_texts[intro]" 
                                        id="email_intro" 
                                        class="large-text" 
                                        rows="3"><?php echo esc_textarea($email_texts['intro']); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="email_coupon_info">
                                    <?php _e('Gutschein-Information', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <textarea name="email_texts[coupon_info]" 
                                        id="email_coupon_info" 
                                        class="large-text" 
                                        rows="2"><?php echo esc_textarea($email_texts['coupon_info']); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="email_footer">
                                    <?php _e('Fußzeile', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <textarea name="email_texts[footer]" 
                                        id="email_footer" 
                                        class="large-text" 
                                        rows="2"><?php echo esc_textarea($email_texts['footer']); ?></textarea>
                                <p class="description">
                                    <?php 
                                    $variables = $email_sender->get_available_variables();
                                    _e('Verfügbare Variablen:', 'newcustomer-discount');
                                    echo ' ';
                                    echo implode(', ', array_keys($variables));
                                    ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" 
                                name="save_email_settings" 
                                class="button button-primary">
                            <?php _e('Einstellungen speichern', 'newcustomer-discount'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Gutschein Settings -->
        <div id="Gutschein-settings" class="ncd-tab-content">
            <div class="ncd-card">
                <h2><?php _e('Gutschein-Einstellungen', 'newcustomer-discount'); ?></h2>
                
                <form method="post" class="ncd-Gutschein-settings-form">
                    <?php wp_nonce_field('ncd_settings', 'ncd_settings_nonce'); ?>

                    <table class="form-table ncd-form-table">
                        <!-- Rabatt-Einstellungen -->
                        <tr>
                            <th scope="row">
                                <label for="discount_amount">
                                    <?php _e('Rabatt-Höhe (%)', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                    name="discount_amount" 
                                    id="discount_amount" 
                                    value="<?php echo esc_attr(get_option('ncd_discount_amount', '20')); ?>" 
                                    min="1" 
                                    max="100" 
                                    step="1" 
                                    class="small-text">
                                <p class="description">
                                    <?php _e('Prozentuale Höhe des Neukundenrabatts.', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="expiry_days">
                                    <?php _e('Gültigkeitsdauer (Tage)', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                    name="expiry_days" 
                                    id="expiry_days" 
                                    value="<?php echo esc_attr(get_option('ncd_expiry_days', '30')); ?>" 
                                    min="1" 
                                    max="365" 
                                    step="1" 
                                    class="small-text">
                                <p class="description">
                                    <?php _e('Anzahl der Tage, die der Gutschein gültig ist.', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Code-Konfiguration -->
                        <tr>
                            <th scope="row">
                                <label for="code_prefix">
                                    <?php _e('Gutschein-Präfix', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                    name="code_prefix" 
                                    id="code_prefix" 
                                    value="<?php echo esc_attr(get_option('ncd_code_prefix', 'NL')); ?>" 
                                    class="regular-text"
                                    maxlength="5">
                                <p class="description">
                                    <?php _e('Präfix für automatisch generierte Gutscheincodes (max. 5 Zeichen).', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="code_length">
                                    <?php _e('Gutschein-Länge', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                    name="code_length" 
                                    id="code_length" 
                                    value="<?php echo esc_attr(get_option('ncd_code_length', '6')); ?>" 
                                    min="4" 
                                    max="12" 
                                    class="small-text">
                                <p class="description">
                                    <?php _e('Länge des Codes ohne Präfix (4-12 Zeichen).', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="code_characters">
                                    <?php _e('Erlaubte Zeichen', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                        name="code_chars[]" 
                                        value="numbers" 
                                        <?php checked(in_array('numbers', (array)get_option('ncd_code_chars', ['numbers', 'uppercase']))); ?>>
                                    <?php _e('Zahlen (0-9)', 'newcustomer-discount'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" 
                                        name="code_chars[]" 
                                        value="uppercase" 
                                        <?php checked(in_array('uppercase', (array)get_option('ncd_code_chars', ['numbers', 'uppercase']))); ?>>
                                    <?php _e('Großbuchstaben (A-Z)', 'newcustomer-discount'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" 
                                        name="code_chars[]" 
                                        value="lowercase" 
                                        <?php checked(in_array('lowercase', (array)get_option('ncd_code_chars', ['numbers', 'uppercase']))); ?>>
                                    <?php _e('Kleinbuchstaben (a-z)', 'newcustomer-discount'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Wählen Sie die Zeichentypen für die Gutscheincodes.', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" 
                                name="save_coupon_settings" 
                                class="button button-primary">
                            <?php _e('Einstellungen speichern', 'newcustomer-discount'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Customer Definition -->
        <div id="customer-settings" class="ncd-tab-content">
            <div class="ncd-card">
                <h2><?php _e('Neukunden-Definition', 'newcustomer-discount'); ?></h2>
                
                <form method="post" class="ncd-customer-settings-form">
                <?php wp_nonce_field('ncd_settings', 'ncd_settings_nonce'); ?>
                <?php wp_nonce_field('ncd_settings', 'ncd_settings_nonce'); ?>

                    <table class="form-table ncd-form-table">
                        <tr>
                            <th scope="row">
                                <label for="cutoff_date">
                                    <?php _e('Stichtag', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="date" 
                                       name="cutoff_date" 
                                       id="cutoff_date" 
                                       value="<?php echo esc_attr(get_option('ncd_cutoff_date', '2024-01-01')); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Kunden ohne Bestellung vor diesem Datum werden als Neukunden behandelt.', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="order_count">
                                    <?php _e('Maximale Bestellungen', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       name="order_count" 
                                       id="order_count" 
                                       value="<?php echo esc_attr(get_option('ncd_order_count', '0')); ?>" 
                                       min="0" 
                                       max="10" 
                                       class="small-text">
                                <p class="description">
                                    <?php _e('Maximale Anzahl vorheriger Bestellungen für Neukunden (0 = keine vorherigen Bestellungen erlaubt).', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="check_period">
                                    <?php _e('Prüfungszeitraum', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <select name="check_period" id="check_period" class="regular-text">
                                    <option value="all" <?php selected(get_option('ncd_check_period', 'all'), 'all'); ?>>
                                        <?php _e('Gesamter Zeitraum', 'newcustomer-discount'); ?>
                                    </option>
                                    <option value="365" <?php selected(get_option('ncd_check_period', 'all'), '365'); ?>>
                                        <?php _e('Letztes Jahr', 'newcustomer-discount'); ?>
                                    </option>
                                    <option value="180" <?php selected(get_option('ncd_check_period', 'all'), '180'); ?>>
                                        <?php _e('Letzte 6 Monate', 'newcustomer-discount'); ?>
                                    </option>
                                    <option value="90" <?php selected(get_option('ncd_check_period', 'all'), '90'); ?>>
                                        <?php _e('Letzte 3 Monate', 'newcustomer-discount'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Zeitraum, in dem vorherige Bestellungen geprüft werden.', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="min_order_amount">
                                    <?php _e('Mindestbestellwert', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       name="min_order_amount" 
                                       id="min_order_amount" 
                                       value="<?php echo esc_attr(get_option('ncd_min_order_amount', '0')); ?>" 
                                       min="0" 
                                       step="0.01" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Mindestbestellwert für die Neukundenrabatt-Berechtigung (0 = kein Minimum).', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="exclude_categories">
                                    <?php _e('Ausgeschlossene Kategorien', 'newcustomer-discount'); ?>
                                </label>
                            </th>
                            <td>
                                <?php
                                $product_categories = get_terms([
                                    'taxonomy' => 'product_cat',
                                    'hide_empty' => false,
                                ]);
                                $excluded_categories = (array)get_option('ncd_excluded_categories', []);
                                
                                if (!empty($product_categories)) :
                                    foreach ($product_categories as $category) :
                                ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="exclude_categories[]" 
                                               value="<?php echo esc_attr($category->term_id); ?>"
                                               <?php checked(in_array($category->term_id, $excluded_categories)); ?>>
                                        <?php echo esc_html($category->name); ?>
                                    </label><br>
                                <?php 
                                    endforeach;
                                endif;
                                ?>
                                <p class="description">
                                    <?php _e('Bestellungen dieser Kategorien werden bei der Neukundenprüfung nicht berücksichtigt.', 'newcustomer-discount'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" 
                                name="save_customer_settings" 
                                class="button button-primary">
                            <?php _e('Einstellungen speichern', 'newcustomer-discount'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Reset Section -->
        <div id="reset-settings" class="ncd-tab-content">
            <div class="ncd-card">
                <h2><?php _e('Reset & Bereinigung', 'newcustomer-discount'); ?></h2>
                
                <div class="ncd-warning-box">
                    <p><strong><?php _e('Warnung:', 'newcustomer-discount'); ?></strong></p>
                    <p><?php _e('Diese Aktionen können nicht rückgängig gemacht werden. Bitte erstellen Sie vorher ein Backup Ihrer Datenbank.', 'newcustomer-discount'); ?></p>
                </div>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ncd-reset-form">
                    <input type="hidden" name="action" value="ncd_reset_data">
                    <?php wp_nonce_field('ncd_reset_settings', 'ncd_reset_nonce'); ?>
                    
                    <table class="form-table ncd-form-table">
                        <tr>
                            <th scope="row">
                                <?php _e('Gutscheine zurücksetzen', 'newcustomer-discount'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                        name="reset_actions[]" 
                                        value="Gutscheine">
                                    <?php _e('Alle erstellten Gutscheine löschen', 'newcustomer-discount'); ?>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <?php _e('Neukunden zurücksetzen', 'newcustomer-discount'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                        name="reset_actions[]" 
                                        value="customers">
                                    <?php _e('Alle Neukunden-Tracking-Daten löschen', 'newcustomer-discount'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <div class="ncd-confirmation-box">
                        <label>
                            <input type="checkbox" 
                                name="confirm_reset" 
                                value="1" 
                                required>
                            <?php _e('Ich verstehe, dass diese Aktion nicht rückgängig gemacht werden kann.', 'newcustomer-discount'); ?>
                        </label>
                    </div>

                    <p class="submit">
                        <button type="submit" class="button button-danger">
                            <?php _e('Ausgewählte Daten zurücksetzen', 'newcustomer-discount'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Donate Section -->
        <div id="donate-settings" class="ncd-tab-content">
            <div class="ncd-card">
                <h2>
                    <span class="dashicons dashicons-heart" style="color: #e25555;"></span>
                    <?php _e('Entwickler unterstützen', 'newcustomer-discount'); ?>
                </h2>
                
                <div class="ncd-donate-wrapper">
                    <div class="ncd-donate-text">
                        <p>
                            <?php _e('Wenn dir dieses Plugin hilft, würde ich mich über eine kleine Spende zur Weiterentwicklung freuen.', 'newcustomer-discount'); ?>
                        </p>
                        <p class="description">
                            <?php _e('Jede Spende hilft dabei, das Plugin weiter zu verbessern und neue Funktionen zu entwickeln.', 'newcustomer-discount'); ?>
                        </p>
                    </div>

                    <div class="ncd-donate-form">
                        <form action="https://www.paypal.com/donate" method="post" target="_blank" id="ncd-donation-form">
                            <!-- PayPal Konfiguration -->
                            <input type="hidden" name="business" value="MXRUVUQFUYCXN">
                            <input type="hidden" name="currency_code" value="EUR">
                            <input type="hidden" name="item_name" value="<?php echo esc_attr__('Cafe-Spende fuer New-Customer-Gutschein for WooCommerce :)', 'newcustomer-discount'); ?>">
                            <input type="hidden" name="no_shipping" value="1">
                            <input type="hidden" name="cmd" value="_donations">
                            <input type="hidden" name="return" value="<?php echo esc_url(admin_url('admin.php?page=new-customers-settings&tab=donate-settings&donation=success')); ?>">
                            <input type="hidden" name="cancel_return" value="<?php echo esc_url(admin_url('admin.php?page=new-customers-settings&tab=donate-settings')); ?>">
                            
                            <!-- Vorausgewählte Beträge -->
                            <div class="ncd-donate-amounts" role="group" aria-label="<?php esc_attr_e('Vorausgewählte Spendenbeträge', 'newcustomer-discount'); ?>">
                                <?php
                                $amounts = [5, 10, 25, 50];
                                foreach ($amounts as $amount):
                                ?>
                                    <button type="submit" 
                                            name="amount" 
                                            value="<?php echo esc_attr($amount); ?>" 
                                            class="button">
                                        <?php echo esc_html($amount); ?> €
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- Eigener Betrag -->
                            <div class="ncd-donate-custom">
                                <label for="custom-amount">
                                    <?php _e('Oder eigenen Betrag wählen:', 'newcustomer-discount'); ?>
                                </label>
                                <div class="ncd-donate-custom-input">
                                    <input type="number" 
                                        name="amount" 
                                        id="custom-amount" 
                                        min="1" 
                                        step="1" 
                                        placeholder="<?php esc_attr_e('Betrag in Euro', 'newcustomer-discount'); ?>"
                                        aria-label="<?php esc_attr_e('Eigener Spendenbetrag', 'newcustomer-discount'); ?>">
                                    <button type="submit" 
                                            class="button button-primary"
                                            disabled>
                                        <?php _e('Spenden', 'newcustomer-discount'); ?>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if (isset($_GET['donation']) && $_GET['donation'] === 'success'): ?>
                            <div class="ncd-donation-success">
                                <p>
                                    <?php _e('Vielen Dank für deine Unterstützung!', 'newcustomer-discount'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Section -->
        <div id="feedback-settings" class="ncd-tab-content">
            <div class="ncd-card">
                <h2>
                    <span class="dashicons dashicons-megaphone" style="margin-right: 8px;"></span>
                    <?php _e('Feedback & Bug Reports', 'newcustomer-discount'); ?>
                </h2>
                
                <div class="ncd-feedback-wrapper">
                    <div class="ncd-feedback-text">
                        <p>
                            <?php _e('Helfen Sie uns, das Plugin zu verbessern! Senden Sie uns Ihre Vorschläge oder melden Sie Fehler.', 'newcustomer-discount'); ?>
                        </p>
                    </div>

                    <form method="post" class="ncd-feedback-form">
                        <?php wp_nonce_field('ncd_feedback', 'ncd_feedback_nonce'); ?>
                        
                        <table class="form-table ncd-form-table">
                            <tr>
                                <th scope="row">
                                    <label for="feedback_type">
                                        <?php _e('Feedback-Typ', 'newcustomer-discount'); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="feedback_type" id="feedback_type" class="ncd-select">
                                        <option value="suggestion">
                                            <?php _e('Verbesserungsvorschlag', 'newcustomer-discount'); ?>
                                        </option>
                                        <option value="bug">
                                            <?php _e('Fehlermeldung', 'newcustomer-discount'); ?>
                                        </option>
                                        <option value="question">
                                            <?php _e('Frage', 'newcustomer-discount'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>

                            <tr class="bug-specific" style="display: none;">
                                <th scope="row">
                                    <label for="bug_version">
                                        <?php _e('Plugin-Version', 'newcustomer-discount'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" 
                                        id="bug_version" 
                                        name="bug_version" 
                                        value="<?php echo esc_attr(NCD_VERSION); ?>" 
                                        readonly 
                                        class="regular-text">
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="feedback_content">
                                        <?php _e('Ihre Nachricht', 'newcustomer-discount'); ?>
                                    </label>
                                </th>
                                <td>
                                    <textarea name="feedback_content" 
                                            id="feedback_content" 
                                            rows="6" 
                                            class="large-text ncd-textarea"
                                            required></textarea>
                                    <p class="description feedback-hint suggestion" style="display: none;">
                                        <?php _e('Beschreiben Sie Ihren Verbesserungsvorschlag so detailliert wie möglich.', 'newcustomer-discount'); ?>
                                    </p>
                                    <p class="description feedback-hint bug" style="display: none;">
                                        <?php _e('Bitte beschreiben Sie: 1. Was Sie getan haben 2. Was Sie erwartet haben 3. Was stattdessen passiert ist', 'newcustomer-discount'); ?>
                                    </p>
                                    <p class="description feedback-hint question" style="display: none;">
                                        <?php _e('Was möchten Sie wissen? Je detaillierter Ihre Frage, desto besser können wir helfen.', 'newcustomer-discount'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="include_system_info">
                                        <?php _e('System-Informationen', 'newcustomer-discount'); ?>
                                    </label>
                                </th>
                                <td>
                                    <label class="ncd-checkbox-label">
                                        <input type="checkbox" 
                                            name="include_system_info" 
                                            id="include_system_info" 
                                            value="1" 
                                            checked>
                                        <?php _e('System-Informationen beifügen (empfohlen für Fehlermeldungen)', 'newcustomer-discount'); ?>
                                    </label>
                                    <div id="system-info-preview" class="ncd-system-info" style="display: none;">
                                        <pre><?php
                                            global $wp_version;
                                            $system_info = "WordPress: " . $wp_version . "\n";
                                            $system_info .= "PHP: " . phpversion() . "\n";
                                            $system_info .= "Plugin Version: " . NCD_VERSION . "\n";
                                            $system_info .= "WooCommerce: " . WC()->version . "\n";
                                            $system_info .= "Theme: " . wp_get_theme()->get('Name') . "\n";
                                            echo esc_html($system_info);
                                        ?></pre>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" 
                                    name="submit_feedback" 
                                    class="button button-primary">
                                <?php _e('Feedback senden', 'newcustomer-discount'); ?>
                            </button>
                        </p>
                    </form>

                    <div class="ncd-feedback-alternative">
                        <h3><?php _e('Alternatives Feedback', 'newcustomer-discount'); ?></h3>
                        <p>
                            <?php printf(
                                __('Sie können auch einen Bug Report oder Feature Request auf der %sWordPress.org Plugin-Seite%s erstellen.', 'newcustomer-discount'),
                                '<a href="https://wordpress.org/support/plugin/newcustomer-discount/" target="_blank">',
                                '</a>'
                            ); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {

    // File Input Preview
    $('#ncd-logo-file').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('.ncd-logo-preview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    $('.ncd-donate-amounts .button').on('click', function(e) {
        e.preventDefault();
        const amount = $(this).val();
        $('#custom-amount').val(amount);
        $(this).closest('form').submit();
    });

    $('#custom-amount').on('input', function() {
        const value = parseFloat($(this).val());
        $(this).closest('.ncd-donate-custom-input')
            .find('button')
            .prop('disabled', isNaN(value) || value < 1);
    });
});
</script>