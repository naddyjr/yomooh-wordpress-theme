<?php
/** Template part for displaying a message that posts cannot be found
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0  
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');
?>

<section class="no-results not-found">
    <header class="not-found-header">
        <h1 class="not-found-title">
            <?php 
            if (is_search()) {
                esc_html_e('Nothing Found', 'yomooh');
            } else {
                esc_html_e('No Content Available', 'yomooh');
            }
            ?>
        </h1>
    </header>

    <div class="page-content">
        <?php if (is_search()) : ?>
			 <div class="container-form-center">
			<div class="search-form-wide">
				<?php yomooh_search_form(); ?>
			</div>
			<p class="no-results-message">
				<?php 
				printf(
					esc_html__('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'yomooh')
				); 
				?>
			</p>
		</div>
        <?php else : ?>
		<div class="container-form-center">

		<div class="search-form-wide">
				<?php yomooh_search_form(); ?>
			</div>
		<p class="no-results-message">
				<?php 
				printf(
					esc_html__('It seems we can\'t find what you\'re looking for. Perhaps searching can help.', 'yomooh')
				); 
				?>
			</p>
            </div>
        <?php endif; ?>
    </div>
</section>