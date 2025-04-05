<?php

namespace CourseManager\PostType;

/**
 * Course class.
 */
class Course {
    /**
     * Register the post type.
     */
    public function register(): void {
        register_post_type('course', [
            'labels' => [
                'name' => 'Kurs',
                'singular_name' => 'Kurs',
                'add_new' => 'Legg til nytt',
                'add_new_item' => 'Legg til nytt kurs',
                'edit_item' => 'Rediger kurs',
                'new_item' => 'Nytt kurs',
                'view_item' => 'Vis kurs',
                'view_items' => 'Vis kurs',
                'search_items' => 'Søk i kurs',
                'not_found' => 'Ingen kurs funnet',
                'not_found_in_trash' => 'Ingen kurs funnet i papirkurven',
                'all_items' => 'Alle kurs',
                'archives' => 'Kursarkiv',
                'attributes' => 'Kursegenskaper',
                'insert_into_item' => 'Sett inn i kurs',
                'uploaded_to_this_item' => 'Lastet opp til dette kurset',
                'filter_items_list' => 'Filtrer kursliste',
                'items_list_navigation' => 'Kursliste-navigasjon',
                'items_list' => 'Kursliste',
                'item_published' => 'Kurs publisert.',
                'item_published_privately' => 'Kurs publisert privat.',
                'item_reverted_to_draft' => 'Kurs tilbakestilt til utkast.',
                'item_scheduled' => 'Kurs planlagt.',
                'item_updated' => 'Kurs oppdatert.',
                'item_link' => 'Kurslenke',
                'item_link_description' => 'En lenke til et kurs.',
                'menu_name' => 'Kurs'
            ],
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'kurs-arkiv'],
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-welcome-learn-more',
            'taxonomies' => [],
        ]);

        // Dynamically add taxonomies to the course post type
        add_filter('register_post_type_args', [$this, 'addDynamicTaxonomies'], 10, 2);
    }

    /**
     * Add dynamic taxonomies to the course post type.
     *
     * @param array $args The arguments for the post type.
     * @param string $postType The post type.
     * @return array
     */
    public function addDynamicTaxonomies(array $args, string $postType): array {
        if ($postType !== 'course') {
            return $args;
        }

        $taxonomies = get_option('course_manager_taxonomies', []);
        $args['taxonomies'] = array_keys($taxonomies);
        return $args;
    }
}