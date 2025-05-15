import CourseDateManager from "./CourseDateManager";
import AdminTaxonomyManager from "./AdminTaxonomyManager";

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('course-dates-wrapper')) {
    new CourseDateManager();
  }

  if (document.getElementById('taxonomies-wrapper')) {
    new AdminTaxonomyManager();
  }

  console.log('Course Manager admin script initialized');
});