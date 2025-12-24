<?php
/** conntent-author template
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$author_id = get_the_author_meta('ID');
$author_description = get_the_author_meta('description');
$author_url = get_author_posts_url($author_id);
?>

<div class="author-boxs">
    <div class="author-avatars">
        <?php echo get_avatar($author_id, 100); ?>
    </div>
    <div class="author-infos">
		 <div class="author-tops">
        <h4 class="author-titles">
            <a href="<?php echo esc_url($author_url); ?>">
                <?php echo esc_html(get_the_author()); ?>
            </a>
        </h4>
		<?php user_social_links(); ?>
			  </div>
        <?php if ($author_description) : ?>
            <div class="author-bios">
                <?php echo wp_kses_post($author_description); ?>
            </div>
        <?php endif; ?>
        <div class="author-links">
            <a href="<?php echo esc_url($author_url); ?>" class="author-posts-link">
                <?php 
                printf(
                    esc_html__('View all posts by %s', 'yomooh'),
                    esc_html(get_the_author())
                ); 
                ?>
            </a>
            <?php if (get_the_author_meta('user_url')) : ?>
                <a href="<?php echo esc_url(get_the_author_meta('user_url')); ?>" class="author-website" target="_blank">
                    <?php esc_html_e('Website', 'yomooh'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>