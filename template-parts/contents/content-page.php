<?php
/** The template part for displaying page content
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0  
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$header_style = get_query_var('header_style', 'left-heading');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    
    <?php if ($header_style !== 'no-heading') : ?>
    <div class="entry-page-header header-style-<?php echo esc_attr($header_style); ?>">
        <?php the_title('<h1 class="entry-page-title">', '</h1>'); ?>
    </div>
    <?php endif; ?>

    <?php if (has_post_thumbnail()) : ?>
        <div class="post-thumbnail">
            <?php the_post_thumbnail('large'); ?>
        </div>
    <?php endif; ?>

    <div class="entry-page-content">
        <?php
        the_content();

        wp_link_pages([
            'before' => '<div class="page-links">' . esc_html__('Pages:', 'yomooh'),
            'after'  => '</div>',
        ]);
        ?>
    </div>

    <?php if (get_edit_post_link()) : ?>
        <div class="entry-page-footer">
            <?php
            edit_post_link(
                sprintf(
                    wp_kses(
                        __('Edit <span class="screen-reader-text">%s</span>', 'yomooh'),
                        ['span' => ['class' => []]]
                    ),
                    get_the_title()
                ),
                '<span class="edit-link">',
                '</span>'
            );
            ?>
        </div>
    <?php endif; ?>
</article>