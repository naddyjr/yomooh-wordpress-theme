<?php
/** Modern Header Design 
 * @package Yomooh  
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
$options = get_option('yomooh_options');

// Header classes
$header_classes = ['desktop-header-2'];

// Container type
$container_type = 'full'; // default
if (!empty($options['header_container'])) {
    $container_type = $options['header_container'];
}
$header_classes[] = 'desktop-header-2-' . $container_type;

// Logo dimensions
$logo_width = !empty($options['logo_dimensions']['width']) ? $options['logo_dimensions']['width'] : 'auto';
$logo_height = !empty($options['logo_dimensions']['height']) ? $options['logo_dimensions']['height'] : '40px';

// Header background
$header_bg = '';
if (!empty($options['header_background'])) {
    switch ($options['header_background']) {
        case '2': // Color
            $header_bg = !empty($options['header_bg_color']) ? 'background-color: ' . esc_attr($options['header_bg_color']) . ';' : '';
            break;
        case '3': // Transparent
            $header_bg = 'background-color: transparent;';
            break;
        default: // Default
            $header_bg = '';
    }
}
$show_social = !empty($options['header_social_enable']) && $options['social_enable'];

// Header border
$header_border = '';
if (!empty($options['header_border']['border-radius'])) {
    $header_border .= 'border-bottom-left-radius: ' . esc_attr($options['header_border']['border-radius']) . ';';
    $header_border .= 'border-bottom-right-radius: ' . esc_attr($options['header_border']['border-radius']) . ';';
}

// Sticky header data attribute
$sticky_data = (!isset($options['header_sticky']) || (isset($options['header_sticky']) && $options['header_sticky'])) ? 'true' : 'false';
?>
<?php if (!empty($options['header_template_shortcode']) && function_exists('do_shortcode')) : ?>
    <div class="header-2-shortcode-container">
        <?php echo do_shortcode($options['header_template_shortcode']); ?>
    </div>
<?php else : ?>
    <!-- Modern Header Design -->
    <header id="mastheader-2" class="<?php echo esc_attr(implode(' ', $header_classes)); ?>" style="<?php echo $header_bg . $header_border; ?>" data-sticky="<?php echo $sticky_data; ?>">
        <!-- Top Bar -->
        <div class="header-2-top-bar">
            <div class="header-2-top-container">
                <div class="header-2-top-left">
                    <div class="header-2-date-time">
                        <span class="header-2-date"><?php echo date_i18n(get_option('date_format')); ?></span>
                        <span class="header-2-time" id="header-2-current-time"></span>
                    </div>
                </div>
                <div class="header-2-top-right">
                    <?php if (has_nav_menu('quick-link')) : ?>
                        <nav class="header-2-quick-links">
                            <?php wp_nav_menu([
                                'theme_location' => 'quick-link',
                                 'container'     => false,
                        'walker'        => new Yomooh_Walker_Nav_Menu(),
                                'depth' => 1,
                                'fallback_cb' => false
                            ]); ?>
                        </nav>
                    <?php endif; ?>
                    
                    <?php if ($show_social) : ?>
                        <div class="header-2-social-icons">
                            <?php render_social_icons($options); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['header_button']) && $options['header_button'] && !empty($options['header_button_text'])) : ?>
                        <div class="header-2-top-button">
                            <a href="<?php echo esc_url($options['header_button_url'] ?? '#'); ?>" class="button primary-button">
                                <?php echo esc_html($options['header_button_text']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Main Header -->
        <div class="header-2-main">
            <div class="header-2-inner">
                <div class="header-2-site-branding">
                    <?php
                    $header_logo = !empty($options['header_logo']) ? $options['header_logo']['url'] : '';
                    $header_logo_dark = !empty($options['header_logo_dark']) ? $options['header_logo_dark']['url'] : '';
                    $site_title = get_bloginfo('name');
                    
                    if ($header_logo || $header_logo_dark) :
                        // Both logos available
                        if ($header_logo && $header_logo_dark) : ?>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="header-2-logo" rel="home">
                                <img src="<?php echo esc_url($header_logo); ?>" class="logo-light" alt="<?php echo esc_attr($site_title); ?>" style="width: <?php echo esc_attr($logo_width); ?>; height: <?php echo esc_attr($logo_height); ?>;">
                                <img src="<?php echo esc_url($header_logo_dark); ?>" class="logo-dark" alt="<?php echo esc_attr($site_title); ?>" style="width: <?php echo esc_attr($logo_width); ?>; height: <?php echo esc_attr($logo_height); ?>;">
                            </a>
                        <?php 
                        // Only light logo available
                        elseif ($header_logo) : ?>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="header-2-logo" rel="home">
                                <img src="<?php echo esc_url($header_logo); ?>" alt="<?php echo esc_attr($site_title); ?>" style="width: <?php echo esc_attr($logo_width); ?>; height: <?php echo esc_attr($logo_height); ?>;">
                            </a>
                        <?php 
                        // Only dark logo available
                        else : ?>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="header-2-logo" rel="home">
                                <img src="<?php echo esc_url($header_logo_dark); ?>" alt="<?php echo esc_attr($site_title); ?>" style="width: <?php echo esc_attr($logo_width); ?>; height: <?php echo esc_attr($logo_height); ?>;">
                            </a>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="header-2-site-title">
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php echo esc_html($site_title); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
               
                <nav id="header-2-site-navigation" class="header-2-main-navigation">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'menu_id'        => 'header-2-primary-menu',
                        'container'     => false,
                        'walker'        => new Yomooh_Walker_Nav_Menu(),
                    ]);
                    ?>
                </nav>

                <div class="header-2-actions">
                    <?php if (!empty($options['header_search']) && $options['header_search']) : ?>
                        <div class="header-search">
                            <button class="search-toggle" aria-label="<?php esc_attr_e('Toggle search', 'yomooh'); ?>">
                                <i class="wp-icon-search"></i>
                            </button>
                            <div class="search-form-wrapper">
                                <?php yomooh_search_form(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['header_isdarkmode']) && $options['header_isdarkmode']) : ?>
                        <?php echo theme_mode_switcher2(); ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($options['header_signin_popup']) && $options['header_signin_popup']) : ?>
                        <?php display_account_dropdown(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <script>
    // Update current time in header
    function updateHeaderTime() {
        const now = new Date();
        const timeElement = document.getElementById('header-2-current-time');
        if (timeElement) {
            timeElement.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
    }
    setInterval(updateHeaderTime, 1000);
    updateHeaderTime();
    </script>
<?php endif; ?>

<?php

/**
 * Mobile Header
 */

