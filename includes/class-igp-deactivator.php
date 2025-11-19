<?php
/**
 * Fired during plugin deactivation
 *
 * @package InstagramGridPreview
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class IGP_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't remove capabilities or drop tables on deactivation
        // This preserves user data in case they want to reactivate later
        // Tables and capabilities will only be removed on uninstall
    }
}