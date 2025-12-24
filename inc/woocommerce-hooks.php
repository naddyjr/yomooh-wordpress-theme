<?php
/**
 * Yomooh WooCommerce Hooks
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
/**
 * Ensure WooCommerce is active
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
    return; // Exit if WooCommerce is not active
}
function yomooh_woocommerce_scripts() {
    if (!function_exists('is_woocommerce')) return;
    // WooCommerce JS
    wp_enqueue_script('yomooh-woocommerce', get_template_directory_uri() . '/assets/js/woocommerce.min.js', array('jquery'), '1.1', true);
    // Localize script data
    wp_localize_script('yomooh-woocommerce', 'yomooh_wc_params', array(
        'ajax_url' => WC()->ajax_url(),
        'wc_ajax_nonce' => wp_create_nonce('wc_ajax_nonce'),
        'base_url' => remove_query_arg(array('view', 'cols'))
    ));
    // Load Dashicons
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'yomooh_woocommerce_scripts');

//  WooCommerce support
add_action('after_setup_theme', 'yomooh_woocommerce_support');
function yomooh_woocommerce_support() {
    if (!class_exists('WooCommerce')) return;
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
/**
 * Cleanup WooCommerce default wrappers & breadcrumbs
 */
function yomooh_woocommerce_cleanup() {
    if (!class_exists('WooCommerce')) return;

    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

    $options = get_option('yomooh_options');
    if (!empty($options['wc_show_breadcrumbs']) && !$options['wc_show_breadcrumbs']) {
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
    }
}
add_action('init', 'yomooh_woocommerce_cleanup');

// Shop sidebar layout
add_filter('body_class', 'yomooh_shop_sidebar_class');
function yomooh_shop_sidebar_class($classes) {
    if (!function_exists('is_woocommerce')) return $classes;
    if (is_shop() || is_product_category() || is_product_tag()) {
        $options = get_option('yomooh_options');
        $sidebar_position = isset($options['wc_archive_sidebar_position']) ? $options['wc_archive_sidebar_position'] : 'left';
        
        if ($sidebar_position !== 'none') {
            $classes[] = 'has-shop-sidebar';
            $classes[] = 'shop-sidebar-' . $sidebar_position;
        } else {
            $classes[] = 'no-shop-sidebar';
        }
    }
    return $classes;
}

// View switcher (grid/list)
add_action('woocommerce_before_shop_loop', 'yomooh_view_switcher', 35);
function yomooh_view_switcher() {
    if (!function_exists('is_woocommerce')) return;
    $current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'grid';
    $current_columns = isset($_GET['cols']) ? absint($_GET['cols']) : 3;
    $base_url = remove_query_arg(array('view', 'cols'));
    
    //  URLs for each view option
    $view_urls = array(
        'grid-2' => add_query_arg(array('view' => 'grid', 'cols' => 2), $base_url),
        'grid-3' => add_query_arg(array('view' => 'grid', 'cols' => 3), $base_url),
        'grid-4' => add_query_arg(array('view' => 'grid', 'cols' => 4), $base_url),
        'list' => add_query_arg('view', 'list', $base_url)
    );
    ?>
    
    <div class="yomooh-view-switcher">
        <a href="<?php echo esc_url($view_urls['grid-2']); ?>" 
           class="<?php echo ($current_view === 'grid' && $current_columns === 2) ? 'active' : ''; ?>" 
           title="<?php esc_attr_e('2 Columns', 'yomooh'); ?>">
            <span class="columns-2">
                <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="15" height="14" fill="none">
                    <g fill="currentColor">
                        <path d="M11.073 6a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM4.07 6a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM11.073 14a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.927 14a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"></path>
                    </g>
                </svg>
            </span>
        </a>
        
        <a href="<?php echo esc_url($view_urls['grid-3']); ?>" 
           class="<?php echo ($current_view === 'grid' && $current_columns === 3) ? 'active' : ''; ?>" 
           title="<?php esc_attr_e('3 Columns', 'yomooh'); ?>">
            <span class="columns-3">
                <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="15" height="14" fill="none">
                    <g fill="currentColor">
                        <path d="M2.073 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2.073 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2.073 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM7.073 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM7.073 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM7.073 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM12.073 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM12.073 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM12.073 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
                    </g>
                </svg>
            </span>
        </a>
        
        <a href="<?php echo esc_url($view_urls['grid-4']); ?>" 
           class="<?php echo ($current_view === 'grid' && $current_columns === 4) ? 'active' : ''; ?>" 
           title="<?php esc_attr_e('4 Columns', 'yomooh'); ?>">
            <span class="columns-4">
                <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="20" height="14" fill="none">
                    <g fill="currentColor">
                        <path d="M2.073 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2.073 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2.073 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM7.073 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM7.073 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM7.073 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM12.073 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM12.073 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM12.073 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17.073 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17.073 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17.073 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
                    </g>
                </svg>
            </span>
        </a>
        
        <a href="<?php echo esc_url($view_urls['list']); ?>" 
           class="<?php echo $current_view === 'list' ? 'active' : ''; ?>" 
           title="<?php esc_attr_e('List View', 'yomooh'); ?>">
            <span class="list-view">
                <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="20" height="14" fill="none">
                    <g fill="currentColor">
                        <path d="M2.073 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2.073 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2.073 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM19.073 2a1 1 0 0 1-1 1h-12a1 1 0 0 1 0-2h12a1 1 0 0 1 1 1ZM19.073 7a1 1 0 0 1-1 1h-12a1 1 0 0 1 0-2h12a1 1 0 0 1 1 1ZM19.073 12a1 1 0 0 1-1 1h-12a1 1 0 0 1 0-2h12a1 1 0 0 1 1 1Z"></path>
                    </g>
                </svg>
            </span>
        </a>
    </div>
    <?php
}