$options = get_option('yomooh_options');
$mobile_classes = ['mobile-header'];

// Add sticky class if enabled
if (!empty($options['mobile_header_sticky']) && $options['mobile_header_sticky']) {
    $mobile_classes[] = 'sticky-mobile-header';
}

// Set mobile menu style
$menu_style = $options['mobile_menu_style'] ?? 'sidebar';
$mobile_classes[] = 'mobile-menu-' . esc_attr($menu_style);

// Set logo position
$logo_position = $options['mobile_header_layout'] ?? 'left';
$mobile_classes[] = 'mobile-logo-' . esc_attr($logo_position);

// Mobile header styles
$mobile_styles = '';
if (!empty($options['mobile_header_bg'])) {
    $mobile_styles .= 'background-color: ' . esc_attr($options['mobile_header_bg']) . ';';
}

// Logo dimensions
$mobile_logo_height = !empty($options['mobile_logo_height']['height']) ? 'height: ' . esc_attr($options['mobile_logo_height']['height']) . '; width: auto;' : '';

// Navigation height
$mobile_nav_height = !empty($options['mobile_nav_height']['height']) ? 'height: ' . esc_attr($options['mobile_nav_height']['height']) . ';' : '';
?>

<!-- Mobile Header -->
<?php if (!empty($options['mobile_header_template_shortcode']) && function_exists('do_shortcode')) : ?>
    <div class="mobile-header-shortcode-container">
        <?php echo do_shortcode($options['mobile_header_template_shortcode']); ?>
    </div>
