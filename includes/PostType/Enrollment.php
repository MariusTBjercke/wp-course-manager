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
                'name' => 'Påmeldinger',
                'singular_name' => 'Påmelding',
                'add_new' => 'Legg til ny',
                'add_new_item' => 'Legg til ny påmelding',
                'edit_item' => 'Rediger påmelding',
                'new_item' => 'Ny påmelding',
                'view_item' => 'Vis påmelding',
                'view_items' => 'Vis påmeldinger',
                'search_items' => 'Søk i påmeldinger',
                'not_found' => 'Ingen påmeldinger funnet',
                'not_found_in_trash' => 'Ingen påmeldinger funnet i papirkurven',
                'all_items' => 'Alle påmeldinger',
                'menu_name' => 'Påmeldinger'
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