// Product loop classes based on view and columns
add_filter('post_class', 'yomooh_product_loop_classes');
function yomooh_product_loop_classes($classes) {
    if (!function_exists('is_woocommerce')) return;
    if ('product' === get_post_type()) {
        $current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'grid';
        $current_columns = isset($_GET['cols']) ? absint($_GET['cols']) : 3;
        
        $classes[] = 'yomooh-product-' . $current_view;
        
        if ($current_view === 'grid') {
            $classes[] = 'yomooh-grid-col-' . $current_columns;
        }
    }
    return $classes;
}
// Remove default WooCommerce outputs for list view
add_action('woocommerce_before_shop_loop', function() {
    if (!function_exists('is_woocommerce')) return;
    if (isset($_GET['view']) && $_GET['view'] === 'list') {
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
        remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    }
}, 5);

// Add custom list view content
add_action('woocommerce_after_shop_loop_item', 'yomooh_list_view_content', 10);
function yomooh_list_view_content() {
    if (!function_exists('is_woocommerce')) return;
    if (!isset($_GET['view']) || $_GET['view'] !== 'list') return;
    
    global $product;
    ?>
    <div class="yomooh-product-list-content">
        <?php if ($product->is_on_sale()) : ?>
            <span class="onsale"><?php esc_html_e('Sale', 'yomooh'); ?></span>
        <?php endif; ?>
        
        <h2 class="woocommerce-loop-product__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        
        <span class="price"><?php echo $product->get_price_html(); ?></span>
        
        <?php if ($product->get_rating_count() > 0) : ?>
            <div class="woocommerce-product-rating">
                <?php echo wc_get_rating_html($product->get_average_rating()); ?>
            </div>
        <?php endif; ?>
        
        <div class="yomooh-product-description">
            <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?>
        </div>
        
        <div class="yomooh-add-to-cart">
            <?php woocommerce_template_loop_add_to_cart(); ?>
        </div>
    </div>
    <?php
}
add_filter('loop_shop_per_page', 'yomooh_products_per_page', 20);
function yomooh_products_per_page($cols) {
    if (!function_exists('is_woocommerce')) return;
    $options = get_option('yomooh_options');
    $products_per_page = !empty($options['wc_products_per_page']) ? absint($options['wc_products_per_page']) : 10;
    return $products_per_page;
}

