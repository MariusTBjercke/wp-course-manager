/**
 * Handles the repeatable course dates meta box in the Course post type admin screen.
 */
export default class CourseDateManager {
  private wrapper: HTMLElement | null;
  private addButton: HTMLButtonElement | null;
  private template: HTMLTemplateElement | null;

  constructor() {
    this.wrapper = document.getElementById('course-dates-wrapper');
    this.addButton = document.getElementById('add-course-date') as HTMLButtonElement;
    this.template = document.getElementById('course-course-date-template') as HTMLTemplateElement;

    if (this.wrapper && this.addButton && this.template) {
      this.bindEvents();
      this.ensureMinimumCourseDate();
    } else {
      console.error('Course Date Manager: Required elements not found.');
    }
  }

  /**
   * Binds click events to the add and remove buttons.
   */
  private bindEvents(): void {
    this.addButton?.addEventListener('click', this.addCourseDate.bind(this));
    this.wrapper?.addEventListener('click', this.handleRemoveCourseDate.bind(this));
  }

  /**
   * Adds a new course date field based on the template.
   */
  private addCourseDate(): void {
    if (!this.wrapper || !this.template) return;

    const templateHtml = this.template.innerHTML;
    const index = this.wrapper.querySelectorAll('.course-course-date').length;
    const newCourseDateHtml = templateHtml
      .replace(/__INDEX__/g, index.toString())
      .replace(/__NUMBER__/g, (index + 1).toString());

    const newCourseDateElement = document.createElement('div');
    newCourseDateElement.innerHTML = newCourseDateHtml.trim();
    this.wrapper.appendChild(newCourseDateElement.firstChild as HTMLElement);

    this.updateCourseDateNumbers();
  }

  /**
   * Handles the click event for removing a course date.
   *
   * @param event The click event.
   */
  private handleRemoveCourseDate(event: Event): void {
    const target = event.target as HTMLElement;
    if (target && target.classList.contains('remove-course-date')) {
      if (this.wrapper && this.wrapper.querySelectorAll('.course-course-date').length > 1) {
        target.closest('.course-course-date')?.remove();
        this.updateCourseDateNumbers();
      } else {
        alert('Du må ha minst én kursdato.');
      }
    }
  }

  /**
   * Updates the course date numbers displayed in the headings.
   */
  private updateCourseDateNumbers(): void {
    this.wrapper?.querySelectorAll('.course-course-date').forEach((courseDateElement, index) => {
      const numberElement = courseDateElement.querySelector('.course-date-number');
      if (numberElement) {
        numberElement.textContent = (index + 1).toString();
      }
      courseDateElement.setAttribute('data-index', index.toString());
      courseDateElement.querySelectorAll('input, select, textarea').forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
          input.setAttribute('name', name.replace(/course_dates\[\d+\]/, `course_dates[${index}]`));
        }
      });
    });
  }

  /**
   * Ensures that at least one course date field is present when the page loads.
   */
  private ensureMinimumCourseDate(): void {
    if (this.wrapper && this.wrapper.querySelectorAll('.course-course-date').length === 0) {
      this.addCourseDate();
    }
  }
}