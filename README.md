# WP Course Manager

A simple and flexible WordPress plugin for managing courses, displaying available sessions, and allowing users to register.

## Features

- Custom post type for courses
- Location and category taxonomies for organizing courses
- Frontend course list with filtering by city and category
- Enrollment form per course
- Easy to integrate via shortcode: `[course_manager]`
- Clean, extendable codebase
- Admin settings page for managing plugin options

## Installation

1. Clone this repository into your `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/MariusTBjercke/wp-course-manager.git
   ```
2. Navigate to the plugin directory and install dependencies:
   ```bash
   cd wp-course-manager
   composer install
   npm install
   ```
3. Build assets:
   ```bash
   npm run build
   ```
4. Activate the plugin in WordPress admin.

## Usage

### Basic Usage
Add the shortcode `[course_manager]` to any page or post to display the course list with filtering options.

### Shortcode Options
- `city` - Filter courses by a specific city (slug)
- `category` - Filter courses by a specific category (slug)
- `show_filters` - Whether to show the filter UI (default: yes)

Example:
```
[course_manager category="webdesign" show_filters="no"]
```

### Admin Management
- **Courses**: Add and manage courses from the "Courses" admin menu
- **Locations**: Manage course locations from the "Steder" taxonomy
- **Categories**: Manage course categories from the "Kategorier" taxonomy
- **Settings**: Configure general settings from the "Innstillinger" submenu

## Development

### Building Assets
- `npm run build` - Build assets for production
- `npm run watch` - Watch for changes and rebuild during development