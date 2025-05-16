interface FilterElements {
  filterButton: HTMLButtonElement | null;
  resetButton: HTMLButtonElement | null;
  filterToggles: NodeListOf<HTMLButtonElement> | null;
}

export default class CourseManager {
  private elements: FilterElements = {
    filterButton: null,
    resetButton: null,
    filterToggles: null
  };

  private participantCount: number = 0;

  constructor() {
    this.init();
  }

  /**
   * Initialize the CourseManager.
   */
  private init(): void {
    this.findElements();
    this.bindEvents();

    // Automatically add the first participant on page load (for enrollment form)
    if (document.querySelector('.cm-enrollment-form')) {
      this.addParticipant();
    }
  }

  /**
   * Find and store filter elements.
   */
  private findElements(): void {
    this.elements.filterButton = document.querySelector('.cm-filter-button');
    this.elements.resetButton = document.querySelector('.cm-reset-button');
    this.elements.filterToggles = document.querySelectorAll('.cm-filter-toggle');
  }

  /**
   * Update the toggle button text based on the number of selected checkboxes.
   *
   * @param toggle The toggle button element.
   */
  private updateToggleText(toggle: HTMLButtonElement): void {
    const optionsContainer = toggle.nextElementSibling as HTMLElement;
    const checkboxes = optionsContainer.querySelectorAll('input[type="checkbox"]') as NodeListOf<HTMLInputElement>;
    const checkedCount = Array.from(checkboxes).filter(checkbox => checkbox.checked).length;
    const taxonomyName = toggle.textContent?.split(' (')[0] || 'Filter'; // Extract the taxonomy name
    toggle.textContent = `${taxonomyName} (${checkedCount === 0 ? 'Alle' : checkedCount} valgt)`;
  }

  /**
   * Bind events to filter elements.
   */
  private bindEvents(): void {
    console.log('Binding events...');
    const { filterButton, resetButton, filterToggles } = this.elements;

    // Toggle dropdown visibility
    if (filterToggles) {
      filterToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
          const options = toggle.nextElementSibling as HTMLElement;
          const isVisible = options.style.display === 'block';
          // Close all other dropdowns
          document.querySelectorAll('.cm-filter-options').forEach(opt => {
            (opt as HTMLElement).style.display = 'none';
          });
          // Toggle the current dropdown
          options.style.display = isVisible ? 'none' : 'block';
        });

