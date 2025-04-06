interface FilterElements {
  filterButton: HTMLButtonElement | null;
  filterSelects: NodeListOf<HTMLSelectElement> | null;
}

export default class CourseManager {
  private elements: FilterElements = {
    filterButton: null,
    filterSelects: null
  };

  constructor() {
    this.init();
  }

  /**
   * Initialize the CourseManager.
   */
  private init(): void {
    document.addEventListener('DOMContentLoaded', () => {
      this.findElements();
      this.bindEvents();
    });
  }

  /**
   * Find and store filter elements.
   */
  private findElements(): void {
    this.elements.filterButton = document.querySelector('.cm-filter-button');
    this.elements.filterSelects = document.querySelectorAll('.cm-filter-group select[id]');
  }

  /**
   * Bind events to filter elements.
   */
  private bindEvents(): void {
    const {filterSelects} = this.elements;

    if (filterSelects) {
      filterSelects.forEach(select => {
        select.addEventListener('change', () => this.handleAutoSubmit());
      });
    }

    const form = document.querySelector('.cm-enrollment-form form') as HTMLFormElement;
    if (form) {
      form.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }
  }

  /**
   * Handle auto-submit of the form when filters change.
   */
  private handleAutoSubmit(): void {
    const form = document.querySelector('.cm-filters form') as HTMLFormElement;
    if (form) {
      form.submit();
    }
  }

  /**
   * Handle form submission with confirmation prompt.
   *
   * @param e The form submission event.
   */
  private handleFormSubmit(e: Event): void {
    const buyerName = (document.getElementById('cm_buyer_name') as HTMLInputElement)?.value;
    if (buyerName && !confirm(`Er du sikker på at du vil melde deg på som ${buyerName}?`)) {
      e.preventDefault();
    }
  }
}