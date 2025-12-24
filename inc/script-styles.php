<?php
/** Custom styles and script for yomooh Theme
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
class Yomooh_Inline_Scripts {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_inline_assets'), 20);
    }

    public function enqueue_inline_assets() {
        $yomooh_options = get_option('yomooh_options');

        // Handle custom CSS
        if (!empty($yomooh_options['custom_css_code'])) {
            wp_add_inline_style('yomooh-main', $yomooh_options['custom_css_code']);
        }

        // Handle custom JS
        if (!empty($yomooh_options['custom_js_code'])) {
            wp_register_script('yomooh-custom-js', '', [], '', true);
            wp_enqueue_script('yomooh-custom-js');
            wp_add_inline_script('yomooh-custom-js', wp_specialchars_decode($yomooh_options['custom_js_code']));
        }

        //  critical theme CSS
        $this->add_critical_theme_css();
    }

    private function add_critical_theme_css() {
        $critical_css = "
            /* Prevent transitions while theme is being set */
            .no-theme-transition,
            .no-theme-transition *,
            .no-theme-transition *:before,
            .no-theme-transition *:after {
                transition: none !important;
            }
            
            /* Initially hide the page while theme is being set */
            body {
                visibility: hidden;
            }
            
            /* Set dark theme variables immediately if cookie exists */
            body[data-theme=\"dark\"] {
                --primary-color: #4a90e2;
                --secondary-color: #00c6ff;
                --text-color: #e0e0e0;
                --heading-color: #ffffff;
                --link-color: #4a90e2;
                --link-hover-color: #3a7bd5;
                --background-color: #121212;
                --background-alt: #1e1e1e;
                --border-color: #333333;
                --card-bg: #1e1e1e;
                --header-bg: #1a1a1a;
                --footer-bg: #121212;
                --footer-text: #e0e0e0;
                --input-bg: #2d2d2d;
                --input-text: #e0e0e0;
                --shadow-color: rgba(0, 0, 0, 0.3);
                --layout-boxed-bg-color: #000000;
            }
        ";

        wp_add_inline_style('yomooh-main', $critical_css);
    }
}

new Yomooh_Inline_Scripts();