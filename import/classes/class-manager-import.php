<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;
class Manager_Import {
	/**
	 * Singleton instance
	 *
	 * @var Manager_Import
	 */
	private static $instance;

	/**
	 * Sites Server API URL
	 *
	 * @var string
	 */
	public $api_url;

	/**
	 * Get singleton instance.
	 *
	 * @return Manager_Import
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @var float
	 */
	private $microtime;
	public function __construct() {
    add_action('after_setup_theme', array($this, 'init'));
    
    // Increase limits for import processes
    add_action('som_before_import', array($this, 'increase_limits'));
    add_action('som_after_import', array($this, 'cleanup_temp_files'));
}
/**
 * Increase memory and time limits for import processes
 */
private function increase_limits() {
    @ini_set('memory_limit', '512M');
    @ini_set('max_execution_time', '300');
    @set_time_limit(300);
}

/**
 * Clean up temporary files
 */
private function cleanup_temp_files() {
    $temp_files = glob(sys_get_temp_dir() . '/som_import_*');
    foreach ($temp_files as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

/**
 * Check server requirements before import
 */
private function check_server_requirements() {
    $errors = array();
    
    // Check memory limit
    $memory_limit = ini_get('memory_limit');
    $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
    
    if ($memory_limit_bytes < 134217728) { // 128MB
        $errors[] = sprintf(
            esc_html__('Insufficient memory limit. Current: %s, Recommended: 128MB or higher', 'yomooh'),
            $memory_limit
        );
    }
    
    // Check max execution time
    $max_execution_time = ini_get('max_execution_time');
    if ($max_execution_time < 120 && $max_execution_time > 0) {
        $errors[] = sprintf(
            esc_html__('Low max execution time. Current: %s seconds, Recommended: 120 seconds or higher', 'yomooh'),
            $max_execution_time
        );
    }
    
    return $errors;
}
	/**
	 * Initialize plugin.
	 */
	public function init() {
		add_action( 'upload_mimes', array( $this, 'add_custom_mimes' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'real_mime_type_for_xml' ), 10, 4 );
		add_filter( 'admin_init', array( $this, 'ajax_runtime_hide_errors' ), 0 );
		add_filter( 'query', array( $this, 'ajax_wpdb_hide_errors' ), 0 );
		add_action( 'wp_ajax_som_import_plugin', array( $this, 'ajax_import_plugin' ) );
		add_action( 'wp_ajax_som_import_contents', array( $this, 'ajax_import_contents' ) );
		add_action( 'wp_ajax_som_import_customizer', array( $this, 'ajax_import_customizer' ) );
		add_action( 'wp_ajax_som_import_widgets', array( $this, 'ajax_import_widgets' ) );
		add_action( 'wp_ajax_som_import_options', array( $this, 'ajax_import_options' ) );
		add_action( 'wp_ajax_som_import_finish', array( $this, 'ajax_import_finish' ) );
	}

	/**
	 * Pre Plugin Setup
	 */
	public function pre_plugin_setup() {
		/* Woocommerce */
		add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_false' );
		add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );

		/* Elementor */
		set_transient( 'elementor_activation_redirect', false );
	}

	public static function import_custom_image( $url, $retina = true ) {

		$data = self::sideload_image( $url );

		if ( $retina ) {
			// Upload @2x image.
			self::sideload_image(
				str_replace( array( '.jpg', '.jpeg', '.png', '.gif', '.webp' ),
					array( '@2x.jpg', '@2x.jpeg', '@2x.png', '@2x.gif', '@2x.webp' ),
					$url
				)
			);
		}

		if ( ! is_wp_error( $data ) ) {
			return $data;
		}
	}

	public static function sideload_image( $file ) {
		$data = new stdClass();

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			call_user_func( 'require_once', ABSPATH . 'wp-admin/includes/media.php' );
			call_user_func( 'require_once', ABSPATH . 'wp-admin/includes/file.php' );
			call_user_func( 'require_once', ABSPATH . 'wp-admin/includes/image.php' );
		}

		if ( ! empty( $file ) ) {
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png|webp)\b/i', $file, $matches );

			$file_array = array();

			$file_array['name'] = basename( $matches[0] );

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			$id = media_handle_sideload( $file_array, 0 );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				unlink( $file_array['tmp_name'] );
				return $id;
			}

