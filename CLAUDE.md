# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress/ClassicPress plugin that creates Instagram-style grid layouts using WordPress media library images. Grids are displayed via shortcodes with mobile-responsive design.

**Plugin Details:**
- **Version:** 1.0.1
- **Requires:** PHP 7.4+, WordPress 5.0+ or ClassicPress 1.0+
- **Text Domain:** instagram-grid-preview
- **Architecture:** WordPress Plugin Boilerplate pattern

## Architecture

The plugin follows WordPress Plugin Boilerplate architecture with clean separation of concerns:

### Core Classes

**Main Plugin Class** (`Instagram_Grid_Preview`):
- Located in: `includes/class-instagram-grid-preview.php`
- Orchestrates plugin initialization via hook loader pattern
- Loads dependencies and defines admin/public hooks
- Entry point via `run_instagram_grid_preview()` in main plugin file

**Hook Loader** (`IGP_Loader`):
- Located in: `includes/class-igp-loader.php`
- Manages all WordPress actions and filters registration
- Centralizes hook management for the plugin

**Grid Model** (`IGP_Grid_Model`):
- Located in: `includes/class-igp-grid-model.php`
- Handles all database operations for grids (CRUD)
- Uses custom table: `{$wpdb->prefix}igp_grids`
- Stores grid configuration (dimensions, aspect ratio) and cell data as JSON

### Admin Interface

**Admin Class** (`IGP_Admin`):
- Located in: `admin/class-igp-admin.php`
- Manages admin menu pages, enqueues assets, handles AJAX endpoints
- Enqueues WordPress media library and Sortable.js for drag-and-drop
- AJAX handlers for: `igp_save_grid`, `igp_delete_grid`, `igp_duplicate_grid`, `igp_get_grid`
- All AJAX endpoints verify nonce (`igp_nonce`) and check capabilities

**Admin JavaScript** (`igp-admin.js`):
- Located in: `admin/js/igp-admin.js`
- Implements grid editor UI with drag-and-drop cell reordering
- WordPress media library integration for image selection
- **Important:** Grid data stored as sparse JavaScript object with cell index as key (not array)
- Right-click context menu for adding URLs to images
- Dynamic row management (add rows above/below)
- XSS protection via `escapeHtml()` function for all user-generated content

**Admin Partials:**
- `admin/partials/igp-admin-grids-list.php` - Lists all grids
- `admin/partials/igp-admin-grid-editor.php` - Grid editor interface

### Public Interface

**Public Class** (`IGP_Public`):
- Located in: `public/class-igp-public.php`
- Registers `[instagram_grid]` shortcode
- Renders grid HTML with responsive CSS grid layout
- Supports optional image linking (set via right-click in editor)
- Opens links in new tab with `rel="noopener noreferrer"` for security

**Shortcode Usage:**
```
[instagram_grid id="123"]
[instagram_grid id="123" class="custom-class"]
```

### Database Schema

Table: `wp_igp_grids` (prefix varies)

```sql
CREATE TABLE wp_igp_grids (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    description text,
    columns tinyint(3) NOT NULL DEFAULT 3,
    `rows` tinyint(3) NOT NULL DEFAULT 3,
    aspect_ratio varchar(10) NOT NULL DEFAULT '1:1',
    grid_data longtext,  -- JSON string of cell data
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)
```

**Grid Data Structure (Sparse Object/Array):**

The grid data is stored as a JSON object where:
- Keys are cell indices calculated as: `row * columns + col`
- Only populated cells are stored (sparse structure saves space)
- Each cell contains image metadata and optional link URL

```json
{
  "0": {
    "image_id": 123,
    "image_url": "https://example.com/image.jpg",
    "thumbnail_url": "https://example.com/thumb.jpg",
    "image_alt": "Alt text",
    "link_url": "https://example.com"
  },
  "5": {
    "image_id": 456,
    "image_url": "https://example.com/another.jpg",
    "thumbnail_url": "https://example.com/another-thumb.jpg",
    "image_alt": "Another image"
  }
}
```

**Why Sparse Structure:**
- Grids can be large (e.g., 10x10 = 100 cells) but users may only populate a few cells
- Storing only populated cells reduces database size
- JavaScript handles missing indices gracefully (returns undefined)

### Activation & Deactivation

