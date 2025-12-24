<?php
/** Pagination template with all types
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
$pagination_type = $options['blog_pagination'] ?? 'standard';
$posts_per_page = isset($options['blog_posts_per_page']) ? (int)$options['blog_posts_per_page'] : 6;

// Common pagination args
$args = [
    'mid_size'  => 2,
    'prev_text' => '<i class="wpi-angle-left"></i> ' . esc_html__('Previous', 'yomooh'),
    'next_text' => esc_html__('Next', 'yomooh') . ' <i class="wpi-angle-right"></i>',
];

switch ($pagination_type) {
    case 'numeric':
        ?>
        <div class="pagination-container numeric-pagination">
            <?php
            echo paginate_links(array_merge($args, [
                'type' => 'plain',
                'format' => '?paged=%#%',
            ]));
            ?>
        </div>
        <?php
        break;

    case 'loadmore':
        global $wp_query;
        if (get_next_posts_link()) :
            $max_pages = $wp_query->max_num_pages;
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            ?>
            <div class="pagination-container loadmore-pagination">
                <button id="loadmore-btn" class="loadmore-button" 
                        data-paged="<?php echo esc_attr($paged); ?>"
                        data-max-pages="<?php echo esc_attr($max_pages); ?>">
                    <?php esc_html_e('Load More', 'yomooh'); ?>
                </button>
                <div class="loading-spinner" style="display: none;"></div>
            </div>
            <?php
        endif;
        break;

    case 'infinite':
        global $wp_query;
        if (get_next_posts_link()) :
            $max_pages = $wp_query->max_num_pages;
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            ?>
            <div class="pagination-container infinite-pagination">
                <div class="loading-spinner" style="display: none;"></div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const spinner = document.querySelector('.infinite-pagination .loading-spinner');
                const container = document.querySelector('.blog-posts-container');
                let paged = <?php echo esc_js($paged); ?>;
                const maxPages = <?php echo esc_js($max_pages); ?>;
                let isLoading = false;
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !isLoading && paged < maxPages) {
                            isLoading = true;
                            spinner.style.display = 'block';
                            
                            // Increment page
                            paged++;
                            
                            // Update the URL with the next page
                            const nextLink = addQueryParam(window.location.href, 'paged', paged);
                            
                            fetch(nextLink)
                                .then(response => response.text())
                                .then(html => {
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(html, 'text/html');
                                    const posts = doc.querySelectorAll('.blog-posts-container > *');
                                    
                                    posts.forEach(post => {
                                        container.appendChild(post);
                                    });
                                    
                                    // Observe the new last post
                                    const lastPost = container.lastElementChild;
                                    if (lastPost) {
                                        observer.observe(lastPost);
                                    }
                                    
                                    isLoading = false;
                                    spinner.style.display = 'none';
                                })
                                .catch(() => {
                                    isLoading = false;
                                    spinner.style.display = 'none';
                                });
                        }
                    });
                }, { threshold: 0.1 });
                
                // Start observing the last post
                const lastPost = document.querySelector('.blog-posts-container > *:last-child');
                if (lastPost) {
                    observer.observe(lastPost);
                }
                
                // Helper function to add query parameters
                function addQueryParam(url, key, value) {
                    const urlObj = new URL(url);
                    urlObj.searchParams.set(key, value);
                    return urlObj.toString();
                }
            });
            </script>
            <?php
        endif;
        break;

    default: // Standard
        ?>
        <div class="pagination-container standard-pagination">
            <?php the_posts_pagination($args); ?>
        </div>
        <?php
        break;
}