<?php else : ?>
<header id="mobile-header" class="<?php echo esc_attr(implode(' ', $mobile_classes)); ?>" style="<?php echo $mobile_styles; ?>">
    <div class="mobile-header-inner" style="<?php echo $mobile_nav_height; ?>">
        <!-- Menu Toggle -->
        <button class="mobile-menu-toggle" aria-label="<?php esc_attr_e('Toggle menu', 'yomooh'); ?>">
            <i class="wp-icon-menu"></i>
            <i class="wp-icon-x"></i>
        </button>

        <div class="mobile-logo">
            <?php
            $mobile_header_logo = !empty($options['mobile_header_logo']) ? $options['mobile_header_logo']['url'] : '';
            $mobile_logo_dark = !empty($options['mobile_header_logo_dark']) ? $options['mobile_header_logo_dark']['url'] : '';
            $site_title = get_bloginfo('name');
            
            if ($mobile_header_logo || $mobile_logo_dark) :
                // Both logos available
                if ($mobile_header_logo && $mobile_logo_dark) : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="header-logo" rel="home">
                        <img src="<?php echo esc_url($mobile_header_logo); ?>" class="logo-light" alt="<?php echo esc_attr($site_title); ?>" style="<?php echo $mobile_logo_height; ?>">
                        <img src="<?php echo esc_url($mobile_logo_dark); ?>" class="logo-dark" alt="<?php echo esc_attr($site_title); ?>" style="<?php echo $mobile_logo_height; ?>">
                    </a>
                <?php 
                // Only light logo available
                elseif ($mobile_header_logo) : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="header-logo" rel="home">
                        <img src="<?php echo esc_url($mobile_header_logo); ?>" alt="<?php echo esc_attr($site_title); ?>" style="<?php echo $mobile_logo_height; ?>">
                    </a>
                <?php 
                // Only dark logo available
                else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="header-logo" rel="home">
                        <img src="<?php echo esc_url($mobile_logo_dark); ?>" alt="<?php echo esc_attr($site_title); ?>" style="<?php echo $mobile_logo_height; ?>">
                    </a>
                <?php endif; ?>
            <?php else : ?>
                <div class="site-title">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php echo esc_html($site_title); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Header Icons -->
        <div class="mobile-header-right">
            <?php if (!empty($options['mobile_search_icon']) && $options['mobile_search_icon']) : ?>
                <button class="mobile-search-toggle" aria-label="<?php esc_attr_e('Toggle search', 'yomooh'); ?>">
                    <i class="wp-icon-search"></i>
                </button>
            <?php endif; ?>
            
            <?php if (!empty($options['header_mobile_isdarkmode']) && $options['header_mobile_isdarkmode']) : ?>
        <?php echo theme_mode_switcher(); ?>
            <?php endif; ?>

            <?php if (!empty($options['mobile_header_signin_popup']) && $options['mobile_header_signin_popup']) : ?>
                  <?php display_account_dropdown(); ?>
                <?php endif; ?>
        </div>
    </div>
    
    <!-- Search Dropdown -->
    <?php if (!empty($options['mobile_search_icon']) && $options['mobile_search_icon']) : ?>
        <div class="mobile-search-dropdown">
            <div class="search-form-container">
                <?php yomooh_search_form(); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Mobile Menu -->
    <?php if ($menu_style === 'sidebar') : ?>
        <div id="mobile-sidebar" class="mobile-sidebar">
			<div class="mobile-sidebar-content">
            <div class="mobile-sidebar-header">
				<div class="site-title">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php echo esc_html($site_title); ?></a>
                </div>
                <button class="mobile-sidebar-close" aria-label="<?php esc_attr_e('Close menu', 'yomooh'); ?>">
                    <i class="wp-icon-x"></i>
                </button>
            </div>
            
            <nav id="mobile-navigation" class="mobile-navigation">
                <?php wp_nav_menu([
                    'theme_location' => 'mobile',
                    'menu_id'        => 'mobile-menu',
                    'container'      => false,
                    'walker'         => new Yomooh_Walker_Nav_Menu(),
                ]); ?>
            </nav>
				<?php if (!empty($options['header_button']) && $options['header_button'] && !empty($options['header_button_text'])) : ?>
			<div class="mobile-header-button">
                    <div class="header-button">
                        <a href="<?php echo esc_url($options['header_button_url'] ?? '#'); ?>" class="button primary-button">
                            <?php echo esc_html($options['header_button_text']); ?>
                        </a>
                    </div></div>
                <?php endif; ?>
        </div> </div>
    <?php else : ?>
        <div id="mobile-dropdown" class="mobile-dropdown">
            <nav id="mobile-navigation" class="mobile-navigation">
                <?php wp_nav_menu([
                    'theme_location' => 'mobile',
                    'menu_id'        => 'mobile-menu',
                    'container'      => false,
                    'walker'         => new Yomooh_Walker_Nav_Menu(),
                ]); ?>
            </nav>
			<?php if (!empty($options['header_button']) && $options['header_button'] && !empty($options['header_button_text'])) : ?>
			<div class="mobile-header-button">
                    <div class="header-button">
                        <a href="<?php echo esc_url($options['header_button_url'] ?? '#'); ?>" class="button primary-button">
                            <?php echo esc_html($options['header_button_text']); ?>
                        </a>
                    </div></div>
                <?php endif; ?>
			    <div class="dropdown-space"></div>
        </div>
    <?php endif; ?>
    
    <div class="mobile-sidebar-overlay"></div>
</header>
<?php endif; ?>