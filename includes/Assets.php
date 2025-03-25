<?php

namespace CourseManager;

/**
 * Assets class.
 */
class Assets {
    public function enqueue(): void
    {
        $base = plugin_dir_url(__FILE__) . '../dist/';

        wp_enqueue_style('course-manager-style', $base . 'style.css');
        wp_enqueue_script('course-manager-script', $base . 'script.js', [], null, true);
    }
}