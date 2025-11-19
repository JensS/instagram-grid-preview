# Instagram Grid Preview

A WordPress/ClassicPress plugin that creates Instagram-style grid layouts using your WordPress media library images. Display beautiful, responsive grids anywhere on your site using simple shortcodes.

## Features

- **Visual Grid Editor** - Drag-and-drop interface for arranging images
- **WordPress Media Library Integration** - Use existing images from your media library
- **Multiple Aspect Ratios** - Support for 1:1 (square) and 3:4 (portrait) ratios
- **Responsive Design** - Grids adapt beautifully to all screen sizes
- **Image Linking** - Add clickable links to individual images
- **Dynamic Row Management** - Add rows above or below existing content
- **Multiple Grids** - Create unlimited grids for different sections of your site
- **Shortcode Display** - Simple shortcode integration `[instagram_grid id="123"]`
- **ClassicPress Compatible** - Works with both WordPress and ClassicPress

## Requirements

- PHP 7.4 or higher
- WordPress 5.0+ or ClassicPress 1.0+
- Modern web browser with JavaScript enabled

## Installation

### From GitHub

1. Download the latest release from the [Releases page](https://github.com/JensS/instagram-grid-preview/releases)
2. Upload the `instagram-grid-preview` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Instagram Grids** in your WordPress admin menu

### Manual Installation

1. Clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/JensS/instagram-grid-preview.git
   ```
2. Activate the plugin through the 'Plugins' menu in WordPress

### Via WordPress.org (Coming Soon)

Once available on WordPress.org, you can install directly from the WordPress admin panel.

## Usage

### Creating a Grid

1. Go to **Instagram Grids** → **Add New** in your WordPress admin
2. Enter a name for your grid
3. Set your desired dimensions (columns and rows)
4. Choose an aspect ratio (1:1 for square, 3:4 for portrait)
5. Click on grid cells to add images from your media library
6. Drag and drop images to reorder them
7. Right-click on images to add clickable URLs (optional)
8. Click **Save Grid**

### Displaying a Grid

After saving your grid, you'll receive a shortcode like:

```
[instagram_grid id="123"]
```

Add this shortcode to any post, page, or widget to display your grid.

#### Shortcode Parameters

- `id` (required) - The grid ID
- `class` (optional) - Custom CSS class for styling

Example:
```
[instagram_grid id="123" class="my-custom-grid"]
```

### Managing Grids

- **Edit Grid** - Click the grid name in the grids list
- **Duplicate Grid** - Use the "Duplicate" action to create a copy
- **Delete Grid** - Use the "Delete" action (this cannot be undone)

### Adding URLs to Images

1. In the grid editor, right-click on any image with content
2. Enter the URL you want the image to link to
3. Leave blank to remove an existing link
4. Images with links will show a link indicator icon

### Managing Rows

- Click **Add Row Above** to insert a new row above the current row
- Click **Add Row Below** to insert a new row below the current row
- Existing images will be preserved and shifted accordingly

## Permissions

The plugin creates custom capabilities:

- `manage_instagram_grids` - View grids list
- `create_instagram_grids` - Create new grids
- `edit_instagram_grids` - Edit existing grids
- `delete_instagram_grids` - Delete grids

By default, these capabilities are granted to **Administrator** and **Editor** roles.

## Development

### File Structure

```
instagram-grid-preview/
├── instagram-grid-preview.php     # Main plugin file
├── includes/                      # Core plugin classes
│   ├── class-instagram-grid-preview.php
│   ├── class-igp-loader.php
│   ├── class-igp-grid-model.php
│   ├── class-igp-activator.php
│   ├── class-igp-deactivator.php
│   └── class-igp-i18n.php
├── admin/                         # Admin interface
│   ├── class-igp-admin.php
│   ├── js/igp-admin.js
│   ├── css/igp-admin.css
│   └── partials/
├── public/                        # Public-facing code
│   ├── class-igp-public.php
│   ├── js/igp-public.js
│   └── css/igp-public.css
└── languages/                     # Translation files
```

### Database Schema

The plugin creates one custom table: `{$prefix}igp_grids`

```sql
CREATE TABLE wp_igp_grids (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    description text,
    columns tinyint(3) NOT NULL DEFAULT 3,
    rows tinyint(3) NOT NULL DEFAULT 3,
    aspect_ratio varchar(10) NOT NULL DEFAULT '1:1',
    grid_data longtext,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

### Local Development

See [CLAUDE.md](CLAUDE.md) for detailed development guidance and architecture documentation.

## Frequently Asked Questions

**Q: Can I use the same image multiple times in a grid?**
A: Yes, you can add the same image to multiple cells.

**Q: What happens if I delete an image from the media library?**
A: The grid will display a placeholder for that cell. You'll need to update the grid with a new image.

**Q: Can I customize the grid styling?**
A: Yes, use the `class` parameter in the shortcode to add custom CSS classes, or override the plugin's CSS in your theme.

**Q: Is this compatible with page builders?**
A: Yes, the shortcode works in most page builders that support WordPress shortcodes.

**Q: Will this slow down my site?**
A: No, the plugin is lightweight and only loads assets on pages where grids are displayed.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes in each version.

## License

This plugin is licensed under the GNU General Public License v2.0 or later - see the [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/JensS/instagram-grid-preview/issues)
- **Documentation**: See this README and [CLAUDE.md](CLAUDE.md)

## Credits

Created by [Jens Sage](https://jenssage.com)

### Third-Party Libraries

- [Sortable.js](https://github.com/SortableJS/Sortable) - MIT License - Drag-and-drop functionality
- WordPress Media Library - GPLv2 - Image selection interface

## Roadmap

Future enhancements under consideration:

- [ ] Grid templates/presets
- [ ] Bulk image upload
- [ ] Caption overlay support
- [ ] Lightbox integration
- [ ] Grid animations
- [ ] Import/export grids
- [ ] Additional aspect ratios
