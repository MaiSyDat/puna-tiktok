<?php

/**
 * Mega.nz Configuration stored inside the theme.
 *
 * @package puna-tiktok
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Mega_Config {

    /**
     * Load credentials from external file if exists
     */
    private static function load_credentials_file() {
        $credentials_file = get_template_directory() . '/mega-credentials.php';
        if (file_exists($credentials_file)) {
            require_once $credentials_file;
        }
    }

    /**
     * Get Mega.nz email.
     *
     * @return string
     */
    public static function get_email() {
        self::load_credentials_file();
        
        $email = get_option('puna_tiktok_mega_email', '');

        if (empty($email) && defined('MEGA_EMAIL')) {
            $email = MEGA_EMAIL;
        }

        if (empty($email)) {
            return '';
        }

        return (string) $email;
    }

    /**
     * Get Mega.nz password.
     *
     * @return string
     */
    public static function get_password() {
        self::load_credentials_file();
        
        $password = get_option('puna_tiktok_mega_password', '');

        if (empty($password) && defined('MEGA_PASSWORD')) {
            $password = MEGA_PASSWORD;
        }

        if (empty($password)) {
            return '';
        }

        return (string) $password;
    }

    /**
     * Get upload folder path.
     *
     * @return string
     */
    public static function get_upload_folder() {
        self::load_credentials_file();
        
        $folder = get_option('puna_tiktok_mega_folder', '');

        if (empty($folder) && defined('MEGA_UPLOAD_FOLDER')) {
            $folder = MEGA_UPLOAD_FOLDER;
        }

        if (empty($folder)) {
            $folder = '/tiktok-video';
        }

        return (string) $folder;
    }
}

