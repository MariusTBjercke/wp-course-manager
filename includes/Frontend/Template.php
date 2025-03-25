<?php

namespace CourseManager\Frontend;

use CourseManager\Log;

/**
 * Template class.
 */
class Template {
    /**
     * Register the template filter.
     *
     * @return void
     */
    public function register(): void {
        add_filter('template_include', [$this, 'loadTemplate']);
    }

    /**
     * Load the template file.
     *
     * @param string $template The template file path.
     * @return string
     */
    public function loadTemplate(string $template): string {
        if (is_singular('course')) {
            $custom = plugin_dir_path(__FILE__) . '../../templates/single-course.php';

            if (file_exists($custom)) {
                return $custom;
            }
        }

        Log::log('Could not find custom template for course, using default: ' . $template);
        return $template;
    }
}