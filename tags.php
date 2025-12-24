<?php
/** The template for displaying tag archive pages
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
$columns = ($layout === 'grid') ? ($options['blog_columns'] ?? '3') : '1'; // Only use columns for grid layout
$sidebar = $options['blog_sidebar'] ?? 'right-sidebar';
$sidebar_id = $options['blogarchive_sidebar'] ?? 'sidebar-blog';
// Check for page-specific layout override
$page_meta_layout = get_post_meta(get_the_ID(), 'blog_sidebar', true);
$page_layout = ($page_meta_layout && $page_meta_layout !== 'default') ? $page_meta_layout : $sidebar;
?>

<main id="main-primary" class="site-main">
<header class="archive-header">
    <?php
    $title = single_cat_title('', false);
    printf('<h1 class="archive-title">%s</h1>', esc_html__($title, 'yomooh'));
    the_archive_description('<div class="archive-description">', '</div>');
	// Display tag cloud for this taxonomy
        $current_tag = get_queried_object();
        if ($current_tag && !empty($options['blog_meta']['tags'])) {
            echo '<div class="tag-cloud-container">';
            wp_tag_cloud([
                'taxonomy' => 'post_tag',
                'child_of' => $current_tag->term_id,
                'smallest' => 12,
                'largest'  => 22,
                'unit'     => 'px',
                'number'   => 20,
                'format'   => 'flat',
                'separator' => ' ',
                'show_count' => true,
            ]);
            echo '</div>';
        }
        ?>
    ?>
</header>
    <div class="page-content-container page-layout-<?php echo esc_attr(str_replace('-sidebar', '', $page_layout)); ?>">
        <div class="page-content-wrapper">
            <?php if (have_posts()) : ?>
                <div class="blog-posts-container <?php echo esc_attr($layout); ?>-layout columns-<?php echo esc_attr($columns); ?>">
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