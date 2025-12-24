<?php
/** The default template part for displaying content Acts as a fallback when more specific templates don't exist
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('content-fallback'); ?>>

    <?php if (!empty($options['blog_featured_image']) && has_post_thumbnail()) : ?>
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                <?php 
                the_post_thumbnail('large', [
                    'alt' => the_title_attribute(['echo' => false]),
                    'class' => 'fallback-thumbnail'
                ]); 
                ?>
            </a>
        </div>
    <?php endif; ?>

    <header class="entry-header">
        <?php
        if (is_singular()) :
            the_title('<h1 class="entry-title">', '</h1>');
        else :
            the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
        endif;
        ?>

        <?php if ('post' === get_post_type()) : ?>
            <div class="entry-meta">
                <?php
                yomooh_posted_on();
                yomooh_posted_by();
                ?>
            </div>
        <?php endif; ?>
    </header>

    <div class="entry-content">
        <?php
        if (is_singular() || (has_excerpt() && !is_archive())) :
            the_content(sprintf(
                wp_kses(
                    __('Continue reading<span class="screen-reader-text"> "%s"</span>', 'yomooh'),
                    ['span' => ['class' => []]]
                ),
                get_the_title()
            ));
        else :
            the_excerpt();
            printf(
                '<a href="%s" class="read-more-link">%s</a>',
                esc_url(get_permalink()),
                esc_html__('Read More', 'yomooh')
            );
        endif;

        wp_link_pages([
            'before' => '<div class="page-links">' . esc_html__('Pages:', 'yomooh'),
            'after'  => '</div>',
        ]);
        ?>
    </div>

    <footer class="entry-footer">
        <?php if ('post' === get_post_type()) : ?>
            <?php
            /* translators: used between list items, there is a space after the comma */
            $categories_list = get_the_category_list(esc_html__(', ', 'yomooh'));
            if ($categories_list) :
                printf('<span class="cat-links">%s</span>', $categories_list);
            endif;

            /* translators: used between list items, there is a space after the comma */
            $tags_list = get_the_tag_list('', esc_html_x(', ', 'list item separator', 'yomooh'));
            if ($tags_list) :
                printf('<span class="tags-links">%s</span>', $tags_list);
            endif;
            ?>
        <?php endif; ?>

        <?php if (!is_singular() && !post_password_required() && (comments_open() || get_comments_number())) : ?>
            <span class="comments-link">
                <?php comments_popup_link(); ?>
            </span>
        <?php endif; ?>

        <?php edit_post_link(
            sprintf(
                wp_kses(
                    __('Edit <span class="screen-reader-text">%s</span>', 'yomooh'),
                    ['span' => ['class' => []]]
                ),
                get_the_title()
            ),
            '<span class="edit-link">',
            '</span>'
        ); ?>
    </footer>
</article>