**Activator** (`IGP_Activator`):
- Located in: `includes/class-igp-activator.php`
- Creates database table on activation using `dbDelta()`
- Handles schema migrations (e.g., `aspect_ratio` column added in v1.1.0)
- Sets custom capabilities for Administrator and Editor roles
- Flushes rewrite rules

**Deactivator** (`IGP_Deactivator`):
- Located in: `includes/class-igp-deactivator.php`
- Cleanup tasks on plugin deactivation

### Custom Capabilities

The plugin defines custom capabilities:
- `manage_instagram_grids` - View grids list
- `create_instagram_grids` - Create new grids
- `edit_instagram_grids` - Edit existing grids
- `delete_instagram_grids` - Delete grids

Granted to Administrator and Editor roles by default in `IGP_Activator::set_default_capabilities()`.

## Key Features

1. **Grid Editor:**
   - Drag-and-drop cell reordering (uses Sortable.js v1.15.0)
   - WordPress media library integration (`wp.media()`)
   - Configurable dimensions (columns/rows)
   - Aspect ratio support (1:1, 3:4)
   - Right-click to add URL links to images
   - Dynamic row management (insert rows above/below, shifts existing content)

2. **Responsive Display:**
   - CSS Grid-based layout (not flexbox)
   - Mobile-responsive via media queries
   - Optional custom CSS classes

3. **AJAX Operations:**
   - All grid operations use AJAX (save, delete, duplicate, get)
   - Nonce verification via `igp_nonce`
   - Capability checks on all operations
   - Input sanitization using WordPress functions

## Important Implementation Details

### Grid Cell Index Calculation

Cell indices are calculated as: `index = row * columns + col`

Example for 3x3 grid:
```
[0][1][2]
[3][4][5]
[6][7][8]
```

This calculation is used throughout the JavaScript and PHP code. When adding/removing rows, all subsequent cell indices must be recalculated.

### Row Management Logic

When inserting a row (see `addRow()` in `igp-admin.js:369`):
1. Increment row count
2. Create new grid data object
3. For each existing cell:
   - Calculate old row/col from index
   - If row >= insertion position, shift down by one row
   - Recalculate new index and copy data
4. Update global `gridData` object
5. Regenerate grid display

### Drag-and-Drop Implementation

Sortable.js is initialized per-row (not for entire grid):
- Allows dragging between rows via `group: 'grid-cells'`
- Only cells with images can be dragged (see `filter` function)
- On drop, recalculates absolute indices and rebuilds entire `gridData` object
- Uses temporary array to handle position swaps correctly

### AJAX Data Sanitization

Server-side validation in `IGP_Admin::ajax_save_grid()`:
- `image_id`: Cast to `intval()`
- `image_url`, `thumbnail_url`: Sanitized with `esc_url_raw()`
- `image_alt`: Sanitized with `sanitize_text_field()`
- `link_url`: Sanitized with `esc_url_raw()`, removed if empty
- Cells without `image_url` are excluded

### Constants

Defined in `instagram-grid-preview.php`:
- `IGP_VERSION` - Plugin version (1.0.1)
- `IGP_PLUGIN_DIR` - Plugin directory path
- `IGP_PLUGIN_URL` - Plugin URL
- `IGP_PLUGIN_BASENAME` - Plugin basename for hooks

### ClassicPress Compatibility

- Helper function `igp_is_classicpress()` detects ClassicPress
- Requirement checks for both WordPress/ClassicPress versions
- Uses classic admin UI (no Gutenberg dependencies)
- Compatible with ClassicPress 1.0+

### External Dependencies

- **Sortable.js** (v1.15.0) - Loaded from CDN (jsdelivr) with SRI integrity hash
- **WordPress Media Library** - Native WordPress media uploader via `wp.media()`
- **Plugin Update Checker** (v5.5) - YahnisElsts library for automatic updates from GitHub

### Security Measures

1. **Nonce Verification:** All AJAX requests verify `igp_nonce`
2. **Capability Checks:** All operations check user capabilities
3. **Input Sanitization:** All user input sanitized before storage
4. **Output Escaping:** All output escaped in admin JS (`escapeHtml()`) and public PHP (`esc_url()`, `esc_attr()`)
5. **SRI Hash:** Sortable.js loaded with Subresource Integrity hash
6. **External Links:** Links open with `rel="noopener noreferrer"`

## File Structure

