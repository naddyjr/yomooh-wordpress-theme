<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */
defined('ABSPATH') || exit;

get_header();

$options = get_option('yomooh_options');
$sidebar_position = isset($options['wc_archive_sidebar_position']) ? $options['wc_archive_sidebar_position'] : 'left';
$archive_sidebar = isset($options['wc_archive_sidebar']) ? $options['wc_archive_sidebar'] : 'sidebar-shop';
$has_sidebar = $sidebar_position !== 'none' && is_active_sidebar($archive_sidebar);

$container_class = 'yomooh-shop-container';
if ($has_sidebar) {
    $container_class .= ' has-sidebar sidebar-' . $sidebar_position;
}

echo '<div class="' . esc_attr($container_class) . '">';

// Left sidebar
if ($has_sidebar && $sidebar_position === 'left') {
    wc_get_template('global/sidebar.php'); //  WooCommerce sidebar template
}

// Main content
$main_content_class = $has_sidebar ? 'yomooh-shop-main-content' : 'yomooh-shop-full-width';
echo '<div class="' . esc_attr($main_content_class) . '">';

// WooCommerce content hooks
do_action('woocommerce_before_main_content');

if (woocommerce_product_loop()) {
    do_action('woocommerce_before_shop_loop');
    woocommerce_product_loop_start();
    
    if (wc_get_loop_prop('total')) {
        while (have_posts()) {
            the_post();
            do_action('woocommerce_shop_loop');
            wc_get_template_part('content', 'product');
        }
    }
    
    woocommerce_product_loop_end();
    do_action('woocommerce_after_shop_loop');
} else {
    do_action('woocommerce_no_products_found');
}

do_action('woocommerce_after_main_content');

echo '</div>'; // .yomooh-shop-main-content

// Right sidebar
if ($has_sidebar && $sidebar_position === 'right') {
    wc_get_template('global/sidebar.php'); //  WooCommerce sidebar template
}

echo '</div>'; // .yomooh-shop-container

get_footer();