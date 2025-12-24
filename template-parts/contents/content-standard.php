<?php
/** Standard blog post content
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('blog-post standard-post'); ?>>
    <?php if (!empty($options['blog_featured_image']) && has_post_thumbnail()) : ?>
        <div class="standard-post-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('large', array('class' => 'standard-post-img')); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="standard-content-wrapper">
        <header class="standard-entry-header">
            <?php if (!empty($options['blog_meta'])) : ?>
                <div class="standard-entry-meta">
                    <?php if (!empty($options['blog_meta']['author'])) : ?>
                        <span class="standard-meta-author"><?php echo __('By', 'yomooh') . ' '; ?><a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"><?php the_author(); ?></a></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['blog_meta']['date'])) : ?>
                        <span class="standard-meta-date"><?php echo get_the_date(); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['blog_meta']['comments']) && !post_password_required() && (comments_open() || get_comments_number())) : ?>
                        <span class="standard-meta-comments"><a href="<?php comments_link(); ?>"><?php comments_number(__('No comments', 'yomooh'), __('1 comment', 'yomooh'), __('% comments', 'yomooh')); ?></a></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['blog_meta']['reading_time'])) : ?>
					<span class="reading-time"><i class="wpi-watch" aria-hidden="true"></i><?php echo calculate_reading_time(); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php the_title('<h2 class="standard-entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>'); ?>
        </header>

        <div class="standard-entry-content">
            <?php
            if (has_excerpt()) {
                the_excerpt();
            } else {
                $excerpt_length = $options['blog_excerpt_length'] ?? 30;
                echo wp_trim_words(get_the_content(), $excerpt_length);
            }
            ?>
            
            <?php if (!empty($options['blog_read_more'])) : ?>
                <a href="<?php the_permalink(); ?>" class="read-more-link">
                    <?php echo esc_html($options['blog_read_more']); ?>
                    <i class="icon-arrow-right" style="margin-left: 8px;"></i>
                </a>
            <?php endif; ?>
        </div>

            <?php if ( (!empty($options['blog_meta']['categories']) && has_category()) || (!empty($options['blog_meta']['tags']) && has_tag()) ) : ?>
            <footer class="standard-entry-footer">
                 <?php if ((!empty($options['blog_meta']['categories']) && has_category()) || (!empty($options['blog_meta']['tags']) && has_tag())) : ?>
                <div class="list-entry-taxonomies">
                    <?php if (!empty($options['blog_meta']['categories']) && has_category()) : ?>
                        <?php the_category(' '); ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['blog_meta']['tags']) && has_tag()) : ?>
                        <?php the_tags('', ' ', ''); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </footer>
        <?php endif; ?>
    </div>
</article>