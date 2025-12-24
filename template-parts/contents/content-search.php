<?php
/** The template part for displaying search results
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('search-result'); ?>>

    <header class="entry-header">
        <?php the_title(sprintf('<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>'); ?>
    </header>

    <div class="entry-summary">
        <?php the_excerpt(); ?>
    </div>

    <footer class="entry-footer">
        <?php
        printf(
            '<a href="%s" class="read-more">%s</a>',
            esc_url(get_permalink()),
            esc_html__('Continue reading', 'yomooh')
        );
        ?>
    </footer>

</article>