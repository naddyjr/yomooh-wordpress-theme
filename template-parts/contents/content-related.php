<?php
/** Related posts template
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
$post_id = get_the_ID();
$related_count = isset($options['single_related_posts_count']) ? absint($options['single_related_posts_count']) : 3;
$columns = isset($options['single_related_posts_columns']) ? absint($options['single_related_posts_columns']) : 3;

// Get related posts by category
$categories = get_the_category($post_id);
$category_ids = [];
foreach ($categories as $category) {
    $category_ids[] = $category->term_id;
}

$args = [
    'post_type' => 'post',
    'post__not_in' => [$post_id],
    'posts_per_page' => $related_count,
    'ignore_sticky_posts' => 1,
    'orderby' => 'rand',
    'tax_query' => [
        [
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $category_ids,
            'operator' => 'IN'
        ]
    ]
];

$related_query = new WP_Query($args);

if ($related_query->have_posts()) :
?>
<section class="related-posts columns-<?php echo esc_attr($columns); ?>">
    <h3 class="related-title"><?php esc_html_e('You May Also Like', 'yomooh'); ?></h3>
    <div class="related-posts-grid">
        <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
            <article <?php post_class('related-post'); ?>>
                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>" class="related-thumbnail">
                        <?php the_post_thumbnail('thumbnail'); ?>
                    </a>
                <?php endif; ?>
                <header class="related-header">
                    <h4 class="related-post-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>
                    <div class="related-meta">
                        <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                            <?php echo esc_html(get_the_date()); ?>
                        </time>
                    </div>
                </header>
            </article>
        <?php endwhile; ?>
    </div>
</section>
<?php
endif;
wp_reset_postdata();
?>