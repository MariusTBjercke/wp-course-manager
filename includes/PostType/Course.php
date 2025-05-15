<?php

namespace CourseManager\PostType;

use DateTime;
use WP_Post;

/**
 * Course post type class.
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

        // Register meta boxes for custom email message, price, course dates, and more info page
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMetaBoxData']);
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

    /**
     * Add meta boxes for the course post type.
     */
    public function addMetaBoxes(): void {
        add_meta_box(
            'course_email_message',
            'Egendefinert e-postbekreftelse',
            [$this, 'renderEmailMessageMetaBox'],
            'course',
            'normal',
            'default'
        );

        // Meta box for multiple course dates (dates and times)
        add_meta_box(
            'course_dates',
            'Kursdatoer',
            [$this, 'renderCourseDateMetaBox'],
            'course',
            'normal',
            'default'
        );

        add_meta_box(
            'course_price',
            'Pris per deltaker',
            [$this, 'renderPriceMetaBox'],
            'course',
            'normal',
            'default'
        );

        add_meta_box(
            'course_more_info_page',
            'Mer info-side',
            [$this, 'renderMoreInfoPageMetaBox'],
            'course',
            'normal',
            'default'
        );
    }

    /**
     * Render the email message meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function renderEmailMessageMetaBox(WP_Post $post): void {
        $customMessage = get_post_meta($post->ID, '_course_custom_email_message', true);
        wp_nonce_field('course_email_message_nonce', 'course_email_message_nonce');
        ?>
        <p>
            <label for="course_custom_email_message">Egendefinert melding (valgfritt):</label><br>
            <textarea name="course_custom_email_message" id="course_custom_email_message" rows="5" cols="50"><?php echo esc_textarea($customMessage); ?></textarea>
        </p>
        <p class="description">Hvis tom, brukes standardmeldingen fra innstillingene. Bruk følgende tagger for å inkludere variabler:<br>
            - [buyer_name]: Navnet på bestilleren<br>
            - [course_title]: Tittelen på kurset<br>
            - [participant_count]: Antall deltakere<br>
            - [total_price]: Total pris i NOK<br>
            - [participants]: Liste over deltakernavn (en per linje, med "- " foran)<br>
            - [course_date_date]: Dato(er) for den valgte kursdatoen<br>
            - [course_date_time]: Tid(er) for den valgte kursdatoen<br>
            Eksempel: "Hei [buyer_name], takk for at du meldte deg på [course_title] den [course_date_date]!"
        </p>
        <?php
    }

    /**
     * Render the course dates meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function renderCourseDateMetaBox(WP_Post $post): void {
        $courseDates = get_post_meta($post->ID, '_course_dates', true);
        if (empty($courseDates) || !is_array($courseDates)) {
            $courseDates = [];
        }
        wp_nonce_field('course_dates_nonce', 'course_dates_nonce');

        $taxonomies = get_option('course_manager_taxonomies', []);
        ?>
        <div id="course-dates-wrapper">
            <?php
            if (!empty($courseDates)) {
                foreach ($courseDates as $index => $courseDate):
                    $selectedTaxonomyTerms = $courseDate['taxonomies'] ?? [];
                    ?>
                    <div class="course-course-date" data-index="<?php echo $index; ?>">
                        <h4>Kursdato #<span class="course-date-number"><?php echo $index + 1; ?></span></h4>
                        <p>
                            <label for="course_dates[<?php echo $index; ?>][start_date]">Startdato:</label><br>
                            <input type="date" name="course_dates[<?php echo $index; ?>][start_date]" value="<?php echo esc_attr($courseDate['start_date'] ?? ''); ?>">
                        </p>
                        <p>
                            <label for="course_dates[<?php echo $index; ?>][end_date]">Sluttdato (valgfritt):</label><br>
                            <input type="date" name="course_dates[<?php echo $index; ?>][end_date]" value="<?php echo esc_attr($courseDate['end_date'] ?? ''); ?>">
                        </p>
                        <p>
                            <label for="course_dates[<?php echo $index; ?>][start_time]">Starttid (valgfritt):</label><br>
                            <input type="time" name="course_dates[<?php echo $index; ?>][start_time]" value="<?php echo esc_attr($courseDate['start_time'] ?? ''); ?>">
                        </p>
                        <p>
                            <label for="course_dates[<?php echo $index; ?>][end_time]">Slutttid (valgfritt):</label><br>
                            <input type="time" name="course_dates[<?php echo $index; ?>][end_time]" value="<?php echo esc_attr($courseDate['end_time'] ?? ''); ?>">
                        </p>
                        <p>
                            <label for="course_dates[<?php echo $index; ?>][max_participants_course_date]">Maks antall deltakere for denne kursdatoen (valgfritt):</label><br>
                            <input type="number" name="course_dates[<?php echo $index; ?>][max_participants_course_date]" value="<?php echo esc_attr($courseDate['max_participants_course_date'] ?? ''); ?>" min="0" step="1">
                            <span class="description">Sett en grense for antall deltakere for denne kursdatoen. La stå tomt for ubegrenset kapasitet.</span>
                        </p>

                        <div class="course-date-taxonomies">
                            <h4>Taksonomier for denne kursdatoen (valgfritt):</h4>
                            <?php foreach ($taxonomies as $slug => $name):
                                $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
                                if (!is_wp_error($terms) && !empty($terms)):
                                    $currentTerms = $selectedTaxonomyTerms[$slug] ?? [];
                                    ?>
                                    <div class="course-date-taxonomy-checklist">
                                        <p class="taxonomy-label"><strong><?php echo esc_html($name); ?>:</strong></p>
                                        <ul class="categorychecklist">
                                            <?php $this->renderTaxonomyCheckboxes($terms, $slug, $currentTerms, $index); ?>
                                        </ul>
                                        <p class="description">Velg spesifikke termer for denne kursdatoen. Hvis ingen er valgt, brukes taksonomier fra selve kurset.</p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="button remove-course-date">Fjern kursdato</button>
                        <hr>
                    </div>
                <?php endforeach;
            }
            ?>
        </div>
        <button type="button" id="add-course-date" class="button">Legg til kursdato</button>

        <script type="text/template" id="course-course-date-template">
            <div class="course-course-date" data-index="__INDEX__">
                <h4>Kursdato #<span class="course-date-number">__NUMBER__</span></h4>
                <p>
                    <label for="course_dates[__INDEX__][start_date]">Startdato:</label><br>
                    <input type="date" name="course_dates[__INDEX__][start_date]" value="">
                </p>
                <p>
                    <label for="course_dates[__INDEX__][end_date]">Sluttdato (valgfritt):</label><br>
                    <input type="date" name="course_dates[__INDEX__][end_date]" value="">
                </p>
                <p>
                    <label for="course_dates[__INDEX__][start_time]">Starttid (valgfritt):</label><br>
                    <input type="time" name="course_dates[__INDEX__][start_time]" value="">
                </p>
                <p>
                    <label for="course_dates[__INDEX__][end_time]">Slutttid (valgfritt):</label><br>
                    <input type="time" name="course_dates[__INDEX__][end_time]" value="">
                </p>
                <p>
                    <label for="course_dates[__INDEX__][max_participants_course_date]">Maks antall deltakere for denne kursdatoen (valgfritt):</label><br>
                    <input type="number" name="course_dates[__INDEX__][max_participants_course_date]" value="" min="0" step="1">
                    <span class="description">Sett en grense for antall deltakere for denne kursdatoen. La stå tomt for ubegrenset kapasitet.</span>
                </p>

                <div class="course-date-taxonomies">
                    <h4>Taksonomier for denne kursdatoen (valgfritt):</h4>
                    <?php foreach ($taxonomies as $slug => $name):
                        $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
                        if (!is_wp_error($terms) && !empty($terms)):
                            ?>
                            <div class="course-date-taxonomy-checklist">
                                <p class="taxonomy-label"><strong><?php echo esc_html($name); ?>:</strong></p>
                                <ul class="categorychecklist">
                                    <?php $this->renderTaxonomyCheckboxes($terms, $slug, [], '__INDEX__'); // Pass empty array for new dates, and the index placeholder ?>
                                </ul>
                                <p class="description">Velg spesifikke termer for denne kursdatoen. Hvis ingen er valgt, brukes taksonomier fra selve kurset.</p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="button remove-course-date">Fjern kursdato</button>
                <hr>
            </div>
        </script>
        <?php
    }

    /**
     * Render the price meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function renderPriceMetaBox(WP_Post $post): void {
        $price = get_post_meta($post->ID, '_course_price', true);
        wp_nonce_field('course_price_nonce', 'course_price_nonce');
        ?>
        <p>
            <label for="course_price">Pris per deltaker (i NOK):</label><br>
            <input type="number" name="course_price" id="course_price" value="<?php echo esc_attr($price); ?>" step="1" min="0">
        </p>
        <p class="description">Angi prisen per deltaker for dette kurset. Dette gjelder for alle kursdatoer.</p>
        <?php
    }

    /**
     * Render the more info page meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function renderMoreInfoPageMetaBox(WP_Post $post): void {
        $selectedPage = get_post_meta($post->ID, '_course_more_info_page', true);
        wp_nonce_field('course_more_info_page_nonce', 'course_more_info_page_nonce');
        ?>
        <p>
            <label for="course_more_info_page">Velg en side for mer informasjon (valgfritt):</label><br>
            <?php
            wp_dropdown_pages([
                'name' => 'course_more_info_page',
                'id' => 'course_more_info_page',
                'selected' => $selectedPage,
                'show_option_none' => '— Ingen side valgt —',
                'option_none_value' => '',
            ]);
            ?>
        </p>
        <p class="description">Velg en side som inneholder mer informasjon om dette kurset. Hvis en side er valgt, vises en "Mer info"-knapp på kurslisten.</p>
        <?php
    }

    /**
     * Save the meta box data.
     *
     * @param integer $post_id The ID of the post being saved.
     */
    public function saveMetaBoxData(int $post_id): void {
        // Save email message
        if (isset($_POST['course_email_message_nonce']) && wp_verify_nonce($_POST['course_email_message_nonce'], 'course_email_message_nonce')) {
            if (isset($_POST['course_custom_email_message'])) {
                update_post_meta($post_id, '_course_custom_email_message', sanitize_textarea_field($_POST['course_custom_email_message']));
            }
        }

        // Save course dates
        if (isset($_POST['course_dates_nonce']) && wp_verify_nonce($_POST['course_dates_nonce'], 'course_dates_nonce')) {
            $courseDates = [];
            if (isset($_POST['course_dates']) && is_array($_POST['course_dates'])) {
                foreach ($_POST['course_dates'] as $courseDateData) {
                    // Sanitize and validate course date data
                    $startDate = sanitize_text_field($courseDateData['start_date'] ?? '');
                    $endDate = sanitize_text_field($courseDateData['end_date'] ?? '');
                    $startTime = sanitize_text_field($courseDateData['start_time'] ?? '');
                    $endTime = sanitize_text_field($courseDateData['end_time'] ?? '');
                    $maxParticipantsCourseDate = sanitize_text_field($courseDateData['max_participants_course_date'] ?? '');
                    $taxonomyTerms = $courseDateData['taxonomies'] ?? [];

                    // Sanitize taxonomy terms
                    $sanitizedTaxonomyTerms = [];
                    if (is_array($taxonomyTerms)) {
                        foreach ($taxonomyTerms as $taxSlug => $terms) {
                            // Ensure $terms is an array before array_map
                            if (is_array($terms)) {
                                $sanitizedTaxonomyTerms[sanitize_key($taxSlug)] = array_map('sanitize_text_field', $terms);
                            }
                        }
                    }

                    // Basic validation for start date
                    if (!empty($startDate) && DateTime::createFromFormat('Y-m-d', $startDate) !== false) {
                        $courseDates[] = [
                            'start_date' => $startDate,
                            'end_date' => (!empty($endDate) && DateTime::createFromFormat('Y-m-d', $endDate) !== false) ? $endDate : '',
                            'start_time' => (!empty($startTime) && preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $startTime)) ? $startTime : '',
                            'end_time' => (!empty($endTime) && preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $endTime)) ? $endTime : '',
                            'max_participants_course_date' => ($maxParticipantsCourseDate !== '' && $maxParticipantsCourseDate >= 0) ? absint($maxParticipantsCourseDate) : '',
                            'taxonomies' => $sanitizedTaxonomyTerms,
                        ];
                    }
                }
            }
            update_post_meta($post_id, '_course_dates', $courseDates);
        }

        // Save price
        if (isset($_POST['course_price_nonce']) && wp_verify_nonce($_POST['course_price_nonce'], 'course_price_nonce')) {
            if (isset($_POST['course_price'])) {
                update_post_meta($post_id, '_course_price', absint($_POST['course_price']));
            }
        }

        // Save more info page
        if (isset($_POST['course_more_info_page_nonce']) && wp_verify_nonce($_POST['course_more_info_page_nonce'], 'course_more_info_page_nonce')) {
            if (isset($_POST['course_more_info_page'])) {
                $pageId = absint($_POST['course_more_info_page']);
                if ($pageId > 0) {
                    update_post_meta($post_id, '_course_more_info_page', $pageId);
                } else {
                    delete_post_meta($post_id, '_course_more_info_page');
                }
            }
        }
    }

    /**
     * Organize terms hierarchically into a nested array.
     * Copied from Shortcode.php for use in admin.
     *
     * @param array $terms List of taxonomy terms.
     * @return array Organized terms with children nested under parents.
     */
    private function organizeTermsHierarchically(array $terms): array {
        $organized = [];
        $termMap = [];

        // First, map all terms by their ID for easy lookup
        foreach ($terms as $term) {
            $termMap[$term->term_id] = [
                'term' => $term,
                'children' => []
            ];
        }

        // Then, organize terms into a hierarchy
        foreach ($termMap as $termId => &$termData) {
            $term = $termData['term'];
            if ($term->parent == 0) {
                // This is a parent term
                $organized[$termId] = $termData;
            } else {
                // This is a child term, add it to its parent's children
                if (isset($termMap[$term->parent])) {
                    $termMap[$term->parent]['children'][$termId] = $termData;
                }
            }
        }

        return $organized;
    }

    /**
     * Render taxonomy terms as hierarchical checkboxes for a course date.
     * Adapted from Shortcode.php's renderTaxonomyTerms.
     *
     * @param array $terms List of taxonomy terms (can be hierarchical).
     * @param string $taxonomy_slug The taxonomy slug.
     * @param array $selected_terms The currently selected term slugs for this date.
     * @param int|string $index The index of the course date (or placeholder).
     */
    private function renderTaxonomyCheckboxes(
        array $terms,
        string $taxonomy_slug,
        array $selected_terms,
        int|string $index
    ): void {
        // Organize terms hierarchically first
        $hierarchicalTerms = $this->organizeTermsHierarchically($terms);

        // Render the hierarchical list
        $this->renderTermCheckboxesList($hierarchicalTerms, $taxonomy_slug, $selected_terms, $index);
    }

    /**
     * Recursive helper to render hierarchical term checkboxes.
     *
     * @param array $terms Organized terms with children.
     * @param string $taxonomy_slug The taxonomy slug.
     * @param array $selected_terms The currently selected term slugs.
     * @param int|string $index The index of the course date (or placeholder).
     */
    private function renderTermCheckboxesList(
        array $terms,
        string $taxonomy_slug,
        array $selected_terms,
        int|string $index
    ): void {
        foreach ($terms as $termData) {
            $term = $termData['term'];
            $children = $termData['children'];
            ?>
            <li id="<?php echo esc_attr($taxonomy_slug); ?>-<?php echo esc_attr($term->term_id); ?>">
                <label class="selectit">
                    <input value="<?php echo esc_attr($term->slug); ?>" type="checkbox" name="course_dates[<?php echo $index; ?>][taxonomies][<?php echo esc_attr($taxonomy_slug); ?>][]" id="term-<?php echo esc_attr($term->term_id); ?>" <?php checked(in_array($term->slug, $selected_terms), true); ?> />
                    <?php echo esc_html($term->name); ?>
                </label>
                <?php if (!empty($children)): ?>
                    <ul class="children">
                        <?php $this->renderTermCheckboxesList($children, $taxonomy_slug, $selected_terms, $index); ?>
                    </ul>
                <?php endif; ?>
            </li>
            <?php
        }
    }
}
