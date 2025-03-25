<?php

namespace CourseManager;

use CourseManager\Frontend\Shortcode;
use CourseManager\Frontend\Template;
use CourseManager\PostType\Course;

/**
 * Init class.
 */
class Init {
    public function register(): void
    {
        add_action('init', [new Course(), 'register']);
        add_action('init', [new Shortcode(), 'register']);
        add_action('init', [new Template(), 'register']);
        add_action('wp_enqueue_scripts', [new Assets(), 'enqueue']);
    }
}