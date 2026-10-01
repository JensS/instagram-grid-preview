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
     * Whether a column exists on the grids table.
     *
     * Guards writes so a not-yet-migrated install never fails to save.
     *
     * @since    1.2.2
     * @param    string    $column    Column name
     * @return   bool
     */
    private static function has_column($column) {
        global $wpdb;

        static $cache = array();

        if (isset($cache[$column])) {
            return $cache[$column];
        }

        $table = self::get_table_name();
        $found = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `$table` LIKE %s", $column));

        $cache[$column] = !empty($found);

        return $cache[$column];
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
     * @param    string   $profile_data Profile data as JSON string
     * @return   int|false    Grid ID on success, false on failure
     */
    public static function create_grid($name, $description = '', $columns = 3, $rows = 3, $aspect_ratio = '1:1', $grid_data = '[]', $profile_data = '{}') {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Validate aspect ratio
        $valid_ratios = array('1:1', '3:4');
        if (!in_array($aspect_ratio, $valid_ratios)) {
            $aspect_ratio = '1:1';
        }
        
        $insert = array(
            'name' => sanitize_text_field($name),
            'description' => sanitize_textarea_field($description),
            'columns' => intval($columns),
            'rows' => intval($rows),
            'aspect_ratio' => $aspect_ratio,
            'grid_data' => $grid_data,
        );
        $format = array('%s', '%s', '%d', '%d', '%s', '%s');

        if (self::has_column('profile_data')) {
            $insert['profile_data'] = $profile_data;
            $format[] = '%s';
        }

        $result = $wpdb->insert($table_name, $insert, $format);
        
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

        if (isset($data['profile_data']) && self::has_column('profile_data')) {
            $update_data['profile_data'] = wp_json_encode($data['profile_data']);
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

        $profile_data = isset($grid->profile_data) ? json_decode($grid->profile_data, true) : array();
        if (!is_array($profile_data)) {
            $profile_data = array();
        }

        return array(
            'id' => $grid->id,
            'name' => $grid->name,
            'description' => $grid->description,
            'columns' => $grid->columns,
            'rows' => $grid->rows,
            'aspect_ratio' => isset($grid->aspect_ratio) ? $grid->aspect_ratio : '1:1',
            'grid_data' => $grid_data ? $grid_data : array(),
            'profile_data' => $profile_data,
            'created_at' => $grid->created_at,
            'updated_at' => $grid->updated_at
        );
    }

    /**
     * Normalize a raw grid_data set into a list of "posts".
     *
     * Each post holds one or more media slides, a caption and engagement
     * counts. Legacy single-image cells are converted on the fly.
     *
     * @since    1.2.0
     * @param    array    $grid_data    Raw grid data (decoded)
     * @return   array    Map of cell index => normalized post
     */
    public static function normalize_grid_data($grid_data) {
        $posts = array();

        if (!is_array($grid_data)) {
            return $posts;
        }

        foreach ($grid_data as $index => $cell) {
            $post = self::normalize_post($cell);
            if ($post !== null) {
                $posts[intval($index)] = $post;
            }
        }

        return $posts;
    }

    /**
     * Normalize a single cell into a post structure.
     *
     * @since    1.2.0
     * @param    array         $cell    Raw cell data
     * @return   array|null    Normalized post or null when empty
     */
    public static function normalize_post($cell) {
        if (!is_array($cell)) {
            return null;
        }

        $media = array();

        if (isset($cell['media']) && is_array($cell['media'])) {
            foreach ($cell['media'] as $slide) {
                if (!is_array($slide)) {
                    continue;
                }

                $is_bunny = (isset($slide['source']) && 'bunny' === $slide['source'] && !empty($slide['bunny_id']));

                // A Bunny slide has no local URL; everything else needs one.
                if (empty($slide['url']) && !$is_bunny) {
                    continue;
                }

                $slide_type = (isset($slide['type']) && 'video' === $slide['type']) ? 'video' : 'image';
                $url = isset($slide['url']) ? $slide['url'] : '';

                // A video has no native poster; never fall back to the video
                // file itself as an image source.
                $thumb = !empty($slide['thumbnail_url']) ? $slide['thumbnail_url'] : ('video' === $slide_type ? '' : $url);

                $media[] = array(
                    'type' => $slide_type,
                    'source' => $is_bunny ? 'bunny' : 'upload',
                    'bunny_id' => $is_bunny ? (string) $slide['bunny_id'] : '',
                    'id' => isset($slide['id']) ? intval($slide['id']) : 0,
                    'url' => $url,
                    'thumbnail_url' => $thumb,
                    'poster_url' => !empty($slide['poster_url']) ? $slide['poster_url'] : '',
                    'alt' => isset($slide['alt']) ? $slide['alt'] : '',
                );
            }
        }

        // Fall back to the legacy single-image format.
        if (empty($media) && !empty($cell['image_url'])) {
            $media[] = array(
                'type' => 'image',
                'source' => 'upload',
                'bunny_id' => '',
                'id' => isset($cell['image_id']) ? intval($cell['image_id']) : 0,
                'url' => $cell['image_url'],
                'thumbnail_url' => !empty($cell['thumbnail_url']) ? $cell['thumbnail_url'] : $cell['image_url'],
                'poster_url' => '',
                'alt' => isset($cell['image_alt']) ? $cell['image_alt'] : '',
            );
        }

        if (empty($media)) {
            return null;
        }

        $type = isset($cell['media_type']) ? $cell['media_type'] : '';
        if (!in_array($type, array('photo', 'carousel', 'reel', 'video'), true)) {
            $type = self::derive_media_type($media);
        }

        return array(
            'media' => $media,
            'media_type' => $type,
            'caption' => isset($cell['caption']) ? $cell['caption'] : '',
            'likes' => isset($cell['likes']) ? max(0, intval($cell['likes'])) : 0,
            'comments' => isset($cell['comments']) ? max(0, intval($cell['comments'])) : 0,
            'link_url' => isset($cell['link_url']) ? $cell['link_url'] : '',
        );
    }

    /**
     * Derive the media type from a list of slides.
     *
     * @since    1.2.0
     * @param    array    $media    List of slides
     * @return   string   One of photo, carousel or video
     */
    private static function derive_media_type($media) {
        if (count($media) > 1) {
            return 'carousel';
        }
        if (isset($media[0]['type']) && 'video' === $media[0]['type']) {
            return 'video';
        }
        return 'photo';
    }
}