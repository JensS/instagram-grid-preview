<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package InstagramGridPreview
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class IGP_Admin {

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
     * The grid model instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      IGP_Grid_Model    $grid_model    The grid model instance.
     */
    private $grid_model;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->grid_model = new IGP_Grid_Model();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        if (strpos($screen->id, 'instagram-grid') !== false) {
            wp_enqueue_style(
                $this->plugin_name,
                IGP_PLUGIN_URL . 'admin/css/igp-admin.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        if (strpos($screen->id, 'instagram-grid') !== false) {
            // Enqueue media uploader
            wp_enqueue_media();
            
            // Enqueue Sortable.js for drag and drop with SRI hash
            wp_enqueue_script(
                'sortablejs',
                'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
                array(),
                '1.15.0',
                true
            );

            // Add SRI integrity hash to Sortable.js script tag
            add_filter('script_loader_tag', array($this, 'add_sortablejs_integrity'), 10, 2);

            wp_enqueue_script(
                $this->plugin_name,
                IGP_PLUGIN_URL . 'admin/js/igp-admin.js',
                array('jquery', 'sortablejs'),
                $this->version,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script(
                $this->plugin_name,
                'igp_ajax',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('igp_nonce'),
                    'strings' => array(
                        'confirm_delete' => __('Are you sure you want to delete this grid?', 'instagram-grid-preview'),
                        'confirm_duplicate' => __('Are you sure you want to duplicate this grid?', 'instagram-grid-preview'),
                        'error_occurred' => __('An error occurred. Please try again.', 'instagram-grid-preview'),
                        'grid_saved' => __('Grid saved successfully!', 'instagram-grid-preview'),
                        'grid_deleted' => __('Grid deleted successfully!', 'instagram-grid-preview')
                    )
                )
            );
        }
    }

    /**
     * Add admin menu pages
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Instagram Grids', 'instagram-grid-preview'),
            __('Instagram Grids', 'instagram-grid-preview'),
            'manage_instagram_grids',
            'instagram-grids',
            array($this, 'display_grids_page'),
            'dashicons-grid-view',
            30
        );
        
        add_submenu_page(
            'instagram-grids',
            __('All Grids', 'instagram-grid-preview'),
            __('All Grids', 'instagram-grid-preview'),
            'manage_instagram_grids',
            'instagram-grids',
            array($this, 'display_grids_page')
        );
        
        add_submenu_page(
            'instagram-grids',
            __('Add New Grid', 'instagram-grid-preview'),
            __('Add New', 'instagram-grid-preview'),
            'create_instagram_grids',
            'instagram-grids-new',
            array($this, 'display_grid_editor')
        );
    }

    /**
     * Display the grids list page
     *
     * @since    1.0.0
     */
    public function display_grids_page() {
        $grids = $this->grid_model->get_all_grids();
        include IGP_PLUGIN_DIR . 'admin/partials/igp-admin-grids-list.php';
    }

    /**
     * Display the grid editor page
     *
     * @since    1.0.0
     */
    public function display_grid_editor() {
        $grid_id = isset($_GET['grid_id']) ? intval($_GET['grid_id']) : 0;
        $grid = null;
        
        if ($grid_id > 0) {
            $grid = $this->grid_model->get_grid_data($grid_id);
            if (!$grid) {
                wp_die(__('Grid not found.', 'instagram-grid-preview'));
            }
        }
        
        include IGP_PLUGIN_DIR . 'admin/partials/igp-admin-grid-editor.php';
    }

    /**
     * AJAX handler for saving grids
     *
     * @since    1.0.0
     */
    public function ajax_save_grid() {
        check_ajax_referer('igp_nonce', 'nonce');
        
        if (!current_user_can('create_instagram_grids')) {
            wp_die(__('You do not have permission to perform this action.', 'instagram-grid-preview'));
        }
        
        $grid_id = isset($_POST['grid_id']) ? intval($_POST['grid_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $columns = intval($_POST['columns']);
        $rows = intval($_POST['rows']);
        $aspect_ratio = isset($_POST['aspect_ratio']) ? sanitize_text_field($_POST['aspect_ratio']) : '1:1';

        // Validate and sanitize grid data
        $raw_grid_data = json_decode(stripslashes($_POST['grid_data']), true);
        $grid_data = array();

        if (is_array($raw_grid_data)) {
            foreach ($raw_grid_data as $index => $cell) {
                if (!is_array($cell)) {
                    continue;
                }

                $validated_cell = array();

                if (isset($cell['image_id'])) {
                    $validated_cell['image_id'] = intval($cell['image_id']);
                }

                if (isset($cell['image_url'])) {
                    $validated_cell['image_url'] = esc_url_raw($cell['image_url']);
                }

                if (isset($cell['thumbnail_url'])) {
                    $validated_cell['thumbnail_url'] = esc_url_raw($cell['thumbnail_url']);
                }

                if (isset($cell['image_alt'])) {
                    $validated_cell['image_alt'] = sanitize_text_field($cell['image_alt']);
                }

                if (isset($cell['link_url']) && !empty($cell['link_url'])) {
                    $validated_cell['link_url'] = esc_url_raw($cell['link_url']);
                }

                // Only add the cell if it has at least an image_url
                if (!empty($validated_cell) && isset($validated_cell['image_url'])) {
                    $grid_data[intval($index)] = $validated_cell;
                }
            }
        }

        $data = array(
            'name' => $name,
            'description' => $description,
            'columns' => $columns,
            'rows' => $rows,
            'aspect_ratio' => $aspect_ratio,
            'grid_data' => $grid_data
        );
        
        if ($grid_id > 0) {
            // Update existing grid
            $result = $this->grid_model->update_grid($grid_id, $data);
            $response_id = $grid_id;
        } else {
            // Create new grid
            $result = $this->grid_model->create_grid(
                $name,
                $description,
                $columns,
                $rows,
                $aspect_ratio,
                wp_json_encode($grid_data)
            );
            $response_id = $result;
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Grid saved successfully!', 'instagram-grid-preview'),
                'grid_id' => $response_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save grid.', 'instagram-grid-preview')
            ));
        }
    }

    /**
     * AJAX handler for deleting grids
     *
     * @since    1.0.0
     */
    public function ajax_delete_grid() {
        check_ajax_referer('igp_nonce', 'nonce');
        
        if (!current_user_can('delete_instagram_grids')) {
            wp_die(__('You do not have permission to perform this action.', 'instagram-grid-preview'));
        }
        
        $grid_id = intval($_POST['grid_id']);
        
        $result = $this->grid_model->delete_grid($grid_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Grid deleted successfully!', 'instagram-grid-preview')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete grid.', 'instagram-grid-preview')
            ));
        }
    }

    /**
     * AJAX handler for duplicating grids
     *
     * @since    1.0.0
     */
    public function ajax_duplicate_grid() {
        check_ajax_referer('igp_nonce', 'nonce');
        
        if (!current_user_can('create_instagram_grids')) {
            wp_die(__('You do not have permission to perform this action.', 'instagram-grid-preview'));
        }
        
        $grid_id = intval($_POST['grid_id']);
        $grid = $this->grid_model->get_grid_data($grid_id);
        
        if (!$grid) {
            wp_send_json_error(array(
                'message' => __('Grid not found.', 'instagram-grid-preview')
            ));
        }
        
        $new_name = sprintf(__('Copy of %s', 'instagram-grid-preview'), $grid['name']);
        
        $result = $this->grid_model->create_grid(
            $new_name,
            $grid['description'],
            $grid['columns'],
            $grid['rows'],
            $grid['aspect_ratio'],
            wp_json_encode($grid['grid_data'])
        );
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Grid duplicated successfully!', 'instagram-grid-preview')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to duplicate grid.', 'instagram-grid-preview')
            ));
        }
    }

    /**
     * AJAX handler for getting grid data
     *
     * @since    1.0.0
     */
    public function ajax_get_grid() {
        check_ajax_referer('igp_nonce', 'nonce');

        if (!current_user_can('edit_instagram_grids')) {
            wp_die(__('You do not have permission to perform this action.', 'instagram-grid-preview'));
        }

        $grid_id = intval($_POST['grid_id']);
        $grid = $this->grid_model->get_grid_data($grid_id);

        if ($grid) {
            wp_send_json_success($grid);
        } else {
            wp_send_json_error(array(
                'message' => __('Grid not found.', 'instagram-grid-preview')
            ));
        }
    }

    /**
     * Add SRI integrity hash to Sortable.js script tag
     *
     * @since    1.0.0
     * @param    string    $tag     The script tag HTML
     * @param    string    $handle  The script handle
     * @return   string    Modified script tag with integrity attribute
     */
    public function add_sortablejs_integrity($tag, $handle) {
        if ('sortablejs' === $handle) {
            // SRI hash for Sortable.js v1.15.0 from jsdelivr CDN
            $tag = str_replace(
                ' src=',
                ' integrity="sha256-ipiJrswvAR4VAx/th+6zWsdeYmVae0iJuiR+6OqHJHQ=" crossorigin="anonymous" src=',
                $tag
            );
        }
        return $tag;
    }
}