```
instagram-grid-preview/
├── instagram-grid-preview.php (main entry point)
├── includes/
│   ├── class-instagram-grid-preview.php (core plugin class)
│   ├── class-igp-loader.php (hook manager)
│   ├── class-igp-grid-model.php (database operations)
│   ├── class-igp-activator.php (activation tasks)
│   ├── class-igp-deactivator.php (deactivation tasks)
│   └── class-igp-i18n.php (internationalization)
├── admin/
│   ├── class-igp-admin.php (admin functionality)
│   ├── js/igp-admin.js (grid editor JavaScript)
│   ├── css/igp-admin.css (admin styles)
│   └── partials/
│       ├── igp-admin-grids-list.php (grids list template)
│       └── igp-admin-grid-editor.php (grid editor template)
└── public/
    ├── class-igp-public.php (public shortcode)
    ├── js/igp-public.js (frontend JavaScript)
    └── css/igp-public.css (frontend styles)
```

## Common Development Tasks

### Debugging

The plugin has requirement checks on load:
- PHP version must be 7.4+
- WordPress 5.0+ or ClassicPress 1.0+
- Auto-deactivates if requirements not met

Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs at: `wp-content/debug.log`

### Adding New Aspect Ratios

1. Update validation in `IGP_Grid_Model::create_grid()` and `::update_grid()`
2. Add CSS rules in `admin/css/igp-admin.css` and `public/css/igp-public.css`
3. Add option to dropdown in `admin/partials/igp-admin-grid-editor.php`

### Modifying AJAX Endpoints

All AJAX handlers follow this pattern:
```php
public function ajax_save_grid() {
    check_ajax_referer('igp_nonce', 'nonce');

    if (!current_user_can('create_instagram_grids')) {
        wp_die(__('Permission denied.', 'instagram-grid-preview'));
    }

    // Process and sanitize data
    // ...

    if ($result) {
        wp_send_json_success(['message' => 'Success!']);
    } else {
        wp_send_json_error(['message' => 'Failed.']);
    }
}
```

JavaScript AJAX calls use:
```javascript
$.post(igp_ajax.ajax_url, {
    action: 'igp_save_grid',
    nonce: igp_ajax.nonce,
    // ... data
});
```

### Localization

Text domain: `instagram-grid-preview`
Domain path: `/languages`

Translate strings using WordPress i18n functions:
- `__()` - Returns translated string
- `_e()` - Echoes translated string
- `esc_html__()` - Returns escaped translated string
- `esc_html_e()` - Echoes escaped translated string

Load translations in `IGP_i18n::load_plugin_textdomain()`.

## Automatic Updates from GitHub

The plugin uses the **Plugin Update Checker** library by Yahnis Elsts to enable automatic updates directly from the GitHub repository.

### How It Works

1. The plugin checks for updates every 12 hours automatically
2. Users can manually check by clicking "Check for updates" on the Plugins page
3. Updates are pulled from the `master` branch on GitHub
4. The `readme.txt` file is used to display version details to users

### Release Process

To release a new version:

1. **Update Version Numbers:**
   - Update version in `instagram-grid-preview.php` header
   - Update `IGP_VERSION` constant
   - Update version in `readme.txt` (Stable tag line)

2. **Update Changelog:**
   - Add new version entry to `CHANGELOG.md`
   - Update changelog section in `readme.txt`
   - Update upgrade notice in `readme.txt` if needed

3. **Commit and Tag:**
   ```bash
   git add -A
   git commit -m "Release version X.Y.Z"
   git tag vX.Y.Z
   git push origin master
   git push origin vX.Y.Z
   ```

4. **Create GitHub Release:**
   - Go to GitHub repository releases page
   - Create a new release from the tag
   - Add release notes (copy from CHANGELOG.md)
   - Attach a ZIP file of the plugin (optional, but recommended)

### Testing Updates Locally

1. Install Debug Bar plugin
2. Click "Debug" in the Admin Bar
3. Open the "PUC (instagram-grid-preview)" panel
4. Click "Check Now" button

### Configuration

Update checker is initialized in `instagram-grid-preview.php`:
```php
$igpUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/JensS/instagram-grid-preview/',
    __FILE__,
    'instagram-grid-preview'
);
$igpUpdateChecker->setBranch('master');
```

For private repositories, add authentication:
```php
$igpUpdateChecker->setAuthentication('your-github-token-here');
```
