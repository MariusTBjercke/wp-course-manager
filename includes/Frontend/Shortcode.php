<?php

namespace CourseManager\Frontend;

/**
 * Shortcode class for managing course-related shortcodes.
 */
class Shortcode {
    /**
     * Register shortcodes.
     */
    public function register(): void {
        add_shortcode('course_manager', [$this, 'renderCourseList']);
        add_shortcode('course_enrollment_form', [$this, 'renderEnrollmentForm']);

        add_filter('wp_mail_from', function ($email) {
            return get_option('admin_email') ?: 'no-reply@' . wp_parse_url(get_site_url(), PHP_URL_HOST);
        });
    }

    /**
     * Render the course list shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the course list.
     */
    public function renderCourseList(array $atts = []): string {
        $attributes = shortcode_atts([
            'show_filters' => 'yes',
        ], $atts);

        $searchTerm = isset($_GET['course_search']) ? sanitize_text_field($_GET['course_search']) : '';
        $selectedTaxonomies = [];
        $taxonomies = get_option('course_manager_taxonomies', []);
        foreach ($taxonomies as $slug => $name) {
            $selectedTaxonomies[$slug] = isset($_GET[$slug]) ? sanitize_text_field($_GET[$slug]) : '';
        }

        $args = [
            'post_type' => 'course',
            'post_status' => 'publish',
            's' => $searchTerm,
            'posts_per_page' => get_option('course_manager_items_per_page', 10),
            'tax_query' => []
        ];

        foreach ($selectedTaxonomies as $taxonomy => $term) {
            if (!empty($term)) {
                $args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $term
                ];
            }
        }

        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        $courses = get_posts($args);
        $taxonomyTerms = [];
        foreach ($taxonomies as $slug => $name) {
            $taxonomyTerms[$slug] = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
        }

        ob_start();
        ?>
        <div class="cm-course-manager">
            <h1>Kurs</h1>
            <?php if ($attributes['show_filters'] === 'yes'): ?>
                <div class="cm-filters">
                    <form method="get" action="<?php echo esc_url(get_permalink()); ?>">
                        <div class="cm-filter-group">
                            <label for="course_search">Søk:</label>
                            <input type="text" id="course_search" name="course_search" placeholder="Søk etter kurs"
                                   value="<?php echo esc_attr($searchTerm); ?>"/>
                        </div>
                        <?php foreach ($taxonomies as $slug => $name): ?>
                            <div class="cm-filter-group">
                                <label for="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?>:</label>
                                <select name="<?php echo esc_attr($slug); ?>" id="<?php echo esc_attr($slug); ?>">
                                    <option value="">Alle <?php echo esc_html(strtolower($name)); ?></option>
                                    <?php foreach ($taxonomyTerms[$slug] as $term): ?>
                                        <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selectedTaxonomies[$slug], $term->slug); ?>>
                                            <?php echo esc_html($term->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="cm-filter-button">Filtrer</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (empty($courses)): ?>
                <div class="cm-no-courses">
                    <p>Ingen kurs funnet. Vennligst prøv andre filtre.</p>
                </div>
            <?php else: ?>
                <div class="cm-course-list">
                    <?php foreach ($courses as $course): ?>
                        <?php
                        $courseTaxonomyData = [];
                        foreach ($taxonomies as $slug => $name) {
                            $terms = get_the_terms($course->ID, $slug);
                            if ($terms && !is_wp_error($terms)) {
                                $courseTaxonomyData[$name] = wp_list_pluck($terms, 'name');
                            }
                        }
                        ?>
                        <div class="cm-course-item">
                            <?php if (has_post_thumbnail($course->ID)): ?>
                                <div class="cm-course-image">
                                    <?php echo get_the_post_thumbnail($course->ID, 'medium'); ?>
                                </div>
                            <?php endif; ?>
                            <div class="cm-course-content">
                                <h3 class="cm-course-title"><?php echo esc_html($course->post_title); ?></h3>
                                <div class="cm-course-meta">
                                    <?php foreach ($courseTaxonomyData as $typeName => $terms): ?>
                                        <span class="cm-course-taxonomy">
                                            <strong><?php echo esc_html($typeName); ?>:</strong> <?php echo esc_html(join(', ', $terms)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="cm-course-excerpt">
                                    <?php echo wp_trim_words($course->post_content, 30); ?>
                                </div>
                                <a href="<?php echo get_permalink($course->ID); ?>" class="cm-course-link">Vis kurs</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the enrollment form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the enrollment form.
     */
    public function renderEnrollmentForm(array $atts = []): string {
        // Ensure we're on a single course page
        if (!is_singular('course')) {
            return '';
        }

        $courseId = get_the_ID();

        // Handle form submission
        $submissionMessage = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cm_name'], $_POST['cm_email'], $_POST['cm_enrollment_nonce'])) {
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['cm_enrollment_nonce'], 'cm_enroll_action')) {
                $submissionMessage = '<p class="error">Sikkerhetskontroll feilet. Vennligst prøv igjen.</p>';
            } else {
                $name = sanitize_text_field($_POST['cm_name']);
                $email = sanitize_email($_POST['cm_email']);

                // Validate input
                if (empty($name) || empty($email) || !is_email($email)) {
                    $submissionMessage = '<p class="error">Vennligst fyll inn alle påkrevde felt med gyldig data.</p>';
                } else {
                    // Create a new enrollment post
                    $enrollmentId = wp_insert_post([
                        'post_type' => 'course_enrollment',
                        'post_title' => sprintf('Påmelding til %s av %s', get_the_title($courseId), $name),
                        'post_status' => 'publish',
                    ]);

                    if ($enrollmentId) {
                        // Save enrollment details as post meta
                        update_post_meta($enrollmentId, 'cm_course_id', $courseId);
                        update_post_meta($enrollmentId, 'cm_name', $name);
                        update_post_meta($enrollmentId, 'cm_email', $email);

                        $submissionMessage = '<p class="success">Du har meldt deg på kurset!</p>';

                        // Optionally send email confirmation to user
                        $subject = 'Bekreftelse på kurspåmelding';
                        $custom_message = get_post_meta($courseId, '_course_custom_email_message', true);
                        $default_message = get_option('course_manager_default_email_message', "Hei %s,\n\nTakk for at du meldte deg på %s! Vi gleder oss til å se deg.\n\nBeste hilsener,\nKursadministrator-teamet");
                        $message = !empty($custom_message) ? $custom_message : $default_message;
                        $message = sprintf($message, $name, get_the_title($courseId));
                        wp_mail($email, $subject, $message);
                    } else {
                        $submissionMessage = '<p class="error">Det oppstod en feil ved registrering av deg i kurset. Vennligst prøv igjen.</p>';
                    }
                }
            }
        }

        ob_start();
        ?>
        <div class="cm-enrollment-form">
            <h2>Påmelding</h2>
            <?php if ($submissionMessage): ?>
                <?php echo $submissionMessage; ?>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('cm_enroll_action', 'cm_enrollment_nonce'); ?>
                <label for="cm_name">Navn</label>
                <input type="text" name="cm_name" id="cm_name" required>

                <label for="cm_email">E-post</label>
                <input type="email" name="cm_email" id="cm_email" required>

                <button type="submit">Meld deg på nå</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}