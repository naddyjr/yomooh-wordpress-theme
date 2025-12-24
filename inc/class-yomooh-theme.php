<?php
/** Main theme class registering widget areas and theme setup
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
class Yomooh_Theme {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    protected function init_hooks() {
        // Register widget areas
        add_action('widgets_init', [$this, 'register_widget_areas']);
        add_action('after_setup_theme', [$this, 'theme_setup']);
    }
    
    public function register_widget_areas() {
         register_sidebar([
            'name'          => __('Sidebar', 'yomooh'),
            'id'            => 'sidebar-1',
            'description'   => __('Add widgets here to appear in your sidebar.', 'yomooh'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
        register_sidebar([
            'name'          => __('Sidebar blog', 'yomooh'),
            'id'            => 'sidebar-blog',
            'description'   => __('Add widgets here to appear in your sidebar blog.', 'yomooh'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
        register_sidebar([
            'name'          => __('Sidebar single page', 'yomooh'),
            'id'            => 'sidebar-spage',
            'description'   => __('Add widgets here to appear in your sidebar single page.', 'yomooh'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
        register_sidebar([
            'name'          => __('Sidebar single blog', 'yomooh'),
            'id'            => 'sidebar-sblog',
            'description'   => __('Add widgets here to appear in your sidebar single blog.', 'yomooh'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
        // WooCommerce-specific sidebars
    if ( class_exists('WooCommerce') ) {
        register_sidebar([
            'name'          => __('Shop Sidebar', 'yomooh'),
            'id'            => 'sidebar-shop',
            'description'   => __('Widgets for the WooCommerce Shop page.', 'yomooh'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);

        register_sidebar([
            'name'          => __('Product Sidebar', 'yomooh'),
            'id'            => 'sidebar-product',
            'description'   => __('Widgets for single WooCommerce product pages.', 'yomooh'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
    }
        
        // Footer widget areas
        for ($i = 1; $i <= 4; $i++) {
            register_sidebar([
                'name'          => sprintf(__('Footer %d', 'yomooh'), $i),
                'id'            => 'footer-' . $i,
                'description'   => __('Add widgets here to appear in your footer.', 'yomooh'),
                'before_widget' => '<section id="%1$s" class="widget %2$s">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2 class="widget-title">',
                'after_title'   => '</h2>',
            ]);
        }
    }
    
    public function theme_setup() {
        // Load text domain
        load_theme_textdomain('yomooh', get_template_directory() . '/languages');
        
        // theme support
		add_theme_support( 'post-formats', array( 'gallery', 'video', 'audio' ) );
        add_theme_support('automatic-feed-links');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ]);
        
        // Registered nav menus
        register_nav_menus([
            'primary' => __('Primary Menu', 'yomooh'),
            'footer'  => __('Footer Menu', 'yomooh'),
            'mobile'  => __('Mobile Menu', 'yomooh'),
			'quick-link'  => __('Quick link Menu', 'yomooh'),
        ]);
        
        //  image sizes
        add_image_size('yomooh-featured', 1200, 800, true);
        add_image_size('yomooh-thumbnail', 400, 400, true);
    }
}
new Yomooh_Theme();
