<?php

namespace CourseManager\Frontend;

use DateTime;
use WC_Order;
use WC_Order_Item_Product;
use WP_Query;

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
        add_shortcode('course_manager_slider', [$this, 'renderCourseSlider']);

        add_filter('wp_mail_from', function ($email) {
            return get_option('admin_email') ?: 'no-reply@' . wp_parse_url(get_site_url(), PHP_URL_HOST);
        });

        // Listen to WooCommerce order completed (for online payments such as credit card and Vipps)
        add_action('woocommerce_order_status_completed', [$this, 'handleWooCommerceOrderCompleted']);

        // Redirect after order completion to the course page with a success message
        add_filter('woocommerce_get_return_url', [$this, 'customizeReturnUrl'], 10, 2);
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
        $startDate = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $endDate = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
        $selectedTaxonomies = [];
        $taxonomies = get_option('course_manager_taxonomies', []);

        // Handle multiple selections for each taxonomy
        foreach ($taxonomies as $slug => $name) {
            $selectedTaxonomies[$slug] = isset($_GET[$slug]) ? (array)$_GET[$slug] : [];
            $selectedTaxonomies[$slug] = array_map('sanitize_text_field', $selectedTaxonomies[$slug]);
        }

        // Get current page from URL (default to 1 if not set)
        $paged = (get_query_var('paged')) ? get_query_var('paged') : (isset($_GET['paged']) ? absint(
            $_GET['paged']
        ) : 1);
        $posts_per_page = get_option('course_manager_items_per_page', 10);

        $args = [
            'post_type' => 'course',
            'post_status' => 'publish',
            's' => $searchTerm,
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'tax_query' => []
        ];

        // Add start date filter
        if ($startDate || $endDate) {
            $meta_query = [
                'key' => 'startdato',
                'type' => 'DATE',
            ];
            if ($startDate) {
                $meta_query['compare'] = '>=';
                $meta_query['value'] = $startDate;
            }
            if ($endDate) {
                $meta_query['compare'] = $startDate ? 'BETWEEN' : '<=';
                $meta_query['value'] = $startDate ? [$startDate, $endDate] : $endDate;
            }
            $args['meta_query'][] = $meta_query;
        }

        // Build tax_query for multiple selections
        foreach ($selectedTaxonomies as $taxonomy => $terms) {
            if (!empty($terms) && !in_array('', $terms)) {
                $args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN',
                ];
            }
        }

        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        $course_query = new WP_Query($args);
        $courses = $course_query->posts;
        $total_pages = $course_query->max_num_pages;

        $taxonomyTerms = [];
        foreach ($taxonomies as $slug => $name) {
            $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
            if (!is_wp_error($terms)) {
                // Organize terms hierarchically
                $taxonomyTerms[$slug] = $this->organizeTermsHierarchically($terms);
            } else {
                $taxonomyTerms[$slug] = [];
            }
        }

        ob_start();
        ?>
        <div class="cm-course-manager-base">
            <div class="cm-course-manager">
                <?php
                if ($attributes['show_filters'] === 'yes'): ?>
                    <div class="cm-filters">
                        <form method="get" action="<?php
                        echo esc_url(get_permalink()); ?>">
                            <div class="cm-filter-group cm-search-group">
                                <label for="course_search">Søk:</label>
                                <input type="text" id="course_search" name="course_search" placeholder="Søk etter kurs"
                                       value="<?php
                                       echo esc_attr($searchTerm); ?>"/>
                            </div>
                            <div class="cm-filter-group">
                                <label for="start_date">Fra dato:</label>
                                <input type="date" id="start_date" name="start_date" value="<?php
                                echo esc_attr($startDate); ?>"/>
                            </div>
                            <div class="cm-filter-group">
                                <label for="end_date">Til dato:</label>
                                <input type="date" id="end_date" name="end_date" value="<?php
                                echo esc_attr($endDate); ?>"/>
                            </div>
                            <?php
                            foreach ($taxonomies as $slug => $name): ?>
                                <div class="cm-filter-group cm-taxonomy-filter">
                                    <label><?php
                                        echo esc_html($name); ?>:</label>
                                    <div class="cm-filter-dropdown">
                                        <button type="button" class="cm-filter-toggle"><?php
                                            echo esc_html($name); ?> (<?php
                                            echo count(array_filter($selectedTaxonomies[$slug])) ?: 'Alle'; ?> valgt)
                                        </button>
                                        <div class="cm-filter-options">
                                            <?php
                                            $this->renderTaxonomyTerms(
                                                $taxonomyTerms[$slug],
                                                $slug,
                                                $selectedTaxonomies[$slug]
                                            ); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            endforeach; ?>
                            <button type="submit" class="cm-filter-button">Filtrer</button>
                            <button type="button" class="cm-reset-button">Nullstill</button>
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
                            $startDate = get_post_meta($course->ID, 'startdato', true);
                            $startDate = $startDate ? DateTime::createFromFormat('Y-m-d', $startDate)->format(
                                'd.m.Y'
                            ) : '';
                            $pricePerParticipant = get_post_meta($course->ID, '_course_price', true);
                            $maxParticipants = get_post_meta($course->ID, '_course_max_participants', true);
                            $more_info_page_id = get_post_meta($course->ID, '_course_more_info_page', true);
                            $more_info_url = $more_info_page_id ? get_permalink($more_info_page_id) : '';

                            $enrollmentArgs = [
                                'post_type' => 'course_enrollment',
                                'post_status' => 'publish',
                                'meta_query' => [
                                    [
                                        'key' => 'cm_course_id',
                                        'value' => $course->ID,
                                        'compare' => '=',
                                    ],
                                ],
                            ];
                            $enrollments = get_posts($enrollmentArgs);
                            $currentParticipants = 0;
                            foreach ($enrollments as $enrollment) {
                                $participants = get_post_meta($enrollment->ID, 'cm_participants', true);
                                if (is_array($participants)) {
                                    $currentParticipants += count($participants);
                                }
                            }

                            $isAvailable = !$maxParticipants || $currentParticipants < $maxParticipants;
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
                                        <div>
                                            <?php
                                            if ($startDate): ?>
                                                <span><strong>Startdato:</strong> <?php
                                                    echo esc_html($startDate); ?></span>
                                            <?php
                                            endif; ?>
                                            <?php
                                            foreach ($courseTaxonomyData as $typeName => $terms): ?>
                                                <span class="cm-course-taxonomy">
                                            <strong><?php
                                                echo esc_html($typeName); ?>:</strong> <?php
                                                    echo esc_html(implode(', ', $terms)); ?>
                                        </span>
                                            <?php
                                            endforeach; ?>
                                            <?php
                                            if ($pricePerParticipant): ?>
                                                <span title="Pris per deltaker"><strong>Pris:</strong> <?php
                                                    echo esc_html($pricePerParticipant); ?> NOK</span>
                                            <?php
                                            endif; ?>
                                        </div>
                                        <div>
                                    <span class="cm-availability <?php
                                    echo $isAvailable ? 'cm-available' : 'cm-full'; ?>">
                                        <span class="cm-availability-indicator"></span>
                                        <?php
                                        echo $isAvailable ? 'Ledige plasser' : 'Fullt'; ?>
                                    </span>
                                        </div>
                                    </div>
                                    <div class="cm-course-excerpt">
                                        <?php
                                        echo wp_trim_words($course->post_content, 30); ?>
                                    </div>
                                    <div class="cm-course-actions">
                                        <a href="<?php
                                        echo get_permalink($course->ID); ?>" class="cm-course-link">Vis kurs</a>
                                        <?php
                                        if ($more_info_url): ?>
                                            <a href="<?php
                                            echo esc_url($more_info_url); ?>" class="cm-more-info-link">Mer info</a>
                                        <?php
                                        endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endforeach; ?>
                    </div>

                    <div class="cm-pagination">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '?paged=%#%',
                            'current' => max(1, $paged),
                            'total' => $total_pages,
                            'prev_text' => __('&laquo; Forrige'),
                            'next_text' => __('Neste &raquo;'),
                            'add_args' => array_filter(
                                [
                                    'course_search' => $searchTerm,
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                ] + $selectedTaxonomies
                            ),
                        ]);
                        ?>
                    </div>
                <?php
                endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Organize terms hierarchically into a nested array.
     *
     * @param array $terms List of taxonomy terms.
     * @return array Organized terms with children nested under parents.
     */
    private function organizeTermsHierarchically(array $terms): array {
        $organized = [];
        $term_map = [];

        // First, map all terms by their ID for easy lookup
        foreach ($terms as $term) {
            $term_map[$term->term_id] = [
                'term' => $term,
                'children' => []
            ];
        }

        // Then, organize terms into a hierarchy
        foreach ($term_map as $term_id => &$term_data) {
            $term = $term_data['term'];
            if ($term->parent == 0) {
                // This is a parent term
                $organized[$term_id] = $term_data;
            } else {
                // This is a child term, add it to its parent's children
                if (isset($term_map[$term->parent])) {
                    $term_map[$term->parent]['children'][$term_id] = $term_data;
                }
            }
        }

        return $organized;
    }

    /**
     * Render taxonomy terms hierarchically in the dropdown.
     *
     * @param array $terms Organized terms with children.
     * @param string $taxonomy_slug The taxonomy slug.
     * @param array $selected_terms The currently selected terms.
     * @param int $level The current nesting level (for indentation).
     */
    private function renderTaxonomyTerms(
        array $terms,
        string $taxonomy_slug,
        array $selected_terms,
        int $level = 0
    ): void {
        foreach ($terms as $term_data) {
            $term = $term_data['term'];
            $children = $term_data['children'];
            if ($level == 0) {
                echo '<div class="cm-filter-option-group">';
            }
            ?>
            <div class="cm-filter-option <?php
            echo $term->parent == 0 ? 'cm-filter-option-parent' : 'cm-filter-option-child'; ?>"
                 style="padding-left: <?php
                 echo $level == 0 ? 1 : 1 + ($level * 0.5); ?>rem;">
                <label>
                    <input type="checkbox" name="<?php
                    echo esc_attr($taxonomy_slug); ?>[]" value="<?php
                    echo esc_attr($term->slug); ?>" <?php
                    echo in_array($term->slug, $selected_terms) ? 'checked' : ''; ?>>
                    <?php
                    echo esc_html($term->name); ?>
                </label>
            </div>
            <?php
            if (!empty($children)) {
                if ($level == 0) {
                    echo '<div class="cm-filter-option-separator"></div>';
                }
                $this->renderTaxonomyTerms($children, $taxonomy_slug, $selected_terms, $level + 1);
            }
            if ($level == 0) {
                echo '</div>';
            }
        }
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

        if (!class_exists('WooCommerce')) {
            return '<p>' . __('Denne funksjonen krever WooCommerce for betalinger.', 'course-manager') . '</p>';
        }

        $courseId = get_the_ID();
        $submissionMessage = '';
        $pricePerParticipant = (int)get_post_meta($courseId, '_course_price', true);
        $maxParticipants = get_post_meta($courseId, '_course_max_participants', true);

        // Check the number of participants
        $enrollmentArgs = [
            'post_type' => 'course_enrollment',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'cm_course_id',
                    'value' => $courseId,
                    'compare' => '=',
                ],
            ],
        ];
        $enrollments = get_posts($enrollmentArgs);
        $currentParticipants = 0;
        foreach ($enrollments as $enrollment) {
            $participants = get_post_meta($enrollment->ID, 'cm_participants', true);
            if (is_array($participants)) {
                $currentParticipants += count($participants);
            }
        }

        if (isset($_GET['payment_success']) && $_GET['payment_success'] === '1') {
            return '<p class="success">Påmeldingen er fullført! Du vil motta en bekreftelse på e-post.</p>';
        }

        if ($maxParticipants && $currentParticipants >= $maxParticipants) {
            return '<p class="cm-no-availability">Dette kurset er fullt. Ingen flere plasser er tilgjengelige.</p>';
        }

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

                $participants = [];
                $participant_count = isset($_POST['cm_participant_count']) ? (int)$_POST['cm_participant_count'] : 0;

                for ($i = 0; $i < $participant_count; $i++) {
                    if (isset($_POST['cm_participant_name'][$i], $_POST['cm_participant_email'][$i])) {
                        $participant_birthdate = sanitize_text_field($_POST['cm_participant_birthdate'][$i] ?? '');
                        if (!empty($participant_birthdate)) {
                            $date = DateTime::createFromFormat('Y-m-d', $participant_birthdate);
                            $participant_birthdate = $date ? $date->format('d.m.Y') : '';
                        }

                        $participants[] = [
                            'name' => sanitize_text_field($_POST['cm_participant_name'][$i]),
                            'email' => sanitize_email($_POST['cm_participant_email'][$i]),
                            'phone' => sanitize_text_field($_POST['cm_participant_phone'][$i] ?? ''),
                            'birthdate' => $participant_birthdate,
                        ];
                    }
                }

                if (empty($buyer_name) || empty($buyer_email) || !is_email($buyer_email)) {
                    $submissionMessage = '<p class="error">Vennligst fyll inn alle påkrevde felt for bestiller med gyldig data.</p>';
                } elseif (empty($participants)) {
                    $submissionMessage = '<p class="error">Minst én deltaker må legges til.</p>';
                } else {
                    $validParticipants = true;
                    foreach ($participants as $participant) {
                        if (empty($participant['name']) || empty($participant['email']) || !is_email(
                                $participant['email']
                            )) {
                            $validParticipants = false;
                            break;
                        }
                    }

                    if (!$validParticipants) {
                        $submissionMessage = '<p class="error">Vennligst fyll inn alle påkrevde felt for deltakere med gyldig data.</p>';
                    } elseif (!empty($buyer_postal_code) && !preg_match('/^\d{4}$/', $buyer_postal_code)) {
                        $submissionMessage = '<p class="error">Postnummer må være 4 sifre (f.eks. 1234).</p>';
                    } elseif ($maxParticipants && ($currentParticipants + count($participants)) > $maxParticipants) {
                        $submissionMessage = '<p class="error">Antall deltakere overskrider maksgrensen på ' . esc_html(
                                $maxParticipants
                            ) . ' plasser.</p>';
                    } else {
                        $totalPrice = $pricePerParticipant * count($participants);

                        $enrollmentData = [
                            'course_id' => $courseId,
                            'buyer_name' => $buyer_name,
                            'buyer_email' => $buyer_email,
                            'buyer_phone' => $buyer_phone,
                            'buyer_street_address' => $buyer_street_address,
                            'buyer_postal_code' => $buyer_postal_code,
                            'buyer_city' => $buyer_city,
                            'buyer_comments' => $buyer_comments,
                            'buyer_company' => $buyer_company,
                            'participants' => $participants,
                            'total_price' => $totalPrice,
                        ];

                        if ($totalPrice === 0) {
                            $enrollmentId = $this->completeEnrollment($enrollmentData);
                            if ($enrollmentId) {
                                $submissionMessage = '<p class="success">Påmeldingen er fullført! En bekreftelse er sendt til ' . esc_html(
                                        $buyer_email
                                    ) . '.</p>';
                            } else {
                                $submissionMessage = '<p class="error">Det oppstod en feil ved registrering av påmeldingen. Vennligst prøv igjen.</p>';
                            }
                        } else {
                            $order = wc_create_order();
                            $item = new WC_Order_Item_Product();
                            $item->set_name(get_the_title($courseId));
                            $item->set_quantity(count($participants));
                            $item->set_subtotal($totalPrice);
                            $item->set_total($totalPrice);
                            $order->add_item($item);

                            $order->set_billing_first_name($buyer_name);
                            $order->set_billing_email($buyer_email);
                            $order->set_billing_phone($buyer_phone);
                            $order->set_billing_address_1($buyer_street_address);
                            $order->set_billing_postcode($buyer_postal_code);
                            $order->set_billing_city($buyer_city);
                            $order->update_meta_data('_cm_enrollment_data', $enrollmentData);
                            $order->calculate_totals();
                            $order->save();

                            wp_redirect($order->get_checkout_payment_url());
                            exit;
                        }
                    }
                }
            }
        }

        ob_start();
        ?>
        <div class="cm-course-manager-base">
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
                    <input type="hidden" name="cm_participant_count" id="cm_participant_count" value="0">

                    <fieldset>
                        <legend>Bestillerinformasjon</legend>
                        <div class="cm-form-field">
                            <label for="cm_buyer_name">Navn <span class="required">*</span></label>
                            <input type="text" name="cm_buyer_name" id="cm_buyer_name" required
                                   placeholder="Ola Nordmann">
                        </div>

                        <div class="cm-form-field">
                            <label for="cm_buyer_company">Firma</label>
                            <input type="text" name="cm_buyer_company" id="cm_buyer_company" placeholder="Eksempel AS">
                        </div>

                        <div class="cm-form-field">
                            <label for="cm_buyer_email">E-post <span class="required">*</span></label>
                            <input type="email" name="cm_buyer_email" id="cm_buyer_email" required
                                   placeholder="navn@eksempel.no">
                        </div>

                        <div class="cm-form-field">
                            <label for="cm_buyer_phone">Telefonnummer</label>
                            <input type="tel" name="cm_buyer_phone" id="cm_buyer_phone" placeholder="12345678">
                        </div>

                        <div class="cm-form-field">
                            <label for="cm_buyer_street_address">Gateadresse</label>
                            <input type="text" name="cm_buyer_street_address" id="cm_buyer_street_address"
                                   placeholder="Gateveien 1">
                        </div>

                        <div class="cm-address-group">
                            <div class="cm-address-field">
                                <label for="cm_buyer_postal_code">Postnummer</label>
                                <input type="text" name="cm_buyer_postal_code" id="cm_buyer_postal_code"
                                       placeholder="1234">
                            </div>
                            <div class="cm-address-field">
                                <label for="cm_buyer_city">Poststed</label>
                                <input type="text" name="cm_buyer_city" id="cm_buyer_city" placeholder="Oslo">
                            </div>
                        </div>

                        <div class="cm-form-field">
                            <label for="cm_buyer_comments">Kommentarer/spørsmål</label>
                            <textarea name="cm_buyer_comments" id="cm_buyer_comments" class="cm-comments-field"
                                      placeholder="Eventuelle kommentarer eller spørsmål"></textarea>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Deltakerinformasjon</legend>
                        <p class="cm-participant-info">Minst én deltaker må legges til.</p>
                        <div id="cm-participant-list"></div>
                        <button type="button" id="cm-add-participant" class="cm-add-participant">Legg til deltaker
                        </button>
                    </fieldset>

                    <div class="cm-total-price">
                        <p><strong>Total pris:</strong> <span id="cm-total-price-value">0</span> NOK (for <span
                                    id="cm-participant-count">0</span> deltakere<?php
                            if ($pricePerParticipant) {
                                echo ' á ' . $pricePerParticipant . ' NOK';
                            } ?>)</p>
                        <input type="hidden" id="cm-price-per-participant" value="<?php
                        echo $pricePerParticipant; ?>">
                    </div>

                    <button type="submit">Gå til betaling</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the course slider shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function renderCourseSlider(array $atts = []): string {
        $attributes = shortcode_atts([], $atts);

        $slider_items = get_option('course_manager_slider_items', 5);

        $args = [
            'post_type' => 'course',
            'post_status' => 'publish',
            'posts_per_page' => $slider_items,
            'orderby' => 'date',
            'order' => 'DESC', // Sort newest first
        ];

        $course_query = new WP_Query($args);
        $courses = $course_query->posts;

        ob_start();
        ?>
        <div class="cm-course-manager-base">
            <div class="cm-course-slider">
                <?php
                if (empty($courses)): ?>
                    <p>Ingen kurs å vise i slideren.</p>
                <?php
                else: ?>
                    <div class="cm-slider-wrapper">
                        <div class="cm-slider-container">
                            <?php
                            foreach ($courses as $course): ?>
                                <div class="cm-slider-item">
                                    <?php
                                    if (has_post_thumbnail($course->ID)): ?>
                                        <div class="cm-slider-image">
                                            <?php
                                            echo get_the_post_thumbnail($course->ID, 'medium'); ?>
                                        </div>
                                    <?php
                                    endif; ?>
                                    <h3 class="cm-slider-title">
                                        <a href="<?php
                                        echo get_permalink($course->ID); ?>">
                                            <?php
                                            echo esc_html($course->post_title); ?>
                                        </a>
                                    </h3>
                                    <div class="cm-slider-excerpt">
                                        <?php
                                        echo wp_trim_words($course->post_content, 15); ?>
                                    </div>
                                </div>
                            <?php
                            endforeach; ?>
                        </div>
                    </div>
                    <button class="cm-slider-prev">❮</button>
                    <button class="cm-slider-next">❯</button>
                <?php
                endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Complete an enrollment by saving it to the database and sending emails.
     *
     * @param array $enrollmentData The enrollment data to save.
     * @return int|bool The enrollment ID on success, false on failure.
     */
    private function completeEnrollment(array $enrollmentData) {
        $courseId = $enrollmentData['course_id'];
        $buyer_name = $enrollmentData['buyer_name'];
        $buyer_email = $enrollmentData['buyer_email'];
        $buyer_phone = $enrollmentData['buyer_phone'];
        $buyer_street_address = $enrollmentData['buyer_street_address'];
        $buyer_postal_code = $enrollmentData['buyer_postal_code'];
        $buyer_city = $enrollmentData['buyer_city'];
        $buyer_comments = $enrollmentData['buyer_comments'];
        $buyer_company = $enrollmentData['buyer_company'];
        $participants = $enrollmentData['participants'];
        $totalPrice = $enrollmentData['total_price'];

        $enrollmentId = wp_insert_post([
            'post_type' => 'course_enrollment',
            'post_title' => sprintf(
                'Påmelding til %s av %d deltakere (bestilt av %s)',
                get_the_title($courseId),
                count($participants),
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
            update_post_meta($enrollmentId, 'cm_participants', $participants);
            update_post_meta($enrollmentId, 'cm_total_price', $totalPrice);

            $enable_emails = get_option('course_manager_enable_emails', true);
            if ($enable_emails) {
                $templateVars = [
                    'buyer_name' => $buyer_name,
                    'course_title' => get_the_title($courseId),
                    'participant_count' => count($participants),
                    'total_price' => $totalPrice,
                    'participants' => implode("\n", array_map(function ($participant) {
                        return "- " . $participant['name'];
                    }, $participants)),
                    'buyer_email' => $buyer_email,
                    'buyer_phone' => $buyer_phone,
                    'buyer_company' => $buyer_company,
                    'buyer_street_address' => $buyer_street_address,
                    'buyer_postal_code' => $buyer_postal_code,
                    'buyer_city' => $buyer_city,
                    'buyer_comments' => $buyer_comments,
                ];

                $subject = 'Bekreftelse på kurspåmelding';
                $custom_message = get_post_meta($courseId, '_course_custom_email_message', true);
                $default_message = get_option(
                    'course_manager_default_email_message',
                    "Hei [buyer_name],\n\nTakk for at du meldte deg på [course_title]! Vi gleder oss til å se deg.\n\nAntall deltakere: [participant_count]\nTotal pris: [total_price] NOK\n\nDeltakere:\n[participants]\n\nBeste hilsener,\nKursadministrator-teamet"
                );
                $message = !empty($custom_message) ? $custom_message : $default_message;

                $message = $this->parseEmailTemplate($message, $templateVars);
                wp_mail($buyer_email, $subject, $message);

                $adminEmail = get_option('course_manager_admin_email');
                if (!empty($adminEmail) && is_email($adminEmail)) {
                    $adminSubject = 'Ny påmelding til [course_title]';
                    $adminMessage = get_option(
                        'course_manager_admin_email_message',
                        "Hei,\n\nEn ny påmelding har blitt registrert for kurset \"[course_title]\".\n\nBestillerinformasjon:\n- Navn: [buyer_name]\n- E-post: [buyer_email]\n- Telefonnummer: [buyer_phone]\n- Firma: [buyer_company]\n- Gateadresse: [buyer_street_address]\n- Postnummer: [buyer_postal_code]\n- Poststed: [buyer_city]\n- Kommentarer/spørsmål: [buyer_comments]\n\nDeltakere ([participant_count]):\n[participants]\n\nTotal pris: [total_price] NOK\n\nBeste hilsener,\nKursadministrator-systemet"
                    );

                    $adminSubject = $this->parseEmailTemplate($adminSubject, $templateVars);
                    $adminMessage = $this->parseEmailTemplate($adminMessage, $templateVars);
                    wp_mail($adminEmail, $adminSubject, $adminMessage);
                } else {
                    error_log('Admin email not sent: Invalid or empty admin email (' . $adminEmail . ')');
                }
            }

            return $enrollmentId;
        }

        return false;
    }

    /**
     * Parse an email template by replacing tags with values.
     *
     * @param string $template The email template with tags.
     * @param array $vars The variables to replace in the template.
     * @return string The parsed email template.
     */
    private function parseEmailTemplate(string $template, array $vars): string {
        $message = $template;
        foreach ($vars as $key => $value) {
            $message = str_replace("[$key]", $value, $message);
        }
        return $message;
    }

    /**
     * Handle WooCommerce order completed.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function handleWooCommerceOrderCompleted(int $order_id): void {
        $order = wc_get_order($order_id);
        $enrollmentData = $order->get_meta('_cm_enrollment_data', true);

        if ($enrollmentData && is_array($enrollmentData)) {
            $enrollmentId = $this->completeEnrollment($enrollmentData);
            if ($enrollmentId) {
                $order->add_order_note(
                    __('Påmelding fullført via Course Manager (ID: ' . $enrollmentId . ')', 'course-manager')
                );
            } else {
                $order->add_order_note(__('Feil ved fullføring av påmelding i Course Manager.', 'course-manager'));
            }
        }
    }

    /**
     * Customize the return URL after order completion.
     * Falling back to the default return URL in case of errors.
     *
     * @param string $return_url Default return URL.
     * @param WC_Order $order The WooCommerce order object.
     * @return string Modified return URL.
     */
    public function customizeReturnUrl(string $return_url, $order): string {
        $enrollmentData = $order->get_meta('_cm_enrollment_data', true);
        if ($enrollmentData && isset($enrollmentData['course_id'])) {
            return add_query_arg('payment_success', '1', get_permalink($enrollmentData['course_id']));
        }
        return $return_url;
    }
}