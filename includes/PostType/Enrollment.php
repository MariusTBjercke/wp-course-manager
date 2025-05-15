<?php

namespace CourseManager\PostType;

use WP_Post;
use CourseManager\Helpers\DateFormatter;

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

        // Register meta box for enrollment details
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
    }

    /**
     * Add meta boxes for the enrollment post type.
     */
    public function addMetaBoxes(): void {
        add_meta_box(
            'enrollment_details',
            'Påmeldingsdetaljer',
            [$this, 'renderDetailsMetaBox'],
            'course_enrollment',
            'normal',
            'high'
        );
    }

    /**
     * Render the details meta box for enrollments.
     *
     * @param WP_Post $post The current post object.
     */
    public function renderDetailsMetaBox(WP_Post $post): void {
        $courseId = get_post_meta($post->ID, 'cm_course_id', true);
        $courseDateIndex = get_post_meta($post->ID, 'cm_course_date_index', true);
        $buyerName = get_post_meta($post->ID, 'cm_buyer_name', true);
        $buyerEmail = get_post_meta($post->ID, 'cm_buyer_email', true);
        $buyerPhone = get_post_meta($post->ID, 'cm_buyer_phone', true);
        $buyerStreetAddress = get_post_meta($post->ID, 'cm_buyer_street_address', true);
        $buyerPostalCode = get_post_meta($post->ID, 'cm_buyer_postal_code', true);
        $buyerCity = get_post_meta($post->ID, 'cm_buyer_city', true);
        $buyerComments = get_post_meta($post->ID, 'cm_buyer_comments', true);
        $buyerCompany = get_post_meta($post->ID, 'cm_buyer_company', true);
        $participants = get_post_meta($post->ID, 'cm_participants', true);
        $totalPrice = get_post_meta($post->ID, 'cm_total_price', true);

        // Fetch course date details
        $courseDateDisplayString = 'Ukjent kursdato';
        if ($courseId) {
            $courseDates = get_post_meta($courseId, '_course_dates', true);
            if (is_array($courseDates) && isset($courseDates[$courseDateIndex])) {
                $selectedCourseDate = $courseDates[$courseDateIndex];
                $courseDateDisplayString = DateFormatter::formatCourseDateDisplay($selectedCourseDate);
            }
        }

        ?>
        <div class="cm-enrollment-details">
            <h3>Bestillerinformasjon</h3>
            <table class="widefat">
                <tbody>
                <tr>
                    <th>Navn</th>
                    <td><?php echo esc_html($buyerName); ?></td>
                </tr>
                <tr>
                    <th>E-post</th>
                    <td><?php echo esc_html($buyerEmail); ?></td>
                </tr>
                <tr>
                    <th>Telefonnummer</th>
                    <td><?php echo esc_html($buyerPhone); ?></td>
                </tr>
                <tr>
                    <th>Firma</th>
                    <td><?php echo esc_html($buyerCompany); ?></td>
                </tr>
                <tr>
                    <th>Gateadresse</th>
                    <td><?php echo esc_html($buyerStreetAddress); ?></td>
                </tr>
                <tr>
                    <th>Postnummer</th>
                    <td><?php echo esc_html($buyerPostalCode); ?></td>
                </tr>
                <tr>
                    <th>Poststed</th>
                    <td><?php echo esc_html($buyerCity); ?></td>
                </tr>
                <tr>
                    <th>Kommentarer/spørsmål</th>
                    <td><?php echo esc_html($buyerComments); ?></td>
                </tr>
                </tbody>
            </table>

            <h3>Kursinformasjon</h3>
            <table class="widefat">
                <tbody>
                <tr>
                    <th>Kurs</th>
                    <td>
                        <?php if ($courseId && get_post($courseId)): ?>
                            <a href="<?php echo get_edit_post_link($courseId); ?>"><?php echo esc_html(get_the_title($courseId)); ?></a>
                        <?php else: ?>
                            <?php echo __('Kurs ikke funnet', 'course-manager'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Kursdato</th>
                    <td><?php echo esc_html($courseDateDisplayString); ?></td>
                </tr>
                <tr>
                    <th>Total pris</th>
                    <td><?php echo esc_html($totalPrice); ?> NOK</td>
                </tr>
                </tbody>
            </table>

            <h3>Deltakere</h3>
            <table class="widefat">
                <thead>
                <tr>
                    <th>Navn</th>
                    <th>E-post</th>
                    <th>Telefonnummer</th>
                    <th>Fødselsdato</th>
                </tr>
                </thead>
                <tbody>
                <?php if (is_array($participants) && !empty($participants)): ?>
                    <?php foreach ($participants as $participant): ?>
                        <tr>
                            <td><?php echo esc_html($participant['name'] ?? ''); ?></td>
                            <td><?php echo esc_html($participant['email'] ?? ''); ?></td>
                            <td><?php echo esc_html($participant['phone'] ?? ''); ?></td>
                            <td><?php echo esc_html($participant['birthdate'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Ingen deltakere registrert.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
