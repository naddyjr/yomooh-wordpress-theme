<?php
/** The main template file
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */

get_header();

$options = get_option('yomooh_options');
$page_id = get_option('page_for_posts');
$page_meta = [
    'blog_sidebar'   => get_post_meta($page_id, 'blog_sidebar', true),
    'blogarchive_sidebar' => get_post_meta($page_id, 'blogarchive_sidebar', true),
    'blog_layout'  => get_post_meta($page_id, 'blog_layout', true),
    'blog_columns' => get_post_meta($page_id, 'blog_columns', true),
];

$blog_settings = [
    'layout' => (!empty($page_meta['blog_layout']) && $page_meta['blog_layout'] !== 'default') 
        ? $page_meta['blog_layout']
        : ($options['blog_layout'] ?? 'standard'),
    
    'columns' => (!empty($page_meta['blog_columns']) && $page_meta['blog_columns'] !== 'default') 
        ? $page_meta['blog_columns']
        : ($options['blog_columns'] ?? '3'),
    
    'sidebar_position' => (!empty($page_meta['blog_sidebar']) && $page_meta['blog_sidebar'] !== 'default') 
        ? $page_meta['blog_sidebar']
        : ($options['blog_sidebar'] ?? 'right-sidebar'),
    
    'pagination_type' => $options['blog_pagination'] ?? 'standard',
    'posts_per_page' => isset($options['blog_posts_per_page']) ? (int)$options['blog_posts_per_page'] : 10,
];
$blog_settings['sidebar_widget'] = ($blog_settings['sidebar_position'] === 'no-sidebar') 
    ? false
    : ((!empty($page_meta['blogarchive_sidebar']) && $page_meta['blogarchive_sidebar'] !== 'default')
        ? $page_meta['blogarchive_sidebar']
        : ($options['blogarchive_sidebar'] ?? 'sidebar-blog'));
global $wp_query;
$current_page = max(1, get_query_var('paged'));
$max_pages = $wp_query->max_num_pages;

?>
<main id="main-primary" class="site-main">
    <div class="page-content-container page-layout-<?php echo esc_attr(str_replace('-sidebar', '', $blog_settings['sidebar_position'])); ?>">
        <div class="page-content-wrapper">
            <?php if (have_posts()) : ?>
                <div class="blog-posts-container <?php echo esc_attr($blog_settings['layout']); ?>-layout columns-<?php echo esc_attr($blog_settings['columns']); ?>" 
                     data-posts-per-page="<?php echo esc_attr($blog_settings['posts_per_page']); ?>"
                     data-current-page="<?php echo esc_attr($current_page); ?>"
                     data-max-pages="<?php echo esc_attr($max_pages); ?>">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php get_template_part('template-parts/contents/content', $blog_settings['layout']); ?>
                    <?php endwhile; ?>
                </div>

                <?php 
                // Include pagination based on selected type
                get_template_part('template-parts/contents/pagination', $blog_settings['pagination_type']);
                ?>

            <?php else : ?>
                <?php get_template_part('template-parts/contents/content', 'none'); ?>
            <?php endif; ?>
        </div>

        <?php if ($blog_settings['sidebar_position'] !== 'no-sidebar' && $blog_settings['sidebar_widget'] && is_active_sidebar($blog_settings['sidebar_widget'])) : ?>
            <aside id="secondary-sidebar" class="widget-area sidebar-<?php echo esc_attr(str_replace('-sidebar', '', $blog_settings['sidebar_position'])); ?>" role="complementary">
                <?php dynamic_sidebar($blog_settings['sidebar_widget']); ?>
            </aside>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>