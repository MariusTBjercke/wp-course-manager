import CourseDateManager from "./CourseDateManager";

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('course-dates-wrapper')) {
    new CourseDateManager();
  }

  console.log('Course Manager admin script initialized');
});