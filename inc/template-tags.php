<?php
/** Custom template tags for functions Theme
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
if (!function_exists('yomooh_posted_on')) {
    function yomooh_posted_on() {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
        if (get_the_time('U') !== get_the_modified_time('U')) {
            $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
        }
        $time_string = sprintf(
            $time_string,
            esc_attr(get_the_date(DATE_W3C)),
            esc_html(get_the_date()),
            esc_attr(get_the_modified_date(DATE_W3C)),
            esc_html(get_the_modified_date())
        );

        $posted_on = sprintf(
            /* translators: %s: post date. */
            esc_html_x('Posted on %s', 'post date', 'yomooh'),
            '<a href="' . esc_url(get_permalink()) . '" rel="bookmark">' . $time_string . '</a>'
        );

        echo '<span class="posted-on">' . $posted_on . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
if (!function_exists('yomooh_modify_main_query')) {
    /**
     * Modify the main query to set posts per page based on theme options.
     *
     * @param WP_Query $query The main query object.
     */
function yomooh_modify_main_query($query) {
    if (!is_admin() && $query->is_main_query()) {
        $options = get_option('yomooh_options');
        $posts_per_page = $options['blog_posts_per_page'] ?? 10;
        $query->set('posts_per_page', $posts_per_page);
    }
}
}
add_action('pre_get_posts', 'yomooh_modify_main_query');

