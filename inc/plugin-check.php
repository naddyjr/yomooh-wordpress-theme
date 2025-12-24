<?php
/** Plugin dependency check for Yomooh Theme
 *
 * @package Yomooh
 */
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
if (!function_exists('yomooh_check_required_plugins')) {
    /**
     * Check for required plugins
     */
    function yomooh_check_required_plugins() {
        $plugins = [
            [
                'name'      => 'Yomooh Core', 
                'slug'      => 'yomooh-core',
                'required'  => true,
                'version'   => '1.0.0',
                'source'    => get_template_directory() . '/plugins/yomooh-core.zip', // Local bundled version
            ],
            [
                'name'      => 'Elementor Page Builder',
                'slug'      => 'elementor',
                'required'  => true,
            ],
            [
                'name'      => 'Redux Framework',
                'slug'      => 'redux-framework',
                'required'  => true,
            ],
            [
                'name'      => 'Contact Form 7',
                'slug'      => 'contact-form-7',
                'required'  => false, 
            ],
            [
                'name'      => 'Mailchimp for WordPress',
                'slug'      => 'mailchimp-for-wp',
                'required'  => false, 
            ],
            [
                'name'      => 'Classic Widgets',
                'slug'      => 'classic-widgets',
                'required'  => false, 
            ]
        ];

             $config = [
            'id'           => 'yomooh-tgmpa',
            'default_path' => '',
            'menu'         => 'tgmpa-install-plugins',
            'parent_slug'  => 'themes.php',
            'capability'   => 'edit_theme_options',
            'has_notices'  => true,
            'dismissable'  => true,
            'dismiss_msg'  => '',
            'is_automatic' => false,
            'message'      => '',
            
            'strings'      => [
                'page_title'                      => __('Install Required Plugins', 'yomooh'),
                'menu_title'                      => __('Install Plugins', 'yomooh'),
                'installing'                      => __('Installing Plugin: %s', 'yomooh'),
                'updating'                        => __('Updating Plugin: %s', 'yomooh'),
                'oops'                            => __('Something went wrong with the plugin API.', 'yomooh'),
                'notice_can_install_required'     => _n_noop(
                    'This theme requires the following plugin: %1$s.',
                    'This theme requires the following plugins: %1$s.',
                    'yomooh'
                ),
                'notice_can_install_recommended'  => _n_noop(
                    'This theme recommends the following plugin: %1$s.',
                    'This theme recommends the following plugins: %1$s.',
                    'yomooh'
                ),
                'notice_ask_to_update'            => _n_noop(
                    'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
                    'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
                    'yomooh'
                ),
                'notice_ask_to_update_maybe'      => _n_noop(
                    'There is an update available for: %1$s.',
                    'There are updates available for the following plugins: %1$s.',
                    'yomooh'
                ),
                'notice_can_activate_required'   => _n_noop(
                    'The following required plugin is currently inactive: %1$s.',
                    'The following required plugins are currently inactive: %1$s.',
                    'yomooh'
                ),
                'notice_can_activate_recommended' => _n_noop(
                    'The following recommended plugin is currently inactive: %1$s.',
                    'The following recommended plugins are currently inactive: %1$s.',
                    'yomooh'
                ),
                'install_link'                    => _n_noop(
                    'Begin installing plugin',
                    'Begin installing plugins',
                    'yomooh'
                ),
                'update_link'                     => _n_noop(
                    'Begin updating plugin',
                    'Begin updating plugins',
                    'yomooh'
                ),
                'activate_link'                   => _n_noop(
                    'Begin activating plugin',
                    'Begin activating plugins',
                    'yomooh'
                ),
                'return'                          => __('Return to Required Plugins Installer', 'yomooh'),
                'plugin_activated'                => __('Plugin activated successfully.', 'yomooh'),
                'activated_successfully'          => __('The following plugin was activated successfully:', 'yomooh'),
                'plugin_already_active'           => __('No action taken. Plugin %1$s was already active.', 'yomooh'),
                'plugin_needs_higher_version'     => __('Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'yomooh'),
                'complete'                        => __('All plugins installed and activated successfully. %1$s', 'yomooh'),
                'dismiss'                         => __('Dismiss this notice', 'yomooh'),
                'notice_cannot_install_activate'  => __('There are one or more required or recommended plugins to install, update or activate.', 'yomooh'),
                'contact_admin'                  => __('Please contact the administrator of this site for help.', 'yomooh'),
            ]
        ];

        tgmpa($plugins, $config);
    }
    add_action('tgmpa_register', 'yomooh_check_required_plugins');
}

if (!function_exists('is_core_plugin_active')) {
    /**
     * Check if Yomooh plugin is active
     */
    function is_core_plugin_active() {
        return class_exists('Yomooh\Backend\settings\AdminOptions');
    }
}
