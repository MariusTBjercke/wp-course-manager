# WP Course Manager

A simple and flexible WordPress plugin for managing courses, displaying available dates, and allowing users to register, with integrated payment handling via WooCommerce.

## Features

- Custom post type for courses ("Courses")
- Dynamic taxonomies for organizing courses (configurable in settings)
- **Support for multiple dates and times per course, with the ability to override course-level taxonomies for each specific date.**
- Frontend course list with filtering based on dynamic taxonomies and search
- Enrollment form per course with optional email confirmation (configurable in settings)
- **Configurable format for the course date dropdown text on the enrollment form using placeholders.**
- **Dynamic display of selected course date details (including all plugin taxonomies) below the enrollment form dropdown, updated via JavaScript.**
- Payment integration with WooCommerce, supporting multiple payment gateways (e.g., Stripe, Vipps, offline payments)
  Easy integration via shortcodes: `[course_manager]` and `[course_enrollment_form]`
- Clean and extendable codebase
- Admin settings page for managing plugin options, including:
    - Number of courses per page
    - Default email confirmation message (can be overridden per course)
    - Option to enable/disable plugin-managed emails
    - **Setting to customize the format of the course date dropdown text.**
- Requires WooCommerce for full functionality

## Installation

1. Clone the repository into your `wp-content/plugins/` directory:
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

4. Activate the plugin in the WordPress admin.

## Usage

### Basic Usage
Add the shortcode `[course_manager]` to any page or post to display the course list with filtering options based on dynamic taxonomies and search.

Add the shortcode `[course_enrollment_form]` to a course page to display the enrollment form (this is automatically added to course content via ContentFilter if not already present). Upon submission, users are redirected to the WooCommerce checkout for payment processing.

### Shortcode Options for `[course_manager]`
- `show_filters` - Whether to show the filter UI (default: "yes")

Example:
`[course_manager show_filters="no"]`

### Admin Management
- **Courses**: Add and manage courses from the "Courses" menu in the admin. Set a price per participant using the `_course_price` meta field (optional for free courses). **Add multiple course dates and override taxonomies and maximum participants for each date.**
- **Dynamic Taxonomies**: Configure taxonomies (e.g., "Locations", "Categories", "Types") from the "Settings" submenu under "Courses". These will appear as taxonomy menus in the admin.
- **Enrollments**: View and manage enrollments from the "Enrollments" submenu under "Courses".
- **Settings**: Configure plugin options from the "Settings" submenu under "Courses":
    - **General**: Set the number of courses per page in the course list. **Configure the format for the course date dropdown text on the enrollment form using placeholders like `[date]`, `[time]`, `[available_slots]`, and `[taxonomy_SLUG]` (e.g., `[date], [time] - [taxonomy_sted] ([available_slots] plasser)`).**
    - **Email**: Configure default email confirmation messages (for customers and admins), the admin email address for notifications, and an option to enable/disable plugin-managed emails.
- **Custom Email Per Course**: Edit a course and specify a custom email confirmation message in the "Custom Email Confirmation" meta box (optional).
- **Payment**: Payment methods (e.g., Stripe, Vipps, offline payments) are managed via **WooCommerce > Settings > Payments**. Course Manager relies on WooCommerce for all payment processing.

### Payment Integration
Course Manager integrates with WooCommerce for payment handling:
- When a user submits the enrollment form with a non-zero total price, a WooCommerce order is created dynamically, and the user is redirected to the WooCommerce checkout page.
- Supported payment methods depend on installed WooCommerce payment gateways (e.g., Stripe for credit cards, Vipps via the "Pay with Vipps and MobilePay for WooCommerce" plugin, or offline methods like check payments).
- Upon payment completion (or order approval for offline methods), the enrollment is finalized, and optional confirmation emails are sent if enabled in settings.

## Development

### Building Assets
- `npm run build` - Build assets for production.
- `npm run watch` - Watch for changes and rebuild during development.

### Dependencies
- **WooCommerce**: Required for payment processing and order management. Install via the WordPress plugin repository.
- Additional payment gateways (e.g., Stripe, Vipps) can be added via their respective WooCommerce plugins.

### Notes for Developers
- The plugin no longer includes a standalone payment page; all payment handling is delegated to WooCommerce.
- Email sending can be toggled in the admin settings under "E-post" (`course_manager_enable_emails`). When disabled, no emails are sent by Course Manager, though WooCommerce may still send its own order-related emails.
- Customizations to the enrollment process or payment flow should be made via WooCommerce hooks and filters.
