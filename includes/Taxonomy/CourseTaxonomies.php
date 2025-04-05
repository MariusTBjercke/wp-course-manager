<?php

namespace CourseManager\Taxonomy;

/**
 * Course taxonomies class.
 */
class CourseTaxonomies {
    /**
     * Register taxonomies.
     */
    public function register(): void {
        // Get taxonomies from settings
        $taxonomies = get_option('course_manager_taxonomies', []);

        foreach ($taxonomies as $slug => $name) {
            register_taxonomy(
                $slug,
                'course',
                [
                    'labels' => [
                        'name' => $name,
                        'singular_name' => $name,
                        'search_items' => 'Søk i ' . strtolower($name),
                        'all_items' => 'Alle ' . strtolower($name),
                        'parent_item' => 'Overordnet ' . strtolower($name),
                        'parent_item_colon' => 'Overordnet ' . strtolower($name) . ':',
                        'parent_field_description' => 'Velg overordnet ' . strtolower($name) . '.',
                        'name_field_description' => 'Skriv inn navnet på ' . strtolower($name) . '.',
                        'desc_field_description' => 'Ikke i bruk.',
                        'edit_item' => 'Rediger ' . strtolower($name),
                        'update_item' => 'Oppdater ' . strtolower($name),
                        'add_new_item' => 'Legg til ny ' . strtolower($name),
                        'new_item_name' => 'Nytt ' . strtolower($name) . 'navn',
                        'menu_name' => $name,
                    ],
                    'hierarchical' => true,
                    'show_ui' => true,
                    'show_admin_column' => true,
                    'query_var' => true,
                    'rewrite' => ['slug' => sanitize_title($name)],
                ]
            );
        }
    }
}