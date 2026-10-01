<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package InstagramGridPreview
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Registers the public profile route (/{instagram-grid}/{id}) and renders
 * the Instagram-style profile and grid.
 */
class IGP_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the public rewrite rule for profile pages.
     *
     * @since    1.2.0
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^instagram-grid/([0-9]+)/?$',
            'index.php?igp_profile=$matches[1]',
            'top'
        );

        // Flush rules once whenever the plugin version changes (e.g. after
        // an update that introduces new routes).
        if (get_option('igp_rewrite_version') !== IGP_VERSION) {
            flush_rewrite_rules(false);
            update_option('igp_rewrite_version', IGP_VERSION);
        }
    }

    /**
     * Register the custom query var.
     *
     * @since    1.2.0
     * @param    array    $vars    Existing query vars
     * @return   array    Modified query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'igp_profile';
        return $vars;
    }

    /**
     * Load the standalone profile template when the route is requested.
     *
     * @since    1.2.0
     * @param    string    $template    The template WordPress intends to load
     * @return   string    Modified template path
     */
    public function load_profile_template($template) {
        $grid_id = absint(get_query_var('igp_profile'));

        if (!$grid_id) {
            return $template;
        }

        $grid = IGP_Grid_Model::get_grid_data($grid_id);

        if (!$grid) {
            return $template;
        }

        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
        nocache_headers();

        // Keep these demo profiles out of search engines.
        header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex', true);

        set_query_var('igp_profile_grid', $grid);

        return IGP_PLUGIN_DIR . 'public/partials/igp-profile.php';
    }

    /**
     * Disallow the profile route in robots.txt.
     *
     * @since    1.2.0
     * @param    string    $output    The robots.txt contents
     * @param    bool      $public    Whether the site is public
     * @return   string    Modified robots.txt contents
     */
    public function add_robots_txt($output, $public) {
        if ($public && false === strpos($output, 'Disallow: /instagram-grid/')) {
            $output .= "\nDisallow: /instagram-grid/\n";
        }
        return $output;
    }

    /**
     * Build the profile data used by the template and the front-end viewer.
     *
     * @since    1.2.0
     * @param    array    $grid    Grid data
     * @return   array    Profile data
     */
    public static function get_profile($grid) {
        $profile = isset($grid['profile_data']) && is_array($grid['profile_data']) ? $grid['profile_data'] : array();
        $posts = IGP_Grid_Model::normalize_grid_data($grid['grid_data']);

        return array(
            'id' => intval($grid['id']),
            'username' => !empty($profile['username']) ? $profile['username'] : sanitize_title($grid['name']),
            'display_name' => !empty($profile['display_name']) ? $profile['display_name'] : $grid['name'],
            'bio' => !empty($profile['bio']) ? $profile['bio'] : $grid['description'],
            'website' => !empty($profile['website']) ? $profile['website'] : '',
            'avatar_url' => !empty($profile['avatar_url']) ? $profile['avatar_url'] : '',
            'followers' => isset($profile['followers']) ? max(0, intval($profile['followers'])) : 0,
            'following' => isset($profile['following']) ? max(0, intval($profile['following'])) : 0,
            'posts_count' => count($posts),
        );
    }

    /**
     * Render the profile grid (clickable tiles).
     *
     * @since    1.2.0
     * @param    array    $grid    Grid data
     * @return   string   HTML output
     */
    public static function render_grid($grid) {
        $posts = IGP_Grid_Model::normalize_grid_data($grid['grid_data']);
        $columns = max(1, intval($grid['columns']));
        $rows = max(1, intval($grid['rows']));
        $aspect_ratio = isset($grid['aspect_ratio']) ? $grid['aspect_ratio'] : '1:1';

        $html = '<div class="igp-grid" data-columns="' . esc_attr($columns) . '" data-rows="' . esc_attr($rows) . '" data-aspect-ratio="' . esc_attr($aspect_ratio) . '">';

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $columns; $col++) {
                $index = $row * $columns + $col;
                $post = isset($posts[$index]) ? $posts[$index] : null;

                if ($post) {
                    $html .= self::render_cell($post, $index, $columns);
                } else {
                    $html .= '<div class="igp-grid-cell igp-grid-cell--empty"></div>';
                }
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a single clickable grid tile.
     *
     * @since    1.2.0
     * @param    array    $post     Normalized post
     * @param    int      $index    Cell index
     * @return   string   HTML output
     */
    private static function render_cell($post, $index, $columns = 3) {
        $first = $post['media'][0];

        if (isset($first['source']) && 'bunny' === $first['source']) {
            $poster = IGP_Bunny::resolve_poster($first);
            if ($poster) {
                $first['thumbnail_url'] = $poster;
            }
        }

        $thumb = $first['thumbnail_url'];
        $alt = $first['alt'];

        $html = '<div class="igp-grid-cell" role="button" tabindex="0" data-post-index="' . esc_attr($index) . '" aria-label="' . esc_attr($alt !== '' ? $alt : __('Open post', 'instagram-grid-preview')) . '">';

        if ($thumb) {
            list($src, $srcset, $sizes) = self::get_grid_image_attrs($first, $columns);
            $html .= '<img src="' . esc_url($src) . '"';
            if ($srcset) {
                $html .= ' srcset="' . esc_attr($srcset) . '" sizes="' . esc_attr($sizes) . '"';
            }
            $html .= ' alt="' . esc_attr($alt) . '" class="igp-grid-image" loading="lazy" />';
        } else {
            // Video without a poster image.
            $html .= '<span class="igp-grid-media-placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>';
        }

        $badge = self::get_media_badge($post['media_type']);
        if ($badge) {
            $html .= $badge;
        }

        $html .= self::get_stats_overlay($post);
        $html .= '</div>';

        return $html;
    }

    /**
     * Build the top-right media type badge for a tile.
     *
     * @since    1.2.0
     * @param    string    $type    Media type
     * @return   string    Badge HTML or empty string
     */
    public static function get_media_badge($type) {
        $icons = array(
            'carousel' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="3" width="14" height="14" rx="2"/><path d="M17 21H5a2 2 0 0 1-2-2V7"/></svg>',
            'reel' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M10 8.5l6 3.5-6 3.5z" fill="currentColor" stroke="none"/></svg>',
            'video' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>',
        );

        if (!isset($icons[$type])) {
            return '';
        }

        return '<span class="igp-grid-badge igp-grid-badge--' . esc_attr($type) . '" aria-hidden="true">' . $icons[$type] . '</span>';
    }

    /**
     * Build the hover overlay with like/comment counts.
     *
     * @since    1.2.0
     * @param    array    $post    Normalized post
     * @return   string   Overlay HTML or empty string
     */
    public static function get_stats_overlay($post) {
        $likes = isset($post['likes']) ? intval($post['likes']) : 0;
        $comments = isset($post['comments']) ? intval($post['comments']) : 0;

        if ($likes <= 0 && $comments <= 0) {
            return '';
        }

        $heart = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
        $comment = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.656 17.008a9.993 9.993 0 1 0-3.59 3.615L22 22z"/></svg>';

        $html = '<span class="igp-grid-overlay" aria-hidden="true">';
        $html .= '<span class="igp-grid-stat">' . $heart . '<span>' . esc_html(self::format_count($likes)) . '</span></span>';
        $html .= '<span class="igp-grid-stat">' . $comment . '<span>' . esc_html(self::format_count($comments)) . '</span></span>';
        $html .= '</span>';

        return $html;
    }

    /**
     * Build the src/srcset/sizes for a grid tile image.
     *
     * Uses a medium-large rendition plus a responsive srcset when the
     * slide still references a WordPress image attachment.
     *
     * @since    1.2.0
     * @param    array    $slide      Media slide
     * @param    int      $columns    Number of grid columns
     * @return   array    array($src, $srcset, $sizes)
     */
    private static function get_grid_image_attrs($slide, $columns) {
        $src = $slide['thumbnail_url'];
        $srcset = '';
        $id = isset($slide['id']) ? intval($slide['id']) : 0;

        if ('image' === $slide['type'] && $id > 0 && function_exists('wp_attachment_is_image') && wp_attachment_is_image($id)) {
            $sized = wp_get_attachment_image_src($id, 'medium_large');
            if ($sized) {
                $src = $sized[0];
            }
            if (function_exists('wp_get_attachment_image_srcset')) {
                $maybe = wp_get_attachment_image_srcset($id, 'medium_large');
                if ($maybe) {
                    $srcset = $maybe;
                }
            }
        }

        $columns = max(1, intval($columns));
        $vw = (int) round(100 / $columns);
        $desktop = (int) round(935 / $columns);
        $sizes = '(max-width: 480px) ' . $vw . 'vw, ' . $desktop . 'px';

        return array($src, $srcset, $sizes);
    }

    /**
     * Get the URL used by the post viewer for a slide.
     *
     * Images use a large rendition instead of the full-size original;
     * videos use the video file itself.
     *
     * @since    1.2.0
     * @param    array    $slide    Media slide
     * @return   string   Display URL
     */
    public static function get_display_url($slide) {
        if ('video' === $slide['type']) {
            return $slide['url'];
        }

        $id = isset($slide['id']) ? intval($slide['id']) : 0;
        if ($id > 0 && function_exists('wp_get_attachment_image_src')) {
            $sized = wp_get_attachment_image_src($id, 'large');
            if ($sized) {
                return $sized[0];
            }
        }

        return $slide['url'];
    }

    /**
     * Get the srcset used by the post viewer for a slide.
     *
     * @since    1.2.0
     * @param    array    $slide    Media slide
     * @return   string   srcset or empty string
     */
    public static function get_display_srcset($slide) {
        if ('video' === $slide['type']) {
            return '';
        }

        $id = isset($slide['id']) ? intval($slide['id']) : 0;
        if ($id > 0 && function_exists('wp_get_attachment_image_srcset')) {
            $srcset = wp_get_attachment_image_srcset($id, 'large');
            if ($srcset) {
                return $srcset;
            }
        }

        return '';
    }

    /**
     * Format a count the way Instagram does (commas under 10k, K/M above).
     *
     * @since    1.2.0
     * @param    int       $count    The count to format
     * @return   string    Formatted count
     */
    public static function format_count($count) {
        $count = intval($count);

        if ($count < 10000) {
            return number_format_i18n($count);
        }

        if ($count < 1000000) {
            return rtrim(rtrim(number_format($count / 1000, 1), '0'), '.') . 'K';
        }

        return rtrim(rtrim(number_format($count / 1000000, 1), '0'), '.') . 'M';
    }
}
