<?php
/** The template for displaying comments
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
if (post_password_required()) {
    return;
}
$options = get_option('yomooh_options');
?>

<div id="comments" class="comments-area">

    <div class="comment-header-row">
        <div class="comment-count-toggle" onclick="toggleComments()">
            <i class="wpi-comment"></i>
            <span class="comments-title">
                <?php
                $comments_number = get_comments_number();
                if ('1' === $comments_number) {
                    printf(
                        esc_html__('One Comment', 'yomooh'),
                        '<span>' . get_the_title() . '</span>'
                    );
                } else {
                    printf(
                        esc_html(_n('%1$s Comment', '%1$s Comments', $comments_number, 'yomooh')),
                        number_format_i18n($comments_number),
                        '<span>' . get_the_title() . '</span>'
                    );
                }
                ?>
            </span>
        </div>
        
        <?php if (comments_open() && !isset($_GET['replytocom'])) : ?>
            <button class="comment-form-toggle" onclick="toggleCommentForm()" id="comment-form-toggle-button">
                <i class="wp-icon-comment-o"></i>
                <span><?php esc_html_e('Leave a Comment', 'yomooh'); ?></span>
            </button>
        <?php endif; ?>
    </div>

    <?php if (have_comments()) : ?>
        <div class="comment-list-container" id="comment-list-container">
            <?php the_comments_navigation(); ?>

            <ol class="comment-list">
                <?php
                wp_list_comments([
                    'style'       => 'ol',
                    'short_ping'  => true,
                    'avatar_size' => 60,
                    'callback'    => 'yomooh_comment_callback',
                ]);
                ?>
            </ol>

            <?php the_comments_navigation(); ?>
        </div>
    <?php else : ?>
        <div class="comment-list-container" id="comment-list-container" style="display:none;">
            <p class="no-comments"><?php esc_html_e('Be the first one to comment!', 'yomooh'); ?></p>
        </div>
    <?php endif; ?>

    <?php
    if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) :
        ?>
        <p class="no-comments"><?php esc_html_e('Comments are closed.', 'yomooh'); ?></p>
    <?php endif; ?>

    <?php if (comments_open()) : ?>
        <div id="comment-form-container" class="comment-form-container" <?php if (isset($_GET['replytocom']) || isset($_GET['edit-comment'])) echo 'style="display:block;"'; ?>>
            <?php
            comment_form([
                'title_reply'          => esc_html__('Leave a Comment', 'yomooh'),
                'title_reply_to'       => esc_html__('Leave a Reply to %s', 'yomooh'),
                'cancel_reply_link'    => esc_html__('Cancel Reply', 'yomooh'),
                'label_submit'         => esc_html__('Post Comment', 'yomooh'),
                'comment_field'        => '<textarea id="comment" name="comment" cols="45" rows="8" aria-required="true" placeholder="' . esc_attr__('Your comment...', 'yomooh') . '"></textarea>',
                'must_log_in'          => '<p class="must-log-in">' . sprintf(
                    esc_html__('You must be %1$slogged in%2$s to post a comment.', 'yomooh'),
                    '<a href="' . esc_url(wp_login_url(get_permalink())) . '">',
                    '</a>'
                ) . '</p>',
                'logged_in_as'         => '<p class="logged-in-as">' . sprintf(
                    esc_html__('Logged in as %1$s. %2$sLog out &raquo;%3$s', 'yomooh'),
                    '<a href="' . esc_url(get_edit_user_link()) . '">' . esc_html($user_identity) . '</a>',
                    '<a href="' . esc_url(wp_logout_url(get_permalink())) . '" title="' . esc_attr__('Log out of this account', 'yomooh') . '">',
                    '</a>'
                ) . '</p>',
                'comment_notes_before' => '<p class="comment-notes">' . esc_html__('Your email address will not be published.', 'yomooh') . '</p>',
                'comment_notes_after'  => '',
                'fields'              => apply_filters('comment_form_default_fields', [
                    'author' => '<div class="comment-form-author"><input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" placeholder="' . esc_attr__('Name', 'yomooh') . '" required /></div>',
                    'email'  => '<div class="comment-form-email"><input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '" size="30" placeholder="' . esc_attr__('Email', 'yomooh') . '" required /></div>',
                    'url'    => '<div class="comment-form-url"><input id="url" name="url" type="url" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" placeholder="' . esc_attr__('Website', 'yomooh') . '" /></div>',
                ]),
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>

<?php
function yomooh_comment_callback($comment, $args, $depth) {
    $tag = ('div' === $args['style']) ? 'div' : 'li';
    ?>
    <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class(empty($args['has_children']) ? '' : 'parent'); ?>>
        <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
            <footer class="comment-meta">
                <div class="comment-author vcard">
                    <?php if (0 != $args['avatar_size']) echo get_avatar($comment, $args['avatar_size']); ?>
                    <?php printf('<cite class="fn">%s</cite>', get_comment_author_link()); ?>
                </div>

                <div class="comment-metadata">
                    <a href="<?php echo esc_url(get_comment_link($comment->comment_ID, $args)); ?>">
                        <time datetime="<?php comment_time('c'); ?>">
                            <?php printf(esc_html__('%1$s at %2$s', 'yomooh'), get_comment_date(), get_comment_time()); ?>
                        </time>
                    </a>
                    <?php edit_comment_link(esc_html__('Edit', 'yomooh'), '<span class="edit-link">', '</span>'); ?>
                <div class="reply">
                <?php
                comment_reply_link(array_merge($args, [
                    'add_below' => 'div-comment',
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                    'before'    => '<span class="reply-link">',
                    'after'     => '</span>'
                ]));
                ?>
            </div>
				</div>

                <?php if ('0' == $comment->comment_approved) : ?>
                    <p class="comment-awaiting-moderation"><?php esc_html_e('Your comment is awaiting moderation.', 'yomooh'); ?></p>
                <?php endif; ?>
            </footer>

            <div class="comment-content">
                <?php comment_text(); ?>
            </div>

            
        </article>
    <?php
}