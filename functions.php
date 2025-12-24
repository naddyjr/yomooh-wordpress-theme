<?php
/** Yomooh Theme functions and definitions
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

if (!defined('Yomooh_THEME_VERSION')) {
    define('Yomooh_THEME_VERSION', '1.0.0');
}
if (!defined('Yomooh_THEME_PATH')) {
    define('Yomooh_THEME_PATH', get_template_directory());
}
if (!defined('Yomooh_THEME_URI')) {
    define('Yomooh_THEME_URI', get_template_directory_uri());
}

// Enqueue scripts and styles
require_once Yomooh_THEME_PATH . '/inc/enqueue.php';
require_once Yomooh_THEME_PATH . '/inc/script-styles.php';

// Template Functions and classes
require_once Yomooh_THEME_PATH . '/inc/class-yomooh-theme.php';
require_once Yomooh_THEME_PATH . '/inc/template-tags.php';
require_once Yomooh_THEME_PATH . '/inc/class-yomooh-walker-nav.php';
require_once Yomooh_THEME_PATH . '/inc/woocommerce-hooks.php';
require_once Yomooh_THEME_PATH . '/import/theme-demo.php';

// Load TGM Plugin Activation
require_once Yomooh_THEME_PATH . '/inc/class-tgm-plugin-activation.php';
require_once Yomooh_THEME_PATH . '/inc/plugin-check.php';
// Load theme options if plugin is active
if (function_exists('is_core_plugin_active') && is_core_plugin_active()) {
    require_once Yomooh_THEME_PATH . '/inc/theme-options.php';
}

add_action('admin_notices', function() {
    if (!function_exists('is_core_plugin_active') || !is_core_plugin_active()) {
    ?>
    <div class="notice notice-error is-dismissible">
    <p>
    <?php
    printf(wp_kses_post(__('The required core plugin <strong>%s</strong> is not active. Please activate it to ensure the theme works properly.', 'yomooh')),'Yomooh Core Plugin:'
        ); ?></p>
        </div>
        <?php
    }
});
