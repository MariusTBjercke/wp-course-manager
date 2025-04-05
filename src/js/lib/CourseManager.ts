interface FilterElements {
  locationSelect: HTMLSelectElement | null;
  categorySelect: HTMLSelectElement | null;
  filterButton: HTMLButtonElement | null;
}

export default class CourseManager {
  private elements: FilterElements = {
    locationSelect: null,
    categorySelect: null,
    filterButton: null
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
    this.elements.locationSelect = document.querySelector('#course_location');
    this.elements.categorySelect = document.querySelector('#course_category');
    this.elements.filterButton = document.querySelector('.cm-filter-button');
  }

  /**
   * Bind events to filter elements.
   */
  private bindEvents(): void {
    const { locationSelect, categorySelect } = this.elements;

    // Add change listeners to selects for auto-submit
    if (locationSelect) {
      locationSelect.addEventListener('change', () => this.handleAutoSubmit());
    }

    if (categorySelect) {
      categorySelect.addEventListener('change', () => this.handleAutoSubmit());
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