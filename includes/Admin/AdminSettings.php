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
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
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
        register_setting('course_manager_settings', 'course_manager_items_per_page', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 10
        ]);

        register_setting('course_manager_settings', 'course_manager_taxonomies', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeTaxonomies'],
            'default' => []
        ]);

        // Add settings fields
        add_settings_field(
            'course_manager_items_per_page',
            'Antall kurs per side',
            [$this, 'renderItemsPerPageField'],
            'course_manager_settings',
            'course_manager_general_section'
        );

        add_settings_field(
            'course_manager_taxonomies',
            'Taksonomier',
            [$this, 'renderTaxonomiesField'],
            'course_manager_settings',
            'course_manager_general_section'
        );
    }

    /**
     * Sanitize taxonomies.
     *
     * @param array|null $input The input to sanitize.
     * @return array Sanitized taxonomies as an associative array.
     */
    public function sanitizeTaxonomies(?array $input): array {
        $sanitized = [];
        if (!is_array($input)) {
            return $sanitized;
        }

        foreach ($input as $slug => $name) {
            $sanitizedSlug = sanitize_key($slug);
            $sanitizedName = sanitize_text_field($name);
            if (!empty($sanitizedSlug) && !empty($sanitizedName)) {
                $sanitized[$sanitizedSlug] = $sanitizedName;
            }
        }
        return $sanitized;
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

    /**
     * Render the taxonomies field.
     */
    public function renderTaxonomiesField(): void {
        $taxonomies = get_option('course_manager_taxonomies', []);
        if (empty($taxonomies)) {
            $taxonomies = ['taxonomy_1' => ''];
        }
        ?>
        <div id="taxonomies-wrapper">
            <?php
            foreach ($taxonomies as $slug => $name): ?>
                <div class="taxonomy-row">
                    <input type="text" name="course_manager_taxonomies[<?php
                    echo esc_attr($slug); ?>]" value="<?php
                    echo esc_attr($name); ?>" placeholder="Navn på taksonomi (f.eks. Steder, Kategorier, Typer)"/>
                    <button type="button" class="button remove-taxonomy">Fjern</button>
                </div>
            <?php
            endforeach; ?>
        </div>
        <button type="button" id="add-taxonomy" class="button">Legg til ny taksonomi</button>
        <p class="description">Legg til eller fjern taksonomier som kan brukes til å filtrere kurs. Eksempler: "Steder",
            "Kategorier", "Typer". Skriv navnet i flertallsform hvis det skal vises slik i menyen (f.eks. "Typer" i
            stedet for "Type").</p>

        <script>
          document.getElementById('add-taxonomy').addEventListener('click', function () {
            const wrapper = document.getElementById('taxonomies-wrapper');
            const newRow = document.createElement('div');
            newRow.className = 'taxonomy-row';
            const timestamp = Date.now();
            newRow.innerHTML = `
                    <input type="text" name="course_manager_taxonomies[custom_${timestamp}]" placeholder="Navn på taksonomi"/>
                    <button type="button" class="button remove-taxonomy">Fjern</button>
                `;
            wrapper.appendChild(newRow);
          });

          document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-taxonomy')) {
              const rows = document.querySelectorAll('#taxonomies-wrapper .taxonomy-row');
              if (rows.length > 1) {
                e.target.parentElement.remove();
              }
            }
          });
        </script>
        <?php
    }

    /**
     * Enqueue admin styles.
     *
     * @param string $hook The name of the action to add the callback to.
     */
    public function enqueueAdminStyles(string $hook): void {
        // Only enqueue styles for the course manager settings page.
        if ($hook !== 'course_page_course_manager_settings') {
            return;
        }

        wp_enqueue_style(
            'course-manager-admin-style',
            plugin_dir_url(__DIR__) . '../dist/admin.css',
            [],
            filemtime(plugin_dir_path(__DIR__) . '../dist/admin.css')
        );
    }
}