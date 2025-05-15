/**
 * Handles the dynamic adding and removing of taxonomy fields in the admin settings.
 */
export default class AdminTaxonomyManager {
  private wrapper: HTMLElement | null;
  private addButton: HTMLButtonElement | null;

  constructor() {
    this.wrapper = document.getElementById('taxonomies-wrapper');
    this.addButton = document.getElementById('add-taxonomy') as HTMLButtonElement;

    if (this.wrapper && this.addButton) {
      this.bindEvents();
    } else {
      console.warn('Admin Taxonomy Manager: Required elements not found.');
      if (!this.wrapper) console.warn('  - #taxonomies-wrapper not found');
      if (!this.addButton) console.warn('  - #add-taxonomy not found');
    }
  }

  /**
   * Binds click events to the add and remove buttons.
   */
  private bindEvents(): void {
    this.addButton?.addEventListener('click', this.addTaxonomyField.bind(this));
    this.wrapper?.addEventListener('click', this.handleRemoveTaxonomyField.bind(this));
  }

  /**
   * Adds a new taxonomy input field.
   */
  private addTaxonomyField(): void {
    if (!this.wrapper) return;

    const newRow = document.createElement('div');
    newRow.className = 'taxonomy-row';
    const timestamp = Date.now();
    // Use a generic placeholder slug for new fields; the actual slug will be generated/sanitized on save
    newRow.innerHTML = `
            <input type="text" name="course_manager_taxonomies[new_${timestamp}]" placeholder="Navn på taksonomi"/>
            <button type="button" class="button remove-taxonomy">Fjern</button>
        `;
    this.wrapper.appendChild(newRow);
  }

  /**
   * Handles the click event for removing a taxonomy field.
   *
   * @param event The click event.
   */
  private handleRemoveTaxonomyField(event: Event): void {
    const target = event.target as HTMLElement;
    if (target && target.classList.contains('remove-taxonomy')) {
      const rows = this.wrapper?.querySelectorAll('.taxonomy-row');
      if (rows && rows.length > 1) {
        target.closest('.taxonomy-row')?.remove();
      } else {
        // Optionally provide feedback that at least one taxonomy is required
        // alert('You must have at least one taxonomy.');
      }
    }
  }
}
