interface FilterElements {
  filterButton: HTMLButtonElement | null;
  filterSelects: NodeListOf<HTMLSelectElement> | null;
}

export default class CourseManager {
  private elements: FilterElements = {
    filterButton: null,
    filterSelects: null
  };

  private participantCount: number = 0;

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

      // Automatically add the first participant on page load
      this.addParticipant();
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

    const addParticipantButton = document.getElementById('cm-add-participant') as HTMLButtonElement;
    if (addParticipantButton) {
      addParticipantButton.addEventListener('click', () => this.addParticipant());
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

  /**
   * Add a new participant entry to the form.
   */
  private addParticipant(): void {
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
        <label for="cm_participant_birthdate_${index}">Fødselsdato (blir lagret som dd.mm.åååå)</label>
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