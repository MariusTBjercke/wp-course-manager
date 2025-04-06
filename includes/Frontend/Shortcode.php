<?php

namespace CourseManager\Frontend;

use DateTime;

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
            <?php
            if ($attributes['show_filters'] === 'yes'): ?>
                <div class="cm-filters">
                    <form method="get" action="<?php
                    echo esc_url(get_permalink()); ?>">
                        <div class="cm-filter-group">
                            <label for="course_search">Søk:</label>
                            <input type="text" id="course_search" name="course_search" placeholder="Søk etter kurs"
                                   value="<?php
                                   echo esc_attr($searchTerm); ?>"/>
                        </div>
                        <?php
                        foreach ($taxonomies as $slug => $name): ?>
                            <div class="cm-filter-group">
                                <label for="<?php
                                echo esc_attr($slug); ?>"><?php
                                    echo esc_html($name); ?>:</label>
                                <select name="<?php
                                echo esc_attr($slug); ?>" id="<?php
                                echo esc_attr($slug); ?>">
                                    <option value="">Alle <?php
                                        echo esc_html(strtolower($name)); ?></option>
                                    <?php
                                    foreach ($taxonomyTerms[$slug] as $term): ?>
                                        <option value="<?php
                                        echo esc_attr($term->slug); ?>" <?php
                                        selected($selectedTaxonomies[$slug], $term->slug); ?>>
                                            <?php
                                            echo esc_html($term->name); ?>
                                        </option>
                                    <?php
                                    endforeach; ?>
                                </select>
                            </div>
                        <?php
                        endforeach; ?>
                        <button type="submit" class="cm-filter-button">Filtrer</button>
                    </form>
                </div>
            <?php
            endif; ?>

            <?php
            if (empty($courses)): ?>
                <div class="cm-no-courses">
                    <p>Ingen kurs funnet. Vennligst prøv andre filtre.</p>
                </div>
            <?php
            else: ?>
                <div class="cm-course-list">
                    <?php
                    foreach ($courses as $course): ?>
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
                            <?php
                            if (has_post_thumbnail($course->ID)): ?>
                                <div class="cm-course-image">
                                    <?php
                                    echo get_the_post_thumbnail($course->ID, 'medium'); ?>
                                </div>
                            <?php
                            endif; ?>
                            <div class="cm-course-content">
                                <h3 class="cm-course-title"><?php
                                    echo esc_html($course->post_title); ?></h3>
                                <div class="cm-course-meta">
                                    <?php
                                    foreach ($courseTaxonomyData as $typeName => $terms): ?>
                                        <span class="cm-course-taxonomy">
                                            <strong><?php
                                                echo esc_html($typeName); ?>:</strong> <?php
                                            echo esc_html(join(', ', $terms)); ?>
                                        </span>
                                    <?php
                                    endforeach; ?>
                                </div>
                                <div class="cm-course-excerpt">
                                    <?php
                                    echo wp_trim_words($course->post_content, 30); ?>
                                </div>
                                <a href="<?php
                                echo get_permalink($course->ID); ?>" class="cm-course-link">Vis kurs</a>
                            </div>
                        </div>
                    <?php
                    endforeach; ?>
                </div>
            <?php
            endif; ?>
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
        if (!is_singular('course')) {
            return '';
        }

        $courseId = get_the_ID();
        $submissionMessage = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cm_enrollment_nonce'])) {
            if (!wp_verify_nonce($_POST['cm_enrollment_nonce'], 'cm_enroll_action')) {
                $submissionMessage = '<p class="error">Sikkerhetskontroll feilet. Vennligst prøv igjen.</p>';
            } else {
                $buyer_name = sanitize_text_field($_POST['cm_buyer_name'] ?? '');
                $buyer_email = sanitize_email($_POST['cm_buyer_email'] ?? '');
                $buyer_phone = sanitize_text_field($_POST['cm_buyer_phone'] ?? '');
                $buyer_street_address = sanitize_text_field($_POST['cm_buyer_street_address'] ?? '');
                $buyer_postal_code = sanitize_text_field($_POST['cm_buyer_postal_code'] ?? '');
                $buyer_city = sanitize_text_field($_POST['cm_buyer_city'] ?? '');
                $buyer_comments = sanitize_textarea_field($_POST['cm_buyer_comments'] ?? '');
                $buyer_company = sanitize_text_field($_POST['cm_buyer_company'] ?? '');
                $participant_name = sanitize_text_field($_POST['cm_participant_name'] ?? '');
                $participant_email = sanitize_email($_POST['cm_participant_email'] ?? '');
                $participant_phone = sanitize_text_field($_POST['cm_participant_phone'] ?? '');
                $participant_birthdate = sanitize_text_field($_POST['cm_participant_birthdate'] ?? '');

                // Convert birthdate to Norwegian format
                if (!empty($participant_birthdate)) {
                    $date = DateTime::createFromFormat('Y-m-d', $participant_birthdate);
                    if ($date) {
                        $participant_birthdate = $date->format('d.m.Y');
                    } else {
                        $participant_birthdate = '';
                    }
                }

                // Validate required fields
                if (empty($buyer_name) || empty($buyer_email) || !is_email($buyer_email) ||
                    empty($participant_name) || empty($participant_email) || !is_email($participant_email)) {
                    $submissionMessage = '<p class="error">Vennligst fyll inn alle påkrevde felt med gyldig data.</p>';
                }
                // Validate postal code (4 digits)
                elseif (!empty($buyer_postal_code) && !preg_match('/^\d{4}$/', $buyer_postal_code)) {
                    $submissionMessage = '<p class="error">Postnummer må være 4 sifre (f.eks. 1234).</p>';
                } else {
                    $enrollmentId = wp_insert_post([
                        'post_type' => 'course_enrollment',
                        'post_title' => sprintf(
                            'Påmelding til %s av %s (bestilt av %s)',
                            get_the_title($courseId),
                            $participant_name,
                            $buyer_name
                        ),
                        'post_status' => 'publish',
                    ]);

                    if ($enrollmentId) {
                        update_post_meta($enrollmentId, 'cm_course_id', $courseId);
                        update_post_meta($enrollmentId, 'cm_buyer_name', $buyer_name);
                        update_post_meta($enrollmentId, 'cm_buyer_email', $buyer_email);
                        update_post_meta($enrollmentId, 'cm_buyer_phone', $buyer_phone);
                        update_post_meta($enrollmentId, 'cm_buyer_street_address', $buyer_street_address);
                        update_post_meta($enrollmentId, 'cm_buyer_postal_code', $buyer_postal_code);
                        update_post_meta($enrollmentId, 'cm_buyer_city', $buyer_city);
                        update_post_meta($enrollmentId, 'cm_buyer_comments', $buyer_comments);
                        update_post_meta($enrollmentId, 'cm_buyer_company', $buyer_company);
                        update_post_meta($enrollmentId, 'cm_participant_name', $participant_name);
                        update_post_meta($enrollmentId, 'cm_participant_email', $participant_email);
                        update_post_meta($enrollmentId, 'cm_participant_phone', $participant_phone);
                        update_post_meta($enrollmentId, 'cm_participant_birthdate', $participant_birthdate);

                        $submissionMessage = '<p class="success">Du har meldt deg på kurset! En bekreftelse er sendt til ' . esc_html(
                                $buyer_email
                            ) . '.</p>';

                        $subject = 'Bekreftelse på kurspåmelding';
                        $custom_message = get_post_meta($courseId, '_course_custom_email_message', true);
                        $default_message = get_option(
                            'course_manager_default_email_message',
                            "Hei %s,\n\nTakk for at du meldte deg på %s! Vi gleder oss til å se deg.\n\nBeste hilsener,\nKursadministrator-teamet"
                        );
                        $message = !empty($custom_message) ? $custom_message : $default_message;
                        $message = sprintf($message, $buyer_name, get_the_title($courseId));
                        wp_mail($buyer_email, $subject, $message);
                    } else {
                        $submissionMessage = '<p class="error">Det oppstod en feil ved registrering av deg i kurset. Vennligst prøv igjen.</p>';
                    }
                }
            }
        }

        ob_start();
        ?>
        <div class="cm-enrollment-form">
            <h2>Påmelding til <?php
                echo esc_html(get_the_title($courseId)); ?></h2>
            <p>Fyll inn detaljene nedenfor for å melde deg på kurset.</p>
            <?php
            if ($submissionMessage): ?>
                <?php
                echo $submissionMessage; ?>
            <?php
            endif; ?>

            <form method="post" action="">
                <?php
                wp_nonce_field('cm_enroll_action', 'cm_enrollment_nonce'); ?>

                <fieldset>
                    <legend>Bestillerinformasjon</legend>
                    <div class="cm-form-field">
                        <label for="cm_buyer_name">Navn <span class="required">*</span></label>
                        <input type="text" name="cm_buyer_name" id="cm_buyer_name" required placeholder="Ola Nordmann">
                    </div>

                    <div class="cm-form-field">
                        <label for="cm_buyer_company">Firma</label>
                        <input type="text" name="cm_buyer_company" id="cm_buyer_company" placeholder="Eksempel AS">
                    </div>

                    <div class="cm-form-field">
                        <label for="cm_buyer_email">E-post <span class="required">*</span></label>
                        <input type="email" name="cm_buyer_email" id="cm_buyer_email" required placeholder="navn@eksempel.no">
                    </div>

                    <div class="cm-form-field">
                        <label for="cm_buyer_phone">Telefonnummer</label>
                        <input type="tel" name="cm_buyer_phone" id="cm_buyer_phone" placeholder="12345678">
                    </div>

                    <div class="cm-form-field">
                        <label for="cm_buyer_street_address">Gateadresse</label>
                        <input type="text" name="cm_buyer_street_address" id="cm_buyer_street_address" placeholder="Gateveien 1">
                    </div>

                    <div class="cm-address-group">
                        <div class="cm-address-field">
                            <label for="cm_buyer_postal_code">Postnummer</label>
                            <input type="text" name="cm_buyer_postal_code" id="cm_buyer_postal_code" placeholder="1234">
                        </div>
                        <div class="cm-address-field">
                            <label for="cm_buyer_city">Poststed</label>
                            <input type="text" name="cm_buyer_city" id="cm_buyer_city" placeholder="Oslo">
                        </div>
                    </div>

                    <div class="cm-form-field">
                        <label for="cm_buyer_comments">Kommentarer/spørsmål</label>
                        <textarea name="cm_buyer_comments" id="cm_buyer_comments" placeholder="Eventuelle kommentarer eller spørsmål"></textarea>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Deltagerinformasjon</legend>
                    <div class="cm-form-field">
                        <label for="cm_participant_name">Navn <span class="required">*</span></label>
                        <input type="text" name="cm_participant_name" id="cm_participant_name" required placeholder="Kari Nordmann">
                    </div>

                    <div class="cm-form-field">
                        <label for="cm_participant_email">E-post <span class="required">*</span></label>
                        <input type="email" name="cm_participant_email" id="cm_participant_email" required placeholder="kari@eksempel.no">
                    </div>

                    <div class="cm-form-field">
                        <label for="cm_participant_phone">Telefonnummer</label>
                        <input type="tel" name="cm_participant_phone" id="cm_participant_phone" placeholder="87654321">
                    </div>

                    <div class="cm-form-field">
                        <label for="cm_participant_birthdate">Fødselsdato</label>
                        <input type="date" name="cm_participant_birthdate" id="cm_participant_birthdate">
                    </div>
                </fieldset>

                <button type="submit">Meld deg på nå</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}