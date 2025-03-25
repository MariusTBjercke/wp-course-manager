<?php

namespace CourseManager\Frontend;

/**
 * Shortcode class.
 */
class Shortcode {
    public function register(): void
    {
        add_shortcode('course_manager', [$this, 'render']);
    }

    public function render(): string
    {
        $courses = get_posts([
            'post_type' => 'course',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);

        ob_start();
        ?>
        <div class="cm-course-list">
            <?php foreach ($courses as $course): ?>
                <div class="cm-course-item">
                    <h3><?php echo esc_html($course->post_title); ?></h3>
                    <p><?php echo wp_trim_words($course->post_content, 30); ?></p>
                    <a href="<?php echo get_permalink($course->ID); ?>">View course</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}