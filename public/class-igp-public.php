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
                    $image_url = esc_url($cell_data['image_url']);
                    $image_alt = isset($cell_data['image_alt']) ? esc_attr($cell_data['image_alt']) : '';
                    
                    // Check if image has a link
                    if (isset($cell_data['link_url']) && !empty($cell_data['link_url'])) {
                        $html .= '<a href="' . esc_url($cell_data['link_url']) . '" target="_blank" rel="noopener noreferrer">';
                        $html .= '<img src="' . $image_url . '" alt="' . $image_alt . '" class="igp-grid-image" />';
                        $html .= '</a>';
                    } else {
                        $html .= '<img src="' . $image_url . '" alt="' . $image_alt . '" class="igp-grid-image" />';
                    }
                } else {
                    $html .= '<div class="igp-grid-placeholder"></div>';
                }
                
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
}