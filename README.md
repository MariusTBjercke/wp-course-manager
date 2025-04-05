# WP Course Manager

A simple and flexible WordPress plugin for managing courses, displaying available sessions, and allowing users to register.

## Features

- Custom post type for courses ("Courses")
- Dynamic taxonomies for organizing courses (configurable in settings)
- Frontend course list with filtering based on dynamic taxonomies and search
- Enrollment form per course with email confirmation
- Easy integration via shortcodes: [course_manager] and [course_enrollment_form]
- Clean and extendable codebase
- Admin settings page for managing plugin options, including default email message and courses per page

## Installation

1. Clone this repository into your `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/MariusTBjercke/wp-course-manager.git
   ```
2. Navigate to the plugin directory and install dependencies:
   cd wp-course-manager
   composer install
   npm install

3. Build assets:
   npm run build

4. Activate the plugin in the WordPress admin.

## Usage

### Basic Usage
Add the shortcode [course_manager] to any page or post to display the course list with filtering options based on dynamic taxonomies and search.

Add the shortcode [course_enrollment_form] to a course page to display the enrollment form (this is automatically added to course content via ContentFilter if not already present).

### Shortcode Options for [course_manager]
- show_filters - Whether to show the filter UI (default: "yes")

Example:
[course_manager show_filters="no"]

### Admin Management
- **Courses**: Add and manage courses from the "Courses" menu in the admin.
- **Dynamic Taxonomies**: Configure taxonomies (e.g., "Locations", "Categories", "Types") from the "Settings" submenu under "Courses". These will then appear as taxonomy menus in the admin.
- **Enrollments**: View and manage enrollments from the "Enrollments" submenu under "Courses".
- **Settings**: Configure general settings from the "Settings" submenu under "Courses", including:
   - Number of courses per page in the course list.
   - Default email confirmation message sent upon enrollment (can be overridden per course).
- **Custom Email Per Course**: Edit a course and specify a custom email confirmation message in the "Custom Email Confirmation" meta box (optional).

## Development

### Building Assets
- npm run build - Build assets for production.
- npm run watch - Watch for changes and rebuild during development.