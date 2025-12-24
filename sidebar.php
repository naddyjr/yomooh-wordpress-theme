<?php
/** The sidebar template
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
$sidebar_id = 'sidebar-1'; 
$page_layout = 'right-sidebar'; 
if (is_search()) {
    $sidebar_id = $options['search_sidebar'] ?? 'sidebar-1';
    $page_layout = $options['search_layout'] ?? 'right-sidebar';
} elseif (is_page()) {
    $sidebar_id = $options['page_sidebar'] ?? 'sidebar-spage';
    $page_layout = get_post_meta(get_the_ID(), 'pagelayout', true) ?: 'default';
    $page_layout = ($page_layout === 'default') ? ($options['pagelayout'] ?? 'right-sidebar') : $page_layout;
} elseif (is_single() && get_post_type() === 'post') {
    $sidebar_id = $options['blogsingle_sidebar'] ?? 'sidebar-sblog';
    $page_layout = $options['single_sidebar'] ?? 'right-sidebar';
} elseif (is_home() || is_archive() || is_category() || is_tag()) {
    $sidebar_id = $options['blogarchive_sidebar'] ?? 'sidebar-blog';
    $page_layout = $options['blog_sidebar'] ?? 'right-sidebar';
}

// Clean layout format
$page_layout = str_replace('-sidebar', '', $page_layout);

// Check if sidebar should be displayed
if ($page_layout === 'no-sidebar' || !is_active_sidebar($sidebar_id)) {
    return;
}

// sidebar classes based on layout
$sidebar_classes = ['widget-area'];
if ($page_layout === 'left') {
    $sidebar_classes[] = 'sidebar-left';
} else {
    $sidebar_classes[] = 'sidebar-right';
}
?>

<aside id="secondary-sidebar" class="<?php echo esc_attr(implode(' ', $sidebar_classes)); ?>" role="complementary">
    <?php dynamic_sidebar($sidebar_id); ?>
</aside>