/**
 * Handles dynamic updates on the enrollment form based on selected course date.
 */
export default class EnrollmentFormHandler {
  private courseDateSelect: HTMLSelectElement | null;
  private selectedDateDisplay: HTMLElement | null;
  private selectedTaxonomyInfo: HTMLElement | null;
  private taxonomySlugs: string[] = [];

  constructor() {
    this.courseDateSelect = document.getElementById('cm_course_date') as HTMLSelectElement;
    this.selectedDateDisplay = document.getElementById('cm-selected-date-display');
    this.selectedTaxonomyInfo = document.getElementById('cm-selected-taxonomy-info');

    if (this.courseDateSelect && this.selectedDateDisplay && this.selectedTaxonomyInfo) {
      this.taxonomySlugs = this.getTaxonomySlugs(); // Get taxonomy slugs from the HTML structure
      this.bindEvents();
      this.updateDisplayedInfo();
    } else {
      console.warn('Enrollment Form Handler: Required elements not found.');
      if (!this.courseDateSelect) console.warn('  - #cm_course_date not found');
      if (!this.selectedDateDisplay) console.warn('  - #cm-selected-date-display not found');
      if (!this.selectedTaxonomyInfo) console.warn('  - #cm-selected-taxonomy-info not found');
    }
  }

  /**
   * Binds event listeners.
   */
  private bindEvents(): void {
    this.courseDateSelect?.addEventListener('change', this.handleDateChange.bind(this));
  }

  /**
   * Handles the change event on the course date select dropdown.
   */
  private handleDateChange(): void {
    this.updateDisplayedInfo();
  }

  /**
   * Updates the displayed date, time, and taxonomy information
   * based on the currently selected course date option.
   */
  private updateDisplayedInfo(): void {
    if (!this.courseDateSelect || !this.selectedDateDisplay || !this.selectedTaxonomyInfo) {
      return;
    }

    const selectedOption = this.courseDateSelect.options[this.courseDateSelect.selectedIndex];

    // Update date and time display
    const dateDisplay = selectedOption.dataset.dateDisplay || 'Uspesifisert dato/tid';
    this.selectedDateDisplay.textContent = dateDisplay;

    // Update taxonomy display
    this.taxonomySlugs.forEach(slug => {
      const taxonomySpan = document.getElementById(`cm-selected-taxonomy-${slug}`);
      const taxonomyParagraph = taxonomySpan?.closest('p');

      if (taxonomySpan && taxonomyParagraph) {
        // Construct the expected dataset key name from the slug
        // dataset converts data-key-name to dataset.keyName
        // Our data attributes are data-taxonomy-[sanitized_slug]
        // The dataset key will be 'taxonomy' + the sanitized slug converted from kebab-case to CamelCase.
        // Underscores in the slug are preserved in the dataset key.
        // Example: data-taxonomy-my-location -> dataset.taxonomyMyLocation
        // Example: data-taxonomy-custom_tax -> dataset.taxonomyCustom_tax

        // Split only by hyphen for dataset key conversion after 'taxonomy-'
        const slugPartsForDataset = slug.split('-');

        let datasetKey = 'taxonomy'; // Start with 'taxonomy'

        slugPartsForDataset.forEach((part, index) => {
          if (part.length > 0) {
            if (index === 0) {
              // The first part after 'taxonomy-'
              datasetKey += part.charAt(0).toUpperCase() + part.slice(1); // Capitalize first letter
            } else {
              // Subsequent parts after hyphens should also be capitalized
              datasetKey += part.charAt(0).toUpperCase() + part.slice(1);
            }
          }
        });

        // Use the constructed datasetKey to retrieve the data
        const taxonomyData = selectedOption.dataset[datasetKey];

        if (!taxonomyData || taxonomyData === 'Ikke spesifisert') {
          taxonomyParagraph.style.display = 'none';
        } else {
          taxonomyParagraph.style.display = '';
          taxonomySpan.textContent = taxonomyData;
        }
      }
    });
  }

  /**
   * Extracts taxonomy slugs from the placeholder HTML structure.
   * This assumes the structure added in renderEnrollmentForm.
   */
  private getTaxonomySlugs(): string[] {
    const slugs: string[] = [];
    if (this.selectedTaxonomyInfo) {
      this.selectedTaxonomyInfo.querySelectorAll('[id^="cm-selected-taxonomy-"]').forEach(element => {
        const id = element.id;
        const slug = id.replace('cm-selected-taxonomy-', '');
        slugs.push(slug);
      });
    }
    return slugs;
  }
}
