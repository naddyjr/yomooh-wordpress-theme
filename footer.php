<?php
/**
 * The footer Yomooh theme
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$footer_options = get_option('yomooh_options');
?>
    <?php 
     if (!isset($footer_options['footer_enable']) || $footer_options['footer_enable']) :
       if (!empty($footer_options['footer_template_shortcode']) && function_exists('do_shortcode')) {
         echo do_shortcode($footer_options['footer_template_shortcode']);
        } else {
          // Load default footer
         get_template_part('template-parts/footers/footer-default');
        }
        endif;
        ?>

    <?php 
    // Cookie notice
    if (!empty($footer_options['enable_cookie_notice']) && $footer_options['enable_cookie_notice']) :
        get_template_part('template-parts/footers/cookie-notice');
    endif;
// Scroll to top button
    scroll_to_top();
    ?>

    <?php wp_footer(); ?>
</body>
</html>
