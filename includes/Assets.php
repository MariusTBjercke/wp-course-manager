<?php

namespace CourseManager;

/**
 * Assets class.
 */
class Assets {
    /**
     * Enqueue the assets.
     *
     * @return void
     */
    public function enqueue(): void {
        $pluginDirUrl = plugin_dir_url(__DIR__);

        wp_enqueue_style(
            'course-manager-style',
            $pluginDirUrl . 'dist/style.css',
            [],
            filemtime(plugin_dir_path(__DIR__) . 'dist/style.css')
        );

        wp_enqueue_script(
            'course-manager-script',
            $pluginDirUrl . 'dist/script.js',
            [],
            filemtime(plugin_dir_path(__DIR__) . 'dist/script.js'),
            true
        );
    }
}