<?php
/** List blog post content
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0 
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('blog-post list-post'); ?>>
    <?php if (!empty($options['blog_featured_image']) && has_post_thumbnail()) : ?>
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium'); ?>
                <?php if (!empty($options['blog_meta']['reading_time'])) : ?>
                    <span class="reading-time"><i class="wpi-watch" aria-hidden="true"></i><?php echo calculate_top_time_reading(); ?></span>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="list-content-wrapper">
        <header class="list-entry-header">
            <?php if (!empty($options['blog_meta'])) : ?>
                <div class="list-entry-meta">
                    <?php if (!empty($options['blog_meta']['author'])) : ?>
                        <span class="meta-author"><?php echo __('By', 'yomooh') . ' '; ?><a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"><?php the_author(); ?></a></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['blog_meta']['date'])) : ?>
                        <span class="list-meta-date"><?php echo get_the_date(); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['blog_meta']['comments']) && comments_open()) : ?>
                        <span class="meta-comments">
                            <a href="<?php comments_link(); ?>">
                                <?php comments_number(__('No comments', 'yomooh'), __('1 comment', 'yomooh'), __('% comments', 'yomooh')); ?>
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php the_title('<h2 class="list-entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>'); ?>
        </header>

        <div class="list-entry-content">
            <?php
            $excerpt_length = $options['blog_excerpt_length'] ?? 25;
            echo wp_trim_words(get_the_excerpt(), $excerpt_length);
            ?>
        </div>

        <footer class="list-entry-footer">
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
            
            <?php if (!empty($options['blog_read_more'])) : ?>
                <a href="<?php the_permalink(); ?>" class="read-more-link">
                    <?php echo esc_html($options['blog_read_more']); ?>
                </a>
            <?php endif; ?>
        </footer>
    </div>
</article>