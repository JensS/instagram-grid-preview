# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/JensS/instagram-grid-preview/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.3...v1.1.0
[1.0.3]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/JensS/instagram-grid-preview/releases/tag/v1.0.0
