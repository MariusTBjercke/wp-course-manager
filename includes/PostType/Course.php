<?php

namespace CourseManager\PostType;

/**
 * Course class.
 */
class Course {
    public function register(): void
    {
        register_post_type('course', [
            'labels' => [
                'name' => 'Courses',
                'singular_name' => 'Course'
            ],
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'courses'],
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-welcome-learn-more',
        ]);
    }
}