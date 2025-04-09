<?php

namespace CourseManager\PostType;

use WP_Post;

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

        // Register meta boxes for custom email message, price, and more info page
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
        $custom_message = get_post_meta($post->ID, '_course_custom_email_message', true);
        wp_nonce_field('course_email_message_nonce', 'course_email_message_nonce');
        ?>
        <p>
            <label for="course_custom_email_message">Egendefinert melding (valgfritt):</label><br>
            <textarea name="course_custom_email_message" id="course_custom_email_message" rows="5" cols="50"><?php echo esc_textarea($custom_message); ?></textarea>
        </p>
        <p class="description">Hvis tom, brukes standardmeldingen fra innstillingene. Bruk følgende tagger for å inkludere variabler:<br>
            - [buyer_name]: Navnet på bestilleren<br>
            - [course_title]: Tittelen på kurset<br>
            - [participant_count]: Antall deltakere<br>
            - [total_price]: Total pris i NOK<br>
            - [participants]: Liste over deltakernavn (en per linje, med "- " foran)<br>
            Eksempel: "Hei [buyer_name], takk for at du meldte deg på [course_title]!"
        </p>
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
        <p class="description">Angi prisen per deltaker for dette kurset.</p>
        <?php
    }

    /**
     * Render the more info page meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function renderMoreInfoPageMetaBox(WP_Post $post): void {
        $selected_page = get_post_meta($post->ID, '_course_more_info_page', true);
        wp_nonce_field('course_more_info_page_nonce', 'course_more_info_page_nonce');
        ?>
        <p>
            <label for="course_more_info_page">Velg en side for mer informasjon (valgfritt):</label><br>
            <?php
            wp_dropdown_pages([
                'name' => 'course_more_info_page',
                'id' => 'course_more_info_page',
                'selected' => $selected_page,
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
        if (!isset($_POST['course_email_message_nonce']) || !wp_verify_nonce($_POST['course_email_message_nonce'], 'course_email_message_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['course_custom_email_message'])) {
            update_post_meta($post_id, '_course_custom_email_message', sanitize_textarea_field($_POST['course_custom_email_message']));
        }

        // Save price
        if (!isset($_POST['course_price_nonce']) || !wp_verify_nonce($_POST['course_price_nonce'], 'course_price_nonce')) {
            return;
        }

        if (isset($_POST['course_price'])) {
            update_post_meta($post_id, '_course_price', absint($_POST['course_price']));
        }

        // Save more info page
        if (!isset($_POST['course_more_info_page_nonce']) || !wp_verify_nonce($_POST['course_more_info_page_nonce'], 'course_more_info_page_nonce')) {
            return;
        }

        if (isset($_POST['course_more_info_page'])) {
            $page_id = absint($_POST['course_more_info_page']);
            if ($page_id > 0) {
                update_post_meta($post_id, '_course_more_info_page', $page_id);
            } else {
                delete_post_meta($post_id, '_course_more_info_page');
            }
        }
    }
}