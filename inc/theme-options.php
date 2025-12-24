<?php
/** Theme options integration with core plugin
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
if (!function_exists('yomooh_get_theme_option')) {
    function yomooh_get_theme_option($option, $default = '') {
        if (!is_core_plugin_active()) {
            return $default;
        }
        
        $options = get_option('yomooh_options');
        return $options[$option] ?? $default;
    }
}

if (!function_exists('yomooh_generate_dynamic_css')) {
    /**
     * Generate dynamic CSS based on theme options
     * 
     * @return string Generated CSS
     */
    function yomooh_generate_dynamic_css() {
        if (!is_core_plugin_active()) {
            return '';
        }
        
   $css = '';
   $options = get_option('yomooh_options');
    
    /* Body Layout CSS */
    // Site Layout
    $site_layout = $options['site_layout'] ?? 'default';
    
    // Boxed Layout Settings
    if ($site_layout === 'boxed') {
        // Boxed Width
        if (isset($options['content_width'])) {
            $content_width = $options['content_width'];
            $css .= ':root { --layout-boxed-width: ' . esc_attr($content_width['width']) . esc_attr($content_width['units']) . '; }';
        }
        
        // Boxed Background Color
        $css .= '.yomooh-layout-boxed { background-color: var(--layout-boxed-bg-color); }';
        $css .= '.yomooh-layout-boxed .site-container { background-color: var(--background-color); }';
    }
    
    // Body Background
    $body_bg_option = $options['body_backg_option'] ?? '1';
    if ($body_bg_option === '2' && !empty($options['body_bg_color'])) {
        $css .= ':root { --body-bg-color: ' . esc_attr($options['body_bg_color']) . '; }';
        $css .= 'body.yomooh-body-bg-color { background-color: var(--body-bg-color); }';
    } elseif ($body_bg_option === '3' && !empty($options['body_bg_image'])) {
        $image_url = wp_get_attachment_url($options['body_bg_image']['id']);
        $css .= ':root { --body-bg-image: url("' . esc_url($image_url) . '"); }';
        $css .= 'body.yomooh-body-bg-image { background-image: var(--body-bg-image); }';
    }
    
    // Page Padding
    $is_page_padding = $options['is_page_padding'] ?? 'default';
    if ($is_page_padding === 'custom' && isset($options['body_padding'])) {
        $padding = $options['body_padding'];
        $css .= 'body.yomooh-page-padding-custom { 
            padding-top: ' . esc_attr($padding['padding-top']) . ';
            padding-right: ' . esc_attr($padding['padding-right']) . ';
            padding-bottom: ' . esc_attr($padding['padding-bottom']) . ';
            padding-left: ' . esc_attr($padding['padding-left']) . ';
        }';
    }
    
    // Content Padding
    if ($is_page_padding === 'custom' && isset($options['content_padding'])) {
        $padding = $options['content_padding'];
        $css .= '.entry-page-content { 
            padding-top: ' . esc_attr($padding['padding-top']) . ';
            padding-right: ' . esc_attr($padding['padding-right']) . ';
            padding-bottom: ' . esc_attr($padding['padding-bottom']) . ';
            padding-left: ' . esc_attr($padding['padding-left']) . ';
        }';
    }
        
        // Typography
        if (yomooh_get_theme_option('enable_font_changes', true)) {
            // Body Typography
            $body_typography = yomooh_get_theme_option('body_typography', []);
            if (!empty($body_typography)) {
                $css .= 'body {';
                if (!empty($body_typography['font-family'])) {
                    $css .= 'font-family: ' . esc_attr($body_typography['font-family']) . ';';
                }
                if (!empty($body_typography['font-size'])) {
                    $css .= 'font-size: ' . esc_attr($body_typography['font-size']) . ';';
                }
                if (!empty($body_typography['font-weight'])) {
                    $css .= 'font-weight: ' . esc_attr($body_typography['font-weight']) . ';';
                }
                if (!empty($body_typography['line-height'])) {
                    $css .= 'line-height: ' . esc_attr($body_typography['line-height']) . ';';
                }
                if (!empty($body_typography['font-style'])) {
                    $css .= 'font-style: ' . esc_attr($body_typography['font-style']) . ';';
                }
                $css .= '}';
            }
            
            // Heading Typography
            $heading_typography = yomooh_get_theme_option('heading_typography', []);
            if (!empty($heading_typography)) {
                $css .= 'h1, h2, h3, h4, h5, h6 {';
                if (!empty($heading_typography['font-family'])) {
                    $css .= 'font-family: ' . esc_attr($heading_typography['font-family']) . ';';
                }
                if (!empty($heading_typography['font-weight'])) {
                    $css .= 'font-weight: ' . esc_attr($heading_typography['font-weight']) . ';';
                }
                if (!empty($heading_typography['font-style'])) {
                    $css .= 'font-style: ' . esc_attr($heading_typography['font-style']) . ';';
                }
                $css .= '}';
            }
            
            // Individual Heading Sizes
            $h1_typography = yomooh_get_theme_option('h1_typography', []);
            if (!empty($h1_typography['font-size']) || !empty($h1_typography['line-height'])) {
                $css .= 'h1 {';
                if (!empty($h1_typography['font-size'])) {
                    $css .= 'font-size: ' . esc_attr($h1_typography['font-size']) . ';';
                }
                if (!empty($h1_typography['line-height'])) {
                    $css .= 'line-height: ' . esc_attr($h1_typography['line-height']) . ';';
                }
                if (!empty($h1_typography['text-align'])) {
                    $css .= 'text-align: ' . esc_attr($h1_typography['text-align']) . ';';
                }
                $css .= '}';
            }
            
            $h2_typography = yomooh_get_theme_option('h2_typography', []);
            if (!empty($h2_typography['font-size']) || !empty($h2_typography['line-height'])) {
                $css .= 'h2 {';
                if (!empty($h2_typography['font-size'])) {
                    $css .= 'font-size: ' . esc_attr($h2_typography['font-size']) . ';';
                }
                if (!empty($h2_typography['line-height'])) {
                    $css .= 'line-height: ' . esc_attr($h2_typography['line-height']) . ';';
                }
                if (!empty($h2_typography['text-align'])) {
                    $css .= 'text-align: ' . esc_attr($h2_typography['text-align']) . ';';
                }
                $css .= '}';
            }
            
            $h3_typography = yomooh_get_theme_option('h3_typography', []);
            if (!empty($h3_typography['font-size']) || !empty($h3_typography['line-height'])) {
                $css .= 'h3 {';
                if (!empty($h3_typography['font-size'])) {
                    $css .= 'font-size: ' . esc_attr($h3_typography['font-size']) . ';';
                }
                if (!empty($h3_typography['line-height'])) {
                    $css .= 'line-height: ' . esc_attr($h3_typography['line-height']) . ';';
                }
                if (!empty($h3_typography['text-align'])) {
                    $css .= 'text-align: ' . esc_attr($h3_typography['text-align']) . ';';
                }
                $css .= '}';
            }
            
            // Menu Typography
            $menu_typography = yomooh_get_theme_option('menu_typography', []);
            if (!empty($menu_typography)) {
                $css .= '.main-navigation a {';
                if (!empty($menu_typography['font-family'])) {
                    $css .= 'font-family: ' . esc_attr($menu_typography['font-family']) . ';';
                }
                if (!empty($menu_typography['font-size'])) {
                    $css .= 'font-size: ' . esc_attr($menu_typography['font-size']) . ';';
                }
                if (!empty($menu_typography['font-weight'])) {
                    $css .= 'font-weight: ' . esc_attr($menu_typography['font-weight']) . ';';
                }
                if (!empty($menu_typography['line-height'])) {
                    $css .= 'line-height: ' . esc_attr($menu_typography['line-height']) . ';';
                }
                if (!empty($menu_typography['font-style'])) {
                    $css .= 'font-style: ' . esc_attr($menu_typography['font-style']) . ';';
                }
                $css .= '}';
            }
        }
        
        // Colors
        if (yomooh_get_theme_option('enable_color_changes', true)) {
            // Text Colors
            $body_text_color = yomooh_get_theme_option('body_text_color', '#333333');
            $heading_color = yomooh_get_theme_option('heading_color', '#222222');
            $link_color = yomooh_get_theme_option('link_color', '#3a7bd5');
            $link_hover_color = yomooh_get_theme_option('link_hover_color', '#2c5fb3');
            
            $css .= '
            body {
                color: ' . esc_attr($body_text_color) . ';
            }
            
            h1, h2, h3, h4, h5, h6, .heading {
                color: ' . esc_attr($heading_color) . ';
            }
            
            a {
                color: ' . esc_attr($link_color) . ';
            }
            
            a:hover, a:focus {
                color: ' . esc_attr($link_hover_color) . ';
            }';
            
            // Color Scheme
            $primary_color = yomooh_get_theme_option('primary_color', '#3a7bd5');
            $secondary_color = yomooh_get_theme_option('secondary_color', '#00d2ff');
            $success_color = yomooh_get_theme_option('success_color', '#28a745');
            $error_color = yomooh_get_theme_option('error_color', '#dc3545');
            $warning_color = yomooh_get_theme_option('warning_color', '#ffc107');
            $info_color = yomooh_get_theme_option('info_color', '#17a2b8');
            $light_color = yomooh_get_theme_option('light_color', '#f8f9fa');
            $dark_color = yomooh_get_theme_option('dark_color', '#343a40');
            $border_color = yomooh_get_theme_option('border_color', '#dee2e6');
            
            $css .= '
            /* Primary Color */
            .text-primary { color: ' . esc_attr($primary_color) . '; }
            .bg-primary { background-color: ' . esc_attr($primary_color) . '; }
            .border-primary { border-color: ' . esc_attr($primary_color) . '; }
            .btn-primary { 
                background-color: ' . esc_attr($primary_color) . ';
                border-color: ' . esc_attr($primary_color) . ';
            }
            
            /* Secondary Color */
            .text-secondary { color: ' . esc_attr($secondary_color) . '; }
            .bg-secondary { background-color: ' . esc_attr($secondary_color) . '; }
            .border-secondary { border-color: ' . esc_attr($secondary_color) . '; }
            .btn-secondary { 
                background-color: ' . esc_attr($secondary_color) . ';
                border-color: ' . esc_attr($secondary_color) . ';
            }
            
            /* Success Color */
            .text-success { color: ' . esc_attr($success_color) . '; }
            .bg-success { background-color: ' . esc_attr($success_color) . '; }
            .border-success { border-color: ' . esc_attr($success_color) . '; }
            .btn-success { 
                background-color: ' . esc_attr($success_color) . ';
                border-color: ' . esc_attr($success_color) . ';
            }
            
            /* Error Color */
            .text-error { color: ' . esc_attr($error_color) . '; }
            .bg-error { background-color: ' . esc_attr($error_color) . '; }
            .border-error { border-color: ' . esc_attr($error_color) . '; }
            .btn-error { 
                background-color: ' . esc_attr($error_color) . ';
                border-color: ' . esc_attr($error_color) . ';
            }
            
            /* Warning Color */
            .text-warning { color: ' . esc_attr($warning_color) . '; }
            .bg-warning { background-color: ' . esc_attr($warning_color) . '; }
            .border-warning { border-color: ' . esc_attr($warning_color) . '; }
            .btn-warning { 
                background-color: ' . esc_attr($warning_color) . ';
                border-color: ' . esc_attr($warning_color) . ';
            }
            
            /* Info Color */
            .text-info { color: ' . esc_attr($info_color) . '; }
            .bg-info { background-color: ' . esc_attr($info_color) . '; }
            .border-info { border-color: ' . esc_attr($info_color) . '; }
            .btn-info { 
                background-color: ' . esc_attr($info_color) . ';
                border-color: ' . esc_attr($info_color) . ';
            }
            
            /* Light Color */
            .text-light { color: ' . esc_attr($light_color) . '; }
            .bg-light { background-color: ' . esc_attr($light_color) . '; }
            .border-light { border-color: ' . esc_attr($light_color) . '; }
            
            /* Dark Color */
            .text-dark { color: ' . esc_attr($dark_color) . '; }
            .bg-dark { background-color: ' . esc_attr($dark_color) . '; }
            .border-dark { border-color: ' . esc_attr($dark_color) . '; }
            
            /* Borders */
            .border, input, textarea, select {
                border-color: ' . esc_attr($border_color) . ';
            }';
        }
        
        return $css;
    }
}

