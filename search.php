<?php
/** The template for displaying search results pages
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
$layout = isset($options['search_layout']) ? $options['search_layout'] : 'right-sidebar';
$show_pagination = isset($options['search_pagination']) ? $options['search_pagination'] : true;
$sidebar_id = isset($options['search_sidebar']) ? $options['search_sidebar'] : 'sidebar-blog';
$bloglayout = $options['blog_layout'] ?? 'standard';
$posts_per_page = isset($options['blog_posts_per_page']) ? (int)$options['blog_posts_per_page'] : 10;
global $wp_query;
$current_page = max(1, get_query_var('paged'));
$max_pages = $wp_query->max_num_pages;

// Function to highlight search terms
function yomooh_highlight_search_term($text) {
    $search_term = get_search_query();
    if (!empty($search_term)) {
        $text = preg_replace(
            '/(' . preg_quote($search_term, '/') . ')/i', 
            '<span class="search-highlight">$1</span>', 
            $text
        );
    }
    return $text;
}

get_header();
?>

<main id="main-primary" class="site-main">
     <?php 
    if (!empty($options['breadcrumbs_enable']) && !empty($options['search_breadcrumbs_enable'])) : 
        $position = isset($options['breadcrumbs_position']) ? $options['breadcrumbs_position'] : 'left';
    ?>
    <div class="breadcrumb-container breadcrumb-position-<?php echo esc_attr($position); ?>">
        <?php get_template_part('template-parts/contents/breadcrumb', '', [
            'position' => $position
        ]); ?>
    </div>
    <?php endif; ?>
    
    <div class="page-content-container search-layout-<?php echo esc_attr(str_replace('-sidebar', '', $layout)); ?>">
        <div class="page-content-wrapper">
            <header class="spage-header">
                <h1 class="spage-title">
                    <?php
                    printf(
                        esc_html__('Search Results for: %s', 'yomooh'),
                        '<span>' . esc_html(get_search_query(false)) . '</span>'
                    );
                    ?>
                </h1>
                <div class="search-meta">
                    <?php 
                    // Get the actual post types being queried
                    global $wp_query;
                    $queried_post_types = array_unique(wp_list_pluck($wp_query->posts, 'post_type'));
                    
                    $post_type_labels = [];
                    foreach ($queried_post_types as $type) {
                        $post_type_obj = get_post_type_object($type);
                        if ($post_type_obj) {
                            $post_type_labels[] = $post_type_obj->labels->singular_name;
                        }
                    }
                    if (!empty($post_type_labels)) {
                    echo '<span class="search-post-types">';
                    echo esc_html__('Searching in: ', 'yomooh') . implode(', ', $post_type_labels);
                    yomooh_search_form(); // Call the function outside echo
                    echo '</span>';
                }

                    ?>
                </div>
				<div class="search-form-wide">
				
			</div>
            </header>

            <?php if (have_posts()) : ?>
                <div class="blog-posts-container <?php echo esc_attr($layout); ?>-layout"data-posts-per-page="<?php echo esc_attr($posts_per_page); ?>"
                     data-current-page="<?php echo esc_attr($current_page); ?>"
                     data-max-pages="<?php echo esc_attr($max_pages); ?>">
                    <?php
                    while (have_posts()) : the_post();
                        // Highlight search terms if enabled
                        if (!empty($options['search_highlight']) && $options['search_highlight']) {
                            add_filter('the_title', 'yomooh_highlight_search_term');
                            add_filter('the_excerpt', 'yomooh_highlight_search_term');
                            add_filter('the_content', 'yomooh_highlight_search_term');
                        }

                        get_template_part('template-parts/contents/content', $bloglayout);

                        // Remove highlight filters after each post
                        if (!empty($options['search_highlight']) && $options['search_highlight']) {
                            remove_filter('the_title', 'yomooh_highlight_search_term');
                            remove_filter('the_excerpt', 'yomooh_highlight_search_term');
                            remove_filter('the_content', 'yomooh_highlight_search_term');
                        }
                    endwhile;
                    ?>
                </div>

                <?php 
                // Include pagination if enabled
                if ($show_pagination) {
                    $pagination_type = isset($options['pagination_type']) ? $options['pagination_type'] : 'numbered';
                    get_template_part('template-parts/contents/pagination', $pagination_type);
                }
                ?>

            <?php else : ?>
                <?php get_template_part('template-parts/contents/content', 'none'); ?>
            <?php endif; ?>
        </div>

        <?php if ($layout !== 'no-sidebar' && is_active_sidebar($sidebar_id)) : ?>
        <aside id="secondary-sidebar" class="widget-area sidebar-<?php echo esc_attr(str_replace('-sidebar', '', $layout)); ?>" role="complementary">
            <?php dynamic_sidebar($sidebar_id); ?>
        </aside>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();