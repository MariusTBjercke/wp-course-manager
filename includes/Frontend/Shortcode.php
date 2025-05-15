<?php

namespace CourseManager\Frontend;

use DateTime;
use WC_Order;
use WC_Order_Item_Product;
use WP_Query;
use CourseManager\Helpers\DateFormatter;

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
        $filterStartDate = !empty($_GET['start_date']) && DateTime::createFromFormat('Y-m-d', $_GET['start_date']) !== false
            ? sanitize_text_field($_GET['start_date'])
            : '';
        $filterEndDate = !empty($_GET['end_date']) && DateTime::createFromFormat('Y-m-d', $_GET['end_date']) !== false
            ? sanitize_text_field($_GET['end_date'])
            : '';

        $selectedTaxonomies = [];
        $taxonomies = get_option('course_manager_taxonomies', []);

        // Handle multiple selections for each taxonomy
        foreach ($taxonomies as $slug => $name) {
            $selectedTaxonomies[$slug] = isset($_GET[$slug]) ? (array)$_GET[$slug] : [];
            $selectedTaxonomies[$slug] = array_map('sanitize_text_field', $selectedTaxonomies[$slug]);
        }

        // Get the current page from URL (default to 1 if not set)
        $paged = (get_query_var('paged')) ?: (isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $posts_per_page = get_option('course_manager_items_per_page', 10);

        // Query for courses first
        $args = [
            'post_type' => 'course',
            'post_status' => 'publish',
            's' => $searchTerm,
            'posts_per_page' => -1, // Get all courses initially to process course dates
        ];

        $course_query = new WP_Query($args);
        $allCourses = $course_query->posts;

        // Process courses to get a flat list of course dates that match date and taxonomy filters and are available
        $filterableCourseDates = [];
        $now = new DateTime();

        foreach ($allCourses as $course) {
            $courseDates = get_post_meta($course->ID, '_course_dates', true);
            if (!empty($courseDates) && is_array($courseDates)) {
                foreach ($courseDates as $courseDateIndex => $courseDate) {
                    $startDate = $courseDate['start_date'] ?? '';
                    $endDate = $courseDate['end_date'] ?? '';
                    $courseDateTaxonomies = $courseDate['taxonomies'] ?? [];

                    $courseDateStartDateObj = $startDate ? DateTime::createFromFormat('Y-m-d', $startDate) : null;
                    $courseDateEndDateObj = $endDate ? DateTime::createFromFormat('Y-m-d', $endDate) : $courseDateStartDateObj;

                    // Filter by date range
                    $dateMatch = true;
                    if ($filterStartDate && $courseDateEndDateObj && $courseDateEndDateObj < DateTime::createFromFormat('Y-m-d', $filterStartDate)) {
                        $dateMatch = false;
                    }
                    if ($filterEndDate && $courseDateStartDateObj && $courseDateStartDateObj > DateTime::createFromFormat('Y-m-d', $filterEndDate)) {
                        $dateMatch = false;
                    }

                    // Only include course dates in the future or today
                    if ($courseDateStartDateObj && $courseDateStartDateObj < $now) {
                        $dateMatch = false; // Exclude past course dates
                    }

                    // Filter by taxonomy
                    $taxonomyMatch = true;
                    foreach ($selectedTaxonomies as $taxSlug => $selectedTerms) {
                        if (!empty($selectedTerms) && !in_array('', $selectedTerms)) {
                            // Determine which terms to check against: date-specific or course-specific
                            $termsToCheck = [];
                            if (!empty($courseDateTaxonomies[$taxSlug])) {
                                // Use date-specific terms if available
                                $termsToCheck = $courseDateTaxonomies[$taxSlug];
                            } else {
                                // Otherwise, use course-specific terms
                                $courseTerms = get_the_terms($course->ID, $taxSlug);
                                if (!is_wp_error($courseTerms) && !empty($courseTerms)) {
                                    $termsToCheck = wp_list_pluck($courseTerms, 'slug');
                                }
                            }

                            // Check if any of the selected terms match the terms for this date/course
                            $matchFound = false;
                            if (!empty($termsToCheck)) { // Ensure there are terms to check against
                                foreach ($selectedTerms as $selectedTerm) {
                                    if (in_array($selectedTerm, $termsToCheck)) {
                                        $matchFound = true;
                                        break;
                                    }
                                }
                            }


                            if (!$matchFound) {
                                $taxonomyMatch = false;
                                break; // No need to check other taxonomies for this date
                            }
                        }
                    }


                    if ($dateMatch && $taxonomyMatch) {
                        // Fetch current participants for this specific course date
                        $enrollmentArgs = [
                            'post_type' => 'course_enrollment',
                            'post_status' => 'publish',
                            'meta_query' => [
                                [
                                    'key' => 'cm_course_id',
                                    'value' => $course->ID,
                                    'compare' => '=',
                                ],
                                [
                                    'key' => 'cm_course_date_index',
                                    'value' => $courseDateIndex,
                                    'compare' => '=',
                                ]
                            ],
                            'posts_per_page' => -1, // Get all enrollments for this course date
                        ];
                        $enrollments = get_posts($enrollmentArgs);
                        $currentParticipants = 0;
                        foreach ($enrollments as $enrollment) {
                            $participantsMeta = get_post_meta($enrollment->ID, 'cm_participants', true);
                            if (is_array($participantsMeta)) {
                                $currentParticipants += count($participantsMeta);
                            }
                        }

                        // Determine availability based on course date-specific limit
                        $isAvailable = true;
                        $capacityLimit = $courseDate['max_participants_course_date'] ?? '';

                        if ($capacityLimit !== '') {
                            $capacityLimit = (int)$capacityLimit;
                            if ($currentParticipants >= $capacityLimit) {
                                $isAvailable = false;
                            }
                        } else {
                            $capacityLimit = null; // No specific limit for this course date
                        }


                        $filterableCourseDates[] = [
                            'course' => $course,
                            'course_date' => $courseDate,
                            'course_date_index' => $courseDateIndex,
                            'is_available' => $isAvailable,
                            'current_participants' => $currentParticipants,
                            'capacity_limit' => $capacityLimit,
                        ];
                    }
                }
            }
        }

        // Sort course dates by start date
        usort($filterableCourseDates, function($a, $b) {
            $dateA = DateTime::createFromFormat('Y-m-d', $a['course_date']['start_date'] ?? '9999-12-31'); // Use a future date if no start date
            $dateB = DateTime::createFromFormat('Y-m-d', $b['course_date']['start_date'] ?? '9999-12-31');
            if ($dateA == $dateB) {
                return 0;
            }
            return ($dateA < $dateB) ? -1 : 1;
        });


        // Paginate the filterable course dates manually
        $totalCourseDates = count($filterableCourseDates);
        $offset = ($paged - 1) * $posts_per_page;
        $paginatedCourseDates = array_slice($filterableCourseDates, $offset, $posts_per_page);
        $totalPages = $posts_per_page > 0 ? ceil($totalCourseDates / $posts_per_page) : 1;


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
                        <form method="get" action="<?php echo esc_url(get_permalink()); ?>">
                            <div class="cm-filter-group cm-search-group">
                                <label for="course_search">Søk:</label>
                                <input type="text" id="course_search" name="course_search" placeholder="Søk etter kurs"
                                       value="<?php echo esc_attr($searchTerm); ?>"/>
                            </div>
                            <div class="cm-filter-group">
                                <label for="start_date">Fra kursdato:</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($filterStartDate); ?>"/>
                            </div>
                            <div class="cm-filter-group">
                                <label for="end_date">Til kursdato:</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($filterEndDate); ?>"/>
                            </div>
                            <?php foreach ($taxonomies as $slug => $name): ?>
                                <div class="cm-filter-group cm-taxonomy-filter">
                                    <label><?php echo esc_html($name); ?>:</label>
                                    <div class="cm-filter-dropdown">
                                        <button type="button" class="cm-filter-toggle"><?php
                                            echo esc_html($name); ?> (<?php echo count(array_filter($selectedTaxonomies[$slug])) ?: 'Alle'; ?> valgt)
                                        </button>
                                        <div class="cm-filter-options">
                                            <?php $this->renderTaxonomyTerms($taxonomyTerms[$slug], $slug, $selectedTaxonomies[$slug]); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button type="submit" class="cm-filter-button">Filtrer</button>
                            <button type="button" class="cm-reset-button">Nullstill</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php
                if (empty($paginatedCourseDates)): ?>
                    <div class="cm-no-courses">
                        <p>Ingen kurs funnet.</p>
                    </div>
                <?php
                else: ?>
                    <div class="cm-course-list">
                        <?php
                        foreach ($paginatedCourseDates as $courseDateData):
                            $course = $courseDateData['course'];
                            $courseDate = $courseDateData['course_date'];
                            $courseDateIndex = $courseDateData['course_date_index'];
                            $isAvailable = $courseDateData['is_available'];
                            $currentParticipants = $courseDateData['current_participants'];
                            $capacityLimit = $courseDateData['capacity_limit'];

                            $displayedTaxonomyData = [];
                            $courseDateTaxonomies = $courseDate['taxonomies'] ?? []; // Get date-specific taxonomies

                            foreach ($taxonomies as $slug => $name) {
                                $termsToDisplay = [];
                                if (!empty($courseDateTaxonomies[$slug])) {
                                    // Use date-specific terms if available
                                    $termsToDisplay = $courseDateTaxonomies[$slug];
                                } else {
                                    // Otherwise, use course-specific terms
                                    $courseTerms = get_the_terms($course->ID, $slug);
                                    if (!is_wp_error($courseTerms) && !empty($courseTerms)) {
                                        $termsToDisplay = wp_list_pluck($courseTerms, 'slug');
                                    }
                                }

                                // Fetch term objects to get their names
                                $termNamesToDisplay = [];
                                if (!empty($termsToDisplay)) {
                                    $termObjects = get_terms([
                                        'taxonomy' => $slug,
                                        'slug' => $termsToDisplay,
                                        'hide_empty' => false,
                                    ]);
                                    if (!is_wp_error($termObjects) && !empty($termObjects)) {
                                        $termNamesToDisplay = wp_list_pluck($termObjects, 'name');
                                    }
                                }


                                if (!empty($termNamesToDisplay)) {
                                    $displayedTaxonomyData[$name] = $termNamesToDisplay;
                                }
                            }

                            $pricePerParticipant = get_post_meta($course->ID, '_course_price', true);
                            $moreInfoPageId = get_post_meta($course->ID, '_course_more_info_page', true);
                            $moreInfoUrl = $moreInfoPageId ? get_permalink($moreInfoPageId) : '';

                            $courseStartDate = $courseDate['start_date'] ? DateTime::createFromFormat('Y-m-d', $courseDate['start_date'])->format('d.m.Y') : '';
                            $courseEndDate = $courseDate['end_date'] ? DateTime::createFromFormat('Y-m-d', $courseDate['end_date'])->format('d.m.Y') : '';

                            $courseStartTime = $courseDate['start_time'] ?? '';
                            $courseEndTime = $courseDate['end_time'] ?? '';

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
                                            if ($courseStartDate): ?>
                                                <span><strong>Startdato:</strong> <?php
                                                    echo esc_html($courseStartDate); ?></span>
                                            <?php
                                            endif; ?>
                                            <?php
                                            if ($courseEndDate && $courseEndDate !== $courseStartDate): ?>
                                                <span><strong>Sluttdato:</strong> <?php
                                                    echo esc_html($courseEndDate); ?></span>
                                            <?php
                                            endif; ?>
                                            <?php
                                            if ($courseStartTime || $courseEndTime): ?>
                                                <span><strong>Tidspunkt:</strong> <?php
                                                    if ($courseStartTime && $courseEndTime) {
                                                        echo esc_html($courseStartTime . ' - ' . $courseEndTime);
                                                    } elseif ($courseStartTime) {
                                                        echo esc_html($courseStartTime);
                                                    } else {
                                                        echo esc_html('Slutt: ' . $courseEndTime);
                                                    }
                                                    ?></span>
                                            <?php
                                            endif; ?>
                                            <?php
                                            foreach ($displayedTaxonomyData as $typeName => $terms): ?>
                                                <span class="cm-course-taxonomy">
                                                    <strong><?php echo esc_html($typeName); ?>:</strong> <?php
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
                                                <?php echo $isAvailable ? 'Ledige plasser' : 'Fullt'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="cm-course-excerpt">
                                        <?php
                                        echo wp_trim_words($course->post_content, 30); ?>
                                    </div>

                                    <div class="cm-course-actions">
                                        <?php if ($isAvailable): ?>
                                            <a href="<?php echo add_query_arg('selected_course_date', $courseDateIndex, get_permalink($course->ID)); ?>" class="cm-course-link">Meld deg på</a>
                                        <?php else: ?>
                                            <span class="cm-full-button">Fullt</span>
                                        <?php
                                        endif; ?>
                                        <?php
                                        if ($moreInfoUrl): ?>
                                            <a
                                                    href="<?php echo esc_url($moreInfoUrl); ?>"
                                                    class="cm-more-info-link"
                                                    target="_blank">Mer info</a>
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
                        // Build the arguments manually for more control
                        $paginationArgs = [];
                        if (!empty($searchTerm)) {
                            $paginationArgs['course_search'] = $searchTerm;
                        }
                        if (!empty($filterStartDate)) {
                            $paginationArgs['start_date'] = $filterStartDate;
                        }
                        if (!empty($filterEndDate)) {
                            $paginationArgs['end_date'] = $filterEndDate;
                        }
                        foreach ($selectedTaxonomies as $taxonomy => $terms) {
                            if (!empty($terms) && !in_array('', $terms)) {
                                $paginationArgs[$taxonomy] = $terms;
                            }
                        }

                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '?paged=%#%',
                            'current' => max(1, $paged),
                            'total' => $totalPages,
                            'prev_text' => __('« Forrige'),
                            'next_text' => __('Neste »'),
                            'add_args' => $paginationArgs,
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
        foreach ($terms as $termData) {
            $term = $termData['term'];
            $children = $termData['children'];
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
        $courseDates = get_post_meta($courseId, '_course_dates', true);
        $submissionMessage = '';
        $pricePerParticipant = (int)get_post_meta($courseId, '_course_price', true);
        $taxonomies = get_option('course_manager_taxonomies', []); // Get defined taxonomies
        $selectedDropdownTaxonomies = get_option('course_manager_dropdown_taxonomies', []); // Get taxonomies to show in dropdown
        $dropdownFormat = get_option('course_manager_dropdown_format', '[date] ([taxonomies]) (Ledige plasser: [available_slots])'); // Get the format pattern


        // Check if there are any course dates available
        if (empty($courseDates) || !is_array($courseDates)) {
            return '<p class="cm-no-availability">Ingen kursdatoer tilgjengelig for dette kurset for øyeblikket.</p>';
        }

        // Determine the pre-selected course date from the URL, if any
        $preSelectedCourseDateIndex = isset($_GET['selected_course_date']) ? absint($_GET['selected_course_date']) : -1;
        $initialCourseDateValid = ($preSelectedCourseDateIndex >= 0 && isset($courseDates[$preSelectedCourseDateIndex]));

        $participants = [];

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cm_enrollment_nonce'])) {
            if (!wp_verify_nonce($_POST['cm_enrollment_nonce'], 'cm_enroll_action')) {
                $submissionMessage = '<p class="error">Sikkerhetskontroll feilet. Vennligst prøv igjen.</p>';
            } else {
                $selectedCourseDateIndex = isset($_POST['cm_course_date']) ? absint($_POST['cm_course_date']) : -1;

                // Validate selected course date
                if ($selectedCourseDateIndex < 0 || !isset($courseDates[$selectedCourseDateIndex])) {
                    $submissionMessage = '<p class="error">Ugyldig kursdato valgt. Vennligst prøv igjen.</p>';
                } else {
                    $selectedCourseDate = $courseDates[$selectedCourseDateIndex];
                    $courseDateMaxParticipants = $selectedCourseDate['max_participants_course_date'] ?? '';

                    // Calculate current participants for this specific course date
                    $enrollmentArgs = [
                        'post_type' => 'course_enrollment',
                        'post_status' => 'publish',
                        'meta_query' => [
                            [
                                'key' => 'cm_course_id',
                                'value' => $courseId,
                                'compare' => '=',
                            ],
                            [
                                'key' => 'cm_course_date_index',
                                'value' => $selectedCourseDateIndex,
                                'compare' => '=',
                            ]
                        ],
                        'posts_per_page' => -1, // Get all enrollments for this course date
                    ];
                    $enrollments = get_posts($enrollmentArgs);
                    $currentParticipants = 0;
                    foreach ($enrollments as $enrollment) {
                        $participants = get_post_meta($enrollment->ID, 'cm_participants', true);
                        if (is_array($participants)) {
                            $currentParticipants += count($participants);
                        }
                    }

                    // Determine availability based on course date-specific limit
                    $isAvailable = true;
                    $capacityLimit = $courseDateMaxParticipants !== '' ? (int)$courseDateMaxParticipants : null;

                    $participantCount = isset($_POST['cm_participant_count']) ? (int)$_POST['cm_participant_count'] : 0;

                    if ($capacityLimit !== null && ($currentParticipants + $participantCount) > $capacityLimit) {
                        $submissionMessage = '<p class="error">Antall deltakere overskrider maksgrensen på ' . esc_html(
                                $capacityLimit
                            ) . ' plasser for denne kursdatoen.</p>';
                    } else {
                        $buyerName = sanitize_text_field($_POST['cm_buyer_name'] ?? '');
                        $buyerEmail = sanitize_email($_POST['cm_buyer_email'] ?? '');
                        $buyerPhone = sanitize_text_field($_POST['cm_buyer_phone'] ?? '');
                        $buyerStreetAddress = sanitize_text_field($_POST['cm_buyer_street_address'] ?? '');
                        $buyerPostalCode = sanitize_text_field($_POST['cm_postal_code'] ?? ''); // Corrected input name
                        $buyerCity = sanitize_text_field($_POST['cm_city'] ?? ''); // Corrected input name
                        $buyerComments = sanitize_textarea_field($_POST['cm_buyer_comments'] ?? '');
                        $buyerCompany = sanitize_text_field($_POST['cm_buyer_company'] ?? '');

                        $participants = [];
                        for ($i = 0; $i < $participantCount; $i++) {
                            if (isset($_POST['cm_participant_name'][$i], $_POST['cm_participant_email'][$i])) {
                                $participantBirthdate = sanitize_text_field($_POST['cm_participant_birthdate'][$i] ?? '');
                                if (!empty($participantBirthdate)) {
                                    $date = DateTime::createFromFormat('Y-m-d', $participantBirthdate);
                                    $participantBirthdate = $date ? $date->format('d.m.Y') : '';
                                }

                                $participants[] = [
                                    'name' => sanitize_text_field($_POST['cm_participant_name'][$i]),
                                    'email' => sanitize_email($_POST['cm_participant_email'][$i]),
                                    'phone' => sanitize_text_field($_POST['cm_participant_phone'][$i] ?? ''),
                                    'birthdate' => $participantBirthdate,
                                ];
                            }
                        }

                        if (empty($buyerName) || empty($buyerEmail) || !is_email($buyerEmail)) {
                            $submissionMessage = '<p class="error">Vennligst fyll inn alle påkrevde felt for bestiller med gyldig data.</p>';
                        } elseif (empty($participants)) {
                            $submissionMessage = '<p class="error">Minst én deltaker må meldes på.</p>';
                        } elseif (!empty($buyerPostalCode) && !preg_match('/^\d{4}$/', $buyerPostalCode)) {
                            $submissionMessage = '<p class="error">Postnummer må være 4 sifre (f.eks. 1234).</p>';
                        } else {
                            $totalPrice = $pricePerParticipant * count($participants);

                            $enrollmentData = [
                                'course_id' => $courseId,
                                'course_date_index' => $selectedCourseDateIndex,
                                'buyer_name' => $buyerName,
                                'buyer_email' => $buyerEmail,
                                'buyer_phone' => $buyerPhone,
                                'buyer_street_address' => $buyerStreetAddress,
                                'buyer_postal_code' => $buyerPostalCode,
                                'buyer_city' => $buyerCity,
                                'buyer_comments' => $buyerComments,
                                'buyer_company' => $buyerCompany,
                                'participants' => $participants,
                                'total_price' => $totalPrice,
                            ];

                            if ($totalPrice === 0) {
                                $enrollmentId = $this->completeEnrollment($enrollmentData);
                                if ($enrollmentId) {
                                    $submissionMessage = '<p class="success">Påmeldingen er fullført! En bekreftelse er sent til ' . esc_html(
                                            $buyerEmail
                                        ) . '.</p>';
                                } else {
                                    $submissionMessage = '<p class="error">Det oppstod en feil ved registrering av påmeldingen. Vennligst prøv igjen.</p>';
                                }
                            } else {
                                $order = wc_create_order();
                                $item = new WC_Order_Item_Product();
                                $item->set_name(get_the_title($courseId) . ' - Kursdato: ' . DateFormatter::formatCourseDateDisplay($selectedCourseDate));
                                $item->set_quantity(count($participants));
                                $item->set_subtotal($totalPrice);
                                $item->set_total($totalPrice);
                                $order->add_item($item);

                                $order->set_billing_first_name($buyerName);
                                $order->set_billing_email($buyerEmail);
                                $order->set_billing_phone($buyerPhone);
                                $order->set_billing_address_1($buyerStreetAddress);
                                $order->set_billing_postcode($buyerPostalCode);
                                $order->set_billing_city($buyerCity);
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
        }

        // Check for payment success message after redirect
        if (isset($_GET['payment_success']) && $_GET['payment_success'] === '1') {
            return '<p class="success">Påmeldingen er fullført! Du vil motta en bekreftelse på e-post.</p>';
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
                        <legend>Velg kursdato <span class="required">*</span></legend>
                        <div class="cm-form-field">
                            <label for="cm_course_date">Tilgjengelige kursdatoer:</label>
                            <select name="cm_course_date" id="cm_course_date" required>
                                <option value="">— Velg en kursdato —</option>
                                <?php foreach ($courseDates as $index => $courseDate):
                                    $courseDateMaxParticipants = $courseDate['max_participants_course_date'] ?? '';

                                    // Calculate current participants for this specific course date
                                    $enrollmentArgs = [
                                        'post_type' => 'course_enrollment',
                                        'post_status' => 'publish',
                                        'meta_query' => [
                                            [
                                                'key' => 'cm_course_id',
                                                'value' => $courseId,
                                                'compare' => '=',
                                            ],
                                            [
                                                'key' => 'cm_course_date_index',
                                                'value' => $index,
                                                'compare' => '=',
                                            ]
                                        ],
                                        'posts_per_page' => -1, // Get all enrollments for this course date
                                    ];
                                    $enrollments = get_posts($enrollmentArgs);
                                    $currentParticipants = 0;
                                    foreach ($enrollments as $enrollment) {
                                        $participants = get_post_meta($enrollment->ID, 'cm_participants', true);
                                        if (is_array($participants)) {
                                            $currentParticipants += count($participants);
                                        }
                                    }

                                    // Determine availability based on course date-specific limit
                                    $isAvailable = true;
                                    $capacityLimit = $courseDateMaxParticipants !== '' ? (int)$courseDateMaxParticipants : null;

                                    if ($capacityLimit !== null && $currentParticipants >= $capacityLimit) {
                                        $isAvailable = false;
                                    }

                                    // Only process and display if available
                                    if ($isAvailable):
                                        $courseDateDisplayString = DateFormatter::formatCourseDateDisplay($courseDate);
                                        $courseDateTimeString = DateFormatter::formatCourseDateTime($courseDate); // Get time string

                                        // Get taxonomy data for this specific course date or fallback to course level
                                        $courseDateTaxonomies = $courseDate['taxonomies'] ?? [];
                                        $dateSpecificTaxonomyData = [];
                                        $dropdownTaxonomyTextParts = [];

                                        // Get registered plugin taxonomy slugs and the selected ones for the dropdown
                                        $pluginTaxonomySlugs = array_keys(get_option('course_manager_taxonomies', []));
                                        $selectedDropdownTaxonomies = get_option('course_manager_dropdown_taxonomies', []);
                                        $allTaxonomies = get_option('course_manager_taxonomies', []);

                                        // Prepare data for placeholders and data attributes
                                        $placeholder_values = [
                                            '[date]' => esc_html($courseDateDisplayString),
                                            '[time]' => esc_html($courseDateTimeString),
                                            '[available_slots]' => esc_html($capacityLimit !== null ? ($capacityLimit - $currentParticipants) : 'Ubegrenset'),
                                        ];

                                        // Iterate through all plugin taxonomies
                                        foreach ($pluginTaxonomySlugs as $slug) {
                                            $termsToDisplay = [];
                                            $isDateSpecific = false;

                                            // Check for date-specific terms first
                                            if (!empty($courseDateTaxonomies[$slug])) {
                                                $termsToDisplay = $courseDateTaxonomies[$slug];
                                                $isDateSpecific = true;
                                            } else {
                                                // Otherwise, use course-specific terms
                                                $courseTerms = get_the_terms($courseId, $slug);
                                                if (!is_wp_error($courseTerms) && !empty($courseTerms)) {
                                                    $termsToDisplay = wp_list_pluck($courseTerms, 'slug');
                                                }
                                            }

                                            // Fetch term objects to get their names
                                            $termNamesToDisplay = [];
                                            if (!empty($termsToDisplay)) {
                                                $termObjects = get_terms([
                                                    'taxonomy' => $slug,
                                                    'slug' => $termsToDisplay,
                                                    'hide_empty' => false,
                                                ]);
                                                if (!is_wp_error($termObjects) && !empty($termObjects)) {
                                                    $termNamesToDisplay = wp_list_pluck($termObjects, 'name');
                                                }
                                            }

                                            // Add data for data attribute (used by JS below the dropdown)
                                            // Always add data attribute for all plugin taxonomies, even if empty, for JS to hide/show
                                            $dateSpecificTaxonomyData[$slug] = !empty($termNamesToDisplay) ? $termNamesToDisplay : ['Ikke spesifisert'];

                                            // Add value for specific taxonomy placeholder [taxonomy_SLUG]
                                            $placeholder_values["[taxonomy_{$slug}]"] = !empty($termNamesToDisplay) ? esc_html(implode(', ', $termNamesToDisplay)) : '';

                                            // Add text for the general [taxonomies] placeholder ONLY if this taxonomy is selected for the dropdown AND has terms
                                            if (in_array($slug, $selectedDropdownTaxonomies) && !empty($termNamesToDisplay)) {
                                                $taxonomyName = $allTaxonomies[$slug] ?? $slug; // Get display name from settings or use slug
                                                $dropdownTaxonomyTextParts[] = esc_html($taxonomyName) . ': ' . esc_html(implode(', ', $termNamesToDisplay));
                                            }
                                        }

                                        // Build the final dropdown option text using the format pattern
                                        $optionText = $dropdownFormat;
                                        $placeholder_values['[taxonomies]'] = implode(', ', $dropdownTaxonomyTextParts); // Set value for [taxonomies] placeholder

                                        // Replace all placeholders in the format string
                                        $optionText = str_replace(array_keys($placeholder_values), array_values($placeholder_values), $optionText);

                                        // Store date-specific info in data attributes (for JS below the dropdown)
                                        $dataAttributes = ' data-date-display="' . esc_attr($courseDateDisplayString) . '"';
                                        foreach($dateSpecificTaxonomyData as $taxSlug => $terms) { // Iterate using the actual registered slug
                                            $dataAttributes .= ' data-taxonomy-' . esc_attr($taxSlug) . '="' . esc_attr(implode(', ', $terms)) . '"';
                                        }

                                        ?>
                                        <option value="<?php echo esc_attr($index); ?>" <?php selected($preSelectedCourseDateIndex, $index); ?> <?php echo $dataAttributes; ?>>
                                            <?php echo $optionText; ?>
                                        </option>
                                    <?php endif; // Close the availability check ?>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($initialCourseDateValid && !$isAvailable): // Show message if the initially selected course date is no longer available ?>
                                <p class="error">Kursdatoen du valgte fra listen er dessverre full. Vennligst velg en annen kursdato.</p>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <div id="cm-selected-course-date-info">
                        <h4>Valgt kursdato: <span id="cm-selected-date-display"></span></h4>
                        <div id="cm-selected-taxonomy-info">
                            <?php $allTaxonomies = get_option('course_manager_taxonomies', []); ?>
                            <?php $selectedDropdownTaxonomies = get_option('course_manager_dropdown_taxonomies', []); // Get selected taxonomies for display ?>

                            <?php foreach ($selectedDropdownTaxonomies as $slug): // Iterate only through selected dropdown taxonomies ?>
                                <?php if (array_key_exists($slug, $allTaxonomies)): // Ensure the slug is a registered plugin taxonomy ?>
                                    <p><strong><?php echo esc_html($allTaxonomies[$slug]); ?>:</strong> <span id="cm-selected-taxonomy-<?php echo esc_attr($slug); ?>"></span></p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

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
                                <label for="cm_postal_code">Postnummer</label>
                                <input type="text" name="cm_postal_code" id="cm_postal_code"
                                       placeholder="1234">
                            </div>
                            <div class="cm-address-field">
                                <label for="cm_city">Poststed</label>
                                <input type="text" name="cm_city" id="cm_city" placeholder="Oslo">
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
                        <p class="cm-participant-info">Minst én deltaker må meldes på.</p>
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
     * Complete an enrollment by saving it to the database and sending emails.
     *
     * @param array $enrollmentData The enrollment data to save.
     * @return int|bool The enrollment ID on success, false on failure.
     */
    private function completeEnrollment(array $enrollmentData) {
        $courseId = $enrollmentData['course_id'];
        $courseDateIndex = $enrollmentData['course_date_index'];
        $buyerName = $enrollmentData['buyer_name'];
        $buyerEmail = $enrollmentData['buyer_email'];
        $buyerPhone = $enrollmentData['buyer_phone'];
        $buyerStreetAddress = $enrollmentData['buyer_street_address'];
        $buyerPostalCode = $enrollmentData['buyer_postal_code'];
        $buyerCity = $enrollmentData['buyer_city'];
        $buyerComments = $enrollmentData['buyer_comments'];
        $buyerCompany = $enrollmentData['buyer_company'];
        $participants = $enrollmentData['participants'];
        $totalPrice = $enrollmentData['total_price'];

        // Get course date details for email and enrollment title
        $courseDates = get_post_meta($courseId, '_course_dates', true);
        $selectedCourseDate = $courseDates[$courseDateIndex] ?? null;
        $courseDateDisplayString = $selectedCourseDate ? DateFormatter::formatCourseDateDisplay($selectedCourseDate) : 'Ukjent kursdato';


        $enrollmentId = wp_insert_post([
            'post_type' => 'course_enrollment',
            'post_title' => sprintf(
                'Påmelding til %s (%s) av %d deltakere (bestilt av %s)',
                get_the_title($courseId),
                $courseDateDisplayString,
                count($participants),
                $buyerName
            ),
            'post_status' => 'publish',
        ]);

        if ($enrollmentId) {
            update_post_meta($enrollmentId, 'cm_course_id', $courseId);
            update_post_meta($enrollmentId, 'cm_course_date_index', $courseDateIndex);
            update_post_meta($enrollmentId, 'cm_buyer_name', $buyerName);
            update_post_meta($enrollmentId, 'cm_buyer_email', $buyerEmail);
            update_post_meta($enrollmentId, 'cm_buyer_phone', $buyerPhone);
            update_post_meta($enrollmentId, 'cm_buyer_street_address', $buyerStreetAddress);
            update_post_meta($enrollmentId, 'cm_buyer_postal_code', $buyerPostalCode);
            update_post_meta($enrollmentId, 'cm_buyer_city', $buyerCity);
            update_post_meta($enrollmentId, 'cm_buyer_comments', $buyerComments);
            update_post_meta($enrollmentId, 'cm_buyer_company', $buyerCompany);
            update_post_meta($enrollmentId, 'cm_participants', $participants);
            update_post_meta($enrollmentId, 'cm_total_price', $totalPrice);

            $enableEmails = get_option('course_manager_enable_emails', true);
            if ($enableEmails) {
                $templateVars = [
                    'buyer_name' => $buyerName,
                    'course_title' => get_the_title($courseId),
                    'participant_count' => count($participants),
                    'total_price' => $totalPrice,
                    'participants' => is_array($participants) ? implode("\n", array_map(function ($participant) {
                        return "- " . ($participant['name'] ?? '');
                    }, $participants)) : '',
                    'course_date_date' => $selectedCourseDate ? DateFormatter::formatCourseDateDate($selectedCourseDate) : 'Ukjent dato',
                    'course_date_time' => $selectedCourseDate ? DateFormatter::formatCourseDateTime($selectedCourseDate) : 'Ukjent tid',
                ];

                $subject = 'Bekreftelse på kurspåmelding: [course_title] ([course_date_date])';
                $customMessage = get_post_meta($courseId, '_course_custom_email_message', true);
                $defaultMessage = get_option(
                    'course_manager_default_email_message',
                    "Hei [buyer_name],\n\nTakk for at du meldte deg på [course_title] den [course_date_date] kl [course_date_time]! Vi gleder oss til å se deg.\n\nAntall deltakere: [participant_count]\nTotal pris: [total_price] NOK\n\nDeltakere:\n[participants]\n\nBeste hilsener,\nKursadministrator-teamet"
                );
                $message = !empty($customMessage) ? $customMessage : $defaultMessage;

                $message = $this->parseEmailTemplate($message, $templateVars);
                wp_mail($buyerEmail, $subject, $message);

                $adminEmail = get_option('course_manager_admin_email');
                if (!empty($adminEmail) && is_email($adminEmail)) {
                    $adminSubject = 'Ny påmelding til [course_title] ([course_date_date])';
                    $adminMessage = get_option(
                        'course_manager_admin_email_message',
                        "Hei,\n\nEn ny påmelding har blitt registrert for kurset \"[course_title]\" den [course_date_date] kl [course_date_time].\n\nBestillerinformasjon:\n- Navn: [buyer_name]\n- E-post: [buyer_email]\n- Telefonnummer: [buyer_phone]\n- Firma: [buyer_company]\n- Gateadresse: [buyer_street_address]\n- Postnummer: [buyer_postal_code]\n- Poststed: [buyer_city]\n- Kommentarer/spørsmål: [buyer_comments]\n\nDeltakere ([participant_count]):\n[participants]\n\nTotal pris: [total_price] NOK\n\nBeste hilsener,\nKursadministrator-systemet"
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

        if (is_array($enrollmentData) && !empty($enrollmentData)) {
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
        if (is_array($enrollmentData) && !empty($enrollmentData) && isset($enrollmentData['course_id'])) {
            return add_query_arg('payment_success', '1', get_permalink($enrollmentData['course_id']));
        }
        return $return_url;
    }
}