// Add body class based on layout
add_filter('body_class', function($classes) {
    if (is_core_plugin_active()) {
        $layout = yomooh_get_theme_option('site_layout', 'default');
        $classes[] = 'yomooh-layout-' . $layout;
        
        // Add background type class
        $bg_option = yomooh_get_theme_option('body_backg_option', '1');
        $classes[] = 'yomooh-body-bg-' . ($bg_option === '1' ? 'default' : ($bg_option === '2' ? 'color' : 'image'));
        
        // Add padding type class
        $padding_option = yomooh_get_theme_option('is_page_padding', 'default');
        $classes[] = 'yomooh-page-padding-' . $padding_option;
    }
    return $classes;
});
?>

<?php 
// Enqueue Google Fonts based on typography settings
add_action('wp_enqueue_scripts', 'yomooh_enqueue_google_fonts');
function yomooh_enqueue_google_fonts() {
    if (!is_core_plugin_active() || !yomooh_get_theme_option('enable_font_changes', true)) {
        return;
    }
    
    $font_families = [];
    $subsets = yomooh_get_theme_option('font_subsets', ['latin' => true]);
    
    // Body font
    $body_typography = yomooh_get_theme_option('body_typography', []);
    if (!empty($body_typography['font-family'])) {
        $font_families[] = $body_typography['font-family'] . ':' . ($body_typography['font-weight'] ?? '400') . ($body_typography['font-style'] === 'italic' ? 'i' : '');
    }
    
    // Heading font
    $heading_typography = yomooh_get_theme_option('heading_typography', []);
    if (!empty($heading_typography['font-family']) && $heading_typography['font-family'] !== ($body_typography['font-family'] ?? '')) {
        $font_families[] = $heading_typography['font-family'] . ':' . ($heading_typography['font-weight'] ?? '700') . ($heading_typography['font-style'] === 'italic' ? 'i' : '');
    }
    
    // Menu font
    $menu_typography = yomooh_get_theme_option('menu_typography', []);
    if (!empty($menu_typography['font-family']) && $menu_typography['font-family'] !== ($body_typography['font-family'] ?? '') && $menu_typography['font-family'] !== ($heading_typography['font-family'] ?? '')) {
        $font_families[] = $menu_typography['font-family'] . ':' . ($menu_typography['font-weight'] ?? '600') . ($menu_typography['font-style'] === 'italic' ? 'i' : '');
    }
    
    if (!empty($font_families)) {
        $query_args = [
            'family' => urlencode(implode('|', array_unique($font_families))),
            'subset' => urlencode(implode(',', array_keys(array_filter($subsets))))
        ];
        
        wp_enqueue_style('yomooh-google-fonts', add_query_arg($query_args, 'https://fonts.googleapis.com/css'), [], null);
    }
}
/**
 * Generate dynamic CSS for footer elements
 */
