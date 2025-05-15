import CourseManager from "./lib/CourseManager";
import EnrollmentFormHandler from "./lib/EnrollmentFormHandler";

document.addEventListener('DOMContentLoaded', () => {
  new CourseManager();

  // Initialize EnrollmentFormHandler if on a course page with the form
  if (document.getElementById('cm_course_date')) {
    new EnrollmentFormHandler();
  }

  console.log('Course Manager script initialized');
});