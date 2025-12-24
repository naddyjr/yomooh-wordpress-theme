<?php
/** Single post template yomooh
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0 
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
// Get theme options
$options = get_option('yomooh_options');
$post_meta = [
    'single_layout'      => get_post_meta(get_the_ID(), 'single_layout', true),
    'single_sidebar'     => get_post_meta(get_the_ID(), 'single_sidebar', true),
    'blogsingle_sidebar' => get_post_meta(get_the_ID(), 'blogsingle_sidebar', true),
    'footer_display'     => get_post_meta(get_the_ID(), 'footer_display', true),
     'single_author_box'    => get_post_meta(get_the_ID(), 'single_author_box', true),
    'single_related_posts' => get_post_meta(get_the_ID(), 'single_related_posts', true),
    'single_navigation'    => get_post_meta(get_the_ID(), 'single_navigation', true),
    'single_comments'      => get_post_meta(get_the_ID(), 'single_comments', true),
];
$post_settings = [
    'layout' => ($post_meta['single_layout'] !== '' && $post_meta['single_layout'] !== 'default') 
        ? $post_meta['single_layout']
        : ($options['single_layout'] ?? 'standard'),

	'sidebar_position' => ($post_meta['single_sidebar'] !== '' && $post_meta['single_sidebar'] !== 'default') 
        ? $post_meta['single_sidebar']
        : ($options['single_sidebar'] ?? 'right-sidebar'),
    
    'sidebar_widget' => ($post_meta['single_sidebar'] === 'no-sidebar') 
    ? false
    : (
        ($post_meta['blogsingle_sidebar'] !== '' && $post_meta['blogsingle_sidebar'] !== 'default')
            ? $post_meta['blogsingle_sidebar']
            : ($options['blogsingle_sidebar'] ?? 'sidebar-sblog')
    ),

    'footer_display' => ($post_meta['footer_display'] !== '' && $post_meta['footer_display'] !== 'default') 
        ? ($post_meta['footer_display'] === 'enable')
        : ($options['footer_display'] ?? true),
    
    'breadcrumbs_enable' => !empty($options['breadcrumbs_enable']) && !empty($options['post_breadcrumbs_enable']),
    'breadcrumbs_position' => $options['breadcrumbs_position'] ?? 'left',
    'single_author_box' => ($post_meta['single_author_box'] !== '') 
        ? (bool) intval($post_meta['single_author_box']) 
        : (!empty($options['single_author_box'])),
    
    'single_related_posts' => ($post_meta['single_related_posts'] !== '') 
        ? (bool) intval($post_meta['single_related_posts']) 
        : (!empty($options['single_related_posts'])),
    
    'single_navigation' => ($post_meta['single_navigation'] !== '') 
        ? (bool) intval($post_meta['single_navigation']) 
        : (!empty($options['single_navigation'])),
    
    'single_comments' => ($post_meta['single_comments'] !== '') 
        ? (bool) intval($post_meta['single_comments']) 
        : (!empty($options['single_comments'])),
];

get_header();
?>

<main id="main-primary" class="site-main">
    <?php 
    if ($post_settings['breadcrumbs_enable']) : 
    ?>
    <div class="breadcrumb-container breadcrumb-position-<?php echo esc_attr($post_settings['breadcrumbs_position']); ?>">
        <?php get_template_part('template-parts/contents/breadcrumb', '', [
            'position' => $post_settings['breadcrumbs_position']
        ]); ?>
    </div>
    <?php endif; ?>
    
    <div class="page-content-container single-layout-<?php echo esc_attr($post_settings['layout']); ?> single-<?php echo esc_attr(str_replace('-sidebar', '', $post_settings['sidebar_position'])); ?>">
        <div class="single-content-wrapper">
            <?php
            while (have_posts()) :
                the_post();

                // Get single post template part
                get_template_part('template-parts/contents/content', 'single');

                // Post navigation
                if ($post_settings['single_navigation']) :
                    the_post_navigation([
                        'prev_text' => '<span class="nav-subtitle"><i class="wpi-angle-left"></i> ' . esc_html__('Previous', 'yomooh') . '</span> <span class="nav-title">%title</span>',
                        'next_text' => '<span class="nav-subtitle">' . esc_html__('Next', 'yomooh') . ' <i class="wpi-angle-right"></i></span> <span class="nav-title">%title</span>',
                    ]);
                endif;

                // Author box
                if ($post_settings['single_author_box']) :
                    get_template_part('template-parts/contents/content', 'author');
                endif;

                // Related posts
                if ($post_settings['single_related_posts']) :
                    get_template_part('template-parts/contents/content', 'related');
                endif;

                // Comments
                if ($post_settings['single_comments']) :
                    if (comments_open() || get_comments_number()) :
                        comments_template();
                    endif;
                endif;

            endwhile; // End of the loop.
            ?>
        </div>

        <?php if ($post_settings['layout'] !== 'fullwidth' && $post_settings['sidebar_position'] !== 'no-sidebar' && $post_settings['sidebar_widget'] && is_active_sidebar($post_settings['sidebar_widget'])) : ?>
        <aside id="secondary-sidebar" class="widget-area sidebar-<?php echo esc_attr(str_replace('-sidebar', '', $post_settings['sidebar_position'])); ?>" role="complementary">
            <?php dynamic_sidebar($post_settings['sidebar_widget']); ?>
        </aside>
        <?php endif; ?>
    </div>
</main>

<?php
if ($post_settings['footer_display']) {
    get_footer();
} else {
    wp_footer();
    echo '</body></html>';
}