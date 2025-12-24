<?php
/** The header Yomooh theme
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

// Get theme options
$yomooh_options = get_option('yomooh_options');

// Initialize header display and design variables
$header_display = '';
$header_design = '';

if (is_singular()) {
    $single_header_display = get_post_meta(get_the_ID(), 'single_header_display', true);
    if ($single_header_display !== 'default' && $single_header_display !== '') {
        $header_display = $single_header_display;
    }
    $single_header_design = get_post_meta(get_the_ID(), 'single_header_design', true);
    if ($single_header_design !== 'default' && $single_header_design !== '') {
        $header_design = $single_header_design;
    }
    
}
elseif (is_home() && !is_front_page()) {
    $blog_page_id = get_option('page_for_posts');
    if ($blog_page_id) {
        $blog_header_display = get_post_meta($blog_page_id, 'single_header_display', true);
        if ($blog_header_display !== 'default' && $blog_header_display !== '') {
            $header_display = $blog_header_display;
        }

        $blog_header_design = get_post_meta($blog_page_id, 'single_header_design', true);
        if ($blog_header_design !== 'default' && $blog_header_design !== '') {
            $header_design = $blog_header_design;
        }
    }
}
if (empty($header_display)) {
    $header_display = !empty($yomooh_options['header_display']) ? $yomooh_options['header_display'] : 'enable';
}

if (empty($header_design)) {
    $header_design = !empty($yomooh_options['header_design']) ? $yomooh_options['header_design'] : 'default';
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="yomooh-html">
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="profile" href="https://gmpg.org/xfn/11" />
    <?php 
    if (!empty($yomooh_options['custom_html_head'])) {
        echo $yomooh_options['custom_html_head'];
    }
    wp_head(); 
    ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php 
if (!empty($yomooh_options['custom_html_body'])) {
    echo $yomooh_options['custom_html_body'];
}
?>

<?php 
// Only show header if it's enabled
if ($header_display !== 'disable') :
    if ($header_design === 'style2') {
        get_template_part('template-parts/headers/header2');
    } else {
        get_template_part('template-parts/headers/header-default');
    }
endif;