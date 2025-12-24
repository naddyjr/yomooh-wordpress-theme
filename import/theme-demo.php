<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
/** Theme Demo Importer Class
 *
 * @package Yomooh
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'Theme_Demo' ) ) {
	class Theme_Demo {
		
		public $settings_menu_slug = 'yomooh_options';
        public $menu_slug = 'yomooh-demo-importer';
		public $demos = array();
		
		public function __construct() {
			$self = $this;

			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			require_once get_theme_file_path( '/import/classes/class-widget-importer.php' );
			require_once get_theme_file_path( '/import/classes/class-customizer-importer.php' );
			require_once get_theme_file_path( '/import/classes/class-manager-import.php' );

			// Actions.
			add_action( 'init', array( $this, 'set_demo' ) );
			add_action( 'admin_menu', array( $this, 'add_menu_page' ), 101 );
            add_action( 'som_register_demo_list', array( $this, 'demos_list' ) );
            add_action( 'som_finish_import', array( $this, 'hook_finish_import' ) );
			add_action( 'wp_ajax_som_html_import_data', array( $this, 'import_data_ui' ) );
			add_action( 'wp_ajax_nopriv_som_html_import_data', array( $this, 'import_data_ui' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 5 );
		}

		/**
		 * Add menu page
		 */
		public function add_menu_page() {
			add_submenu_page( 'yomooh-admin', 
			esc_html__( 'Theme Demo', 'yomooh' ),
			 esc_html__( 'Theme Demo', 'yomooh' ), 
			 'manage_options', 
			 $this->menu_slug, 
			 array( $this, 'render_demo_importer_page' ), 100 );
		}

        public function set_demo( $demos ) {
			$this->demos = apply_filters( 'som_register_demo_list', $this->demos );
		}

		/**
		 * @param string $plugin_path Plugin path.
		 */
		public function get_plugin_status( $plugin_path ) {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_path ) ) {
				return 'not_installed';
			} elseif ( in_array( $plugin_path, (array) get_option( 'active_plugins', array() ), true ) || is_plugin_active_for_network( $plugin_path ) ) {
				return 'active';
			} else {
				return 'inactive';
			}
		}

		public function import_data_ui() {
            global $wpdb;

            check_ajax_referer( 'nonce', 'nonce' );

            $demo_id = isset( $_POST['demo_id'] ) ? sanitize_text_field( $_POST['demo_id'] ) : false;

            if ( $demo_id ) {
                if ( ! isset( $this->demos[ $demo_id ] ) ) {
                    wp_send_json_error( esc_html__( 'Invalid demo content id.', 'yomooh' ) );
                    wp_die();
                }
                $like_pattern = $wpdb->esc_like( 'som_importer_data_' ) . '%';
                $wpdb->query( $wpdb->prepare( 
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 
                    $like_pattern 
                ) );

             
                $demo_data = $this->demos[ $demo_id ];
                ob_start();
                $demo_plugins = isset( $demo_data['plugins'] ) ? $demo_data['plugins'] : array();
                if ( $demo_plugins ) {
                    foreach ( $demo_plugins as $key => $plugin ) {
                        if ( ! isset( $plugin['name'] ) ) {
                            unset( $demo_plugins[ $key ] );
                            continue;
                        }
                        if ( ! isset( $plugin['slug'] ) ) {
                            unset( $demo_plugins[ $key ] );
                            continue;
                        }
                        if ( ! isset( $plugin['path'] ) ) {
                            unset( $demo_plugins[ $key ] );
                            continue;
                        }
                        if ( 'active' === $this->get_plugin_status( $plugin['path'] ) ) {
                            unset( $demo_plugins[ $key ] );
                            continue;
                        }
                    }
                }
                ?>
                <div class="import-data">
                    <?php if ( $demo_plugins ) { ?>
                        <div class="import-plugins">
                            <div class="import-subheader">
                                <?php esc_html_e( 'Install Plugins', 'yomooh' ); ?>
                            </div>

                            <?php foreach ( $demo_plugins as $plugin ) {
                                $required = isset( $plugin['required'] ) ? $plugin['required'] : false;
                                ?>
                                <form>
                                    <div class="switcher">
                                        <?php echo esc_html( $plugin['name'] ); ?> <input class="checkbox" type="checkbox" name="<?php echo esc_attr( $plugin['slug'] ); ?>" value="1" <?php echo wp_kses( $required ? 'readony onclick="return false;"' : null, 'content' ); ?> checked>

                                        <?php if ( isset( $plugin['desc'] ) && $plugin['desc'] ) { ?>
                                            <div class="tooltip-help"><i class="dashicons dashicons-editor-help">												</i></div>
										<?php if ( $required ) : ?>
										<span class="plugin-required"><?php esc_html_e( 'Required', 'yomooh' ); ?>											</span>
									<?php else : ?>
										<span class="plugin-recommended"><?php esc_html_e( 'Recommended', 'yomooh' 											); ?></span>
									<?php endif; ?>
                                            <div class="tooltip-desc"><?php echo esc_html( $plugin['desc'] ); ?>												</div>
                                        <?php } ?>

                                        <div class="switch"><span class="switch-slider"></span></div>
                                        <div class="tooltip"><?php esc_html_e( 'Required plugin will be installed', 'yomooh' ); ?></div>
                                    </div>

                                    <input type="hidden" name="plugin_slug" value="<?php echo esc_attr( $plugin['slug'] ); ?>">
                                    <input type="hidden" name="plugin_path" value="<?php echo esc_attr( $plugin['path'] ); ?>">
                                    <input type="hidden" name="step_name" value="<?php esc_attr_e( 'Installing and activating', 'yomooh' ); ?> <?php echo esc_attr( $plugin['name'] ); ?>...">
                                    <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'nonce' ) ); ?>">
                                    <input type="hidden" name="action" value="som_import_plugin">
                                </form>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <div class="import-content">
                        <form class="hidden">
                            <input type="hidden" name="step_name" value="<?php esc_attr_e( 'Pre import...', 'yomooh' ); ?>">
                            <input type="hidden" name="_nonce" value="<?php echo esc_attr( wp_create_nonce( 'elementor_recreate_kit' ) ); ?>">
                            <input type="hidden" name="action" value="elementor_recreate_kit">
                            <input class="checkbox" type="checkbox" name="pre_import" value="1" checked>
                        </form>

                        <div class="import-subheader">
                            <?php esc_html_e( 'Import Content', 'yomooh' ); ?>
                        </div>

                        <?php if ( isset( $demo_data['import']['content'] ) && is_array( $demo_data['import']['content'] ) && $demo_data['import']['content'] ) {
                            $kits = $demo_data['import']['content'];
                            ?>
                            <div class="import-kits">
                                <?php foreach ( $kits as $kit ) { ?>
                                    <form>
                                        <div class="switcher">
                                            <?php echo esc_html( $kit['label'] ); ?> <input class="checkbox" type="checkbox" name="url" value="<?php echo esc_attr( $kit['url'] ); ?>" checked>

                                            <?php if ( isset( $kit['desc'] ) && $kit['desc'] ) { ?>
                                                <div class="tooltip-help"><i class="dashicons dashicons-editor-help"></i></div>
                                                <div class="tooltip-desc"><?php echo esc_html( $kit['desc'] ); ?></div>
                                            <?php } ?>

                                            <div class="switch"><span class="switch-slider"></span></div>

                                            <input type="hidden" name="type" value="<?php echo esc_attr( isset( $kit['type'] ) ? $kit['type'] : 'default' ); ?>">
                                            <input type="hidden" name="step_name" value="<?php esc_attr_e( 'Importing', 'yomooh' ); ?> <?php echo esc_attr( $kit['label'] ); ?> ...">
                                            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'nonce' ) ); ?>">
                                            <input type="hidden" name="action" value="som_import_contents">
                                        </div>
                                    </form>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <?php if ( isset( $demo_data['import']['customizer'] ) && $demo_data['import']['customizer'] ) { ?>
                            <form>
                                <div class="switcher">
                                    <?php esc_html_e( 'Customizer', 'yomooh' ); ?> <input class="checkbox" type="checkbox" name="url" value="<?php echo esc_attr( $demo_data['import']['customizer'] ); ?>" checked>
                                    <div class="switch"><span class="switch-slider"></span></div>
                                </div>

                                <input type="hidden" name="step_name" value="<?php esc_attr_e( 'Importing customizer options...', 'yomooh' ); ?>">
                                <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'nonce' ) ); ?>">
                                <input type="hidden" name="action" value="som_import_customizer">
                            </form>
                        <?php } ?>

                        <?php if ( isset( $demo_data['import']['options'] ) && $demo_data['import']['options'] ) { ?>
                            <form>
                                <div class="switcher">
                                    <?php esc_html_e( 'Options', 'yomooh' ); ?> <input class="checkbox" type="checkbox" name="url" value="<?php echo esc_attr( $demo_data['import']['options'] ); ?>" checked>
                                    <div class="switch"><span class="switch-slider"></span></div>
                                </div>

                                <input type="hidden" name="step_name" value="<?php esc_attr_e( 'Importing options...', 'yomooh' ); ?>">
                                <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'nonce' ) ); ?>">
                                <input type="hidden" name="action" value="som_import_options">
                            </form>
                        <?php } ?>

                        <?php if ( isset( $demo_data['import']['widgets'] ) && $demo_data['import']['widgets'] ) { ?>
                            <form>
                                <div class="switcher">
                                    <?php esc_html_e( 'Widgets', 'yomooh' ); ?> <input class="checkbox" type="checkbox" name="url" value="<?php echo esc_attr( $demo_data['import']['widgets'] ); ?>" checked>
                                    <div class="switch"><span class="switch-slider"></span></div>
                                </div>

                                <input type="hidden" name="step_name" value="<?php esc_attr_e( 'Importing widgets...', 'yomooh' ); ?>">
                                <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'nonce' ) ); ?>">
                                <input type="hidden" name="action" value="som_import_widgets">
                            </form>
                        <?php } ?>

                        <form class="hidden">
                            <div class="switcher">
                                <?php esc_html_e( 'Finish', 'yomooh' ); ?> <input class="checkbox" type="checkbox" name="finish" value="1" checked>
                                <div class="switch"><span class="switch-slider"></span></div>
                            </div>

                            <input type="hidden" name="step_name" value="<?php esc_attr_e( 'Finishing setup...', 'yomooh' ); ?>">
                            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'nonce' ) ); ?>">
                            <input type="hidden" name="action" value="som_import_finish">
                        </form>
                    </div>
                </div>

                <div class="import-actions">
                    <div class="import-theme-cancel">
                        <a href="#" class="button demo-import-close button">
                            <?php esc_html_e( 'Cancel', 'yomooh' ); ?>
                        </a>
                    </div>

                    <div class="import-theme-start">
                        <a href="#" class="demo-import-start button button-primary">
                            <?php esc_html_e( 'Import', 'yomooh' ); ?>
                        </a>
                    </div>
                </div>
                <?php
                wp_send_json_success( ob_get_clean() );
            } else {
                wp_send_json_error( esc_html__( 'Demo content id not set.', 'yomooh' ) );
            }

            wp_die();
        }

		    public function render_demo_importer_page() {
                ?>
 				<?php
                yomooh_dashboard('demo');
                ?>
                <div class="yomooh-demo-importer-wrapper">
                    <div class="demos-page">
                        <div class="dashboard-tips">
                        <h2><?php esc_html_e('Import a Prebuilt Website', 'yomooh'); ?></h2>
            			<p>
            				<i class="dashicons dashicons-lightbulb"></i><?php esc_html_e( 'If the import process takes longer than 5 minutes, please refresh this page and try importing again!', 'yomooh' ); ?>
            			</p>
            			<p>
                        <i class="dashicons dashicons-info"></i>
                        <?php esc_html_e( 'If your imported Elementor pages appear broken or empty, it may be due to a new feature conflict. To fix this Clear Elementor Cache, Or go to Elementor → Settings → Features, and under "Flexbox Container" or "Nested Elements", set them to Inactive. Then save changes and re-edit the page in Elementor.', 'yomooh' ); ?>
                        </p>
                        <p><?php esc_html_e('Launch your website instantly with our ready-to-use demo — import in one click..', 'yomooh'); ?></p>
                    </div>
                        <h1 class="hidden"><?php esc_html_e('Theme Demos', 'yomooh'); ?></h1>
                         <!-- Active theme information -->
                        <div class="active-theme-info">
                            <?php
                            $current_theme = wp_get_theme();
                            $theme_screenshot = $current_theme->get_screenshot();
                            $theme_name = $current_theme->get('Name');
                            $theme_version = $current_theme->get('Version');
                            $theme_author = $current_theme->get('Author');
                            $theme_description = $current_theme->get('Description');
                            ?>
                            <div class="theme-screenshot">
                                <?php if ($theme_screenshot) : ?>
                                    <img src="<?php echo esc_url($theme_screenshot); ?>" alt="<?php echo esc_attr($theme_name); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="theme-details">
                                <h3><?php echo esc_html($theme_name); ?></h3>
                                <div class="theme-meta">
                                    <span class="theme-version"><?php printf(esc_html__('Version: %s', 'yomooh'), $theme_version); ?></span>
                                    <span class="theme-author"><?php printf(esc_html__('By %s', 'yomooh'), $theme_author); ?></span>
                                </div>
                                <div class="theme-description">
                                    <?php echo esc_html($theme_description); ?>
                                </div>
                             <?php if ($this->demos) : ?>
                                <?php foreach ($this->demos as $demo_id => $demo) : 
                                    $name = isset($demo['name']) ? $demo['name'] : '';
                                    $preview = isset($demo['preview']) ? $demo['preview'] : 'false';
                                    ?>
                                    <div class="demo-item demo-item-active"
                                        data-id="<?php echo esc_attr($demo_id); ?>"
                                        data-name="<?php echo esc_attr($name); ?>"
                                        data-preview="<?php echo esc_url($preview); ?>">
                                            <div class="demo-data">
                                                <div class="demo-info">
                                                    <?php if ($name) : ?>
                                                        <div class="demo-name"><?php echo esc_html($name); ?></div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="demo-action">
                                                    <?php if ($preview && $preview !== 'false') : ?>
                                                        <span class="preview-button">
                                                            <?php esc_html_e('Preview Demo', 'yomooh'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <a href="#" target="_blank" data-id="<?php echo esc_attr($demo_id); ?>" class="demo-import-open button button-primary">
                                                        <?php esc_html_e('Import', 'yomooh'); ?>
                                                    </a>
                                                </div> 
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                        <?php endif; ?>
                            </div>
                        </div>
                    <div class="import-theme">
                        <div class="import-overlay"></div>

                        <div class="import-popup">
                            <div class="import-container">
                                <div class="import-step import-step-active import-start">
                                    <div class="header-col header-info"></div>
                                    <div class="import-header">
                                    <h3><?php esc_html_e('Import Theme', 'yomooh'); ?></h3>
                                </div>
                                    <div class="import-output"></div>
                                </div>

                                <div class="import-step import-process">
                                    <div class="import-header">
                                        <?php esc_html_e('Installing', 'yomooh'); ?>
                                    </div>
                                    <div class="import-output">
                                        <div class="import-desc">
                                            <?php esc_html_e('Please be patient and don\'t refresh this page, the import process may take a while, this also depends on your server.', 'yomooh'); ?>
                                        </div>
                                        <div class="import-progress">
                                            <div class="import-progress-label"></div>
                                            <div class="import-progress-bar">
                                                <div class="import-progress-indicator"></div>
                                            </div>
                                            <div class="import-progress-sublabel">0%</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="import-step import-error">
                                    <div class="import-info">
                                        <div class="import-logo">
                                            <svg class="error-icon" width="96" height="96" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                                <g class="error-icon" stroke-width="1" stroke="#d63638" transform="translate(1, 1.2)">
                                                    <path id="error-outline-path" d="M14 28c7.732 0 14-6.268 14-14S21.732 0 14 0 0 6.268 0 14s6.268 14 14 14z"/>
                                                    <path id="error-path" d="M8 8L22 22 M22 8L8 22"/>
                                                </g>
                                            </svg>
                                        </div>
                                        <div class="import-title">
                                            <?php esc_html_e('Import Failed', 'yomooh'); ?>
                                        </div>
                                        <div class="import-desc error-message">
                                            <?php esc_html_e('Something went wrong during the import process. Please try again or contact support.', 'yomooh'); ?>
                                        </div>
                                    </div>
                                    <div class="import-actions">
                                        <a href="#" class="button demo-import-close button-primary">
                                            <?php esc_html_e('Cancel', 'yomooh'); ?>
                                        </a>
                                    </div>
                                </div>

                                <div class="import-step import-finish">
                                      <div class="demo-import-close import-cancel" title="Cancel import">&times;</div>

                                    <div class="import-info">
                                        <div class="import-logo">
                                            <svg class="progress-icon" width="96" height="96" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                                <g class="tick-icon" stroke-width="1" stroke="#3FB28F" transform="translate(1, 1.2)">
                                                    <path id="tick-outline-path" d="M14 28c7.732 0 14-6.268 14-14S21.732 0 14 0 0 6.268 0 14s6.268 14 14 14z"/>
                                                    <path id="tick-path" d="M6.173 16.252l5.722 4.228 9.22-12.69"/>
                                                </g>
                                            </svg>
                                        </div>
                                        <div class="import-title">
                                            <?php esc_html_e('Imported Successfully', 'yomooh'); ?>
                                        </div>
                                        <div class="import-desc">
                                            <?php esc_html_e('Go ahead, customize the design to make it yours!', 'yomooh'); ?>
                                        </div>
                                        <div class="import-customize">
                                            
                                        </div>
                                    </div>
                                    <div class="import-actions">
                                        <a href="<?php echo esc_url(home_url()); ?>" class="visit" target="_blank">
                                            <?php esc_html_e('View Site', 'yomooh'); ?>
                                        </a>
                                        <a href="<?php echo esc_url( add_query_arg( 'page', $this->settings_menu_slug, admin_url( 'admin.php' ) ) ); ?>" class="button button-primary" target="_blank">
                                                <?php esc_html_e( 'Customize theme', 'yomooh' ); ?>
                                            </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="preview">
                        <div class="header">
                            <div class="header-left">
                                <div class="header-col header-logo">
                                    <div class="logo">
                                        <a target="_blank" href="<?php echo esc_url('https://somnest.net/'); ?>">
                                            Somnest
                                        </a>
                                    </div>
                                </div>
                                <div class="header-col header-info"></div>
                            </div>
                            <div class="header-right">
                                <div class="preview-cancel">
                                    <a href="#" class="button">
                                        <?php esc_html_e('Cancel', 'yomooh'); ?>
                                    </a>
                                </div>
                                <div class="preview-actions"></div>
                                <!--share preview link -->
                                  <a href="https://yomooh.somnest.net/demo/" 
                                    target="_blank" 
                                    class="preview-share" 
                                    title="<?php esc_attr_e('Open in new tab', 'yomooh'); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18 13V19C18 19.5304 17.7893 20.0391 17.4142 20.4142C17.0391 20.7893 16.5304 21 16 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V8C3 7.46957 3.21071 6.96086 3.58579 6.58579C3.96086 6.21071 4.46957 6 5 6H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M15 3H21V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M10 14L21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                            </div>
                        </div>
                        <iframe id="preview-iframe" class="preview-iframe"></iframe>
                    </div>
                </div>
            </div>
            <?php
            }
            function demos_list() {
                // Recommended plugins for the demo
                $plugins = array(
                    array(
                        'name'     => 'Regenerate Thumbnails',
                        'slug'     => 'regenerate-thumbnails',
                        'path'     => 'regenerate-thumbnails/regenerate-thumbnails.php',
                        'required' => false,
                        'desc'     => esc_html__( 'Regenerate your image thumbnails after changing image sizes.', 'yomooh' ),
                    ),
                    array(
                        'name'     => 'Contact Form 7',
                        'slug'     => 'contact-form-7',
                        'path'     => 'contact-form-7/wp-contact-form-7.php',
                        'required' => false,
                        'desc'     => esc_html__( 'Flexible contact form plugin. Useful for demo forms setup.', 'yomooh' ),
                    ),
                );

                // Demo list
                $demos = array(
                    'main-default' => array(
                        'name'      => esc_html__( 'Main Default', 'yomooh' ),
                        'preview'   => 'https://yomooh.somnest.net/demo/',
                        'thumbnail' => 'https://yomooh.somnest.net/wp-content/uploads/2025/09/Cover-3-1.png',
                        'plugins'   => $plugins,
                        'import'    => array(
                            'customizer' => 'https://yomooh.somnest.net/files/customizer.dat',
                            'widgets'    => 'https://yomooh.somnest.net/files/widgets.wie',
                            'options'    => 'https://yomooh.somnest.net/files/options.json',
                            'content'    => array(
                                array(
                                    'label' => esc_html__( 'Demo Content', 'yomooh' ),
                                    'url'   => 'https://yomooh.somnest.net/files/content.xml',
                                    'desc'  => esc_html__( 'Imports demo pages, posts, categories, and menus. Disable if your site already has content.', 'yomooh' ),
                                ),
                            ),
                        ),
                    ),
                );

                return $demos;
            }

            function import_terms_images_for_categories() {

                $categories = get_terms( array(
                    'taxonomy'   => 'category',
                    'hide_empty' => false,
                ) );

                $fields = array(
                    'category_logo' => '_category_logo',
                    'category_icon' => '_category_icon',
                );

                foreach ( $categories as $category ) {

                    foreach ( $fields as $meta_key_id => $meta_key_url ) {

                        $meta_val = get_term_meta( $category->term_id, $meta_key_url, true );

                        if ( $meta_val && Manager_Import::is_image_url( $meta_val ) ) {
                            $data = Manager_Import::import_custom_image( $meta_val );

                            if ( ! is_wp_error( $data ) ) {
                                update_term_meta( $category->term_id, $meta_key_id, $data->attachment_id );
                                update_term_meta( $category->term_id, $meta_key_url, $data->url );
                            }
                        }
                    }
                }
            }
        function hook_finish_import() {
            $default_mapping = [
                'primary'    => 'main menu',           
                'footer'     => 'Footer',         
                'mobile'     => 'mobile menu', 
                'quick-link' => 'top navigation'  
            ];

            $custom_mapping = [
                'footer'   => 'Footer'
            ];

            $nav_menu_locations = [];
            
            foreach ($default_mapping as $location => $menu_name) {
                $menu = get_term_by('name', $menu_name, 'nav_menu') ?: 
                        get_term_by('slug', sanitize_title($menu_name), 'nav_menu');
                
                if ($menu) {
                    $nav_menu_locations[$location] = $menu->term_id;
                    error_log("Assigned DEFAULT menu '{$menu_name}' → '{$location}'");
                }
            }

            foreach ($custom_mapping as $menu_name => $location) {
                $menu = get_term_by('name', $menu_name, 'nav_menu') ?: 
                        get_term_by('slug', sanitize_title($menu_name), 'nav_menu');
                
                if ($menu) {
                    $nav_menu_locations[$location] = $menu->term_id;
                    error_log("Assigned CUSTOM menu '{$menu_name}' → '{$location}'");
                }
            }

            // Save final assignments
            if (!empty($nav_menu_locations)) {
                set_theme_mod('nav_menu_locations', $nav_menu_locations);
                error_log('Final Menu Assignments: ' . print_r($nav_menu_locations, true));
            }

            // Other import tasks
            if (!get_option('once_finished_import')) {
                $this->import_terms_images_for_categories();
            }
            update_option('once_finished_import', true);
            wp_cache_flush();
        }
		public function admin_enqueue_scripts( $page ) {
			 wp_enqueue_style(
                'theme-demo',
                get_theme_file_uri( '/import/assets/theme-demo.css' ),
                array(),
                filemtime( get_theme_file_path( '/import/assets/theme-demo.css' ) )
            );
			wp_enqueue_script(
                'theme-demo',
                get_theme_file_uri( '/import/assets/theme-demo.js' ),
                array( 'jquery' ),
                filemtime( get_theme_file_path( '/import/assets/theme-demo.js' ) ),
                true
            );

            wp_localize_script( 'theme-demo', 'ThemeDemoConfig', array(
                'ajax_url'       => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'nonce' ),
                'failed_message' => esc_html__( 'Something went wrong, contact support.', 'yomooh' ),
            ) );

		}
	}

	new Theme_Demo();
}
