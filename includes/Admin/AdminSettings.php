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

        register_setting('course_manager_settings', 'course_manager_default_email_message', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => "Hei [buyer_name],\n\nTakk for at du meldte deg på [course_title]! Vi gleder oss til å se deg.\n\nAntall deltakere: [participant_count]\nTotal pris: [total_price] NOK\n\nDeltakere:\n[participants]\n\nBeste hilsener,\nKursadministrator-teamet"
        ]);

        register_setting('course_manager_settings', 'course_manager_admin_email', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default' => get_option('admin_email')
        ]);

        register_setting('course_manager_settings', 'course_manager_admin_email_message', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => "Hei,\n\nEn ny påmelding har blitt registrert for kurset \"[course_title]\".\n\nBestillerinformasjon:\n- Navn: [buyer_name]\n- E-post: [buyer_email]\n- Telefonnummer: [buyer_phone]\n- Firma: [buyer_company]\n- Gateadresse: [buyer_street_address]\n- Postnummer: [buyer_postal_code]\n- Poststed: [buyer_city]\n- Kommentarer/spørsmål: [buyer_comments]\n\nDeltakere ([participant_count]):\n[participants]\n\nTotal pris: [total_price] NOK\n\nBeste hilsener,\nKursadministrator-systemet"
        ]);

        register_setting('course_manager_settings', 'course_manager_vipps_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
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

        add_settings_field(
            'course_manager_default_email_message',
            'Standard e-postbekreftelse til kunde',
            [$this, 'renderDefaultEmailMessageField'],
            'course_manager_settings',
            'course_manager_general_section'
        );

        add_settings_field(
            'course_manager_admin_email',
            'E-postadresse for administratorvarsler',
            [$this, 'renderAdminEmailField'],
            'course_manager_settings',
            'course_manager_general_section'
        );

        add_settings_field(
            'course_manager_admin_email_message',
            'Standard e-postvarsel til administrator',
            [$this, 'renderAdminEmailMessageField'],
            'course_manager_settings',
            'course_manager_general_section'
        );

        add_settings_field(
            'course_manager_vipps_api_key',
            'Vipps API-nøkkel',
            [$this, 'renderVippsApiKeyField'],
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
     * Render the default email message field for customers.
     */
    public function renderDefaultEmailMessageField(): void {
        $value = get_option('course_manager_default_email_message', "Hei [buyer_name],\n\nTakk for at du meldte deg på [course_title]! Vi gleder oss til å se deg.\n\nAntall deltakere: [participant_count]\nTotal pris: [total_price] NOK\n\nDeltakere:\n[participants]\n\nBeste hilsener,\nKursadministrator-teamet");
        ?>
        <textarea name="course_manager_default_email_message" rows="5" cols="50"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Standard melding som sendes til kunder ved påmelding. Bruk følgende tagger for å inkludere variabler:<br>
            - [buyer_name]: Navnet på bestilleren<br>
            - [course_title]: Tittelen på kurset<br>
            - [participant_count]: Antall deltakere<br>
            - [total_price]: Total pris i NOK<br>
            - [participants]: Liste over deltakernavn (en per linje, med "- " foran)<br>
            Eksempel: "Hei [buyer_name], takk for at du meldte deg på [course_title]!"
        </p>
        <?php
    }

    /**
     * Render the admin email field.
     */
    public function renderAdminEmailField(): void {
        $value = get_option('course_manager_admin_email', get_option('admin_email'));
        ?>
        <input type="email" name="course_manager_admin_email" value="<?php echo esc_attr($value); ?>" style="width: 300px;" />
        <p class="description">E-postadressen som skal motta varsler om nye påmeldinger. La stå tomt for å deaktivere varsler.</p>
        <?php
    }

    /**
     * Render the admin email message field.
     */
    public function renderAdminEmailMessageField(): void {
        $value = get_option('course_manager_admin_email_message', "Hei,\n\nEn ny påmelding har blitt registrert for kurset \"[course_title]\".\n\nBestillerinformasjon:\n- Navn: [buyer_name]\n- E-post: [buyer_email]\n- Telefonnummer: [buyer_phone]\n- Firma: [buyer_company]\n- Gateadresse: [buyer_street_address]\n- Postnummer: [buyer_postal_code]\n- Poststed: [buyer_city]\n- Kommentarer/spørsmål: [buyer_comments]\n\nDeltakere ([participant_count]):\n[participants]\n\nTotal pris: [total_price] NOK\n\nBeste hilsener,\nKursadministrator-systemet");
        ?>
        <textarea name="course_manager_admin_email_message" rows="5" cols="50"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Standard melding som sendes til administratoren ved nye påmeldinger. Bruk følgende tagger for å inkludere variabler:<br>
            - [buyer_name]: Navnet på bestilleren<br>
            - [buyer_email]: E-postadressen til bestilleren<br>
            - [buyer_phone]: Telefonnummeret til bestilleren<br>
            - [buyer_company]: Firmanavnet til bestilleren<br>
            - [buyer_street_address]: Gateadressen til bestilleren<br>
            - [buyer_postal_code]: Postnummeret til bestilleren<br>
            - [buyer_city]: Poststedet til bestilleren<br>
            - [buyer_comments]: Kommentarer/spørsmål fra bestilleren<br>
            - [course_title]: Tittelen på kurset<br>
            - [participant_count]: Antall deltakere<br>
            - [total_price]: Total pris i NOK<br>
            - [participants]: Liste over deltakernavn (en per linje, med "- " foran)<br>
            Eksempel: "Ny påmelding til [course_title] fra [buyer_name]."
        </p>
        <?php
    }

    /**
     * Render the Vipps API key field.
     */
    public function renderVippsApiKeyField(): void {
        $value = get_option('course_manager_vipps_api_key', '');
        ?>
        <input type="text" name="course_manager_vipps_api_key" value="<?php echo esc_attr($value); ?>" style="width: 300px;" />
        <p class="description">API-nøkkel for Vipps-integrering. La stå tomt for å deaktivere betaling via Vipps.</p>
        <?php
    }

    /**
     * Enqueue admin styles.
     *
     * @param string $hook The name of the action to add the callback to.
     */
    public function enqueueAdminStyles(string $hook): void {
        // Enqueue styles for the course manager settings page and enrollment edit screens
        if ($hook === 'course_page_course_manager_settings' || get_current_screen()->post_type === 'course_enrollment') {
            wp_enqueue_style(
                'course-manager-admin-style',
                plugin_dir_url(__DIR__) . '../dist/admin.css',
                [],
                filemtime(plugin_dir_path(__DIR__) . '../dist/admin.css')
            );
        }
    }
}