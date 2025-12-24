<?php
/** Single post content
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

$options = get_option('yomooh_options');

$single_social_sharing_position_meta = get_post_meta(get_the_ID(), 'single_social_sharing_position', true);

if (is_array($single_social_sharing_position_meta)) {
    $single_social_sharing_position_meta = reset($single_social_sharing_position_meta);
}
if (!empty($single_social_sharing_position_meta) && $single_social_sharing_position_meta !== 'default') {
    $social_sharing_position = $single_social_sharing_position_meta;
} else {
    $social_sharing_position = !empty($options['single_social_sharing_position'])
        ? $options['single_social_sharing_position']
        : '';
}

$post_settings = [
    'single_social_sharing_position' => $social_sharing_position,
    'single_featured_image' => (bool) (
        get_post_meta(get_the_ID(), 'single_featured_image', true) !== '' 
            ? intval(get_post_meta(get_the_ID(), 'single_featured_image', true)) 
            : (!empty($options['single_featured_image']))
    ),
];
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?>>
    <?php 
    // Featured image
    if (!empty($post_settings['single_featured_image']) && has_post_thumbnail()) : ?>
       <div class="single-post-thumbnail">
            <figure class="yomooh-featured">
                <?php the_post_thumbnail('yomooh-featured', [
                    'loading' => 'eager',
                    'alt' => esc_attr(get_the_title())
                ]); ?>

                <?php if (!empty($options['single_featured_image_caption'])) : 
                    $thumbnail_id = get_post_thumbnail_id();
                    $caption = wp_get_attachment_caption($thumbnail_id);
                    if ($caption) : ?>
                        <figcaption class="wp-caption-text"><?php echo esc_html($caption); ?></figcaption>
                    <?php endif;
                endif; ?>
            </figure>
        </div>
    <?php endif; ?>

    <header class="single-entry-header">
        <?php
        the_title('<h1 class="single-entry-title">', '</h1>');
        
        if (!empty($options['single_meta']['author'])) :
            echo '<div class="single-entry-meta">';

		if ( ! empty( $options['single_meta']['author'] ) ) :
				echo '<div class="post-author-metas">';
				// Author avatar & name
				echo '<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">';
				echo get_avatar( get_the_author_meta( 'ID' ), 60 );
				echo '</a>';

				echo '<div class="post-single-author-info">';

				// Author name
				echo '<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '" class="post-single-author-name">' . esc_html( get_the_author() ) . '</a>';

				if ( ! empty( $options['single_meta']['date'] ) ) {
					echo '<span class="post-date">' . esc_html( get_the_date( 'F j, Y \a\t g:i a' ) ) . '</span>';
				}

				echo '</div>'; // .post-single-author-info
				echo '</div>'; // .post-author-metas
			endif;
            
            // Categories
            if (!empty($options['single_meta']['categories']) && 'post' === get_post_type()) :
                $categories_list = get_the_category_list(' ');
                if ($categories_list) {
                    printf('<span class="cat-links">%s</span>', $categories_list);
                }
            endif;
            
            // Reading time + comments
            echo '<div class="post-single-meta-row">';
            if (!empty($options['single_meta']['reading_time'])) :
                $word_count = str_word_count(strip_tags(get_the_content()));
                $reading_time = ceil($word_count / 200);
                echo '<span class="single-reading-time"><i class="wpi-watch" aria-hidden="true"></i> ' . sprintf(_n('%d min read', '%d min read', $reading_time, 'yomooh'), $reading_time) . '</span>';
            endif;

            if (!empty($options['single_meta']['comments']) && !post_password_required() && (comments_open() || get_comments_number())) :
                echo '<span class="single-comments-link">';
                comments_popup_link(
                    sprintf(
                        wp_kses(
                            __('Leave a Comment<span class="screen-reader-text"> on %s</span>', 'yomooh'),
                            ['span' => ['class' => []]]
                        ),
                        get_the_title()
                    )
                );
                echo '</span>';
            endif;
            echo '</div>';

            // Social sharing (top) - Check both social sharing enabled AND position
            if (!empty($options['single_social_sharing']) &&
                in_array($post_settings['single_social_sharing_position'], ['top', 'both'])) :
                echo yomooh_get_icon();
            endif;
            echo '</div>'; // .single-entry-meta
        endif;
        ?>
    </header>

    <div class="single-entry-content">
        <?php
        the_content();
        wp_link_pages([
            'before' => '<div class="page-links">' . esc_html__('Pages:', 'yomooh'),
            'after'  => '</div>',
        ]);
        ?>
    </div>

    <?php if (!empty($options['single_meta']['tags']) && 'post' === get_post_type()) : ?>
        <footer class="single-entry-footer">
            <?php
            $tags_list = get_the_tag_list('', ' ');
            if ($tags_list) {
                printf('<span class="single-tags-links">' . esc_html__('Tagged %1$s', 'yomooh') . '</span>', $tags_list);
            }
            ?>
        </footer>
    <?php endif; ?>

    <?php 
    // Social sharing (bottom) - Check both social sharing enabled AND position
   if (!empty($options['single_social_sharing']) &&
    in_array($post_settings['single_social_sharing_position'], ['bottom', 'both'])) :
    echo yomooh_get_icon();
    endif;
    ?>

</article>
<?php
// Floating
if (!empty($options['single_social_sharing']) &&
    $post_settings['single_social_sharing_position'] === 'floating') :
echo yomooh_get_icon();endif;
?>