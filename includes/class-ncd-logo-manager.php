<?php
/**
 * Logo Manager Class
 *
 * Manages the upload, storage and retrieval of logos for email templates
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Logo_Manager {
    /**
     * Option name for the logo in WordPress database
     *
     * @var string
     */
    private static $option_name = 'ncd_logo_base64';

    /**
     * Allowed image types
     *
     * @var array
     */
    private static $allowed_types = ['image/jpeg', 'image/png'];

    /**
     * Maximum file size in bytes (2MB)
     *
     * @var int
     */
    private static $max_file_size = 2097152;

    /**
     * Saves a base64 string as logo
     *
     * @param string $base64_string The base64 string to be saved
     * @return bool True on success, False on error
     */
    public static function save_base64($base64_string) {
        try {
            if (!self::validate_base64($base64_string)) {
                throw new Exception(__('Invalid Base64 string.', 'newcustomer-discount'));
            }
            
            return update_option(self::$option_name, $base64_string);
        } catch (Exception $e) {
            self::log_error('Base64 save failed', [
                'error' => $e->getMessage(),
                'base64_length' => strlen($base64_string)
            ]);
            return false;
        }
    }

    /**
     * Saves a logo via file upload
     *
     * @param array $file $_FILES array of the upload
     * @return bool True on success, False on error
     */
    public static function save_logo($file) {
        try {
            if (!self::validate_upload($file)) {
                throw new Exception(__('Invalid file.', 'newcustomer-discount'));
            }

            $base64 = self::convert_to_base64($file);
            if (!$base64) {
                throw new Exception(__('Conversion failed.', 'newcustomer-discount'));
            }

            return self::save_base64($base64);
        } catch (Exception $e) {
            self::log_error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $file['name']
            ]);
            return false;
        }
    }

    /**
     * Retrieves the stored logo
     *
     * @return string Base64 string of the logo or empty string
     */
    public static function get_logo() {
        return get_option(self::$option_name, '');
    }

    /**
     * Deletes the stored logo
     *
     * @return bool True on success, False on error
     */
    public static function delete_logo() {
        return delete_option(self::$option_name);
    }

    /**
     * Validates a base64 string
     *
     * @param string $string Base64 string to validate
     * @return bool True if valid, False if invalid
     */
    private static function validate_base64($string) {
        if (!preg_match('/^data:image\/(jpeg|png);base64,/', $string)) {
            return false;
        }

        $base64_string = preg_replace('/^data:image\/(jpeg|png);base64,/', '', $string);
        $decoded = base64_decode($base64_string, true);

        if (!$decoded) {
            return false;
        }

        if (strlen($decoded) > self::$max_file_size) {
            return false;
        }

        return true;
    }

    /**
     * Validates a file upload
     *
     * @param array $file $_FILES array of the upload
     * @return bool True if valid, False if invalid
     */
    private static function validate_upload($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        if (!in_array($file['type'], self::$allowed_types)) {
            return false;
        }

        if ($file['size'] > self::$max_file_size) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, self::$allowed_types)) {
            return false;
        }

        return true;
    }

    /**
     * Converts a file to base64
     *
     * @param array $file $_FILES array of the upload
     * @return string|false Base64 string or False on error
     */
    private static function convert_to_base64($file) {
        try {
            if (!file_exists($file['tmp_name'])) {
                throw new Exception('Temporary file not found');
            }

            $data = file_get_contents($file['tmp_name']);
            if ($data === false) {
                throw new Exception('Could not read file');
            }

            return 'data:' . $file['type'] . ';base64,' . base64_encode($data);
        } catch (Exception $e) {
            self::log_error('Base64 conversion failed', [
                'error' => $e->getMessage(),
                'file' => $file['name']
            ]);
            return false;
        }
    }

    /**
     * Logs errors for debugging
     *
     * @param string $message Error message
     * @param array $context Additional context information
     * @return void
     */
    private static function log_error($message, $context = []) {
        if (WP_DEBUG) {
            error_log(sprintf(
                '[NewCustomerDiscount] Logo Manager Error: %s | Context: %s',
                $message,
                json_encode($context)
            ));
        }
    }

    /**
     * Returns the allowed file types
     *
     * @return array Array with allowed MIME types
     */
    public static function get_allowed_types() {
        return self::$allowed_types;
    }

    /**
     * Returns the maximum file size in bytes
     *
     * @return int Maximum file size in bytes
     */
    public static function get_max_file_size() {
        return self::$max_file_size;
    }
}