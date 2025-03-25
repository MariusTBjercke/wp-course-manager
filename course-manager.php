<?php
/**
 * Plugin Name: CourseManager
 * Description: A simple course listing and enrollment plugin.
 * Version: 1.0
 */

use CourseManager\Init;

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

// Start the plugin.
(new Init())->register();
