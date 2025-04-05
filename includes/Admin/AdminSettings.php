<?php

namespace CourseManager\Admin;

/**
 * Admin settings class.
 */
class AdminSettings {
    /**
     * Register admin hooks.
     */
    public function register(): void {
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Add menu pages.
     */
    public function addMenuPages(): void {
        add_submenu_page(
            'edit.php?post_type=course',
            'Kursinnstillinger',
            'Innstillinger',
            'manage_options',
            'course_manager_settings',
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Register settings.
     */
    public function registerSettings(): void {
        // Register a setting section
        add_settings_section(
            'course_manager_general_section',
            'Generelle innstillinger',
            [$this, 'renderGeneralSection'],
            'course_manager_settings'
        );

        // Register individual settings
        register_setting('course_manager_settings', 'course_manager_items_per_page');

        add_settings_field(
            'course_manager_items_per_page',
            'Antall kurs per side',
            [$this, 'renderItemsPerPageField'],
            'course_manager_settings',
            'course_manager_general_section'
        );
    }

    /**
     * Render the settings page.
     */
    public function renderSettingsPage(): void {
        ?>
        <div class="wrap">
            <h1>Kursinnstillinger</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?post_type=course&page=course_manager_settings" class="nav-tab nav-tab-active">Generelt</a>
                <a href="<?php
                echo admin_url('edit-tags.php?taxonomy=course_location&post_type=course'); ?>"
                   class="nav-tab">Steder</a>
                <a href="<?php
                echo admin_url('edit-tags.php?taxonomy=course_category&post_type=course'); ?>" class="nav-tab">Kategorier</a>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('course_manager_settings');
                do_settings_sections('course_manager_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the general section.
     */
    public function renderGeneralSection(): void {
        echo '<p>Generelle innstillinger for Course Manager.</p>';
    }

    /**
     * Render the items per page field.
     */
    public function renderItemsPerPageField(): void {
        $value = get_option('course_manager_items_per_page', 10);
        ?>
        <input type="number" name="course_manager_items_per_page" value="<?php
        echo esc_attr($value); ?>" min="1" max="100"/>
        <p class="description">Antall kurs som vises per side i kurslisten.</p>
        <?php
    }
}