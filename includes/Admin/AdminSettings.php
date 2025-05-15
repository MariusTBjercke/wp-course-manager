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
        // Register settings group
        register_setting('course_manager_settings', 'course_manager_items_per_page', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 10
        ]);

        register_setting('course_manager_settings', 'course_manager_slider_items', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 5
        ]);

        register_setting('course_manager_settings', 'course_manager_taxonomies', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeTaxonomies'],
            'default' => []
        ]);

        register_setting('course_manager_settings', 'course_manager_dropdown_taxonomies', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeDropdownTaxonomies'],
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

        register_setting('course_manager_settings', 'course_manager_enable_emails', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        $tabs = [
            'general' => [
                'title' => 'Generelt',
                'section_id' => 'course_manager_general_section',
                'callback' => [$this, 'renderGeneralSection']
            ],
            'email' => [
                'title' => 'E-post',
                'section_id' => 'course_manager_email_section',
                'callback' => [$this, 'renderEmailSection']
            ]
        ];

        // Register sections for each tab
        foreach ($tabs as $tab_id => $tab) {
            add_settings_section(
                $tab['section_id'],
                $tab['title'],
                $tab['callback'],
                'course_manager_settings_' . $tab_id
            );
        }

        // General tab fields
        add_settings_field(
            'course_manager_items_per_page',
            'Antall kurs per side',
            [$this, 'renderItemsPerPageField'],
            'course_manager_settings_general',
            'course_manager_general_section'
        );

        add_settings_field(
            'course_manager_slider_items',
            'Antall kurs i slider',
            [$this, 'renderSliderItemsField'],
            'course_manager_settings_general',
            'course_manager_general_section'
        );

        add_settings_field(
            'course_manager_taxonomies',
            'Taksonomier',
            [$this, 'renderTaxonomiesField'],
            'course_manager_settings_general',
            'course_manager_general_section'
        );

        add_settings_field(
            'course_manager_dropdown_taxonomies',
            'Taksonomier i påmeldingsskjema (dropdown)',
            [$this, 'renderDropdownTaxonomiesField'],
            'course_manager_settings_general',
            'course_manager_general_section'
        );

        add_settings_field(
            'course_manager_enable_emails',
            'Aktiver e-poster',
            [$this, 'renderEnableEmailsField'],
            'course_manager_settings_email',
            'course_manager_email_section'
        );

        add_settings_field(
            'course_manager_default_email_message',
            'Standard e-postbekreftelse til kunde',
            [$this, 'renderDefaultEmailMessageField'],
            'course_manager_settings_email',
            'course_manager_email_section'
        );

        add_settings_field(
            'course_manager_admin_email',
            'E-postadresse for administratorvarsler',
            [$this, 'renderAdminEmailField'],
            'course_manager_settings_email',
            'course_manager_email_section'
        );

        add_settings_field(
            'course_manager_admin_email_message',
            'E-postvarsel til administrator',
            [$this, 'renderAdminEmailMessageField'],
            'course_manager_settings_email',
            'course_manager_email_section'
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
     * Sanitize dropdown taxonomies.
     *
     * @param array|null $input The input to sanitize.
     * @return array Sanitized dropdown taxonomies as an array of slugs.
     */
    public function sanitizeDropdownTaxonomies(?array $input): array {
        $sanitized = [];
        if (!is_array($input)) {
            return $sanitized;
        }

        // Get registered plugin taxonomy slugs to validate against
        $registered_taxonomies = array_keys(get_option('course_manager_taxonomies', []));

        foreach ($input as $slug) {
            $sanitizedSlug = sanitize_key($slug);
            // Only include slugs that correspond to registered plugin taxonomies
            if (!empty($sanitizedSlug) && in_array($sanitizedSlug, $registered_taxonomies)) {
                $sanitized[] = $sanitizedSlug;
            }
        }
        // Ensure uniqueness of slugs
        $sanitized = array_unique($sanitized);
        return $sanitized;
    }

    /**
     * Render the settings page.
     */
    public function renderSettingsPage(): void {
        $tabs = [
            'general' => 'Generelt',
            'email' => 'E-post'
        ];
        $activeTab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        if (!array_key_exists($activeTab, $tabs)) {
            $activeTab = 'general';
        }
        ?>
        <div class="wrap">
            <h1>Kursinnstillinger</h1>

            <?php
            if (!class_exists('WooCommerce')) {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('WooCommerce er ikke aktivert. Course Manager krever WooCommerce for å håndtere betalinger. Installer og aktiver WooCommerce for full funksjonalitet.', 'course-manager') . '</p></div>';
            }
            ?>

            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_id => $tab_name): ?>
                    <a href="?post_type=course&page=course_manager_settings&tab=<?php echo esc_attr($tab_id); ?>"
                       class="nav-tab <?php echo $activeTab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('course_manager_settings');
                do_settings_sections('course_manager_settings_' . $activeTab);
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
     * Render the email section.
     */
    public function renderEmailSection(): void {
        echo '<p>Innstillinger for e-posthåndtering i Course Manager.</p>';
    }

    /**
     * Render the items per page field.
     */
    public function renderItemsPerPageField(): void {
        $value = get_option('course_manager_items_per_page', 10);
        ?>
        <input type="number" name="course_manager_items_per_page" value="<?php
        echo esc_attr($value); ?>" min="1" max="100"/>
        <p class="description">Antall kurs som vises per side i kurslisten ([course_manager]).</p>
        <?php
    }

    /**
     * Render the slider items field.
     */
    public function renderSliderItemsField(): void {
        $value = get_option('course_manager_slider_items', 5);
        ?>
        <input type="number" name="course_manager_slider_items" value="<?php echo esc_attr($value); ?>" min="1" max="20"/>
        <p class="description">Antall kurs som vises i slideren ([course_manager_slider]).</p>
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

        <?php
    }

    /**
     * Render the dropdown taxonomies field.
     */
    public function renderDropdownTaxonomiesField(): void {
        $registered_taxonomies = get_option('course_manager_taxonomies', []);
        $selected_dropdown_taxonomies = get_option('course_manager_dropdown_taxonomies', []);

        if (empty($registered_taxonomies)) {
            echo '<p>Ingen taksonomier er registrert ennå. Vennligst legg til taksonomier ovenfor for å kunne velge hvilke som skal vises i dropdownen.</p>';
            return;
        }

        ?>
        <p class="description">Velg hvilke taksonomier som skal vises i dropdown-listen for kursdatoer på påmeldingsskjemaet.</p>
        <ul>
            <?php foreach ($registered_taxonomies as $slug => $name): ?>
                <li>
                    <label>
                        <input type="checkbox" name="course_manager_dropdown_taxonomies[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $selected_dropdown_taxonomies), true); ?> />
                        <?php echo esc_html($name); ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    /**
     * Render the enable emails field.
     */
    public function renderEnableEmailsField(): void {
        $value = get_option('course_manager_enable_emails', true);
        ?>
        <input type="checkbox" name="course_manager_enable_emails" value="1" <?php checked($value, true); ?> />
        <p class="description">Aktiver eller deaktiver sending av e-poster fra Course Manager (bekreftelser til kunder og varsler til administrator).</p>
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
}
