=== Hotel Booking Core ===
Contributors: hotelbookinglearners
Tags: hotel, rooms, booking, custom-post-type
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Rooms CPT, custom inquiries table, REST API, and shortcodes for the Hotel Booking theme.

== Description ==

Hotel Booking Core is the companion plugin for the Hotel Booking block theme. WordPress.org theme review treats custom post types and shortcodes as plugin territory, so this plugin provides:

* `hb_room` custom post type and room meta
* Custom table `wp_hb_inquiries` for booking inquiries
* REST route `GET /wp-json/hotel-booking/v1/rooms`
* Shortcodes `[hotel_inquiry_form]`, `[hotel_inquiry_list]`, `[hotel_room_meta]`
* wp-admin inquiries list and settings

The booking form does not take payments. It stores a staff inquiry row.

== Installation ==

1. Upload the `hotel-booking-core` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Activate the Hotel Booking theme.
4. Optional: create Booking and Desk pages and add the inquiry shortcodes.

== Frequently Asked Questions ==

= Does this plugin process payments? =

No. Inquiries are stored in a custom table for staff to follow up.

= Where do inquiries live? =

In `{$wpdb->prefix}hb_inquiries`, created on plugin activation. They are not posts and are not included in a WXR export.

== Changelog ==

= 1.0.0 =
* Initial release.
