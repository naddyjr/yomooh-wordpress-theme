<?php
/** The template for displaying category archive pages 
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
get_header();
$options = get_option('yomooh_options');
$layout = $options['category_layout'] ?? 'standard';
$pagination_type = $options['blog_pagination'] ?? 'standard';
$columns = ($layout === 'grid') ? ($options['category_columns'] ?? '3') : '1'; 
$sidebar = $options['category_sidebar'] ?? 'right-sidebar';
$sidebar_id = $options['category_sidebarid'] ?? 'sidebar-blog';
$page_meta_layout = get_post_meta(get_the_ID(), 'category_sidebar', true);
$page_layout = ($page_meta_layout && $page_meta_layout !== 'default') ? $page_meta_layout : $sidebar;
$display_posts_per_page = isset($options['category_posts_per_page']) 
    ? (int)$options['category_posts_per_page'] 
    : 10;
global $wp_query;
$current_page = max(1, get_query_var('paged'));
$max_pages = $wp_query->max_num_pages;
?>

<main id="main-primary" class="site-main">
	 <?php 
    if (!empty($options['breadcrumbs_enable']) && !empty($options['category_breadcrumbs_enable'])) : 
        $position = isset($options['breadcrumbs_position']) ? $options['breadcrumbs_position'] : 'left';
    ?>
    <div class="breadcrumb-container breadcrumb-position-<?php echo esc_attr($position); ?>">
        <?php get_template_part('template-parts/contents/breadcrumb', '', [
            'position' => $position
        ]); ?>
    </div>
    <?php endif; ?>
    <header class="category-header">
    <?php
    // Get clean category name without "Category:" prefix
    $title = single_cat_title('', false);
    printf('<h1 class="archive-title">%s</h1>', esc_html__($title, 'yomooh'));
    the_archive_description('<div class="archive-description">', '</div>');
    ?>
</header>
    <div class="page-content-container page-layout-<?php echo esc_attr(str_replace('-sidebar', '', $page_layout)); ?>">
        <div class="page-content-wrapper">
            <?php if (have_posts()) : ?>
                <div class="blog-posts-container <?php echo esc_attr($layout); ?>-layout columns-<?php echo esc_attr($columns); ?>"
                    data-posts-per-page="<?php echo esc_attr($display_posts_per_page); ?>"
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