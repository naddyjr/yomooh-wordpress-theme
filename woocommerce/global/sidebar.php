<?php
/**
 * Sidebar
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/sidebar.php.
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$options = get_option('yomooh_options');

if ( is_shop() || is_product_category() || is_product_tag() ) {
	$sidebar = isset($options['wc_archive_sidebar']) ? $options['wc_archive_sidebar'] : 'sidebar-shop';
} elseif ( is_product() ) {
	$sidebar = isset($options['wc_single_sidebar']) ? $options['wc_single_sidebar'] : 'sidebar-product';
} else {
	$sidebar = 'sidebar-1';
}

if ( is_active_sidebar( $sidebar ) ) : ?>

	<aside id="wc-sidebar" class="widget-area yomooh-woocommerce-sidebar">
		<?php dynamic_sidebar( $sidebar ); ?>
	</aside>

<?php endif; ?>