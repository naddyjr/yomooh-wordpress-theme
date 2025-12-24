<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

defined('ABSPATH') || exit;

get_header();

$options = get_option('yomooh_options');
$sidebar_position = isset($options['wc_single_sidebar_position']) ? $options['wc_single_sidebar_position'] : 'left';
$has_sidebar = $sidebar_position !== 'none' && is_active_sidebar('sidebar-shop');
// Main content wrapper start
$container_class = 'yomooh-shop-container';
if ($has_sidebar) {
    $container_class .= ' has-sidebar sidebar-' . $sidebar_position;
}
echo '<div class="' . esc_attr($container_class) . '">';

if ($has_sidebar && $sidebar_position === 'left') {
    wc_get_template('global/sidebar.php'); //  WooCommerce sidebar template
}

// Main content
$main_content_class = $has_sidebar ? 'yomooh-shop-main-content' : 'yomooh-shop-full-width';
echo '<div class="' . esc_attr($main_content_class) . '">';
/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked yomooh_woocommerce_wrapper_start - 10
 * @hooked woocommerce_breadcrumb - 20 (if enabled)
 */
do_action('woocommerce_before_main_content');

while (have_posts()) :
    the_post();
    wc_get_template_part('content', 'single-product');
endwhile;

/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked yomooh_woocommerce_wrapper_end - 10
 */
do_action('woocommerce_after_main_content');

echo '</div>'; // .yomooh-product-main-content

if ($has_sidebar && $sidebar_position === 'right') {
    wc_get_template('global/sidebar.php'); //  WooCommerce sidebar template
}

echo '</div>'; // .yomooh-product-container

get_footer();