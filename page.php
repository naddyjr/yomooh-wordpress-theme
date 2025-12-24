<?php
/**
 * The template for displaying all pages
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
$page_meta = [
    'page_header_style' => get_post_meta(get_the_ID(), 'page_header_style', true),
    'page_breadcrumb'   => get_post_meta(get_the_ID(), 'page_breadcrumb', true),
    'pagelayout'        => get_post_meta(get_the_ID(), 'pagelayout', true),
    'page_sidebar'      => get_post_meta(get_the_ID(), 'page_sidebar', true),
    'footer_display'    => get_post_meta(get_the_ID(), 'footer_display', true),
    'blog_layout'       => get_post_meta(get_the_ID(), 'blog_layout', true),
    'blog_columns'      => get_post_meta(get_the_ID(), 'blog_columns', true),
];
$page_settings = [
    'header_style' => ($page_meta['page_header_style'] !== '' && $page_meta['page_header_style'] !== 'default') 
        ? $page_meta['page_header_style']
        : ($options['page_header_style'] ?? 'left-heading'),
    
    'breadcrumbs_enable' => ($page_meta['page_breadcrumb'] !== '' && $page_meta['page_breadcrumb'] !== 'default') 
        ? ($page_meta['page_breadcrumb'] === 'enable')
        : ($options['page_breadcrumbs_enable'] ?? true),
    
    'layout' => ($page_meta['pagelayout'] !== '' && $page_meta['pagelayout'] !== 'default') 
        ? $page_meta['pagelayout']
        : ($options['pagelayout'] ?? 'right-sidebar'),
    
    'sidebar' => ($page_meta['pagelayout'] === 'no-sidebar') 
        ? false
        : (($page_meta['page_sidebar'] !== '' && $page_meta['page_sidebar'] !== 'default')
            ? $page_meta['page_sidebar']
            : ($options['page_sidebar'] ?? 'sidebar-spage')),
    
    'footer_display' => ($page_meta['footer_display'] !== '' && $page_meta['footer_display'] !== 'default') 
        ? ($page_meta['footer_display'] === 'enable')
        : ($options['footer_display'] ?? true),
    
    'blog_layout' => ($page_meta['blog_layout'] !== '' && $page_meta['blog_layout'] !== 'default') 
        ? $page_meta['blog_layout']
        : ($options['blog_layout'] ?? 'standard'),
    
    'blog_columns' => ($page_meta['blog_columns'] !== '' && $page_meta['blog_columns'] !== 'default') 
        ? $page_meta['blog_columns']
        : ($options['blog_columns'] ?? '2'),
];

get_header();
?>

<main id="main-primary" class="site-main">
    <?php 
    if ($page_settings['breadcrumbs_enable'] && !empty($options['breadcrumbs_enable'])) : 
        $position = isset($options['breadcrumbs_position']) ? $options['breadcrumbs_position'] : 'left';
    ?>
    <div class="breadcrumb-container breadcrumb-position-<?php echo esc_attr($position); ?>">
        <?php get_template_part('template-parts/contents/breadcrumb', '', [
            'position' => $position
        ]); ?>
    </div>
    <?php endif; ?>

    <div class="page-content-container page-layout-<?php echo esc_attr(str_replace('-sidebar', '', $page_settings['layout'])); ?>">
        <div class="page-content-wrapper">
            <?php
            while (have_posts()) :
                the_post();

                set_query_var('header_style', $page_settings['header_style']);
                get_template_part('template-parts/contents/content', 'page');

                if (comments_open() || get_comments_number()) :
                    comments_template();
                endif;

            endwhile; // End of the loop.
            ?>
        </div>

        <?php if ($page_settings['layout'] !== 'no-sidebar' && $page_settings['sidebar'] && is_active_sidebar($page_settings['sidebar'])) : ?>
        <aside id="secondary-sidebar" class="widget-area sidebar-<?php echo esc_attr(str_replace('-sidebar', '', $page_settings['layout'])); ?>" role="complementary">
            <?php dynamic_sidebar($page_settings['sidebar']); ?>
        </aside>
        <?php endif; ?>
    </div>

</main>

<?php
if ($page_settings['footer_display']) {
    get_footer();
} else {
    wp_footer();
    echo '</body></html>';
}