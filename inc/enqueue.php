<?php
/** Theme asset enqueuing with automatic versioning
 *
 * @package Yomooh
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
function yomooh_theme_assets() {
    // Define asset paths
    $theme_path = get_template_directory();
    $theme_uri = get_template_directory_uri();
    
    // enqueue styles
    yomooh_enqueue_styles($theme_path, $theme_uri);
    
    // enqueue scripts
    yomooh_enqueue_theme_scripts($theme_path, $theme_uri);
    
    // Localize scripts and dynamic CSS
    yomooh_add_script_data();
}
add_action('wp_enqueue_scripts', 'yomooh_theme_assets');

/**
 * Handle all style enqueuing
 */
function yomooh_enqueue_styles($theme_path, $theme_uri) {
    // Iconly CSS
    wp_register_style(
        'iconly',
        $theme_uri . '/assets/iconly/css/style.min.css',
        [],
        filemtime($theme_path . '/assets/iconly/css/style.min.css')
    );

    // Main CSS
    wp_register_style(
        'yomooh-main',
        $theme_uri . '/assets/css/main.min.css',
        ['iconly'],
        filemtime($theme_path . '/assets/css/main.min.css')
    );
    
    // Theme stylesheet
    wp_register_style(
        'yomooh-style',
        get_stylesheet_uri(),
        ['yomooh-main'],
        filemtime($theme_path . '/style.css')
    );
    
    // Enqueue all styles
    wp_enqueue_style('iconly');
    wp_enqueue_style('yomooh-main');
    wp_enqueue_style('yomooh-style');
    
    //  dynamic CSS if plugin is active
    if (function_exists('is_core_plugin_active') && is_core_plugin_active()) {
        wp_add_inline_style('yomooh-main', yomooh_generate_dynamic_css());
    }
}

/**
 * Handle all script enqueuing
 */
function yomooh_enqueue_theme_scripts($theme_path, $theme_uri) {
    // Main JS bundle
    wp_register_script(
        'yomooh-main',
        $theme_uri . '/assets/js/main.min.js',
        ['jquery'],
        filemtime($theme_path . '/assets/js/main.min.js'),
        true
    );
    
    // Theme Toggle JS
    wp_register_script(
        'yomooh-theme-toggle',
        $theme_uri . '/assets/js/theme-toggle.min.js',
        ['yomooh-main'],
        filemtime($theme_path . '/assets/js/theme-toggle.min.js'),
        true
    );
    
    // Enqueue scripts
    wp_enqueue_script('yomooh-main');
    wp_enqueue_script('yomooh-theme-toggle');
    
    // Localize theme settings
    $options = get_option('yomooh_options');
    wp_localize_script('yomooh-theme-toggle', 'yomoohTheme', [
        'defaultTheme' => 'light',
        'customJS' => !empty($options['custom_js_code']) ? true : false
    ]);
    
    // Comment reply script
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}

/**
 * Localize scripts and add additional data
 */
function yomooh_add_script_data() {
    global $wp_query;
    wp_localize_script('yomooh-main', 'yomooh_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'sticky_enabled' => get_theme_mod('header_sticky', false),
        'mobile_menu_style' => get_theme_mod('mobile_menu_style', 'dropdown'),
        'security' => wp_create_nonce('ajax-login-nonce'), 
        'login_text' => __('Login', 'yomooh'), 
        'login_error' => __('An error occurred during login', 'yomooh'),
        'archive_query' => (is_archive() || is_home()) ? json_encode($wp_query->query ?? []) : null
    ]);
}

/**
 * Admin styles and scripts
 */
function yomooh_admin_assets() {
   wp_enqueue_style('iconly');
    
}
add_action('admin_enqueue_scripts', 'yomooh_admin_assets');

/**
 * Gutenberg editor styles
 */
function yomooh_editor_assets() {
    wp_enqueue_style('iconly');
}
add_action('enqueue_block_editor_assets', 'yomooh_editor_assets');
