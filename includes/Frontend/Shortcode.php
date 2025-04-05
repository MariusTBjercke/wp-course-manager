<?php

namespace CourseManager\Frontend;

/**
 * Shortcode class.
 */
class Shortcode {
    /**
     * Register the shortcode.
     *
     * @return void
     */
    public function register(): void {
        add_shortcode('course_manager', [$this, 'render']);
    }

    /**
     * Render the course manager shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render(array $atts = []): string {
        $attributes = shortcode_atts([
            'location' => '',
            'category' => '',
            'show_filters' => 'yes',
        ], $atts);

        // Get filter values from URL if present
        $search_term = isset($_GET['course_search']) ? sanitize_text_field($_GET['course_search']) : '';
        $selected_location = isset($_GET['course_location']) ? sanitize_text_field($_GET['course_location']) : '';
        $selected_category = isset($_GET['course_category']) ? sanitize_text_field($_GET['course_category']) : '';

        // Build query arguments
        $args = [
            'post_type' => 'course',
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => -1,
            'tax_query' => []
        ];

        // Add taxonomy filters if set
        if (!empty($selected_location)) {
            $args['tax_query'][] = [
                'taxonomy' => 'course_location',
                'field' => 'slug',
                'terms' => $selected_location
            ];
        }

        if (!empty($selected_category)) {
            $args['tax_query'][] = [
                'taxonomy' => 'course_category',
                'field' => 'slug',
                'terms' => $selected_category
            ];
        }

        // Combine tax queries if needed
        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        // Get all courses matching our criteria
        $courses = get_posts($args);

        // Get all locations and categories for filters
        $locations = get_terms([
            'taxonomy' => 'course_location',
            'hide_empty' => false,
        ]);

        $categories = get_terms([
            'taxonomy' => 'course_category',
            'hide_empty' => false,
        ]);

        ob_start();
        ?>
        <div class="cm-course-manager">
            <?php
            if ($attributes['show_filters'] === 'yes'): ?>
                <div class="cm-filters">
                    <form method="get" action="<?php
                    echo esc_url(get_permalink()); ?>">
                        <div class="cm-filter-group">
                            <label for="course_search">Søk:</label>
                            <input
                                    type="text"
                                    id="course_search"
                                    name="course_search"
                                    placeholder="Søk etter kurs..."
                                    value="<?php echo esc_attr($search_term); ?>"
                            />
                        </div>

                        <div class="cm-filter-group">
                            <label for="course_location">Sted:</label>
                            <select name="course_location" id="course_location">
                                <option value="">Alle steder</option>
                                <?php
                                foreach ($locations as $location): ?>
                                    <?php
                                    $indent = '';
                                    if ($location->parent) {
                                        $indent = '&nbsp;&nbsp;';
                                    }
                                    ?>
                                    <option value="<?php
                                    echo esc_attr($location->slug); ?>" <?php
                                    selected($selected_location, $location->slug); ?>>
                                        <?php
                                        echo $indent . esc_html($location->name); ?>
                                    </option>
                                <?php
                                endforeach; ?>
                            </select>
                        </div>

                        <div class="cm-filter-group">
                            <label for="course_category">Kategori:</label>
                            <select name="course_category" id="course_category">
                                <option value="">Alle kategorier</option>
                                <?php
                                foreach ($categories as $category): ?>
                                    <option value="<?php
                                    echo esc_attr($category->slug); ?>" <?php
                                    selected($selected_category, $category->slug); ?>>
                                        <?php
                                        echo esc_html($category->name); ?>
                                    </option>
                                <?php
                                endforeach; ?>
                            </select>
                        </div>

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
                        // Get course locations and categories
                        $course_locations = get_the_terms($course->ID, 'course_location');
                        $course_categories = get_the_terms($course->ID, 'course_category');
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
                                    if ($course_locations && !is_wp_error($course_locations)): ?>
                                        <span class="cm-course-location">
                                            <strong>Sted:</strong>
                                            <?php
                                            echo esc_html(join(', ', wp_list_pluck($course_locations, 'name'))); ?>
                                        </span>
                                    <?php
                                    endif; ?>

                                    <?php
                                    if ($course_categories && !is_wp_error($course_categories)): ?>
                                        <span class="cm-course-category">
                                            <strong>Kategori:</strong>
                                            <?php
                                            echo esc_html(join(', ', wp_list_pluck($course_categories, 'name'))); ?>
                                        </span>
                                    <?php
                                    endif; ?>
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
}