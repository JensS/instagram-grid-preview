# Instagram Grid Preview

A WordPress/ClassicPress plugin that turns your media library into a public, Instagram-style profile page. Each profile has its own URL, and clicking a tile opens a post viewer with a full slideshow (images and video), caption and like/comment counts.

## Features

- **Public Profile Page** - Each grid gets its own standalone Instagram-style profile at `/instagram-grid/{id}`
- **Post Viewer** - Click a tile to open a slideshow with arrows, dots and keyboard navigation
- **Carousels** - Add multiple images to a single post
- **Video Posts** - Add video slides that play in the viewer, with a separate poster image
- **Profile Details** - Avatar, username, display name, bio, website, followers and following
- **Visual Grid Editor** - Drag-and-drop interface for arranging posts
- **Media Type Badges** - Carousel, Reel and Video badges shown in the tile corner
- **Hover Overlay** - Like and comment counts, formatted like Instagram (e.g. 12.5K)
- **Multiple Aspect Ratios** - Support for 1:1 (square) and 3:4 (portrait) ratios
- **Responsive Design** - Profiles adapt beautifully to all screen sizes
- **Private by Default** - Profile pages are excluded from search engines
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

### Creating a Profile

1. Go to **Instagram Grids** → **Add New** in your WordPress admin
2. Fill in the profile details (avatar, username, display name, bio, website, followers, following)
3. Set the internal name, dimensions (columns and rows) and aspect ratio
4. Click an empty tile to add images and/or video from your media library
5. Click a filled tile to edit the caption, media type, likes, comments and link
6. Drag and drop to reorder posts
7. Click **Save Profile**

### Viewing the Profile

After saving, the public profile is available at:

```
https://example.com/instagram-grid/{id}
```

The URL is shown in the editor and in the profiles list, with a copy button and a "View profile" link.

### The Post Viewer

Clicking a tile opens the Instagram-style post viewer:

- Slideshow navigation with arrows, dots and the left/right arrow keys
- Images display full-size; video slides play inline with controls
- Caption, media type, like count, comment count and date are shown alongside

### Carousels and Video

- **Carousel** - select two or more images for a single post
- **Video** - add a video from the media library. WordPress/ClassicPress do not generate video posters, so open the post and use the camera button on the video slide to choose a poster image. The poster is used in the grid tile and viewer.

### Managing Profiles

- **Edit Profile** - Click the profile name in the list
- **View Profile** - Open the public profile page
- **Duplicate Profile** - Use the "Duplicate" action to create a copy
- **Delete Profile** - Use the "Delete" action (this cannot be undone)

### Adding Links to Posts

1. Open a post in the editor
2. Enter a URL in the **Link URL** field
3. Leave blank to remove an existing link

### Managing Rows

- Click **Add Row Above** to insert a new row above the current row
- Click **Add Row Below** to insert a new row below the current row
- Existing posts will be preserved and shifted accordingly

## SEO

Profile pages are intentionally excluded from search engines:

- `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex` HTTP header
- `<meta name="robots">` and `<meta name="googlebot">` tags
- `Disallow: /instagram-grid/` in `robots.txt` (when the site is public)

## Permissions

The plugin creates custom capabilities:

- `manage_instagram_grids` - View profiles list
- `create_instagram_grids` - Create new profiles
- `edit_instagram_grids` - Edit existing profiles
- `delete_instagram_grids` - Delete profiles

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
│   ├── partials/igp-profile.php   # Standalone profile template
│   ├── js/igp-profile.js          # Post viewer
│   ├── css/igp-public.css         # Grid styles
│   └── css/igp-profile.css        # Profile + viewer styles
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
    grid_data longtext,      -- JSON: map of cell index => post
    profile_data longtext,   -- JSON: profile fields
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

Each post stores an array of media slides:

```json
{
  "0": {
    "media": [
      { "type": "image", "id": 12, "url": "…", "thumbnail_url": "…", "alt": "" },
      { "type": "video", "id": 34, "url": "…", "thumbnail_url": "…", "alt": "" }
    ],
    "media_type": "carousel",
    "caption": "Hello",
    "likes": 120,
    "comments": 8,
    "link_url": ""
  }
}
```

### Local Development

See [AGENTS.md](AGENTS.md) for detailed development guidance and architecture documentation.

## Frequently Asked Questions

**Q: Where can I find the public profile page?**
A: Every profile has its own URL in the form `/instagram-grid/{id}`. It is shown in the editor and the profiles list.

**Q: Can I embed a profile inside a page?**
A: Not currently. Profiles are served as standalone Instagram-style pages; link to the profile URL from any menu, post or page.

**Q: How do I add a poster image to a video?**
A: Open the post in the editor and click the camera button on the video slide to choose a poster image.

**Q: What happens if I delete an image from the media library?**
A: The tile will display a placeholder. You'll need to update the post with a new image.

**Q: Can I customize the styling?**
A: Yes, override the plugin's CSS (`igp-public.css`, `igp-profile.css`) in your theme.

**Q: Will this slow down my site?**
A: No, the plugin only loads its assets on profile pages.

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
- **Documentation**: See this README and [AGENTS.md](AGENTS.md)

## Credits

Created by [Jens Sage](https://jenssage.com)

### Third-Party Libraries

- [Sortable.js](https://github.com/SortableJS/Sortable) - MIT License - Drag-and-drop functionality
- WordPress Media Library - GPLv2 - Media selection interface

## Roadmap

Future enhancements under consideration:

- [ ] Import/export profiles
- [ ] Grid templates/presets
- [ ] Additional aspect ratios
- [ ] Optional comments list in the post viewer
- [ ] Custom domain / slug for profile URLs
