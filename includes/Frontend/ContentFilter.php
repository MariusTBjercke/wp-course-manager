<?php

namespace CourseManager\Frontend;

/**
 * Content filter class for adding course metadata and enrollment form to content.
 */
class ContentFilter {
    /**
     * Register content filter hooks.
     */
    public function register(): void {
        add_filter('the_content', [$this, 'appendCourseMetaAndForm']);
    }

    /**
     * Append course metadata and enrollment form to the content.
     *
     * @param string $content The original content.
     * @return string The modified content with metadata and enrollment form.
     */
    public function appendCourseMetaAndForm(string $content): string {
        if (!is_singular('course')) {
            return $content;
        }

        // Add course metadata (e.g., location, start date)
        $location = get_post_meta(get_the_ID(), 'sted', true);
        $startDate = get_post_meta(get_the_ID(), 'startdato', true);

        $metaHtml = '';
        if ($location) {
            $metaHtml .= '<p><strong>Location:</strong> ' . esc_html($location) . '</p>';
        }
        if ($startDate) {
            $metaHtml .= '<p><strong>Start Date:</strong> ' . esc_html($startDate) . '</p>';
        }

        // Check if the content already contains the enrollment form shortcode
        if (has_shortcode($content, 'course_enrollment_form')) {
            // If the shortcode is already present, don't append it again
            return $content . $metaHtml;
        }

        // Append the enrollment form shortcode
        $formHtml = do_shortcode('[course_enrollment_form]');

        return $content . $metaHtml . $formHtml;
    }
}