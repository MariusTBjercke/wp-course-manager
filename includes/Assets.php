<?php

namespace CourseManager;

/**
 * Assets class.
 * Handles enqueuing scripts and styles.
 */
class Assets {
    /**
     * Enqueue the assets.
     * This method is called for both frontend and admin.
     * We enqueue frontend assets on wp_enqueue_scripts and admin assets on admin_enqueue_scripts.
     *
     * @return void
     */
    public function enqueue(): void {
        // Frontend assets
        if (!is_admin()) {
            $pluginDirUrl = plugin_dir_url(__DIR__);
            $pluginDirPath = plugin_dir_path(__DIR__);

            wp_enqueue_style(
                'course-manager-style',
                $pluginDirUrl . 'dist/style.css',
                [],
                filemtime($pluginDirPath . 'dist/style.css')
            );

            wp_enqueue_script(
                'course-manager-script',
                $pluginDirUrl . 'dist/script.js',
                [],
                filemtime($pluginDirPath . 'dist/script.js'),
                true
            );
        }
    }

    /**
     * Enqueue admin assets.
     * This method is called specifically for the admin area.
     *
     * @return void
     */
    public function enqueueAdmin(): void {
        if (is_admin()) {
            $pluginDirUrl = plugin_dir_url(__DIR__);
            $pluginDirPath = plugin_dir_path(__DIR__);

            // Enqueue admin CSS
            if (file_exists($pluginDirPath . 'dist/admin-style.css')) {
                wp_enqueue_style(
                    'course-manager-admin-style',
                    $pluginDirUrl . 'dist/admin-style.css',
                    [],
                    filemtime($pluginDirPath . 'dist/admin-style.css')
                );
            }

            // Enqueue admin JavaScript
            if (file_exists($pluginDirPath . 'dist/admin-script.js')) {
                wp_enqueue_script(
                    'course-manager-admin-script',
                    $pluginDirUrl . 'dist/admin-script.js',
                    [],
                    filemtime($pluginDirPath . 'dist/admin-script.js'),
                    true
                );
            }
        }
    }
}