if (!function_exists('yomooh_generate_footer_css')) {
    function yomooh_generate_footer_css() {
        if (!is_core_plugin_active()) {
            return '';
        }

        $options = get_option('yomooh_options');
        $css = '';

        // Social Icons Styles
        $css .= yomooh_get_social_icons_css($options);
        
        // Footer Layout Styles
        $css .= yomooh_get_footer_layout_css($options);
        
        // Footer Background and Colors
        $css .= yomooh_get_footer_background_css($options);

        return $css;
    }
}

/**
 * Generate CSS for social icons
 */
if (!function_exists('yomooh_get_social_icons_css')) {
    function yomooh_get_social_icons_css($options) {
        $css = '';

        // Custom social colors
        $css .= '.social-icons-wrapper.color-custom .social-icon a {';
        $css .= 'color: ' . esc_attr($options['social_custom_color'] ?? '#333333') . ';';
        $css .= 'background-color: transparent;';
        $css .= '}';
        
        $css .= '.social-icons-wrapper.color-custom .social-icon a:hover {';
        $css .= 'color: ' . esc_attr($options['social_custom_hover'] ?? '#000000') . ';';
        $css .= '}';

        // Social icon size
        if (!empty($options['social_size'])) {
            $css .= '.social-icon i {';
            $css .= 'font-size: ' . esc_attr($options['social_size']['width']) . ';';
            $css .= 'width: ' . esc_attr($options['social_size']['width']) . ';';
            $css .= 'height: ' . esc_attr($options['social_size']['height']) . ';';
            $css .= 'line-height: ' . esc_attr($options['social_size']['height']) . ';';
            $css .= '}';
        }

        // Social icon spacing
		if (!empty($options['social_spacing']) && !empty($options['social_spacing']['margin-left'])) {
            $css .= '.social-icons-list {';
            $css .= 'gap: ' . esc_attr($options['social_spacing']['margin-left']) . ';';
            $css .= '}';
        }

        return $css;
    }
}

