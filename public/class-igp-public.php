<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package InstagramGridPreview
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            IGP_PLUGIN_URL . 'public/css/igp-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            IGP_PLUGIN_URL . 'public/js/igp-public.js',
            array('jquery'),
            $this->version,
            true
        );
    }

    /**
     * Register shortcodes
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('instagram_grid', array($this, 'instagram_grid_shortcode'));
    }

    /**
     * Instagram grid shortcode handler
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function instagram_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'class' => ''
        ), $atts, 'instagram_grid');

        $grid_id = intval($atts['id']);
        $custom_class = sanitize_html_class($atts['class']);

        if ($grid_id <= 0) {
            return '<p>' . __('Invalid grid ID.', 'instagram-grid-preview') . '</p>';
        }

        $grid = IGP_Grid_Model::get_grid_data($grid_id);

        if (!$grid) {
            return '<p>' . __('Grid not found.', 'instagram-grid-preview') . '</p>';
        }

        return $this->render_grid($grid, $custom_class);
    }

    /**
     * Render the grid HTML
     *
     * @since    1.0.0
     * @param    array     $grid          Grid data
     * @param    string    $custom_class  Custom CSS class
     * @return   string    HTML output
     */
    private function render_grid($grid, $custom_class = '') {
        $grid_data = $grid['grid_data'];
        $columns = $grid['columns'];
        $rows = $grid['rows'];
        $aspect_ratio = isset($grid['aspect_ratio']) ? $grid['aspect_ratio'] : '1:1';

        $classes = array('igp-grid');
        if (!empty($custom_class)) {
            $classes[] = $custom_class;
        }

        $html = '<div class="' . implode(' ', $classes) . '" data-columns="' . $columns . '" data-rows="' . $rows . '" data-aspect-ratio="' . esc_attr($aspect_ratio) . '">';

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $columns; $col++) {
                $cell_index = $row * $columns + $col;
                $cell_data = isset($grid_data[$cell_index]) ? $grid_data[$cell_index] : null;

                $html .= '<div class="igp-grid-cell" data-row="' . $row . '" data-col="' . $col . '">';

                if ($cell_data && isset($cell_data['image_url'])) {
                    $html .= $this->render_cell($cell_data);
                } else {
                    $html .= '<div class="igp-grid-placeholder"></div>';
                }

                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a single populated grid cell.
     *
     * @since    1.1.0
     * @param    array    $cell_data    Sanitized cell data
     * @return   string   HTML output
     */
    private function render_cell($cell_data) {
        $image_url = esc_url($cell_data['image_url']);
        $image_alt = isset($cell_data['image_alt']) ? $cell_data['image_alt'] : '';
        $link_url = isset($cell_data['link_url']) ? $cell_data['link_url'] : '';

        $is_link = !empty($link_url);
        $tag = $is_link ? 'a' : 'span';
        $attrs = $is_link
            ? ' href="' . esc_url($link_url) . '" target="_blank" rel="noopener noreferrer"'
            : '';

        $html = '<' . $tag . ' class="igp-grid-item"' . $attrs . '>';
        $html .= '<img src="' . $image_url . '" alt="' . esc_attr($image_alt) . '" class="igp-grid-image" />';

        $badge = $this->get_media_badge($cell_data);
        if ($badge) {
            $html .= $badge;
        }

        $html .= $this->get_stats_overlay($cell_data);

        $html .= '</' . $tag . '>';

        return $html;
    }

    /**
     * Build the top-right media type badge for a cell.
     *
     * @since    1.1.0
     * @param    array    $cell_data    Sanitized cell data
     * @return   string   Badge HTML or empty string
     */
    private function get_media_badge($cell_data) {
        $type = isset($cell_data['media_type']) ? $cell_data['media_type'] : '';

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
     * @since    1.1.0
     * @param    array    $cell_data    Sanitized cell data
     * @return   string   Overlay HTML or empty string
     */
    private function get_stats_overlay($cell_data) {
        $likes = isset($cell_data['likes']) ? intval($cell_data['likes']) : 0;
        $comments = isset($cell_data['comments']) ? intval($cell_data['comments']) : 0;

        if ($likes <= 0 && $comments <= 0) {
            return '';
        }

        $heart = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78l8.84 8.84 8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>';
        $comment = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';

        $html = '<span class="igp-grid-overlay" aria-hidden="true">';
        $html .= '<span class="igp-grid-stat">' . $heart . '<span>' . esc_html($this->format_count($likes)) . '</span></span>';
        $html .= '<span class="igp-grid-stat">' . $comment . '<span>' . esc_html($this->format_count($comments)) . '</span></span>';
        $html .= '</span>';

        return $html;
    }

    /**
     * Format a count the way Instagram does (commas under 10k, K/M above).
     *
     * @since    1.1.0
     * @param    int       $count    The count to format
     * @return   string    Formatted count
     */
    private function format_count($count) {
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