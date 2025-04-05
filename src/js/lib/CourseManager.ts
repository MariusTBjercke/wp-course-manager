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
    // Wait for DOM to be ready
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
    this.elements.filterSelects = document.querySelectorAll('.cm-filter-group select[id]'); // Henter alle <select> i filtergrupper
  }

  /**
   * Bind events to filter elements.
   */
  private bindEvents(): void {
    const {filterSelects} = this.elements;

    // Add change listeners to all filter selects for auto-submit
    if (filterSelects) {
      filterSelects.forEach(select => {
        select.addEventListener('change', () => this.handleAutoSubmit());
      });
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
}