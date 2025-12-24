<?php
/** The template for displaying author archive pages
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
get_header();

$options = get_option('yomooh_options');
$layout = $options['blog_layout'] ?? 'standard';
$pagination_type = $options['blog_pagination'] ?? 'standard';
$columns = ($layout === 'grid') ? ($options['blog_columns'] ?? '3') : '1'; 
$sidebar = $options['blog_sidebar'] ?? 'right-sidebar';
$sidebar_id = $options['blogarchive_sidebar'] ?? 'sidebar-blog';
$page_meta_layout = get_post_meta(get_the_ID(), 'blog_sidebar', true);
$page_layout = ($page_meta_layout && $page_meta_layout !== 'default') ? $page_meta_layout : $sidebar;
$posts_per_page = isset($options['blog_posts_per_page']) ? (int)$options['blog_posts_per_page'] : 10;
global $wp_query;
$current_page = max(1, get_query_var('paged'));
$max_pages = $wp_query->max_num_pages;
?>
    <main id="main-primary" class="site-main">
       <header class="author-header">
    <div class="author-avatar-container">
        <?php echo get_avatar(get_the_author_meta('ID'), 96, '', '', ['class' => 'author-avatar']); ?>
        <div class="author-stats">
            <?php echo esc_html(count_user_posts(get_the_author_meta('ID'))); ?>
        </div>
    </div>
    
    <div class="author-info-container">
        <div class="author-name-social">
            <h1 class="author-name"><?php echo esc_html(get_the_author()); ?></h1>
            <div class="author-social"><?php echo user_social_links(); ?></div>
        </div>
        
        <?php if (get_the_author_meta('description')) : ?>
            <div class="author-bio"><?php echo wp_kses_post(get_the_author_meta('description')); ?></div>
        <?php endif; ?>
    </div>
</header>
    <div class="page-content-container page-layout-<?php echo esc_attr(str_replace('-sidebar', '', $page_layout)); ?>">
        <div class="page-content-wrapper">
            <?php if (have_posts()) : ?>
                <div class="blog-posts-container <?php echo esc_attr($layout); ?>-layout columns-<?php echo esc_attr($columns); ?>"data-posts-per-page="<?php echo esc_attr($posts_per_page); ?>"
                     data-current-page="<?php echo esc_attr($current_page); ?>"
                     data-max-pages="<?php echo esc_attr($max_pages); ?>">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php get_template_part('template-parts/contents/content', $layout); ?>
                    <?php endwhile; ?>
                </div>

                <?php 
                // Include pagination based on selected type
                get_template_part('template-parts/contents/pagination', $pagination_type);
                ?>

            <?php else : ?>
                <?php get_template_part('template-parts/contents/content', 'none'); ?>
            <?php endif; ?>
        </div>

        <?php if ($page_layout !== 'no-sidebar' && is_active_sidebar($sidebar_id)) : ?>
            <aside id="secondary-sidebar" class="widget-area sidebar-<?php echo esc_attr(str_replace('-sidebar', '', $page_layout)); ?>" role="complementary">
                <?php dynamic_sidebar($sidebar_id); ?>
            </aside>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>