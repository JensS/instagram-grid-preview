<?php
/**
 * Plugin Name: Instagram Grid Preview
 * Plugin URI: https://jenssage.com/plugins/instagram-grid-preview
 * Description: Create Instagram-style grid layouts with WordPress media images. Display them using shortcodes with mobile-responsive design.
 * Version: 1.0.1
 * Author: Jens Sage
 * Author URI: https://jenssage.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: instagram-grid-preview
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package InstagramGridPreview
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('IGP_VERSION', '1.0.1');

/**
 * Plugin directory path
 */
define('IGP_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL
 */
define('IGP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename
 */
define('IGP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_instagram_grid_preview() {
    require_once IGP_PLUGIN_DIR . 'includes/class-igp-activator.php';
    IGP_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_instagram_grid_preview() {
    require_once IGP_PLUGIN_DIR . 'includes/class-igp-deactivator.php';
    IGP_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_instagram_grid_preview');
register_deactivation_hook(__FILE__, 'deactivate_instagram_grid_preview');

/**
 * The core plugin class
 */
require IGP_PLUGIN_DIR . 'includes/class-instagram-grid-preview.php';

/**
 * Plugin Update Checker
 * Enables automatic updates from GitHub
 */
require IGP_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$igpUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/JensS/instagram-grid-preview/',
    __FILE__,
    'instagram-grid-preview'
);

// Set the branch that contains the stable release
$igpUpdateChecker->setBranch('master');

// Optional: If using a private repository, you can set authentication
// $igpUpdateChecker->setAuthentication('your-token-here');

/**
 * Begins execution of the plugin.
 */
function run_instagram_grid_preview() {
    $plugin = new Instagram_Grid_Preview();
    $plugin->run();
}

/**
 * Check if we're running on WordPress or ClassicPress
 */
function igp_is_classicpress() {
    return function_exists('classicpress_version');
}

/**
 * Check minimum requirements
 */
function igp_check_requirements() {
    $errors = array();
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = sprintf(
            __('Instagram Grid Preview requires PHP 7.4 or higher. You are running PHP %s.', 'instagram-grid-preview'),
            PHP_VERSION
        );
    }
    
    // Check WordPress/ClassicPress version
    global $wp_version;
    if (igp_is_classicpress()) {
        if (version_compare(classicpress_version(), '1.0', '<')) {
            $errors[] = sprintf(
                __('Instagram Grid Preview requires ClassicPress 1.0 or higher. You are running ClassicPress %s.', 'instagram-grid-preview'),
                classicpress_version()
            );
        }
    } else {
        if (version_compare($wp_version, '5.0', '<')) {
            $errors[] = sprintf(
                __('Instagram Grid Preview requires WordPress 5.0 or higher. You are running WordPress %s.', 'instagram-grid-preview'),
                $wp_version
            );
        }
    }
    
    return $errors;
}

/**
 * Display admin notice for requirement errors
 */
function igp_requirements_notice() {
    $errors = igp_check_requirements();
    if (!empty($errors)) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . __('Instagram Grid Preview Plugin Error:', 'instagram-grid-preview') . '</strong><br>';
        echo implode('<br>', $errors);
        echo '</p></div>';
        
        // Deactivate the plugin
        deactivate_plugins(IGP_PLUGIN_BASENAME);
        return false;
    }
    return true;
}

// Check requirements before running the plugin
add_action('admin_notices', 'igp_requirements_notice');

// Only run the plugin if requirements are met
if (igp_check_requirements() === array()) {
    run_instagram_grid_preview();
}