if (!function_exists('yomooh_modify_category_posts_per_page')) {
    /**
     * Modify the category query to set posts per page based on theme options.
     *
     * @param WP_Query $query The main query object.
     */
function yomooh_modify_category_posts_per_page( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_category() ) {
		return;
	}

	$options = get_option('yomooh_options');
	$posts_per_page = isset($options['category_posts_per_page']) ? (int) $options['category_posts_per_page'] : 10;
	$query->set( 'posts_per_page', $posts_per_page );
}
}
add_action( 'pre_get_posts', 'yomooh_modify_category_posts_per_page' );
if (!function_exists('yomooh_posted_by')) {
    /**
     * Prints HTML with meta information for the current author.
     */
    function yomooh_posted_by() {
        $byline = sprintf(
            /* translators: %s: post author. */
            esc_html_x('by %s', 'post author', 'yomooh'),
            '<span class="author vcard"><a class="url fn n" href="' . esc_url(get_author_posts_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a></span>'
        );

        echo '<span class="byline"> ' . $byline . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
if (!function_exists('calculate_top_time_reading')) {
    function calculate_top_time_reading() {
        $content = get_post_field('post_content');
        $word_count = str_word_count(strip_tags($content));
        $readingtime = ceil($word_count / 200); // 200 words per minute
        
        if ($readingtime == 1) {
            return $readingtime . ' min read';
        } else {
            return $readingtime . ' mins read';
        }
    }
}
// Calculate reading time function
if (!function_exists('calculate_reading_time')) {
    function calculate_reading_time() {
        $content = get_post_field('post_content');
        $word_count = str_word_count(strip_tags($content));
        $readingtime = ceil($word_count / 200);
        return $readingtime . ' min read';
    }
}

if (!function_exists('yomooh_entry_footer')) {
    /**
     * Prints HTML with meta information for the categories, tags and comments.
     */
    function yomooh_entry_footer() {
        // Hide category and tag text for pages.
        if ('post' === get_post_type()) {
            /* translators: used between list items, there is a space after the comma */
            $categories_list = get_the_category_list(esc_html__(', ', 'yomooh'));
            if ($categories_list) {
                /* translators: 1: list of categories. */
                printf('<span class="cat-links">' . esc_html__('Posted in %1$s', 'yomooh') . '</span>', $categories_list); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            /* translators: used between list items, there is a space after the comma */
            $tags_list = get_the_tag_list('', esc_html_x(', ', 'list item separator', 'yomooh'));
            if ($tags_list) {
                /* translators: 1: list of tags. */
                printf('<span class="tags-links">' . esc_html__('Tagged %1$s', 'yomooh') . '</span>', $tags_list); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }

        if (!is_single() && !post_password_required() && (comments_open() || get_comments_number())) {
            echo '<span class="comments-link">';
            comments_popup_link(
                sprintf(
                    wp_kses(
                        /* translators: %s: post title */
                        __('Leave a Comment<span class="screen-reader-text"> on %s</span>', 'yomooh'),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    wp_kses_post(get_the_title())
                )
            );
            echo '</span>';
        }

        edit_post_link(
            sprintf(
                wp_kses(
                    /* translators: %s: Name of current post. Only visible to screen readers */
                    __('Edit <span class="screen-reader-text">%s</span>', 'yomooh'),
                    array(
                        'span' => array(
                            'class' => array(),
                        ),
                    )
                ),
                wp_kses_post(get_the_title())
            ),
            '<span class="edit-link">',
            '</span>'
        );
    }
}

if (!function_exists('yomooh_post_thumbnail')) {
    /**
     * Displays an optional post thumbnail.
     *
     * Wraps the post thumbnail in an anchor element on index views, or a div
     * element when on single views.
     */
    function yomooh_post_thumbnail() {
        if (post_password_required() || is_attachment() || !has_post_thumbnail()) {
            return;
        }

        if (is_singular()) :
            ?>

            <div class="post-thumbnail">
                <?php the_post_thumbnail(); ?>
            </div><!-- .post-thumbnail -->

        <?php else : ?>

            <a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                <?php
                    the_post_thumbnail(
                        'post-thumbnail',
                        array(
                            'alt' => the_title_attribute(
                                array(
                                    'echo' => false,
                                )
                            ),
                        )
                    );
                ?>
            </a>

            <?php
        endif; // End is_singular().
    }
}
/**
 * Get the current theme mode
 * 
 * @return string 'light' or 'dark'
 */
if (!function_exists('get_theme_mode')) {
function get_theme_mode() {
    // Check if user has saved preference
    if (isset($_COOKIE['yomooh_theme'])) {
        return sanitize_text_field($_COOKIE['yomooh_theme']);
    }
    
    // Check WordPress theme mod
    $theme_mod = get_theme_mod('default_theme', 'system');
    
    // Handle system preference
    if ($theme_mod === 'system') {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            // Check for dark mode preference in user agent
            if (strpos($user_agent, 'dark') !== false) {
                return 'dark';
            }
        }
    } elseif (in_array($theme_mod, ['light', 'dark'])) {
        return $theme_mod;
    }
    
    // Default to light
    return 'light';
}
}
if ( ! function_exists( 'user_social_links' ) ) {
	/**
	 * User Social URLs
	 */
	function user_social_links() {

		$options = array(
			'facebook'   => array(
				'key'  => 'facebook',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12C2 16.991 5.657 21.128 10.438 21.879V14.89H7.898V12H10.438V9.797C10.438 7.291 11.93 5.907 14.215 5.907C15.309 5.907 16.453 6.102 16.453 6.102V8.562H15.193C13.95 8.562 13.563 9.333 13.563 10.124V12H16.336L15.893 14.89H13.563V21.879C18.343 21.129 22 16.99 22 12C22 6.477 17.523 2 12 2Z"/></svg>',
			),
			'twitter'    => array(
				'key'     => 'twitter',
				'prepend' => 'https://twitter.com/',
				'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path d="M13.8994 10.472L21.3321 2.02222H19.5708L13.117 9.35906L7.96237 2.02222H2.01709L9.81193 13.1168L2.01709 21.9777H3.7785L10.5939 14.2298L16.0376 21.9777H21.9829L13.899 10.472H13.8994ZM11.4869 13.2146L10.6972 12.1098L4.41317 3.31901H7.1186L12.1899 10.4135L12.9796 11.5182L19.5717 20.7399H16.8662L11.4869 13.215V13.2146Z"/></svg>',
			),
			'instagram'  => array(
				'key'  => 'instagram',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><g clip-path="url(#clip0_3400_4483)"><path d="M12 2C14.717 2 15.056 2.01 16.122 2.06C17.187 2.11 17.912 2.277 18.55 2.525C19.21 2.779 19.766 3.123 20.322 3.678C20.8305 4.1779 21.224 4.78259 21.475 5.45C21.722 6.087 21.89 6.813 21.94 7.878C21.987 8.944 22 9.283 22 12C22 14.717 21.99 15.056 21.94 16.122C21.89 17.187 21.722 17.912 21.475 18.55C21.2247 19.2178 20.8311 19.8226 20.322 20.322C19.822 20.8303 19.2173 21.2238 18.55 21.475C17.913 21.722 17.187 21.89 16.122 21.94C15.056 21.987 14.717 22 12 22C9.283 22 8.944 21.99 7.878 21.94C6.813 21.89 6.088 21.722 5.45 21.475C4.78233 21.2245 4.17753 20.8309 3.678 20.322C3.16941 19.8222 2.77593 19.2175 2.525 18.55C2.277 17.913 2.11 17.187 2.06 16.122C2.013 15.056 2 14.717 2 12C2 9.283 2.01 8.944 2.06 7.878C2.11 6.812 2.277 6.088 2.525 5.45C2.77524 4.78218 3.1688 4.17732 3.678 3.678C4.17767 3.16923 4.78243 2.77573 5.45 2.525C6.088 2.277 6.812 2.11 7.878 2.06C8.944 2.013 9.283 2 12 2ZM12 7C10.6739 7 9.40215 7.52678 8.46447 8.46447C7.52678 9.40215 7 10.6739 7 12C7 13.3261 7.52678 14.5979 8.46447 15.5355C9.40215 16.4732 10.6739 17 12 17C13.3261 17 14.5979 16.4732 15.5355 15.5355C16.4732 14.5979 17 13.3261 17 12C17 10.6739 16.4732 9.40215 15.5355 8.46447C14.5979 7.52678 13.3261 7 12 7ZM18.5 6.75C18.5 6.41848 18.3683 6.10054 18.1339 5.86612C17.8995 5.6317 17.5815 5.5 17.25 5.5C16.9185 5.5 16.6005 5.6317 16.3661 5.86612C16.1317 6.10054 16 6.41848 16 6.75C16 7.08152 16.1317 7.39946 16.3661 7.63388C16.6005 7.8683 16.9185 8 17.25 8C17.5815 8 17.8995 7.8683 18.1339 7.63388C18.3683 7.39946 18.5 7.08152 18.5 6.75ZM12 9C12.7956 9 13.5587 9.31607 14.1213 9.87868C14.6839 10.4413 15 11.2044 15 12C15 12.7956 14.6839 13.5587 14.1213 14.1213C13.5587 14.6839 12.7956 15 12 15C11.2044 15 10.4413 14.6839 9.87868 14.1213C9.31607 13.5587 9 12.7956 9 12C9 11.2044 9.31607 10.4413 9.87868 9.87868C10.4413 9.31607 11.2044 9 12 9Z"/></g><defs><clipPath id="clip0_3400_4483"><rect width="28" height="28" fill="white"/></clipPath></defs></svg>',
			),
			'linkedin'   => array(
				'key'  => 'linkedin',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.66667 2C2.74619 2 2 2.74619 2 3.66667V20.3333C2 21.2538 2.74619 22 3.66667 22H20.3333C21.2538 22 22 21.2538 22 20.3333V3.66667C22 2.74619 21.2538 2 20.3333 2H3.66667ZM8.13418 6.44747C8.14043 7.50997 7.34512 8.16466 6.40137 8.15997C5.5123 8.15528 4.7373 7.44747 4.74199 6.44903C4.74668 5.50997 5.48887 4.75528 6.45293 4.77716C7.43106 4.79903 8.14043 5.51622 8.13418 6.44747ZM12.3108 9.51307H9.51079H9.50922V19.024H12.4686V18.8021C12.4686 18.38 12.4682 17.9578 12.4679 17.5354C12.467 16.409 12.466 15.2813 12.4718 14.1552C12.4733 13.8818 12.4858 13.5974 12.5561 13.3364C12.8201 12.3614 13.6968 11.7318 14.6749 11.8866C15.303 11.9849 15.7186 12.349 15.8936 12.9412C16.0014 13.3114 16.0499 13.7099 16.0546 14.0959C16.0672 15.2599 16.0654 16.4239 16.0637 17.588C16.063 17.9989 16.0623 18.41 16.0623 18.8209V19.0224H19.0311V18.7943C19.0311 18.2921 19.0309 17.79 19.0306 17.2879C19.03 16.0329 19.0293 14.7779 19.0327 13.5224C19.0342 12.9552 18.9733 12.3959 18.8342 11.8474C18.6264 11.0318 18.1968 10.3568 17.4983 9.86933C17.003 9.52243 16.4592 9.299 15.8514 9.274C15.7822 9.27112 15.7124 9.26736 15.6423 9.26357C15.3316 9.24677 15.0157 9.2297 14.7186 9.28962C13.8686 9.45993 13.1218 9.849 12.5577 10.5349C12.4921 10.6136 12.428 10.6934 12.3323 10.8127L12.3108 10.8397V9.51307ZM4.9796 19.0271H7.92491V9.51926H4.9796V19.0271Z" /></svg>',
			),
			'myspace'    => array(
				'key'  => 'myspace',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path d="M19.8694 2H4.13063C2.95283 2 1.99854 2.95401 1.99854 4.13168V19.8683C1.99854 21.046 2.95283 22 4.13063 22H19.8694C21.0471 22 22.0015 21.046 22.0015 19.8683V4.13168C22.0015 2.95401 21.0471 2 19.8694 2ZM14.9898 5.58237C16.1037 5.58237 17.0066 6.48581 17.0066 7.60001C17.0066 8.71324 16.1037 9.61672 14.9898 9.61672C13.8754 9.61672 12.9725 8.71324 12.9725 7.60001C12.9725 6.48581 13.8754 5.58237 14.9898 5.58237ZM10.9258 6.2494C11.7837 6.2494 12.4814 6.94702 12.4814 7.80654C12.4814 8.66505 11.7838 9.36271 10.9258 9.36271C10.066 9.36271 9.36831 8.66505 9.36831 7.80654C9.36835 6.94702 10.066 6.2494 10.9258 6.2494ZM7.93989 6.75812C8.51765 6.75812 8.98734 7.22807 8.98734 7.80654C8.98734 8.38401 8.51769 8.85399 7.93989 8.85399C7.36209 8.85399 6.89185 8.38405 6.89185 7.80654C6.89185 7.22807 7.36213 6.75812 7.93989 6.75812ZM17.7152 17.946C17.7152 18.2126 17.4991 18.4287 17.2326 18.4287H12.4827C12.2161 18.4287 12 18.2126 12 17.946V15.5717H8.91045C8.64386 15.5717 8.42776 15.3556 8.42776 15.0891V13.4286H6.76732C6.50073 13.4286 6.28463 13.2125 6.28463 12.946V10.9286C6.28463 9.94244 7.08459 9.14289 8.07074 9.14289C8.7951 9.14289 9.41461 9.57543 9.6947 10.1949C10.0608 9.98644 10.4776 9.8571 10.9284 9.8571C11.8788 9.8571 12.6963 10.3945 13.119 11.1756C13.6017 10.8023 14.2003 10.5714 14.8576 10.5714C16.4359 10.5714 17.7152 11.8507 17.7152 13.4287V17.946Z" /></svg>',
			),
			'pinterest'  => array(
				'key'  => 'pinterest',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path d="M12.001 22C6.47813 22 2.00098 17.5228 2.00098 12C2.00098 6.47715 6.47813 2 12.001 2C17.5238 2 22.001 6.47715 22.001 12C22.001 17.5228 17.5238 22 12.001 22ZM18.671 12C18.65 11.425 18.2932 10.916 17.7598 10.7C17.2265 10.4841 16.6161 10.6016 16.201 11C15.0634 10.2267 13.7262 9.7995 12.351 9.77L13.001 6.65L15.141 7.1C15.1935 7.58851 15.5932 7.96647 16.0839 7.99172C16.5745 8.01696 17.0109 7.68201 17.1133 7.20147C17.2157 6.72094 16.9538 6.23719 16.4955 6.06019C16.0372 5.88318 15.5181 6.06536 15.271 6.49L12.821 6C12.74 5.98224 12.6554 5.99763 12.5858 6.04272C12.5163 6.08781 12.4678 6.15886 12.451 6.24L11.711 9.71C10.3189 9.73099 8.96325 10.1585 7.81098 10.94C7.38972 10.5436 6.77418 10.4333 6.2415 10.6588C5.70882 10.8842 5.35944 11.4028 5.35067 11.9812C5.3419 12.5595 5.67538 13.0885 6.20098 13.33C6.18972 13.4765 6.18972 13.6235 6.20098 13.77C6.20098 16.01 8.81098 17.83 12.031 17.83C15.251 17.83 17.861 16.01 17.861 13.77C17.8722 13.6235 17.8722 13.4765 17.861 13.33C18.3646 13.0797 18.6797 12.5623 18.671 12ZM8.67098 13C8.67098 12.4477 9.11869 12 9.67098 12C10.2233 12 10.671 12.4477 10.671 13C10.671 13.5523 10.2233 14 9.67098 14C9.40576 14 9.15141 13.8946 8.96387 13.7071C8.77633 13.5196 8.67098 13.2652 8.67098 13ZM14.481 15.75C13.7715 16.2847 12.8986 16.5568 12.011 16.52C11.1234 16.5568 10.2505 16.2847 9.54098 15.75C9.45288 15.6427 9.46057 15.486 9.55877 15.3878C9.65696 15.2896 9.81363 15.2819 9.92098 15.37C10.5222 15.811 11.2561 16.0333 12.001 16C12.7468 16.0406 13.4841 15.8254 14.091 15.39C14.1624 15.3203 14.2656 15.2941 14.3617 15.3211C14.4577 15.3482 14.5321 15.4244 14.5567 15.5211C14.5813 15.6178 14.5524 15.7203 14.481 15.79V15.75ZM14.301 14.04C13.7487 14.04 13.301 13.5923 13.301 13.04C13.301 12.4877 13.7487 12.04 14.301 12.04C14.8533 12.04 15.301 12.4877 15.301 13.04C15.312 13.3138 15.2101 13.5802 15.0192 13.7767C14.8282 13.9733 14.565 14.083 14.291 14.08L14.301 14.04Z"/></svg>',
			),
			'youtube'    => array(
				'key'  => 'youtube',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path d="M21.8593 7.22498C21.6189 6.36844 20.9255 5.69771 20.0366 5.46862C18.8427 5.02444 7.27814 4.8068 3.92342 5.48135C3.03455 5.71298 2.33851 6.38116 2.10078 7.23771C1.56191 9.51589 1.52097 14.4413 2.11399 16.7704C2.35436 17.627 3.04776 18.2977 3.93663 18.5268C6.30078 19.0512 17.5404 19.125 20.0498 18.5268C20.9387 18.2952 21.6347 17.627 21.8725 16.7704C22.447 14.2886 22.4879 9.66862 21.8593 7.22498Z"/><path d="M15.3877 11.9975L9.99902 9.01929V14.9757L15.3877 11.9975Z" fill="white"/></svg>',
			),
			'soundcloud' => array(
				'key'  => 'soundcloud',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 20 20"><g clip-path="url(#clip0_3402_895)"><path d="M0.226503 9.49439C0.184772 9.49439 0.151649 9.52666 0.146253 9.57268L0 10.6836L0.146253 11.7754C0.151649 11.8214 0.184751 11.8535 0.226503 11.8535C0.266922 11.8535 0.299842 11.8216 0.306289 11.7758L0.479642 10.6836L0.306289 9.57245C0.300044 9.52664 0.26672 9.49439 0.226503 9.49439ZM1.04899 8.88741C1.0421 8.83967 1.00791 8.80675 0.965752 8.80675C0.923373 8.80675 0.888311 8.84074 0.882511 8.88741C0.882511 8.88783 0.685917 10.6836 0.685917 10.6836L0.882511 12.44C0.888108 12.4871 0.923171 12.5211 0.965752 12.5211C1.00769 12.5211 1.0419 12.488 1.04877 12.4405L1.27244 10.6836L1.04899 8.88741ZM4.14713 7.23059C4.06647 7.23059 3.99978 7.29661 3.99592 7.3805L3.83934 10.6851L3.99592 12.8207C4 12.9042 4.06647 12.97 4.14713 12.97C4.22736 12.97 4.29382 12.9039 4.29855 12.8203V12.8209L4.47556 10.6851L4.29855 7.3805C4.2938 7.29661 4.22734 7.23059 4.14713 7.23059ZM2.54364 8.37915C2.4817 8.37915 2.43115 8.42862 2.42618 8.49464L2.24982 10.6842L2.42618 12.8026C2.43091 12.8682 2.48168 12.9177 2.54364 12.9177C2.60493 12.9177 2.65547 12.8682 2.66087 12.8026L2.86132 10.6842L2.66087 8.4942C2.65547 8.42862 2.60493 8.37915 2.54364 8.37915ZM5.77665 12.9698C5.87666 12.9698 5.95796 12.8891 5.96204 12.7859L6.11563 10.6862L5.96227 6.28654C5.95818 6.18329 5.87686 6.10264 5.77687 6.10264C5.67598 6.10264 5.59448 6.18351 5.59123 6.28676L5.45508 10.686L5.59123 12.7865C5.59446 12.8889 5.67596 12.9698 5.77665 12.9698ZM9.11266 12.9797C9.25033 12.9797 9.3639 12.8663 9.36647 12.727L9.47359 10.6875L9.36645 5.60622C9.36408 5.46706 9.2503 5.3535 9.11264 5.3535C8.97477 5.3535 8.861 5.46686 8.85883 5.60644L8.76312 10.6858C8.76312 10.689 8.85883 12.7282 8.85883 12.7282C8.86102 12.8663 8.975 12.9797 9.11266 12.9797ZM7.43195 12.9717C7.55177 12.9717 7.64833 12.8756 7.65156 12.753V12.7545L7.78189 10.6866L7.65134 6.25515C7.64811 6.13254 7.55155 6.03663 7.43173 6.03663C7.31106 6.03663 7.2145 6.13256 7.21192 6.25515L7.096 10.6868L7.21214 12.7543C7.21472 12.8756 7.31128 12.9717 7.43195 12.9717ZM3.34226 12.9524C3.41346 12.9524 3.47111 12.8954 3.47649 12.8203L3.66534 10.6843L3.47649 8.65231C3.47133 8.57726 3.4137 8.52067 3.34226 8.52067C3.27001 8.52067 3.21236 8.57766 3.20785 8.65318L3.04157 10.6843L3.20785 12.8201C3.21258 12.8954 3.26999 12.9524 3.34226 12.9524ZM1.75148 12.8354C1.80352 12.8354 1.84568 12.7943 1.8517 12.7375L2.06377 10.6838L1.8519 8.55272C1.84566 8.49573 1.8035 8.45463 1.75146 8.45463C1.69876 8.45463 1.6566 8.49594 1.65122 8.55272L1.46453 10.6838L1.65122 12.7371C1.6564 12.794 1.69855 12.8354 1.75148 12.8354ZM8.26909 6.18137C8.13897 6.18137 8.03463 6.28484 8.03206 6.41733L7.92625 10.687L8.03206 12.7416C8.03465 12.8723 8.13874 12.9758 8.26909 12.9758C8.399 12.9758 8.50269 12.8726 8.50568 12.7401V12.7418L8.62441 10.6873L8.50568 6.41689C8.50267 6.28484 8.39898 6.18137 8.26909 6.18137ZM4.95886 12.9741C5.04877 12.9741 5.12298 12.9007 5.12729 12.8072L5.29248 10.6857L5.12729 6.6253C5.12276 6.53151 5.04877 6.4584 4.95886 6.4584C4.86811 6.4584 4.79412 6.53173 4.79024 6.6253L4.64399 10.6857L4.79046 12.808C4.7941 12.9007 4.86809 12.9741 4.95886 12.9741ZM6.80347 12.7738V12.7727L6.94542 10.6864L6.80347 6.13857C6.80001 6.02566 6.71097 5.93704 6.60106 5.93704C6.49049 5.93704 6.40145 6.02544 6.39846 6.13857L6.27242 10.6862L6.39866 12.7736C6.40167 12.8855 6.49073 12.9738 6.60107 12.9738C6.71119 12.9738 6.79981 12.8855 6.80349 12.773V12.7738H6.80347ZM17.5402 8.0649C17.2032 8.0649 16.8816 8.13308 16.5887 8.25589C16.3929 6.03877 14.5337 4.29956 12.2658 4.29956C11.7109 4.29956 11.17 4.40883 10.6923 4.59358C10.5066 4.66543 10.4572 4.73941 10.4555 4.88288V12.6906C10.4574 12.8411 10.5742 12.9665 10.7211 12.9814C10.7273 12.982 17.4962 12.9855 17.5401 12.9855C18.8985 12.9855 20 11.884 20 10.5253C20 9.16657 18.8987 8.0649 17.5402 8.0649ZM9.95257 4.876C9.8048 4.876 9.68351 4.99732 9.68134 5.14636L9.57036 10.6881L9.68157 12.6994C9.68351 12.8463 9.80482 12.9674 9.95257 12.9674C10.0999 12.9674 10.2212 12.8463 10.2234 12.6973V12.6996L10.344 10.6881L10.2234 5.14594C10.2212 4.99732 10.0999 4.876 9.95257 4.876ZM1.98657 14.5494C1.69556 14.481 1.61683 14.4444 1.61683 14.3291C1.61683 14.2478 1.68243 14.1637 1.87902 14.1637C2.047 14.1637 2.17822 14.2319 2.2961 14.3526L2.56088 14.0958C2.38774 13.9149 2.17801 13.8073 1.89238 13.8073C1.53017 13.8073 1.23657 14.0119 1.23657 14.3451C1.23657 14.7068 1.47276 14.8144 1.81088 14.8929C2.15694 14.9714 2.21997 15.0241 2.21997 15.1422C2.21997 15.2811 2.11737 15.3415 1.90012 15.3415C1.7244 15.3415 1.55921 15.2813 1.4306 15.1314L1.16578 15.3676C1.30472 15.5721 1.57251 15.7006 1.879 15.7006C2.38017 15.7006 2.6004 15.4646 2.6004 15.1159C2.60042 14.7172 2.27779 14.6176 1.98657 14.5494ZM3.67585 13.8073C3.17513 13.8073 2.87895 14.1954 2.87895 14.7539C2.87895 15.3125 3.17511 15.7003 3.67585 15.7003C4.17659 15.7003 4.47318 15.3125 4.47318 14.7539C4.47318 14.1954 4.17659 13.8073 3.67585 13.8073ZM3.67585 15.3362C3.37969 15.3362 3.25621 15.0817 3.25621 14.7541C3.25621 14.4264 3.37967 14.1719 3.67585 14.1719C3.97246 14.1719 4.09549 14.4264 4.09549 14.7541C4.09549 15.0817 3.97246 15.3362 3.67585 15.3362ZM5.95495 14.8669C5.95495 15.1604 5.81042 15.3415 5.57726 15.3415C5.34389 15.3415 5.20234 15.1553 5.20234 14.8617V13.8359H4.83498V14.8669C4.83498 15.4016 5.13418 15.7006 5.57724 15.7006C6.04399 15.7006 6.32209 15.3936 6.32209 14.8617V13.8359H5.95495V14.8669ZM7.90579 14.6124C7.90579 14.7148 7.91095 14.9454 7.91095 15.0136C7.8873 14.9662 7.82708 14.8746 7.78254 14.8062L7.13191 13.8357H6.78065V15.6718H7.14266V14.8639C7.14266 14.7615 7.13749 14.5309 7.13749 14.4627C7.16093 14.5098 7.22157 14.6017 7.26589 14.6701L7.93997 15.6717H8.26776V13.8357H7.90577L7.90579 14.6124ZM9.31398 13.8359H8.7397V15.6719H9.28751C9.75963 15.6719 10.2266 15.399 10.2266 14.7539C10.2266 14.0824 9.83837 13.8359 9.31398 13.8359ZM9.28751 15.3153H9.10686V14.1928H9.30087C9.6891 14.1928 9.84914 14.3788 9.84914 14.7539C9.84912 15.0897 9.67318 15.3153 9.28751 15.3153ZM11.3621 14.1717C11.5272 14.1717 11.6322 14.2453 11.6978 14.3711L12.0387 14.2139C11.9232 13.9753 11.7133 13.8073 11.3672 13.8073C10.8877 13.8073 10.5518 14.1954 10.5518 14.7539C10.5518 15.3334 10.8742 15.7003 11.354 15.7003C11.6872 15.7003 11.9075 15.5457 12.0333 15.2861L11.724 15.1024C11.627 15.2652 11.5298 15.336 11.3646 15.336C11.0893 15.336 10.9293 15.0841 10.9293 14.7539C10.9291 14.4132 11.0869 14.1717 11.3621 14.1717ZM12.7238 13.8359H12.3566V15.6719H13.46V15.3097H12.7238V13.8359ZM14.4273 13.8073C13.9261 13.8073 13.6299 14.1954 13.6299 14.7539C13.6299 15.3125 13.9261 15.7003 14.4273 15.7003C14.928 15.7003 15.2244 15.3125 15.2244 14.7539C15.2244 14.1954 14.928 13.8073 14.4273 13.8073ZM14.4273 15.3362C14.1307 15.3362 14.0076 15.0817 14.0076 14.7541C14.0076 14.4264 14.1307 14.1719 14.4273 14.1719C14.7232 14.1719 14.8465 14.4264 14.8465 14.7541C14.8465 15.0817 14.7232 15.3362 14.4273 15.3362ZM16.7059 14.8669C16.7059 15.1604 16.562 15.3415 16.3287 15.3415C16.0949 15.3415 15.9535 15.1553 15.9535 14.8617V13.8359H15.5864V14.8669C15.5864 15.4016 15.8854 15.7006 16.3287 15.7006C16.7954 15.7006 17.0733 15.3936 17.0733 14.8617V13.8359H16.7059V14.8669ZM18.1064 13.8359H17.5323V15.6719H18.0801C18.5524 15.6719 19.019 15.399 19.019 14.7539C19.019 14.0824 18.631 13.8359 18.1064 13.8359ZM18.0801 15.3153H17.8992V14.1928H18.0935C18.4812 14.1928 18.6413 14.3788 18.6413 14.7539C18.6413 15.0897 18.4655 15.3153 18.0801 15.3153Z" /></g><defs><clipPath id="clip0_3402_895"><rect width="28" height="28" fill="white"/></clipPath></defs></svg>',
			),
			'tumblr'     => array(
				'key'  => 'tumblr',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 20 20"><g clip-path="url(#clip0_3402_907)"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 0V20H20V0H0ZM14.2212 16.1534C13.7059 16.4291 13.1706 16.6274 12.6139 16.7473C12.1399 16.8658 11.6258 16.9231 11.0693 16.9231C10.5899 16.9231 10.0386 16.8458 9.40475 16.69C8.88936 16.4916 8.49393 16.2533 8.21824 15.9776C7.85873 15.654 7.62035 15.3424 7.50585 15.0255C7.3447 14.6247 7.26615 14.1506 7.26615 13.5993V8.78294H5.77873V6.82273C6.25675 6.66031 6.70949 6.40598 7.14624 6.04642C7.58308 5.69354 7.90127 5.29807 8.09833 4.86127C8.25415 4.50176 8.41525 3.90923 8.5724 3.07692H10.4606V6.65099H13.6592V8.78294H10.4606V12.2891C10.4606 13.0854 10.5325 13.5994 10.6524 13.8391C10.6936 14.0362 10.8348 14.2133 11.0692 14.3744C11.4687 14.5675 11.8229 14.666 12.1398 14.666C12.8109 14.666 13.5087 14.4316 14.2211 13.9536V16.1534H14.2212Z" /></g><defs><clipPath id="clip0_3402_907"><rect width="20" height="20" fill="white"/></clipPath></defs></svg>',
			),
			'wikipedia'  => array(
				'key'  => 'wikipedia',
				'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 20 20"><path d="M15.1358 3.73584V4.07005C14.6949 4.14835 14.3613 4.28685 14.1347 4.48556C13.8102 4.78064 13.4276 5.23227 13.1766 5.84047L8.06951 16.2642H7.72971L2.60269 5.70498C2.36391 5.16302 2.03929 4.83182 1.94133 4.71138C1.78827 4.52471 1.6 4.37868 1.37653 4.27329C1.15305 4.16792 0.85152 4.10017 0.471924 4.07005V3.73584H5.46066V4.07005C4.88513 4.12426 4.61075 4.22061 4.43932 4.3591C4.26789 4.49761 4.18217 4.67526 4.18218 4.89203C4.18217 5.19313 4.32299 5.66283 4.60463 6.30114L8.39094 13.4822L12.0929 6.39146C12.3807 5.69294 12.6194 5.20819 12.6194 4.93719C12.6194 4.76257 12.5306 4.59547 12.3531 4.43588C12.1755 4.27631 11.9746 4.1634 11.5522 4.09715C11.5215 4.09114 11.4695 4.08211 11.396 4.07005V3.73584H15.1358Z" /><path d="M19.5281 3.73584V4.07005C19.0873 4.14835 18.7536 4.28685 18.5271 4.48556C18.2026 4.78064 17.82 5.23227 17.569 5.84047L13.0869 16.2642H12.7471L8.01068 5.70498C7.7719 5.16302 7.44728 4.83182 7.34932 4.71138C7.19625 4.52471 7.00799 4.37868 6.78452 4.27329C6.56104 4.16792 6.3586 4.10017 5.979 4.07005V3.73584H10.8686V4.07005C10.2931 4.12426 10.0187 4.22061 9.84731 4.3591C9.67588 4.49761 9.59016 4.67526 9.59017 4.89203C9.59016 5.19313 9.73098 5.66283 10.0126 6.30114L13.4083 13.4822L16.4853 6.39146C16.773 5.69294 17.0118 5.20819 17.0118 4.93719C17.0118 4.76257 16.923 4.59547 16.7455 4.43588C16.5679 4.27631 16.2679 4.1634 15.8454 4.09715C15.8148 4.09114 15.7628 4.08211 15.6893 4.07005V3.73584H19.5281Z" /></svg>',
			),
		);

		$output = array();

		foreach ( $options as $social => $settings ) {

			$url = get_user_meta( get_the_author_meta( 'ID' ), $settings['key'], true );

			if ( 'twitter' === $social && $url ) {
				$url = sprintf( 'https://twitter.com/%s/', $url );
			}

			if ( $url && ! empty( $settings['icon'] ) ) {
				$output[] = '<a href="' . esc_url_raw( $url ) . '">' . $settings['icon'] . '<span class="screen-reader-text">' . $social . '</span></a>';
			}
		}

		if ( ! empty( $output ) ) {
			call_user_func( 'printf', '%s', '<div class="user-social-links">' . join( ' ', $output ) . '</div>' );
		}
	}
}
/**
 * Add social media fields to user profiles
 */
function add_user_social_fields($user) {
    ?>
    <h3><?php esc_html_e('Social Media Links', 'yomooh'); ?></h3>
    
    <table class="form-table">
        <?php
        $social_options = array(
            'facebook'   => __('Facebook URL', 'yomooh'),
            'twitter'    => __('Twitter Username', 'yomooh'),
            'instagram'  => __('Instagram URL', 'yomooh'),
            'linkedin'   => __('LinkedIn URL', 'yomooh'),
            'youtube'    => __('YouTube URL', 'yomooh'),
            'pinterest'  => __('Pinterest URL', 'yomooh'),
            'soundcloud' => __('SoundCloud URL', 'yomooh'),
            'tumblr'     => __('Tumblr URL', 'yomooh'),
            'wikipedia'  => __('Wikipedia URL', 'yomooh')
        );
        
        foreach ($social_options as $key => $label) {
            $value = get_user_meta($user->ID, $key, true);
            ?>
            <tr>
                <th>
                    <label for="<?php echo esc_attr($key); ?>">
                        <?php echo esc_html($label); ?>
                    </label>
                </th>
                <td>
                    <?php if ($key === 'twitter') : ?>
                        <input type="text" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" 
                               value="<?php echo esc_attr($value); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Enter just your username (without @)', 'yomooh'); ?></p>
                    <?php else : ?>
                        <input type="url" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" 
                               value="<?php echo esc_url($value); ?>" class="regular-text code" />
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
    <?php
}
add_action('show_user_profile', 'add_user_social_fields');
add_action('edit_user_profile', 'add_user_social_fields');

/**
 * Save social media fields
 */
function save_user_social_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    $social_fields = array(
        'facebook', 'twitter', 'instagram', 'linkedin', 
        'youtube', 'pinterest', 'soundcloud', 'tumblr', 'wikipedia'
    );
    
    foreach ($social_fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            
            // Special handling for Twitter to remove @ if present
            if ($field === 'twitter') {
                $value = ltrim($value, '@');
            }
            
            update_user_meta($user_id, $field, $value);
        }
    }
}
add_action('personal_options_update', 'save_user_social_fields');
add_action('edit_user_profile_update', 'save_user_social_fields');
/**
 * Displays account dropdown with appropriate content based on login status
 * 
 * @return void
 */
function display_account_dropdown() {
    if (is_user_logged_in()) {
        display_user_account_dropdown();
    } else {
        display_login_dropdown();
    }
}

/**
 * Displays user account dropdown for logged-in users
 */
function display_user_account_dropdown() {
    $current_user = wp_get_current_user();
    ?>
    <div class="header-account logged-in">
        <button class="account-toggle user-toggle" aria-label="<?php esc_attr_e('My Account', 'yomooh'); ?>">
            <span class="account-username"><?php echo esc_html($current_user->display_name); ?></span>
            <i class="icon-user-solid"></i>
        </button>
        
        <div class="account-popup user-popup">
            <div class="user-info">
                <p><?php esc_html_e('Hello,', 'yomooh'); ?> <strong><?php echo esc_html($current_user->display_name); ?></strong></p>
                <ul class="user-links">
                    <li><a href="<?php echo esc_url(get_edit_profile_url()); ?>"><i class="iconly-Profile"></i> <?php esc_html_e('Profile', 'yomooh'); ?></a></li>
                    <li><a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"><i class="iconly-Logout"></i> <?php esc_html_e('Logout', 'yomooh'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Displays login dropdown for non-logged-in users
 */
function display_login_dropdown() {
    ?>
    <div class="header-account logged-out">
        <button class="account-toggle login-toggle" aria-label="<?php esc_attr_e('Login', 'yomooh'); ?>">
			<i class="iconly-Login"></i>
			<span class="login-text"><?php esc_html_e('Login', 'yomooh'); ?></span>
		</button>
        <div class="account-popup login-popup">
            <div class="popup-header">
                <h3><?php esc_html_e('Sign In', 'yomooh'); ?></h3>
                <button class="popup-close"><i class="wp-icon-x"></i></button>
            </div>
            <form class="login-form" method="post">
                <div class="form-group">
                    <label for="username"><?php esc_html_e('Username or Email', 'yomooh'); ?></label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password"><?php esc_html_e('Password', 'yomooh'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group remember-me">
                    <input type="checkbox" id="rememberme" name="rememberme" value="forever">
                    <label for="rememberme"><?php esc_html_e('Remember Me', 'yomooh'); ?></label>
                </div>
                <button type="submit" class="login-button"><?php esc_html_e('Login', 'yomooh'); ?></button>
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>">
                <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
                <div class="form-links">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Forgot Password?', 'yomooh'); ?></a>
                    <?php if (get_option('users_can_register')) : ?>
                        <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php esc_html_e('Register', 'yomooh'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Handle AJAX login
 */
function ajax_login() {
    // Verify nonce first
    if (!check_ajax_referer('ajax-login-nonce', 'security', false)) {
        wp_send_json_error(array(
            'message' => __('Security verification failed.', 'yomooh')
        ));
        wp_die();
    }

    $info = array();
    $info['user_login'] = sanitize_user($_POST['username']);
    $info['user_password'] = $_POST['password'];
    $info['remember'] = isset($_POST['rememberme']) ? true : false;
    
    $user_signon = wp_signon($info, false);
    
    if (is_wp_error($user_signon)) {
        wp_send_json_error(array(
            'message' => __('Incorrect username or password.', 'yomooh')
        ));
    }
    
    wp_send_json_success(array(
        'redirect' => !empty($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url()
    ));
    
    wp_die(); // Always include this
}
add_action('wp_ajax_ajaxlogin', 'ajax_login');
add_action('wp_ajax_nopriv_ajaxlogin', 'ajax_login');

if (!function_exists('theme_mode_switcher')) {
    /**
     * Original theme mode switcher (simple icon toggle)
     */
    function theme_mode_switcher() {
        $current_mode = get_theme_mode();
        $light_icon = 'icon-sun-icon';
        $dark_icon = 'icon-moon-icon';
        
        ob_start(); ?>
        <div class="theme-switcher version-1">
            <button class="theme-toggle-btn" aria-label="<?php esc_attr_e('Toggle theme mode', 'yomooh'); ?>">
                <span class="light-icon <?php echo $current_mode === 'light' ? 'active' : ''; ?>">
                    <i class="<?php echo esc_attr($light_icon); ?>"></i>
                </span>
                <span class="dark-icon <?php echo $current_mode === 'dark' ? 'active' : ''; ?>">
                    <i class="<?php echo esc_attr($dark_icon); ?>"></i>
                </span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('theme_mode_switcher2')) {
    /**
     * Enhanced theme mode switcher (slider with icons)
     */
    function theme_mode_switcher2() {
        $current_mode = get_theme_mode();
        $light_icon = 'icon-sun-icon';
        $dark_icon = 'icon-moon-icon';
        
        ob_start(); ?>
        <div class="theme-switcher version-2">
            <button class="theme-toggle-btn" aria-label="<?php esc_attr_e('Toggle theme mode', 'yomooh'); ?>">
                <span class="theme-toggle-track">
                    <span class="theme-toggle-thumb">
                        <i class="<?php echo $current_mode === 'light' ? esc_attr($light_icon) : esc_attr($dark_icon); ?>"></i>
                    </span>
                </span>
                <span class="light-icon">
                    <i class="<?php echo esc_attr($light_icon); ?>"></i>
                </span>
                <span class="dark-icon">
                    <i class="<?php echo esc_attr($dark_icon); ?>"></i>
                </span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
}
/**
 * Add theme mode class to html element
 */
function add_theme_mode_class($output) {
    $theme_mode = get_theme_mode();
    return str_replace('class="yomooh-html"', 'class="yomooh-html" data-theme="' . esc_attr($theme_mode) . '"', $output);
}
add_filter('language_attributes', 'add_theme_mode_class');
if ( ! function_exists( 'scroll_to_top' ) ) {
	/**
	 * Scroll to Top
	 *
	 * @param array $settings The advanced settings.
	 */
	function scroll_to_top( $settings = array() ) {
		?>
			<button class="scroll-top" role="button" aria-label="<?php echo esc_attr__( 'Scroll to top button', 'yomooh' ); ?>">
				<i class="wp-icon-chevron-up"></i>
				<div class="scroll-top-border">
					<svg width="49" height="49" viewBox="0 0 49 49">
						<path d="M24.5,2 a22.5,22.5 0 0,1 0,45 a22.5,22.5 0 0,1 0,-45" style="stroke-width: 2; fill: none;"></path>
					</svg>
				</div>
				<div class="scroll-top-progress">
					<svg width="49" height="49" viewBox="0 0 49 49">
						<path d="M24.5,2 a22.5,22.5 0 0,1 0,45 a22.5,22.5 0 0,1 0,-45" style="stroke-width: 2; fill: none;"></path>
					</svg>
				</div>
			</button>
		<?php
	}
}
if (!function_exists('render_social_icons')) {
// Social icons rendering function
function render_social_icons($options) {
    $social_options = $options;
    if (empty($social_options['social_enable']) || empty($social_options['social_links'])) return;

    $social_classes = ['social-icons-wrapper'];
    $social_classes[] = 'style-' . ($social_options['social_style'] ?? 'minimal');
    $social_classes[] = 'color-' . ($social_options['social_color'] ?? 'brand');
    ?>
    <div class="<?php echo esc_attr(implode(' ', $social_classes)); ?>">
        <ul class="social-icons-list">
            <?php 
            $social_platforms = [
                'facebook' => ['icon' => 'wp-icon-facebook', 'color' => '#3b5998'],
                'twitter' => ['icon' => 'wp-icon-x', 'color' => '#1da1f2'],
                'instagram' => ['icon' => 'wp-icon-instagram', 'color' => '#e1306c'],
                'linkedin' => ['icon' => 'wp-icon-linkedIn', 'color' => '#0077b5'],
                'youtube' => ['icon' => 'icon-youtube', 'color' => '#ff0000'],
                'pinterest' => ['icon' => 'wp-icon-pinterest', 'color' => '#bd081c'],
                'tiktok' => ['icon' => 'wp-icon-tiktok', 'color' => '#010101'],
                'whatsapp' => ['icon' => 'wpi-whatsapp', 'color' => '#25d366'],
                'telegram' => ['icon' => 'icon-telegram', 'color' => '#0088cc'],
                'vimeo' => ['icon' => 'wp-icon-vimeo', 'color' => '#1ab7ea'],
                'reddit' => ['icon' => 'wpi-reddit', 'color' => '#ff4500'],
                'discord' => ['icon' => 'wpi-discord', 'color' => '#7289da'],
            ];
            
            foreach ($social_options['social_links'] as $platform => $url) : 
                if (!empty($url) && isset($social_platforms[$platform])) :
                    $platform_data = $social_platforms[$platform];
                    ?>
                    <li class="social-icon <?php echo esc_attr($platform); ?>">
                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(ucfirst($platform)); ?>">
                            <i class="<?php echo esc_attr($platform_data['icon']); ?>"></i>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}}
/**
 * Custom search form for yomooh
 */
function yomooh_search_form() {
    $unique_id = esc_attr(uniqid('search-form-'));
    $search_query = get_search_query();
    ?>
    <form role="search" method="get" class="yomooh-search-form" action="<?php echo esc_url(home_url('/')); ?>">
        <span class="search-icon">
            <i class="iconly-Search"></i>
        </span>
        <input
            type="search"
            id="<?php echo $unique_id; ?>"
            class="search-field"
            placeholder="<?php esc_attr_e('Search...', 'yomooh'); ?>"
            value="<?php echo esc_attr($search_query); ?>"
            name="s"
            autocomplete="off"
            autocorrect="off"
            spellcheck="false"
            aria-label="<?php esc_attr_e('Search for:', 'yomooh'); ?>"
            inputmode="search"
        />
        <span class="search-submit">
            <input type="submit" class="search-submit-input" value="<?php esc_attr_e('Search', 'yomooh'); ?>" />
            <i class="wp-icon-arrow-long-right search-submit-icon"></i>
        </span>
    </form>
    <?php
}

   if (!function_exists('yomooh_get_icon')) {
    /**
     *  social sharing icons based on selected options
     */
    function yomooh_get_icon($icon_name = '', $class = '') {
        $icons = [
			'copy' => '<i class="wpi-copy"></i>',
            'facebook' => '<i class="wp-icon-facebook"></i>',
            'twitter' => '<i class="wp-icon-x"></i>',
            'linkedin' => '<i class="wp-icon-linkedIn"></i>',
            'pinterest' => '<i class="wp-icon-pinterest"></i>',
            'reddit' => '<i class="wpi-reddit"></i>',
            'whatsapp' => '<i class="wpi-whatsapp"></i>',
            'email' => '<i class="wpi-email"></i>',
            'print' => '<i class="wpi-print"></i>'
        ];

        // Return specific icon if requested
        if (!empty($icon_name)) {
            return isset($icons[$icon_name]) ? $icons[$icon_name] : '';
        }

        // Social sharing functionality
        $options = get_option('yomooh_options');
        if (empty($options['single_social_sharing'])) return '';

        $post_url = urlencode(get_permalink());
        $post_title = urlencode(get_the_title());
        $post_thumbnail = has_post_thumbnail() ? urlencode(get_the_post_thumbnail_url()) : '';
        
        $meta_position = get_post_meta(get_the_ID(), 'single_social_sharing_position', true);
        if (is_array($meta_position)) {
            $meta_position = reset($meta_position);
        }

        if (!empty($meta_position) && $meta_position !== 'default') {
            $position = $meta_position; // post meta wins
        } else {
            $position = $options['single_social_sharing_position'] ?? 'bottom';
        }
        $wrapper_class = 'social-sharing-' . esc_attr($position);
        
        ob_start();
        ?>
        <div class="social-sharing <?php echo $wrapper_class; ?>">
            <?php if ($position !== 'floating') : ?>
                <span class="share-label"><i class="wpi-share"></i><?php esc_html_e('Share:', 'yomooh'); ?></span>
            <?php endif; ?>
            <ul class="social-sharing-buttons">
				  <li>
                <a href="#" class="js-copy-link" data-url="<?php echo esc_url_raw(get_permalink()); ?>" aria-label="<?php esc_attr_e('Copy link', 'yomooh'); ?>">
                    <?php echo $icons['copy']; ?>
                    <span class="tooltip"><?php esc_html_e('Copy link', 'yomooh'); ?></span>
                </a>
            </li>
                <?php if (!empty($options['single_social_sharing']['facebook'])) : ?>
                    <li>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url($post_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Facebook', 'yomooh'); ?>">
                            <?php echo $icons['facebook']; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (!empty($options['single_social_sharing']['twitter'])) : ?>
                    <li>
                        <a href="https://twitter.com/intent/tweet?text=<?php echo esc_attr($post_title); ?>&url=<?php echo esc_url($post_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Twitter', 'yomooh'); ?>">
                            <?php echo $icons['twitter']; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (!empty($options['single_social_sharing']['linkedin'])) : ?>
                    <li>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo esc_url($post_url); ?>&title=<?php echo esc_attr($post_title); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on LinkedIn', 'yomooh'); ?>">
                            <?php echo $icons['linkedin']; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (!empty($options['single_social_sharing']['pinterest'])) : ?>
                    <li>
                        <a href="https://pinterest.com/pin/create/button/?url=<?php echo esc_url($post_url); ?>&media=<?php echo esc_url($post_thumbnail); ?>&description=<?php echo esc_attr($post_title); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Pinterest', 'yomooh'); ?>">
                            <?php echo $icons['pinterest']; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (!empty($options['single_social_sharing']['reddit'])) : ?>
                    <li>
                        <a href="https://www.reddit.com/submit?url=<?php echo esc_url($post_url); ?>&title=<?php echo esc_attr($post_title); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Reddit', 'yomooh'); ?>">
                            <?php echo $icons['reddit']; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (!empty($options['single_social_sharing']['whatsapp'])) : ?>
                    <li>
                        <a href="https://wa.me/?text=<?php echo esc_attr($post_title) . ' ' . esc_url($post_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on WhatsApp', 'yomooh'); ?>">
                            <?php echo $icons['whatsapp']; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (!empty($options['single_social_sharing']['email'])) : ?>
                    <li>
                        <a href="mailto:?subject=<?php echo esc_attr($post_title); ?>&body=<?php echo esc_attr($post_title) . ': ' . esc_url($post_url); ?>" aria-label="<?php esc_attr_e('Share via Email', 'yomooh'); ?>">
                            <?php echo $icons['email']; ?>
                        </a>
                    </li>
                <?php endif; ?>
				  <li>
				<a href="#" class="js-print-post" aria-label="<?php esc_attr_e('Print this post', 'yomooh'); ?>">
					<?php echo $icons['print']; ?>
					<span class="tooltip"><?php esc_html_e('Print', 'yomooh'); ?></span>
				</a>
			</li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Display newsletter popup
 */
function get_popup_newsletter() {
    $options = get_option('yomooh_options');

    if (empty($options['newsletter_enable'])) {
        return;
    }

    if (isset($_COOKIE['yomooh_newsletter_closed']) && $_COOKIE['yomooh_newsletter_closed'] === 'true') {
        return;
    }
    ?>
    <div id="yomooh-newsletter-popup" class="newsletter-popup">
        <div class="ns-popup-overlay"></div>
        <div class="ns-popup-content">
            <button class="ns-popup-close" aria-label="<?php esc_attr_e('Close newsletter popup', 'yomooh'); ?>">
                <i class="wp-icon-x"></i>
            </button>

            <?php if (!empty($options['newsletter_title'])) : ?>
                <h3 class="ns-popup-title"><span class="ns-popup-newsletter-icon"><i class="wpi-plane"></i></span><?php echo esc_html($options['newsletter_title']); ?></h3>
            <?php endif; ?>

            <?php if (!empty($options['newsletter_description'])) : ?>
                <p class="ns-popup-description"><?php echo esc_html($options['newsletter_description']); ?></p>
            <?php endif; ?>

            <?php if (!empty($options['newsletter_shortcode'])) : ?>
                <div class="ns-popup-form">
                    <?php echo do_shortcode($options['newsletter_shortcode']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'get_popup_newsletter', 10 );
add_action('pre_get_posts', 'yomooh_customize_search_query');
function yomooh_customize_search_query($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        $options = get_option('yomooh_options');

        $allowed = !empty($options['search_post_types']) 
            ? array_map('trim', explode(',', $options['search_post_types'])) 
            : [];

        $disallowed = !empty($options['disallow_search_post_types']) 
            ? array_map('trim', explode(',', $options['disallow_search_post_types'])) 
            : [];

        // Fetch all registered post types
        $all_post_types = get_post_types(['public' => true]);

        // Filter post types
        if (!empty($allowed)) {
            $post_types = array_intersect($all_post_types, $allowed);
        } else {
            $post_types = $all_post_types;
        }

        if (!empty($disallowed)) {
            $post_types = array_diff($post_types, $disallowed);
        }

        $query->set('post_type', $post_types);
    }
}
