<?php
/**
 * Plugin Name: Course Manager
 * Description: A WordPress plugin for managing course listings and enrollments, tailored for Norwegian users.
 * Version: 1.0
 */

use CourseManager\Init;

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

// Start the plugin.
(new Init())->register();