/**
 * Generate CSS for footer layout
 */
if (!function_exists('yomooh_get_footer_layout_css')) {
    function yomooh_get_footer_layout_css($options) {
        $css = '';

        // Boxed footer styles
        $css .= '.footer-boxed {';
        $css .= 'background-color: ' . esc_attr($options['footer_boxed_bg'] ?? '#ffffff') . ';';
        $css .= 'padding: 30px;';
        $css .= 'margin: 0 auto;';
        $css .= 'box-shadow: 0 0 15px rgba(0,0,0,0.1);';
        $css .= '}';

        // Custom width for boxed footer
        if (!empty($options['footer_custom_width']['width']) && $options['footer_width'] === 'boxed') {
            $css .= '.footer-boxed .footer-boxed-container {';
            $css .= 'max-width: ' . esc_attr($options['footer_custom_width']['width']) . ';';
            $css .= 'margin: 0 auto;';
            $css .= '}';
        } elseif ($options['footer_width'] === 'boxed') {
            $css .= '.footer-boxed .footer-boxed-container {';
            $css .= 'max-width: 1200px;';
            $css .= 'margin: 0 auto;';
            $css .= '}';
        }

        return $css;
    }
}

/**
 * Generate CSS for footer background and colors
 */
if (!function_exists('yomooh_get_footer_background_css')) {
    function yomooh_get_footer_background_css($options) {
        $css = '';

        if (!empty($options['footer_background'])) {
            $bg = $options['footer_background'];
            
            $css .= '#site-footer, #site-footer .footer-copyright {';
            if (!empty($bg['background-color'])) {
                $css .= 'background-color: ' . esc_attr($bg['background-color']) . ';';
            }
            if (!empty($bg['background-image'])) {
                $css .= 'background-image: url(' . esc_url($bg['background-image']) . ');';
                if (!empty($bg['background-repeat'])) {
                    $css .= 'background-repeat: ' . esc_attr($bg['background-repeat']) . ';';
                }
                if (!empty($bg['background-position'])) {
                    $css .= 'background-position: ' . esc_attr($bg['background-position']) . ';';
                }
                if (!empty($bg['background-size'])) {
                    $css .= 'background-size: ' . esc_attr($bg['background-size']) . ';';
                }
                if (!empty($bg['background-attachment'])) {
                    $css .= 'background-attachment: ' . esc_attr($bg['background-attachment']) . ';';
                }
            }
            $css .= '}';
            
            // For boxed layout, we need to override the copyright background
            if ($options['footer_width'] === 'boxed') {
                $css .= '#site-footer .footer-copyright { background-color: transparent; }';
            }
        }

        // Adjust colors if background is set
        if (!empty($options['footer_background']['background-color'])) {
            $bg_color = $options['footer_background']['background-color'];
            $brightness = yomooh_get_color_brightness($bg_color);
            
            if ($brightness > 128) {
                $css .= '.site-footer { color: #333333; }';
                $css .= '.footer-widget .widget-title { color: #333333; }';
                $css .= '.footer-widget a { color: #555555; }';
                $css .= '.footer-widget a:hover { color: #000000; }';
                $css .= '.footer-widget p { color: #555555; }';
                $css .= '.copyright-text { color: #555555; }';
                $css .= '.copyright-text a { color: #333333; }';
                $css .= '.social-icons-wrapper.color-inherit .social-icon a { color: #333333; }';
                $css .= '.social-icons-wrapper.style-rounded .social-icon a, 
                          .social-icons-wrapper.style-square .social-icon a { 
                            background-color: rgba(0,0,0,0.05); 
                          }';
            } else {
                $css .= '.site-footer { color: #ffffff; }';
                $css .= '.footer-widget .widget-title { color: #ffffff; }';
                $css .= '.footer-widget a { color: #dddddd; }';
                $css .= '.footer-widget a:hover { color: #ffffff; }';
                $css .= '.footer-widget p { color: #dddddd; }';
                $css .= '.copyright-text { color: #dddddd; }';
                $css .= '.copyright-text a { color: #ffffff; }';
                $css .= '.social-icons-wrapper.color-inherit .social-icon a { color: #ffffff; }';
                $css .= '.social-icons-wrapper.style-rounded .social-icon a, 
                          .social-icons-wrapper.style-square .social-icon a { 
                            background-color: rgba(255,255,255,0.1); 
                          }';
            }
        }

        return $css;
    }
}

/**
 * Helper function to calculate color brightness
 */
if (!function_exists('yomooh_get_color_brightness')) {
    function yomooh_get_color_brightness($hex) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    }
}

/**
 * Enqueue the generated footer CSS
 */
add_action('wp_enqueue_scripts', 'yomooh_enqueue_footer_css');
function yomooh_enqueue_footer_css() {
    $css = yomooh_generate_footer_css();
    if (!empty($css)) {
        wp_add_inline_style('yomooh-main', $css);
    }
}