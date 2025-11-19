# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Plugin Update Checker integration for automatic updates from GitHub
- GPL-2.0-or-later license file
- WordPress.org compatible readme.txt file

## [1.0.1] - 2024-11-07

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

[Unreleased]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/JensS/instagram-grid-preview/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/JensS/instagram-grid-preview/releases/tag/v1.0.0
