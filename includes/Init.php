<?php

namespace CourseManager;

use CourseManager\Admin\AdminSettings;
use CourseManager\Frontend\ContentFilter;
use CourseManager\Frontend\Shortcode;
use CourseManager\PostType\Course;
use CourseManager\PostType\Enrollment;
use CourseManager\Taxonomy\CourseTaxonomies;

/**
 * Init class.
 */
class Init {
    public function register(): void {
        // Register post type and taxonomies
        add_action('init', [new Course(), 'register']);
        add_action('init', [new Enrollment(), 'register']);
        add_action('init', [new CourseTaxonomies(), 'register']);

        // Register frontend components
        add_action('init', [new Shortcode(), 'register']);
        add_action('init', [new ContentFilter(), 'register']);
        add_action('wp_enqueue_scripts', [new Assets(), 'enqueue']);

        // Register admin components
        if (is_admin()) {
            (new AdminSettings())->register();
            add_action('admin_enqueue_scripts', [new Assets(), 'enqueueAdmin']);
        }

        // Flush rewrite rules on activation and deactivation
        $this->flushRewriteRules();
    }

    /**
     * Flush rewrite rules on activation and deactivation.
     *
     * @return void
     */
    private function flushRewriteRules(): void {
        register_activation_hook(__FILE__, function() {
            flush_rewrite_rules();
        });

        register_deactivation_hook(__FILE__, function() {
            flush_rewrite_rules();
        });
    }
}