add_action('pre_get_posts', 'yomooh_wc_archive_pre_get_posts');
function yomooh_wc_archive_pre_get_posts($query) {
    if (!function_exists('is_woocommerce')) return;
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category() || is_product_tag())) {
        $options = get_option('yomooh_options');
        $products_per_page = !empty($options['wc_products_per_page']) ? absint($options['wc_products_per_page']) : 10;
        
        $query->set('posts_per_page', $products_per_page);
    }
}
remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
add_action('woocommerce_after_shop_loop', 'yomooh_wc_custom_pagination', 10);
function yomooh_wc_custom_pagination() {
    if (!function_exists('is_woocommerce')) return;
    if (woocommerce_products_will_display()) {
        $add_args = array();
        if (isset($_GET['view'])) {
            $add_args['view'] = sanitize_text_field($_GET['view']);
        }
        if (isset($_GET['cols'])) {
            $add_args['cols'] = absint($_GET['cols']);
        }

        $args = [
            'mid_size'  => 2,
            'prev_text' => '<i class="wpi-angle-left"></i> ' . esc_html__('Previous', 'yomooh'),
            'next_text' => esc_html__('Next', 'yomooh') . ' <i class="wpi-angle-right"></i>',
            'before_page_number' => '<span aria-label="' . esc_attr__('Page', 'yomooh') . ' %s" class="yomooh-wc-page-numbers">',
            'after_page_number'  => '</span>',
            'add_args'  => $add_args, 
        ];

        ?>
        <nav class="yomooh-wc-navigation yomooh-wc-pagination" aria-label="<?php esc_attr_e('Products pagination', 'yomooh'); ?>">
            <div class="yomooh-wc-nav-links">
                <?php echo paginate_links(apply_filters('yomooh_wc_pagination_args', $args)); ?>
            </div>
        </nav>
        <?php
    }
}
add_action('woocommerce_after_single_product_summary', 'yomooh_handle_related_products', 1);
function yomooh_handle_related_products() {
    if (!function_exists('is_woocommerce')) return;
    $options = get_option('yomooh_options');
    
    if (isset($options['wc_related_products']) && !$options['wc_related_products']) {
        remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    } else {
        add_filter('woocommerce_output_related_products_args', 'yomooh_related_products_args');
    }
}

function yomooh_related_products_args($args) {
    if (!function_exists('is_woocommerce')) return;
    return array(
        'posts_per_page' => 3, 
        'columns' => 3,         
        'orderby' => 'rand'    
    );
}

remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
add_action('woocommerce_before_shop_loop_item_title', 'yomooh_template_loop_product_thumbnail', 10);
function yomooh_template_loop_product_thumbnail() {
    if (!function_exists('is_woocommerce')) return;
    global $product;
    
    $image_size = apply_filters('single_product_archive_thumbnail_size', 'woocommerce_thumbnail');
    $gallery_ids = $product->get_gallery_image_ids();
    
    echo '<div class="yomooh-product-image-wrapper">';
    // Main image
    echo woocommerce_get_product_thumbnail($image_size);
    
    // Gallery images (hover effect)
    if (!empty($gallery_ids)) {
        echo '<div class="yomooh-product-gallery">';
        foreach ($gallery_ids as $gallery_id) {
            echo wp_get_attachment_image($gallery_id, $image_size);
        }
        echo '</div>';
    }
    
    echo '</div>';
}
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
add_action('woocommerce_after_single_product_summary', 'yomooh_wc_single_related_products', 10);

//  related products
function yomooh_wc_single_related_products() {
    if (!function_exists('is_woocommerce')) return;
    global $product;
    
    $related_products = wc_get_related_products($product->get_id(), 4);
    
    if ($related_products) {
        echo '<div class="yomooh-wc-related-products">';
        echo '<h2>' . esc_html__('Related Products', 'yomooh') . '</h2>';
        echo '<div class="yomooh-wc-related-columns">';
        
        foreach ($related_products as $related_product_id) {
            $related_product = wc_get_product($related_product_id);
            if ($related_product) {
                echo '<div class="yomooh-wc-related-product">';
                echo '<a href="' . get_permalink($related_product_id) . '">';
                echo $related_product->get_image('woocommerce_thumbnail');
                echo '<h3>' . $related_product->get_name() . '</h3>';
                echo '<span class="price">' . $related_product->get_price_html() . '</span>';
                echo '</a>';
                echo '</div>';
            }
        }
        
        echo '</div>'; // .yomooh-wc-related-columns
        echo '</div>'; // .yomooh-wc-related-products
    }
}