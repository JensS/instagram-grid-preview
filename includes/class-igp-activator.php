<?php
/**
 * Fired during plugin activation
 *
 * @package InstagramGridPreview
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class IGP_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_tables();
        self::set_default_capabilities();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create grids table
        $table_grids = $wpdb->prefix . 'igp_grids';
        
        $sql = "CREATE TABLE $table_grids (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            columns tinyint(3) NOT NULL DEFAULT 3,
            `rows` tinyint(3) NOT NULL DEFAULT 3,
            aspect_ratio varchar(10) NOT NULL DEFAULT '1:1',
            grid_data longtext,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql);
        
        // Update existing tables if needed
        self::update_tables();
    }

    /**
     * Update existing database tables
     *
     * @since    1.1.0
     */
    private static function update_tables() {
        global $wpdb;

        $table_grids = $wpdb->prefix . 'igp_grids';
        
        // Check if aspect_ratio column exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_grids LIKE %s",
            'aspect_ratio'
        ));
        
        // Add aspect_ratio column if it doesn't exist
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_grids ADD COLUMN aspect_ratio varchar(10) NOT NULL DEFAULT '1:1' AFTER `rows`");
        }
    }

    /**
     * Set default capabilities for user roles
     *
     * @since    1.0.0
     */
    private static function set_default_capabilities() {
        $capabilities = array(
            'manage_instagram_grids',
            'create_instagram_grids',
            'edit_instagram_grids',
            'delete_instagram_grids'
        );
        
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Add capabilities to editor role
        $editor_role = get_role('editor');
        if ($editor_role) {
            foreach ($capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }
    }
}