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
        register_taxonomy(
            'course_location',
            'course',
            [
                'labels' => [
                    'name' => 'Steder',
                    'singular_name' => 'Sted',
                    'search_items' => 'Søk i steder',
                    'all_items' => 'Alle steder',
                    'parent_item' => 'Overordnet sted',
                    'parent_item_colon' => 'Overordnet sted:',
                    'parent_field_description' => 'Velg overordnet sted.',
                    'name_field_description' => 'Skriv inn navnet på stedet.',
                    'desc_field_description' => 'Ikke i bruk.',
                    'edit_item' => 'Rediger sted',
                    'update_item' => 'Oppdater sted',
                    'add_new_item' => 'Legg til nytt sted',
                    'new_item_name' => 'Nytt stedsnavn',
                    'menu_name' => 'Steder',
                ],
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'kurs-sted'],
            ]
        );

        register_taxonomy(
            'course_category',
            'course',
            [
                'labels' => [
                    'name' => 'Kurskategorier',
                    'singular_name' => 'Kurskategori',
                    'search_items' => 'Søk i kategorier',
                    'all_items' => 'Alle kategorier',
                    'parent_item' => 'Foreldrekategori',
                    'parent_item_colon' => 'Foreldrekategori:',
                    'parent_field_description' => 'Velg foreldrekategori.',
                    'name_field_description' => 'Skriv inn navnet på kategorien.',
                    'desc_field_description' => 'Ikke i bruk.',
                    'edit_item' => 'Rediger kategori',
                    'update_item' => 'Oppdater kategori',
                    'add_new_item' => 'Legg til ny kategori',
                    'new_item_name' => 'Nytt kategorinavn',
                    'menu_name' => 'Kategorier',
                ],
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'course-category'],
            ]
        );
    }
}