=== Instagram Grid Preview ===
Contributors: jenssage
Tags: instagram, grid, gallery, images, media
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create a fake Instagram profile from your WordPress media library, complete with a carousel/video post viewer and a visual drag-and-drop editor.

== Description ==

Instagram Grid Preview turns your WordPress media library into a public, Instagram-style profile page. Each profile has its own URL, and clicking a tile opens a post viewer with a full slideshow (images and video), caption, and like/comment counts. Perfect for photographers, artists, and anyone who wants to showcase media in an eye-catching Instagram-like format.

= Features =

* **Public Profile Page** - Each grid gets its own standalone Instagram-style profile at `/instagram-grid/{id}`
* **Post Viewer** - Click a tile to open a slideshow with arrows, dots and keyboard navigation
* **Carousels** - Add multiple images to a single post
* **Video Posts** - Add video slides that play in the viewer, with a separate poster image
* **Profile Details** - Avatar, username, display name, bio, website, followers and following
* **Visual Grid Editor** - Intuitive drag-and-drop interface for arranging posts
* **Media Type Badges** - Carousel, Reel and Video badges shown in the tile corner
* **Hover Overlay** - Like and comment counts, formatted like Instagram (e.g. 12.5K)
* **Multiple Aspect Ratios** - Support for 1:1 (square) and 3:4 (portrait) ratios
* **Fully Responsive** - Profiles adapt beautifully to all screen sizes
* **Unlimited Profiles** - Create as many as you need
* **Private by Default** - Profile pages are excluded from search engines
* **ClassicPress Compatible** - Works with both WordPress and ClassicPress

= Perfect For =

* Photography portfolios
* Product showcases
* Gallery displays
* Social media feed recreations
* Image-heavy landing pages

= How It Works =

1. Navigate to **Instagram Grids** in your WordPress admin
2. Create a new profile and set its dimensions and details
3. Click on empty tiles to add images or video from your media library
4. Drag and drop to reorder posts
5. Open a post to add a caption, media type, likes, comments and links
6. Share the public profile URL shown in the editor

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

= Where can I find the public profile page? =

Every profile has its own URL in the form `/instagram-grid/{id}`. The exact URL is shown in the editor and in the profiles list, with a copy button and a "View profile" link.

= Can I embed a profile inside a page? =

Not currently. Profiles are served as their own standalone Instagram-style pages. You can link to the profile URL from any menu, post or page.

= How do I add a poster image to a video? =

WordPress and ClassicPress do not generate poster images for videos. Open the post in the editor and click the camera button on the video slide to choose a poster image from your media library. This image is used in the grid tile and in the post viewer.

= Can I customize the styling? =

Yes, you can override the plugin's CSS (`igp-public.css`, `igp-profile.css`) in your theme.

= Will this slow down my site? =

No, the plugin is lightweight and only loads its assets on profile pages. Images are served from your media library with standard WordPress optimization.

= Can I export/import grids? =

Not yet, but this feature is on the roadmap for a future release.

= Is it compatible with ClassicPress? =

Yes! The plugin is fully compatible with ClassicPress 1.0 and higher.

== Screenshots ==

1. Instagram-style profile page
2. Post viewer with carousel slideshow
3. Profile editor with drag-and-drop grid
4. Post settings modal (caption, media type, likes, comments)
5. Video slide with a separate poster image picker
6. Responsive profile display on mobile
7. Profiles list management page

== Changelog ==

= 1.3.1 - 2026-10-01 =

**Fixed**
* You can now set a custom poster image for Bunny Stream videos (and uploaded videos) from the post editor. The camera button on a video slide sets a custom poster, which is highlighted and can be removed to fall back to the Bunny thumbnail

= 1.3.0 - 2026-10-01 =

**New Features**
* Bunny Stream support: add Bunny videos to posts instead of uploading files
* Reuses the site's Bunny Stream settings and the theme's Bunny video picker
* Bunny videos play as HLS with an MP4 fallback (hls.js is loaded only on pages that need it)

**Fixed**
* Fixed "Failed to save grid" on existing installs: the `profile_data` column migration now runs automatically after a plugin update (it previously only ran on activation), and saves no longer fail if the column is missing

= 1.2.1 - 2026-10-01 =

**Fixed**
* Fixed a mediaelement JavaScript error that could appear after choosing a video poster image (media frames are now reused and DOM updates wait until the media modal closes)

**Improved**
* Grid tiles now use a responsive `srcset`/`sizes` (medium-large rendition) instead of a single image
* The post viewer now loads a `large` rendition with `srcset` instead of the full-size original
* @handles in captions are now highlighted in blue and link to Instagram

= 1.2.0 - 2026-10-01 =

**New Features**
* Public Instagram-style profile page at `/instagram-grid/{id}`, served as a standalone page with no theme chrome
* Post viewer: click any tile to open a slideshow with arrows, dots and keyboard navigation, plus caption, likes and comments
* Carousel support: add multiple images to a single post
* Video support: add video slides that play in the post viewer
* Separate poster image picker for videos (WordPress/ClassicPress have no native video poster)
* Editable profile: avatar, username, display name, bio, website, followers and following
* Media type badges and hover overlays on the public grid

**Changed**
* Replaced the `[instagram_grid]` shortcode with the public profile page
* Profiles list now shows the profile URL with a copy button and a "View profile" action

**SEO**
* Profile pages are excluded from search engines via `X-Robots-Tag`, a `robots` meta tag and a `robots.txt` Disallow rule

= 1.1.0 - 2026-10-01 =

**New Features**
* Redesigned grid to faithfully match Instagram's profile look: square (or 3:4) tiles, thin gaps and no rounded corners
* Per-image media type badges (Carousel, Reel, Video), shown in the tile corner
* Hover overlay with like and comment counts, formatted like Instagram (e.g. 12.5K)
* New "Post settings" modal in the editor to set the link URL, media type, likes and comments per image
* Grid list now shows a 3x3 mini-grid preview thumbnail for each grid

**Admin UI**
* Instagram-style grid editor: placeholder tiles, hover overlay, corner badges and fade-in row controls
* Empty tiles display an "Add image" affordance

**Fixed**
* Drag-and-drop now rebuilds the grid so every row always keeps the correct number of cells

= 1.0.3 - 2026-10-01 =

**Security & Compliance**
* Escaped `wp_die()` output with `esc_html__()` for improved XSS prevention
* Replaced `_e()` with `esc_html_e()` for proper output escaping
* Added translator comments for `sprintf` placeholders per WordPress i18n standards

= 1.0.2 - 2025-01-19 =

**Bug Fixes**
* Fixed drag-and-drop issue where moving images would insert instead of swap positions
* Images now properly exchange positions when dragged, preventing unwanted shifts

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

= 1.3.1 =
Fix: set a custom poster image for Bunny videos (and uploaded videos) from the post editor.

= 1.3.0 =
Adds Bunny Stream video support. Also fixes a database migration bug that could cause "Failed to save grid" after updating on existing installs. Recommended for all users.

= 1.2.1 =
Bug fix for a mediaelement error when setting video poster images, plus responsive image loading and blue @mention highlighting in captions.

= 1.2.0 =
Major update: grids are now public Instagram-style profile pages with a carousel/video post viewer. The [instagram_grid] shortcode has been replaced by the /instagram-grid/{id} page. Recommended for all users.

= 1.1.0 =
Major UI update with an Instagram-faithful grid, per-image media badges and like/comment hover overlays. Recommended for all users.

= 1.0.3 =
Security and internationalization compliance update. Recommended for all users.

= 1.0.2 =
Bug fix for drag-and-drop functionality. Images now properly swap positions when moved.

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
