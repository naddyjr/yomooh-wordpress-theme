<?php
/** The template for displaying 404 pages (Not Found) 
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');

get_header();

// Check if custom 404 template is enabled
if (!empty($options['404_enable_custom']) && $options['404_enable_custom'] && !empty($options['404_template_shortcode']) && function_exists('do_shortcode')) {
    echo do_shortcode($options['404_template_shortcode']);
} else {
    ?>
    <div id="page404" class="page404-wrap">
        <div class="notfound">
            <?php if (!empty($options['404_image']['url'])) : ?>
                <div class="error-image-404">
                    <img src="<?php echo esc_url($options['404_image']['url']); ?>" alt="<?php esc_attr_e('404 Error', 'yomooh'); ?>">
                </div>
            <?php else : ?>
                <div class="notfound-404">
                    <h1>4<span>0</span>4</h1>
                </div>
            <?php endif; ?>
            <h1 class="error-title-404"><?php echo esc_html($options['404_title'] ?? __('Oops! That page can&rsquo;t be found.', 'yomooh')); ?></h1>
            <p class="error-description-404"><?php echo esc_html($options['404_description'] ?? __('It looks like nothing was found at this location. Maybe try a search?', 'yomooh')); ?></p>
			            <?php if (!empty($options['404_search_form'])) : ?>
							<div class="search-form-404">
							 <?php yomooh_search_form(); ?>
						</div>
			<?php endif; ?>
            <?php if (!empty($options['404_button_text'])) : ?>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="button home-button-404">
                    <?php echo esc_html($options['404_button_text']); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Check if footer should be shown
if (!isset($options['404_show_footer']) || $options['404_show_footer']) {
    get_footer();
} else {
    wp_footer();
    echo '</body></html>';
}
?>
