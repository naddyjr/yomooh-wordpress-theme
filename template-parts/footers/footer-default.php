<?php
/** Default footer 
 * @package Yomooh  
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
if (empty($options['footer_enable'])) return;

$footer_classes = ['site-footer'];
$container_class = 'container';

// Footer width
if (!empty($options['footer_width'])) {
    if ($options['footer_width'] === 'full') {
        $container_class = 'container-fluid';
    } elseif ($options['footer_width'] === 'boxed') {
        $footer_classes[] = 'footer-boxed';
        $container_class = 'container';
    }
}

// Custom width
if (!empty($options['footer_custom_width']['width'])) {
    if ($options['footer_width'] === 'boxed') {
        $footer_classes[] = 'has-custom-width';
    }
}


// Social icons position
$social_position = !empty($options['footer_social_position']) ? $options['footer_social_position'] : 'bottom';
$show_social = !empty($options['footer_social_enable']) && $options['footer_social_enable'];
?>

<footer id="site-footer" class="<?php echo esc_attr(implode(' ', $footer_classes)); ?>">
    <?php if ($options['footer_width'] === 'boxed') : ?>
        <div class="footer-boxed-container">
    <?php endif; ?>

    <?php if (!empty($options['footer_widgets_enable']) && $options['footer_widgets_enable']) : ?>
        <div class="footer-widgets">
            <div class="line_wrap line_white">
               <div class="line_item"></div>
            </div>
            <div class="<?php echo esc_attr($container_class); ?>">
                <div class="footer-widgets-inner footer-layout-<?php echo esc_attr($options['footer_layout'] ?? '4-column'); ?>">
                    <?php
                    $widget_columns = substr($options['footer_layout'] ?? '4-column', 0, 1);
                    for ($i = 1; $i <= $widget_columns; $i++) :
                        if (is_active_sidebar('footer-' . $i)) :
                            ?>
                            <div class="footer-widget footer-widget-<?php echo esc_attr($i); ?>">
                                <?php dynamic_sidebar('footer-' . $i); ?>
                            </div>
                            <?php
                        endif;
                    endfor;
                    ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php 
    // Social icons at top
    if ($show_social && $social_position === 'top') : 
        render_social_icons($options);
    endif;
    ?>

    <?php if (!empty($options['footer_copyright_enable']) && $options['footer_copyright_enable']) : ?>
        <div class="footer-copyright">
            <div class="<?php echo esc_attr($container_class); ?>">
                <div class="copyright-inner">
                    <div class="copyright-text">
                        <?php 
                        if (!empty($options['footer_copyright_text'])) {
                            echo wp_kses_post($options['footer_copyright_text']);
                        } else {
                            echo '&copy; ' . date('Y') . ' ' . get_bloginfo('name') . '. ' . esc_html__('All Rights Reserved.', 'yomooh');
                        }
                        ?>
                    </div>
                    
                    <?php 
                    // Social icons at bottom (inside copyright section)
                    if ($show_social && $social_position === 'bottom') :
                        render_social_icons($options);
                    endif;
                    ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($options['footer_width'] === 'boxed') : ?>
        </div><!-- .footer-boxed-container -->
    <?php endif; ?>
</footer>

<?php
?>

