<?php
/**
 * Plugin Name: Course Manager
 * Description: A WordPress plugin for managing course listings and enrollments, tailored for Norwegian users.
 * Version: 0.1.0-beta.1
 * Requires Plugins: woocommerce
 */

use CourseManager\Init;

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

// Start the plugin if WooCommerce is installed and active
add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' .
                __('Course Manager krever WooCommerce for å fungere. Vennligst installer og aktiver WooCommerce, eller deaktiver Course Manager.', 'course-manager') .
                '</p></div>';
        });
        return;
    }

    (new Init())->register();
});