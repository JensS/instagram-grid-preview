# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.2] - 2026-10-01

### Added
- Reorder the slides within a post by drag and drop in the post editor

## [1.3.1] - 2026-10-01

### Fixed
- Custom poster images can now be set for Bunny Stream videos (and uploaded videos) via the camera button on a video slide. A custom poster is highlighted and can be removed to fall back to the Bunny thumbnail

## [1.3.0] - 2026-10-01

### Added
- Bunny Stream support: posts can now include Bunny videos (by GUID) instead of only uploaded files
- Reuses the site's shared Bunny Stream settings and the theme's Bunny video picker
- Bunny videos play as HLS with an MP4 fallback; hls.js is loaded only on pages that contain a Bunny slide

### Fixed
- Fixed "Failed to save grid" on existing installs: the `profile_data` column migration now runs automatically on plugin update (previously it only ran on activation). Saves also skip `profile_data` gracefully if the column is not present yet

## [1.2.1] - 2026-10-01

### Fixed
- Fixed a `mediaelement` JavaScript error that could appear after choosing a video poster image (media frames are now reused and DOM updates are deferred until the media modal closes)

### Changed
- Grid tiles now use a responsive `srcset`/`sizes` (medium-large rendition) instead of a single image
- The post viewer now loads a `large` rendition with `srcset` instead of the full-size original

### Added
- `@handles` in captions are highlighted in blue and link to Instagram

## [1.2.0] - 2026-10-01

### Added
- Public Instagram-style profile page at `/instagram-grid/{id}`, served as a standalone page with no theme chrome
- Post viewer: click any tile to open a slideshow with arrows, dots and keyboard navigation, plus caption, likes and comments
- Carousel support: multiple images per post
- Video support: video slides that play in the post viewer
- Separate poster image picker for videos, since WordPress/ClassicPress have no native video poster
- Editable profile fields: avatar, username, display name, bio, website, followers and following
- `profile_data` column on the grids table (with activation migration)
- Media type badges and hover overlays on the public grid

### Changed
- Replaced the `[instagram_grid]` shortcode with the public profile page
- Grid data now stores an array of media slides per cell; legacy single-image data is migrated automatically on read
- Profiles list now shows the profile URL with a copy button and a "View profile" action

### SEO
- Profile pages are excluded from search engines via an `X-Robots-Tag` header, a `robots` meta tag and a `robots.txt` Disallow rule

## [1.1.0] - 2026-10-01

### Added
- Instagram-faithful grid design: square (or 3:4) tiles, thin 4px gaps, no rounded corners and no hover zoom
- Per-image media type badges (Carousel, Reel, Video) rendered in the tile corner
- Hover overlay with like and comment counts, formatted the way Instagram does (e.g. `12.5K`, `1.2M`)
- "Post settings" modal in the editor to set link URL, media type, likes and comments per image
- 3x3 mini-grid preview thumbnail column on the grids list page
- Instagram-style admin editor with placeholder tiles, hover overlay, corner badges and fade-in row controls

### Fixed
- Drag-and-drop now rebuilds the grid after a drop so every row always keeps exactly the configured number of cells

## [1.0.3] - 2026-10-01

### Security
- Escaped `wp_die()` output with `esc_html__()` for improved XSS prevention
- Replaced `_e()` with `esc_html_e()` for proper output escaping

### Fixed
- Added translator comments for `sprintf` placeholders per WordPress i18n standards

## [1.0.2] - 2025-01-19

### Fixed
- Fixed drag-and-drop bug where moving images would insert instead of swap, causing all subsequent images to shift down
- Images now properly exchange positions when dragged to a new location

## [1.0.1] - 2024-11-07

### Added
- Plugin Update Checker integration for automatic updates from GitHub
- GPL-2.0-or-later license file
- WordPress.org compatible readme.txt file

### Security
- Comprehensive security audit completed
- Enhanced XSS protection with `escapeHtml()` function in admin JavaScript
- Improved input sanitization for all user inputs
- Added SRI (Subresource Integrity) hash for Sortable.js CDN loading
- Enhanced AJAX nonce verification
- Added `rel="noopener noreferrer"` to external links
- Improved capability checks across all operations

### Fixed
- Grid data sanitization edge cases
- URL validation for image links

## [1.0.0] - 2024-09-27

### Added
- Initial release
- Visual drag-and-drop grid editor
- WordPress media library integration
- Support for 1:1 (square) and 3:4 (portrait) aspect ratios
- Responsive CSS Grid layout
- Image linking functionality via right-click context menu
- Dynamic row management (add rows above/below)
- Grid duplication feature
- Custom capabilities system (manage, create, edit, delete)
- ClassicPress compatibility
- AJAX-based grid operations
- Shortcode display `[instagram_grid id="X"]`
- Custom CSS class support for grids
- Sortable.js integration for drag-and-drop
- Sparse grid data structure for efficient storage

### Technical
- WordPress Plugin Boilerplate architecture
- PSR-4 autoloading ready structure
- Custom database table for grid storage
- JSON-based grid data storage
- i18n ready with translation support

[Unreleased]: https://github.com/JensS/instagram-grid-preview/compare/v1.3.2...HEAD
[1.3.2]: https://github.com/JensS/instagram-grid-preview/compare/v1.3.1...v1.3.2
[1.3.1]: https://github.com/JensS/instagram-grid-preview/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/JensS/instagram-grid-preview/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/JensS/instagram-grid-preview/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/JensS/instagram-grid-preview/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.3...v1.1.0
[1.0.3]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/JensS/instagram-grid-preview/releases/tag/v1.0.0
