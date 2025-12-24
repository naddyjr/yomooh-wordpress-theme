<?php

class Customizer_Importer {
    public static function import($data) {
        // First import site info and reading settings
        self::import_site_info($data);
        self::import_reading_settings($data);
        
        // Then import customizer options
        return self::import_customizer_options($data);
    }

    protected static function import_site_info($data) {
        if (!empty($data['site_info'])) {
            // Update site title
            if (isset($data['site_info']['title'])) {
                update_option('blogname', $data['site_info']['title']);
            }
            
            // Update site description
            if (isset($data['site_info']['description'])) {
                update_option('blogdescription', $data['site_info']['description']);
            }
            
            // Note: Favicon import would require additional handling to download and set the image
        }
    }

    protected static function import_reading_settings($data) {
        if (!empty($data['reading_settings'])) {
            // First set the front page display option
            if (isset($data['reading_settings']['show_on_front'])) {
                update_option('show_on_front', $data['reading_settings']['show_on_front']);
            }
            
            // Then set the actual pages - only if show_on_front is 'page'
            if (get_option('show_on_front') === 'page') {
                if (isset($data['reading_settings']['page_on_front'])) {
                    update_option('page_on_front', $data['reading_settings']['page_on_front']);
                }
                
                if (isset($data['reading_settings']['page_for_posts'])) {
                    update_option('page_for_posts', $data['reading_settings']['page_for_posts']);
                }
            } else {
                // If show_on_front isn't 'page', reset these values
                update_option('page_on_front', 0);
                update_option('page_for_posts', 0);
            }
        }
    }

    public static function import_customizer_options($data) {
        // Setup global vars
        global $wp_customize;

        // Data check
        if (!is_array($data) || !isset($data['mods'])) {
            return new WP_Error(
                'customizer_import_data_error',
                esc_html__('Error: The customizer import file is not in a correct format. Please make sure to use the correct customizer import file.', 'yomooh')
            );
        }

        if (apply_filters('som_customizer_import_images', true)) {
            $data['mods'] = self::import_customizer_images($data['mods']);
        }

        // Import custom options
        if (isset($data['options'])) {
            // Require modified customizer options class
            if (!class_exists('WP_Customize_Setting')) {
                require_once ABSPATH . 'wp-includes/class-wp-customize-setting.php';
            }

            if (!class_exists('som_Customizer_Option')) {
                require_once get_theme_file_path('/import/classes/class-customizer-option.php');
            }

            foreach ($data['options'] as $option_key => $option_value) {
                $option = new Customizer_Option($wp_customize, $option_key, array(
                    'default'    => '',
                    'type'       => 'option',
                    'capability' => 'edit_theme_options',
                ));

                $option->import($option_value);
            }
        }

        $use_wp_customize_save_hooks = apply_filters('som_enable_wp_customize_save_hooks', false);

        if ($use_wp_customize_save_hooks) {
            do_action('customize_save', $wp_customize);
        }

        // Import mods
        if (isset($data['mods']) && $data['mods']) {
            foreach ($data['mods'] as $key => &$value) {
                if ($use_wp_customize_save_hooks) {
                    do_action('customize_save_' . $key, $wp_customize);
                }
                set_theme_mod($key, $value);
            }
        }

        // Import Adobe mods
        if (isset($data['mods_adobe']) && $data['mods_adobe']) {
            foreach ($data['mods_adobe'] as $key => &$value) {
                if ($use_wp_customize_save_hooks) {
                    do_action('customize_save_' . $key, $wp_customize);
                }

                $token = get_option('powerkit_typekit_fonts_token');
                $kit   = get_option('powerkit_typekit_fonts_kit');

                $kit_fonts  = get_option('pk_typekit_' . $kit . '_s');
                $families   = ($kit_fonts) ? $kit_fonts['kit']['families'] : false;
                $font_found = false;

                // Search for the font slug from a theme_mod in the active Adobe font kit
                if (isset($value['font-family']) && $families) {
                    foreach ($families as $k => $v) {
                        if (isset($v['slug']) && $value['font-family'] === $v['slug']) {
                            $font_found = true;
                            break;
                        }
                        if (isset($v['css_names'][0]) && $value['font-family'] === $v['css_names'][0]) {
                            $font_found = true;
                            break;
                        }
                    }
                }

                // Set default font family
                if (is_array($value) && (!$token || !$kit || !$font_found)) {
                    $value['font-family'] = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
                }

                // Save the mod
                set_theme_mod($key, $value);
            }
        }

        if ($use_wp_customize_save_hooks) {
            do_action('customize_save_after', $wp_customize);
        }

        return true;
    }

    private static function import_customizer_images($mods) {
        foreach ($mods as $key => $val) {
            if (Manager_Import::is_image_url($val)) {
                $data = Manager_Import::import_custom_image($val);

                if (!is_wp_error($data)) {
                    $mods[$key] = $data->url;
                }
            }
        }

        return $mods;
    }
}