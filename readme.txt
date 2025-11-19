=== Instagram Grid Preview ===
Contributors: jenssage
Tags: instagram, grid, gallery, images, media
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create beautiful Instagram-style grid layouts using your WordPress media library images with a visual drag-and-drop editor.

== Description ==

Instagram Grid Preview allows you to create beautiful, responsive Instagram-style grid layouts directly from your WordPress media library. Perfect for photographers, artists, and anyone who wants to showcase images in an eye-catching grid format.

= Features =

* **Visual Grid Editor** - Intuitive drag-and-drop interface for arranging images
* **WordPress Media Library Integration** - Use existing images from your media library
* **Multiple Aspect Ratios** - Support for 1:1 (square) and 3:4 (portrait) ratios
* **Fully Responsive** - Grids adapt beautifully to all screen sizes
* **Image Linking** - Add clickable links to individual images
* **Dynamic Row Management** - Add rows above or below existing content
* **Unlimited Grids** - Create as many grids as you need
* **Simple Shortcode** - Display anywhere with `[instagram_grid id="123"]`
* **ClassicPress Compatible** - Works with both WordPress and ClassicPress

= Perfect For =

* Photography portfolios
* Product showcases
* Gallery displays
* Social media feed recreations
* Image-heavy landing pages

= How It Works =

1. Navigate to **Instagram Grids** in your WordPress admin
2. Create a new grid and set dimensions
3. Click on cells to add images from your media library
4. Drag and drop to reorder images
5. Right-click images to add optional links
6. Copy the shortcode and paste it anywhere on your site

= Privacy & Security =

This plugin does not collect, store, or transmit any personal data. All images are stored in your WordPress media library. The plugin has been thoroughly security audited and follows WordPress security best practices.

= Developer Friendly =

* Clean, documented code following WordPress standards
* Custom capabilities for granular permission control
* Extensive inline documentation
* GitHub repository available for contributions

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Instagram Grid Preview"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. Activate the plugin

= From GitHub =

1. Download the latest release from [GitHub](https://github.com/JensS/instagram-grid-preview/releases)
2. Upload to `/wp-content/plugins/`
3. Activate through the Plugins menu

== Frequently Asked Questions ==

= Can I use the same image multiple times? =

Yes, you can add the same image to multiple cells in your grid.

= What happens if I delete an image from the media library? =

The grid will display a placeholder for that cell. You'll need to update the grid with a new image.

= Can I customize the styling? =

Yes, use the `class` parameter in the shortcode to add custom CSS classes: `[instagram_grid id="123" class="my-custom-grid"]`. You can also override the plugin's CSS in your theme.

= Is this compatible with page builders? =

Yes, the shortcode works with most page builders that support WordPress shortcodes, including Elementor, Beaver Builder, and Divi.

= Will this slow down my site? =

No, the plugin is lightweight and only loads assets on pages where grids are displayed. Images are served from your media library with standard WordPress optimization.

= Can I export/import grids? =

Not yet, but this feature is on the roadmap for a future release.

= Is it compatible with ClassicPress? =

Yes! The plugin is fully compatible with ClassicPress 1.0 and higher.

== Screenshots ==

1. Grid editor interface with drag-and-drop functionality
2. WordPress media library integration
3. Grid configuration options (dimensions, aspect ratio)
4. Right-click context menu for adding links
5. Responsive grid display on desktop
6. Responsive grid display on mobile
7. Grids list management page

== Changelog ==

= 1.0.1 - 2024-11-07 =

**Security Enhancements**
* Enhanced XSS protection in admin JavaScript
* Added SRI hash for Sortable.js CDN loading
* Improved input sanitization for all user inputs
* Added rel="noopener noreferrer" to external links
* Enhanced AJAX nonce verification
* Improved capability checks across all operations

**Bug Fixes**
* Fixed grid data sanitization edge cases
* Improved URL validation for image links

= 1.0.0 - 2024-09-27 =

* Initial release
* Visual drag-and-drop grid editor
* WordPress media library integration
* Support for 1:1 and 3:4 aspect ratios
* Responsive CSS Grid layout
* Image linking functionality
* Dynamic row management
* Grid duplication feature
* ClassicPress compatibility

== Upgrade Notice ==

= 1.0.1 =
Security update with enhanced XSS protection and improved input sanitization. Recommended for all users.

= 1.0.0 =
Initial release of Instagram Grid Preview.

== Development ==

This plugin is actively developed on GitHub. Contributions, bug reports, and feature requests are welcome!

**GitHub Repository:** [https://github.com/JensS/instagram-grid-preview](https://github.com/JensS/instagram-grid-preview)

= Third-Party Libraries =

* [Sortable.js](https://github.com/SortableJS/Sortable) (MIT License) - Drag-and-drop functionality
* WordPress Media Library (GPL-2.0) - Image selection interface

== Credits ==

Created by [Jens Sage](https://jenssage.com)

Special thanks to all contributors and testers who helped make this plugin better.