			// Build the object to return.
			$meta                = wp_get_attachment_metadata( $id );
			$data->attachment_id = $id;
			$data->url           = wp_get_attachment_url( $id );
			$data->thumbnail_url = wp_get_attachment_thumb_url( $id );
			$data->height        = $meta['height'];
			$data->width         = $meta['width'];
		}

		return $data;
	}

	public static function is_image_url( $string = '' ) {
		if ( is_string( $string ) ) {
			if ( preg_match( '/\.(jpg|jpeg|png|gif|webp)/i', $string ) ) {
				return true;
			}
		}

		return false;
	}

	public function add_custom_mimes( $mimes ) {
		// Allow XML files.
		$mimes['xml'] = 'text/xml';

		// Allow JSON files.
		$mimes['json'] = 'application/json';

		return $mimes;
	}

	/**
	 * Filters the "real" file type of the given file.
	 *
	 * @param array  $wp_check_filetype_and_ext The wp_check_filetype_and_ext.
	 * @param string $file                      The file.
	 * @param string $filename                  The filename.
	 * @param array  $mimes                     The mimes.
	 */
	public function real_mime_type_for_xml( $wp_check_filetype_and_ext, $file, $filename, $mimes ) {
		if ( '.xml' === substr( $filename, -4 ) ) {
			$wp_check_filetype_and_ext['ext']  = 'xml';
			$wp_check_filetype_and_ext['type'] = 'text/xml';
		}

		return $wp_check_filetype_and_ext;
	}

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

	public function install_plugin( $plugin_slug ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( false === filter_var( $plugin_slug, FILTER_VALIDATE_URL ) ) {
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $plugin_slug,
					'fields' => array(
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					),
				)
			);

			$download_link = $api->download_link;
		} else {
			$download_link = $plugin_slug;
		}

		// ref: function wp_ajax_install_plugin().
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

		$this->pre_plugin_setup();

		$install = $upgrader->install( $download_link );

		if ( false === $install ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Activate a plugin.
	 *
	 * @param string $plugin_path Plugin path.
	 */
	public function activate_plugin( $plugin_path ) {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		$this->pre_plugin_setup();

		$activate = activate_plugin( $plugin_path, '', false, true );

		if ( is_wp_error( $activate ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Detect ajax import.
	 */
	public function is_ajax_import() {
		if ( __return_false() ) {
			check_ajax_referer();
		}

		$current_action = __return_empty_string();

		if ( isset( $_REQUEST['action'] ) ) {
			$current_action = sanitize_text_field( $_REQUEST['action'] );
		}

		if ( preg_match( '/som_import/', $current_action ) ) {
			return $current_action;
		}
	}

	/**
	 * Hide errors for wpdb
	 *
	 * @param object $query The query.
	 */
	public function ajax_wpdb_hide_errors( $query ) {
		global $wpdb;

		if ( $this->is_ajax_import() ) {
			$wpdb->hide_errors();
		}

		return $query;
	}

	/**
	 * Hide errors for runtime
	 */
	public function ajax_runtime_hide_errors() {
		call_user_func( 'ini_set', 'display_errors', 'Off' );
	}

	/**
	 * Ajax import start
	 */
	public function ajax_import_start() {
		ob_start();
	}

	/**
	 * Ajax import end
	 */
	public function ajax_import_end() {
		ob_end_flush();
	}

	/**
	 * Sends a JSON response back to an Ajax request, indicating failure.
	 *
	 * @param mixed $data Data to encode as JSON, then print and die.
	 */
	public function send_json_error( $data = null ) {
		$log = trim( ob_get_clean() );

		if ( $log ) {
			$data .= sprintf( '%s', PHP_EOL . $log );
		}

		wp_send_json_error( $data );
	}

	/**
	 * Sends a JSON response back to an Ajax request, indicating success.
	 *
	 * @param mixed $data Data to encode as JSON, then print and die.
	 */
	public function send_json_success( $data = null ) {
		$log = trim( ob_get_clean() );

		if ( $log ) {
			$data .= sprintf( '%s', PHP_EOL . $log );
		}

		wp_send_json_success( $data );
	}

	/**
	 * AJAX callback to install and activate a plugin.
	 */
	public function ajax_import_plugin() {

		set_time_limit( 0 );

		$this->ajax_import_start();

		check_ajax_referer( 'nonce', 'nonce' );

		if ( ! isset( $_POST['plugin_slug'] ) || ! sanitize_text_field( $_POST['plugin_slug'] ) ) {
			$this->send_json_error( esc_html__( 'Unknown slug in a plugin.', 'yomooh' ) );
		}

		if ( ! isset( $_POST['plugin_path'] ) || ! sanitize_text_field( $_POST['plugin_path'] ) ) {
			$this->send_json_error( esc_html__( 'Unknown path in a plugin.', 'yomooh' ) );
		}

		$plugin_slug = sanitize_text_field( $_POST['plugin_slug'] );
		$plugin_path = sanitize_text_field( $_POST['plugin_path'] );

		if ( ! current_user_can( 'install_plugins' ) ) {
			$this->send_json_error( esc_html__( 'Insufficient permissions to install the plugin.', 'yomooh' ) );
		}

		if ( 'not_installed' === $this->get_plugin_status( $plugin_path ) ) {

			$this->install_plugin( $plugin_slug );

			$this->activate_plugin( $plugin_path );

		} elseif ( 'inactive' === $this->get_plugin_status( $plugin_path ) ) {

			$this->activate_plugin( $plugin_path );
		}

		if ( 'active' === $this->get_plugin_status( $plugin_path ) ) {
			$this->send_json_success();
		}

		do_action( 'som_import_plugin', $plugin_slug, $plugin_path );

		$this->send_json_error( esc_html__( 'Failed to initialize or activate importer plugin.', 'yomooh' ) );

		$this->ajax_import_end();
	}

	/**
	 * AJAX callback to import contents and media files from contents.xml.
	 */
	public function ajax_import_contents() {
    $this->ajax_import_start();
    
    // Setup debug log
    $debug_log = array();
    $debug_log[] = '=== Import Process Started ===';
    $debug_log[] = 'Memory usage: ' . memory_get_usage() . ' bytes';
    $debug_log[] = 'Current user: ' . get_current_user_id();

    check_ajax_referer('nonce', 'nonce');
    $debug_log[] = 'Nonce verification passed';

    $import_type = 'default';
    if (!empty($_POST['type'])) {
        $import_type = sanitize_text_field($_POST['type']);
    }
    $debug_log[] = 'Import type set: ' . $import_type;

    // Validate and sanitize URL
    if (empty($_POST['url'])) {
        $error = esc_html__('The URL address of the demo content is not specified.', 'yomooh');
        $debug_log[] = 'ERROR: ' . $error;
        $this->log_debug_info($debug_log);
        $this->send_json_error($error);
    }

    $file_url = sanitize_text_field($_POST['url']);
    $debug_log[] = 'File URL to import: ' . $file_url;

    // Check cache
    $xml_file_hash_id = 'som_importer_data_' . md5($file_url);
    $xml_file_path = get_transient($xml_file_hash_id);

    if (!$xml_file_path || !file_exists($xml_file_path)) {
        $debug_log[] = 'No cached XML file found or file missing, downloading...';

        if (!current_user_can('edit_theme_options')) {
            $error = esc_html__('You are not permitted to import contents.', 'yomooh');
            $debug_log[] = 'PERMISSION ERROR: ' . $error;
            $this->log_debug_info($debug_log);
            $this->send_json_error($error);
        }

        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $debug_log[] = 'Loaded download_url function';
        }

        // Validate XML file before processing
        $url = wp_unslash($file_url);
        $timeout = 30; // Increased timeout for larger files
        $temp_file = download_url($url, $timeout);
        
        if (is_wp_error($temp_file)) {
            $error = $temp_file->get_error_message();
            $debug_log[] = 'DOWNLOAD ERROR: ' . $error;
            $this->log_debug_info($debug_log);
            $this->send_json_error($error);
        }

        // Validate XML structure
        $xml_content = file_get_contents($temp_file);
        
        // Check for multiple XML declarations
        $xml_declaration_count = substr_count($xml_content, '<?xml version=');
        if ($xml_declaration_count > 1) {
            $debug_log[] = 'WARNING: Multiple XML declarations found: ' . $xml_declaration_count;
            
            // Clean the XML by removing extra declarations
            $first_pos = strpos($xml_content, '<?xml');
            if ($first_pos !== false) {
                $next_pos = strpos($xml_content, '<?xml', $first_pos + 1);
                if ($next_pos !== false) {
                    // Remove all XML declarations except the first one
                    $clean_xml = substr($xml_content, 0, $first_pos + 5) . 
                                str_replace('<?xml', '', substr($xml_content, $first_pos + 5));
                    file_put_contents($temp_file, $clean_xml);
                    $debug_log[] = 'Cleaned multiple XML declarations';
                }
            }
        }

        $file_args = array(
            'name'     => basename($url),
            'tmp_name' => $temp_file,
            'error'    => 0,
            'size'     => filesize($temp_file),
        );

        $overrides = array(
            'test_form'   => false,
            'test_size'   => true,
            'test_upload' => true,
            'mimes'       => array('xml' => 'text/xml'),
        );

        $download_response = wp_handle_sideload($file_args, $overrides);

        if (isset($download_response['error'])) {
            $error = $download_response['error'];
            $debug_log[] = 'SIDELOAD ERROR: ' . $error;
            $this->log_debug_info($debug_log);
            $this->send_json_error($error);
        }

        $xml_file_path = $download_response['file'];
        set_transient($xml_file_hash_id, $xml_file_path, HOUR_IN_SECONDS);
        $debug_log[] = 'File saved to: ' . $xml_file_path;
    } else {
        $debug_log[] = 'Using cached XML file: ' . $xml_file_path;
    }

    // Validate XML file
    if (!$this->validate_xml_file($xml_file_path)) {
        $error = esc_html__('Invalid XML file structure.', 'yomooh');
        $debug_log[] = 'XML VALIDATION ERROR: ' . $error;
        $this->log_debug_info($debug_log);
        $this->send_json_error($error);
    }

    // Load Importer
    if (!class_exists('WP_Importer')) {
        if (!defined('WP_LOAD_IMPORTERS')) {
            define('WP_LOAD_IMPORTERS', true);
        }
        require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
        $debug_log[] = 'Loaded WP_Importer';
    }
    
    // Load importer classes
    $importer_files = [
        '/import/classes/importer-v2/WPImporterLogger.php',
        '/import/classes/importer-v2/WPImporterLoggerCLI.php',
        '/import/classes/importer-v2/WXRImportInfo.php',
        '/import/classes/importer-v2/WXRImporter.php',
        '/import/classes/importer-v2/Logger.php'
    ];
    
    foreach ($importer_files as $file) {
        $file_path = get_theme_file_path($file);
        if (file_exists($file_path)) {
            require_once $file_path;
            $debug_log[] = 'Loaded: ' . $file;
        } else {
            $error = sprintf(esc_html__('Importer file not found: %s', 'yomooh'), $file);
            $debug_log[] = 'FILE ERROR: ' . $error;
            $this->log_debug_info($debug_log);
            $this->send_json_error($error);
        }
    }

    set_time_limit(0);
    $this->microtime = microtime(true);
    $debug_log[] = 'Import started at: ' . $this->microtime;

    add_filter('wxr_importer.pre_process.user', '__return_null');
    add_filter('wxr_importer.pre_process.post', array($this, 'ajax_request_maybe'));

    $importer = new WXRImporter(array(
        'fetch_attachments' => true,
        'default_author'    => get_current_user_id(),
    ));
    
    $logger_options = apply_filters('som_logger_options', array(
        'logger_min_level' => 'warning',
    ));
    
    $logger = new Logger();
    $logger->min_level = $logger_options['logger_min_level'];
    $importer->set_logger($logger);
    $debug_log[] = 'Logger min level: ' . $logger->min_level;
    
    try {
        $importer->import($xml_file_path);
        
        if ($logger->error_output) {
            $debug_log[] = 'Logger errors: ' . $logger->error_output;
            $this->log_debug_info($debug_log);
            $this->send_json_error($logger->error_output);
        }
        
        do_action('som_import_contents');
        $debug_log[] = 'Import completed successfully';
        $this->log_debug_info($debug_log);
        
        $this->send_json_success(esc_html__('Content imported successfully!', 'yomooh'));
        
    } catch (Exception $e) {
        $error = sprintf(esc_html__('Import failed: %s', 'yomooh'), $e->getMessage());
        $debug_log[] = 'EXCEPTION: ' . $error;
        $this->log_debug_info($debug_log);
        $this->send_json_error($error);
    }
    
    $this->ajax_import_end();
}

// Add this helper method to validate XML files
private function validate_xml_file($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Simple XML validation
    $xml_content = file_get_contents($file_path);
    
    // Check if it's a valid XML file
    if (strpos($xml_content, '<?xml') === false) {
        return false;
    }
    
    // Try to parse the XML to check for well-formedness
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($file_path);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    
    return empty($errors);
}

/**
 * Helper function to log debug information
 */
private function log_debug_info($debug_log) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(print_r($debug_log, true));
        
        // Optionally write to a dedicated debug file
        $log_file = WP_CONTENT_DIR . '/debug-import.log';
        file_put_contents(
            $log_file,
            date('Y-m-d H:i:s') . " - Import Debug Log:\n" . implode("\n", $debug_log) . "\n\n",
            FILE_APPEND
        );
    }
}

	public function ajax_request_maybe( $data ) {

		$time = microtime( true ) - $this->microtime;

		if ( $time > apply_filters( 'som_time_for_one_ajax_call', 22 ) ) {
			$response = array(
				'success' => true,
				'status'  => 'newAJAX',
				'message' => 'Time for new AJAX request!: ' . $time,
			);

			// Send the request for a new AJAX call.
			wp_send_json( $response );
		}

		$current_user_obj = wp_get_current_user();

		$data['post_author'] = $current_user_obj->user_login;

		return $data;
	}

	public function ajax_import_customizer() {
    set_time_limit(0);
    $this->ajax_import_start();

    check_ajax_referer('nonce', 'nonce');

    if (!isset($_POST['url']) || !sanitize_text_field($_POST['url'])) {
        $this->send_json_error(esc_html__('The url address of the demo content is not specified.', 'yomooh'));
    }

    $file_url = sanitize_text_field($_POST['url']);

    if (!current_user_can('edit_theme_options')) {
        $this->send_json_error(esc_html__('You are not permitted to import customizer.', 'yomooh'));
    }

    if (!isset($file_url)) {
        $this->send_json_error(esc_html__('No customizer JSON file specified.', 'yomooh'));
    }

    $raw = wp_remote_get(wp_unslash($file_url), array(
        'sslverify' => false,
    ));

    // Abort if customizer.json response code is not successful
    if (200 !== wp_remote_retrieve_response_code($raw)) {
        $this->send_json_error();
    }

    // Decode raw JSON string to associative array
    $data = json_decode(wp_remote_retrieve_body($raw), true);

    $customizer = new Customizer_Importer();

    // Import
    $results = $customizer->import($data);

    if (is_wp_error($results)) {
        $error_message = $results->get_error_message();
        $this->send_json_error($error_message);
    }

    do_action('som_import_customizer', $data);

    $this->send_json_success();
    $this->ajax_import_end();
}


	public function ajax_import_widgets() {

		set_time_limit( 0 );

		$this->ajax_import_start();

		check_ajax_referer( 'nonce', 'nonce' );

		if ( ! isset( $_POST['url'] ) || ! sanitize_text_field( $_POST['url'] ) ) {
			$this->send_json_error( esc_html__( 'The url address of the demo content is not specified.', 'yomooh' ) );
		}

		$file_url = sanitize_text_field( $_POST['url'] );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			$this->send_json_error( esc_html__( 'You are not permitted to import widgets.', 'yomooh' ) );
		}

		if ( ! isset( $file_url ) ) {
			$this->send_json_error( esc_html__( 'No widgets WIE file specified.', 'yomooh' ) );
		}

		$raw = wp_remote_get( wp_unslash( $file_url ), array(
			'sslverify' => false,
		) );

		// Abort if customizer.json response code is not successful.
		if ( 200 !== (int) wp_remote_retrieve_response_code( $raw ) ) {
			$this->send_json_error();
		}

		// Decode raw JSON string to associative array.
		$data = json_decode( wp_remote_retrieve_body( $raw ) );

		$widgets = new Widget_Importer();

		// Import.
		$results = $widgets->import( $data );

		if ( is_wp_error( $results ) ) {
			$error_message = $results->get_error_message();

			$this->send_json_error( $error_message );
		}

		do_action( 'som_import_widgets' );

		$this->send_json_success();

		$this->ajax_import_end();
	}

	public function ajax_import_options() {
    set_time_limit(0);
    $this->ajax_import_start();

    // Verify nonce and permissions
    check_ajax_referer('nonce', 'nonce');
    if (!current_user_can('import')) {
        $this->send_json_error(esc_html__('You do not have permission to import options.', 'yomooh'));
    }

    // Validate URL
    if (empty($_POST['url'])) {
        $this->send_json_error(esc_html__('The URL address of the demo content is not specified.', 'yomooh'));
    }

    $file_url = esc_url_raw(wp_unslash($_POST['url']));
    
    // Validate URL format
    if (!filter_var($file_url, FILTER_VALIDATE_URL)) {
        $this->send_json_error(esc_html__('Invalid URL format for options file.', 'yomooh'));
    }

    // Fetch the file with timeout and SSL verification
    $response = wp_remote_get($file_url, [
        'timeout' => 30,
        'sslverify' => false,
        'headers' => [
            'Accept' => 'application/json'
        ]
    ]);

    // Check for HTTP errors
    if (is_wp_error($response)) {
        $this->send_json_error(sprintf(
            esc_html__('Failed to fetch options file: %s', 'yomooh'), 
            $response->get_error_message()
        ));
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if (200 !== $response_code) {
        $this->send_json_error(sprintf(
            esc_html__('Server responded with status code: %d', 'yomooh'), 
            $response_code
        ));
    }

    // Get and validate response body
    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        $this->send_json_error(esc_html__('Options file is empty.', 'yomooh'));
    }

    // Decode JSON with error handling
    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $this->send_json_error(sprintf(
            esc_html__('Invalid JSON format: %s', 'yomooh'), 
            json_last_error_msg()
        ));
    }

    // Clean and validate data structure
    if (!is_array($data) || empty($data)) {
        $this->send_json_error(esc_html__('Options data is not in expected format.', 'yomooh'));
    }

    // Remove backup key if exists
    unset($data['redux-backup']);

    try {
        // Save to options table
        update_option('yomooh_options', $data);

        // Update Redux if available
        if (class_exists('Redux')) {
    $redux = Redux::instance('yomooh_options');
    if (isset($redux->options_class)) {
        $redux->options_class->set($data);
    }
}

        // Allow other plugins/themes to process the data
        do_action('som_import_options', $data);

        $this->send_json_success([
            'message' => esc_html__('Options imported successfully!', 'yomooh'),
            'count' => count($data)
        ]);

    } catch (Exception $e) {
        $this->send_json_error(sprintf(
            esc_html__('Error saving options: %s', 'yomooh'), 
            $e->getMessage()
        ));
    } finally {
        $this->ajax_import_end();
    }
}


	public function ajax_import_finish() {

		set_time_limit( 0 );

		$this->ajax_import_start();

		do_action( 'som_finish_import' );

		$this->send_json_success();

		$this->ajax_import_end();
	}
}

new Manager_Import();
