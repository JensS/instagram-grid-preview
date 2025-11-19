<?php
/**
 * The grid model class
 *
 * @package InstagramGridPreview
 */

/**
 * The grid model class.
 *
 * This class handles all database operations for Instagram grids.
 */
class IGP_Grid_Model {

    /**
     * Get the table name
     *
     * @since    1.0.0
     * @return   string    The table name
     */
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'igp_grids';
    }

    /**
     * Get all grids
     *
     * @since    1.0.0
     * @return   array    Array of grid objects
     */
    public static function get_all_grids() {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $results = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY created_at DESC",
            OBJECT
        );
        
        return $results ? $results : array();
    }

    /**
     * Get a single grid by ID
     *
     * @since    1.0.0
     * @param    int    $id    The grid ID
     * @return   object|null    Grid object or null if not found
     */
    public static function get_grid($id) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $id
            ),
            OBJECT
        );
        
        return $result;
    }

    /**
     * Create a new grid
     *
     * @since    1.0.0
     * @param    string   $name         Grid name
     * @param    string   $description  Grid description
     * @param    int      $columns      Number of columns
     * @param    int      $rows         Number of rows
     * @param    string   $aspect_ratio Aspect ratio (1:1 or 3:4)
     * @param    string   $grid_data    Grid data as JSON string
     * @return   int|false    Grid ID on success, false on failure
     */
    public static function create_grid($name, $description = '', $columns = 3, $rows = 3, $aspect_ratio = '1:1', $grid_data = '[]') {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Validate aspect ratio
        $valid_ratios = array('1:1', '3:4');
        if (!in_array($aspect_ratio, $valid_ratios)) {
            $aspect_ratio = '1:1';
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'columns' => intval($columns),
                'rows' => intval($rows),
                'aspect_ratio' => $aspect_ratio,
                'grid_data' => $grid_data
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an existing grid
     *
     * @since    1.0.0
     * @param    int      $id      Grid ID
     * @param    array    $data    Grid data
     * @return   bool     True on success, false on failure
     */
    public static function update_grid($id, $data) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $update_data = array();
        $format = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }
        
        if (isset($data['columns'])) {
            $update_data['columns'] = intval($data['columns']);
            $format[] = '%d';
        }
        
        if (isset($data['rows'])) {
            $update_data['rows'] = intval($data['rows']);
            $format[] = '%d';
        }
        
        if (isset($data['aspect_ratio'])) {
            // Validate aspect ratio
            $valid_ratios = array('1:1', '3:4');
            $aspect_ratio = in_array($data['aspect_ratio'], $valid_ratios) ? $data['aspect_ratio'] : '1:1';
            $update_data['aspect_ratio'] = $aspect_ratio;
            $format[] = '%s';
        }
        
        if (isset($data['grid_data'])) {
            $update_data['grid_data'] = wp_json_encode($data['grid_data']);
            $format[] = '%s';
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Delete a grid
     *
     * @since    1.0.0
     * @param    int    $id    Grid ID
     * @return   bool   True on success, false on failure
     */
    public static function delete_grid($id) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Get grid data as array
     *
     * @since    1.0.0
     * @param    int    $id    Grid ID
     * @return   array|null    Grid data array or null if not found
     */
    public static function get_grid_data($id) {
        $grid = self::get_grid($id);
        
        if (!$grid) {
            return null;
        }
        
        $grid_data = json_decode($grid->grid_data, true);
        
        return array(
            'id' => $grid->id,
            'name' => $grid->name,
            'description' => $grid->description,
            'columns' => $grid->columns,
            'rows' => $grid->rows,
            'aspect_ratio' => isset($grid->aspect_ratio) ? $grid->aspect_ratio : '1:1',
            'grid_data' => $grid_data ? $grid_data : array(),
            'created_at' => $grid->created_at,
            'updated_at' => $grid->updated_at
        );
    }
}