        // Initialize toggle text on page load
        this.updateToggleText(toggle);
      });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
      const target = e.target as HTMLElement;
      if (!target.closest('.cm-filter-dropdown')) {
        document.querySelectorAll('.cm-filter-options').forEach(opt => {
          (opt as HTMLElement).style.display = 'none';
        });
      }
    });

    // Update toggle text on checkbox change
    const checkboxes = document.querySelectorAll('.cm-filter-option input[type="checkbox"]') as NodeListOf<HTMLInputElement>;
    if (checkboxes) {
      checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
          const toggle = checkbox.closest('.cm-filter-dropdown')?.querySelector('.cm-filter-toggle') as HTMLButtonElement;
          if (toggle) {
            this.updateToggleText(toggle);
          }
        });
      });
    }

    // Reset button functionality
    if (resetButton) {
      resetButton.addEventListener('click', () => {
        const form = document.querySelector('.cm-filters form') as HTMLFormElement;
        if (form) {
          // Clear search input
          const searchInput = form.querySelector('#course_search') as HTMLInputElement;
          if (searchInput) searchInput.value = '';
          // Clear date inputs
          const startDateInput = form.querySelector('#start_date') as HTMLInputElement;
          const endDateInput = form.querySelector('#end_date') as HTMLInputElement;
          if (startDateInput) startDateInput.value = '';
          if (endDateInput) endDateInput.value = '';
          // Uncheck all checkboxes
          const checkboxes = form.querySelectorAll('input[type="checkbox"]') as NodeListOf<HTMLInputElement>;
          checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            const toggle = checkbox.closest('.cm-filter-dropdown')?.querySelector('.cm-filter-toggle') as HTMLButtonElement;
            if (toggle) {
              this.updateToggleText(toggle);
            }
          });
          form.submit();
        }
      });
    }

    const form = document.querySelector('.cm-enrollment-form form') as HTMLFormElement;
    if (form) {
      form.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }

    const addParticipantButton = document.getElementById('cm-add-participant') as HTMLButtonElement;
    if (addParticipantButton) {
      addParticipantButton.addEventListener('click', () => this.addParticipant());
    }
  }

  /**
   * Handle form submission with confirmation prompt.
   *
   * @param e The form submission event.
   */
  private handleFormSubmit(e: Event): void {
    if (!confirm(`Er du sikker på at du vil fortsette med påmeldingen?`)) {
      e.preventDefault();
    }
  }

  /**
   * Add a new participant entry to the form.
   */
  private addParticipant(): void {
    console.log('Adding participant...');
    const participantList = document.getElementById('cm-participant-list') as HTMLDivElement;
    const index = this.participantCount;
    const participantNumber = index + 1;

    const participantEntry = document.createElement('div');
    participantEntry.classList.add('cm-participant-entry');
    participantEntry.innerHTML = `
      <div class="cm-participant-number">Deltaker ${participantNumber}</div>
      <div class="cm-form-field">
        <label for="cm_participant_name_${index}">Navn <span class="required">*</span></label>
        <input type="text" name="cm_participant_name[${index}]" id="cm_participant_name_${index}" required placeholder="Kari Nordmann">
      </div>
      <div class="cm-form-field">
        <label for="cm_participant_email_${index}">E-post <span class="required">*</span></label>
        <input type="email" name="cm_participant_email[${index}]" id="cm_participant_email_${index}" required placeholder="kari@eksempel.no">
      </div>
      <div class="cm-form-field">
        <label for="cm_participant_phone_${index}">Telefonnummer</label>
        <input type="tel" name="cm_participant_phone[${index}]" id="cm_participant_phone_${index}" placeholder="87654321">
      </div>
      <div class="cm-form-field">
        <label for="cm_participant_birthdate_${index}">Fødselsdato</label>
        <input type="date" name="cm_participant_birthdate[${index}]" id="cm_participant_birthdate_${index}">
      </div>
      <button type="button" class="cm-remove-participant" title="Fjern deltaker">X</button>
    `;

    participantList.appendChild(participantEntry);

    // Bind remove event
    const removeButton = participantEntry.querySelector('.cm-remove-participant') as HTMLButtonElement;
    removeButton.addEventListener('click', () => this.removeParticipant(participantEntry));

    this.participantCount++;
    this.updateParticipantCount();
  }

  /**
   * Remove a participant entry from the form.
   *
   * @param entry The participant entry element to remove.
   */
  private removeParticipant(entry: HTMLElement): void {
    entry.remove();
    this.participantCount--;
    this.updateParticipantCount();

    // Re-number remaining participants
    const remainingEntries = document.querySelectorAll('.cm-participant-entry');
    remainingEntries.forEach((entry, index) => {
      const numberElement = entry.querySelector('.cm-participant-number') as HTMLElement;
      numberElement.textContent = `Deltaker ${index + 1}`;
    });
  }

  /**
   * Update the participant count and total price display.
   */
  private updateParticipantCount(): void {
    const countField = document.getElementById('cm_participant_count') as HTMLInputElement;
    const countDisplay = document.getElementById('cm-participant-count') as HTMLSpanElement;
    const totalPriceDisplay = document.getElementById('cm-total-price-value') as HTMLSpanElement;
    const pricePerParticipant = parseInt((document.getElementById('cm-price-per-participant') as HTMLInputElement).value) || 0;

    countField.value = this.participantCount.toString();
    countDisplay.textContent = this.participantCount.toString();
    totalPriceDisplay.textContent = (pricePerParticipant * this.participantCount).toString();
  }
}