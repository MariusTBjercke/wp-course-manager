<?php

namespace CourseManager\PostType;

/**
 * Enrollment post type class for managing course enrollments.
 */
class Enrollment {
    /**
     * Register the enrollment post type.
     */
    public function register(): void {
        register_post_type('course_enrollment', [
            'labels' => [
                'name' => 'Enrollments',
                'singular_name' => 'Enrollment',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Enrollment',
                'edit_item' => 'Edit Enrollment',
                'new_item' => 'New Enrollment',
                'view_item' => 'View Enrollment',
                'view_items' => 'View Enrollments',
                'search_items' => 'Search Enrollments',
                'not_found' => 'No enrollments found',
                'not_found_in_trash' => 'No enrollments found in Trash',
                'all_items' => 'All Enrollments',
                'menu_name' => 'Enrollments'
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=course',
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }
}