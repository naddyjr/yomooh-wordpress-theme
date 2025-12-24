<?php
/** Breadcrumb navigation
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');

// Check if breadcrumbs are enabled
if (empty($options['breadcrumbs_enable'])) {
    return;
}
// Get breadcrumb settings
$home_text = $options['breadcrumbs_home_text'] ?? __('Home', 'yomooh');
$separator = $options['breadcrumbs_separator'] ?? '/';
$show_current = $options['breadcrumbs_show_current'] ?? true;
$position = $options['breadcrumbs_position'] ?? 'left'; 
?>

<nav class="yomooh-breadcrumbs" data-position="<?php echo esc_attr($position); ?>" aria-label="<?php esc_attr_e('Breadcrumb', 'yomooh'); ?>">
    <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <!-- Home Breadcrumb -->
        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?php echo esc_url(home_url('/')); ?>" itemprop="item">
                <span itemprop="name"><?php echo esc_html($home_text); ?></span>
            </a>
            <meta itemprop="position" content="1" />
        </li>
        
        <?php 
        // Track if we need to show separator
        $needs_separator = false;
        
        if (is_category() || is_single() || is_page()|| is_tag()) : 
            $needs_separator = true;
            
            if (is_category()) : ?>
                <li class="breadcrumb-separator"><?php echo esc_html($separator); ?></li>
                <li class="breadcrumb-item">
                    <?php single_cat_title(); ?>
                </li>
		 <?php elseif (is_tag()) : ?>
                <li class="breadcrumb-separator"><?php echo esc_html($separator); ?></li>
                <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name"><?php single_tag_title(); ?></span>
                    <meta itemprop="position" content="2" />
                </li>
            <?php elseif (is_page()) : ?>
                <?php if ($post->post_parent) : ?>
                    <?php 
                    $ancestors = get_post_ancestors($post->ID);
                    $ancestors = array_reverse($ancestors);
                    
                    foreach ($ancestors as $ancestor) : ?>
                        <li class="breadcrumb-separator"><?php echo esc_html($separator); ?></li>
                        <li class="breadcrumb-item">
                            <a href="<?php echo esc_url(get_permalink($ancestor)); ?>">
                                <?php echo esc_html(get_the_title($ancestor)); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($show_current) : ?>
                    <?php if ($needs_separator || $post->post_parent) : ?>
                        <li class="breadcrumb-separator"><?php echo esc_html($separator); ?></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active">
                        <?php the_title(); ?>
                    </li>
                <?php endif; ?>
                
            <?php elseif (is_single()) : ?>
                <li class="breadcrumb-separator"><?php echo esc_html($separator); ?></li>
                <li class="breadcrumb-item">
                    <?php 
                    $categories = get_the_category();
                    if (!empty($categories)) {
                        echo esc_html($categories[0]->cat_name);
                    }
                    ?>
                </li>
                
                <?php if ($show_current) : ?>
                    <li class="breadcrumb-separator"><?php echo esc_html($separator); ?></li>
                    <li class="breadcrumb-item active">
                        <?php the_title(); ?>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
            
        <?php elseif (is_search()) : ?>
            <li class="breadcrumb-separator"><?php echo esc_html($separator); ?></li>
            <li class="breadcrumb-item active">
				<?php
				printf(
					esc_html__('You searched for: %s', 'yomooh'),
					'<span class="search-keyword">' . esc_html(get_search_query()) . '</span>'
				);
				?>
			</li>
        <?php elseif (is_404()) : ?>
            <li class="breadcrumb-separator"><?php echo esc_html($separator); ?></li>
            <li class="breadcrumb-item active">
                <?php esc_html_e('404 Not Found', 'yomooh'); ?>
            </li>
        <?php endif; ?>
    </ol>
</nav>