<?php
class WXRImporter extends WP_Importer {
	const MAX_WXR_VERSION = 1.2;

	const REGEX_HAS_ATTACHMENT_REFS = '!
		(
			# Match anything with an image or attachment class
			class=[\'"].*?\b(wp-image-\d+|attachment-[\w\-]+)\b
		|
			# Match anything that looks like an upload URL
			src=[\'"][^\'"]*(
				[0-9]{4}/[0-9]{2}/[^\'"]+\.(jpg|jpeg|png|gif)
			|
				content/uploads[^\'"]+
			)[\'"]
		)!ix';

	protected $version = '1.0';

	protected $categories = array();
	protected $tags       = array();
	protected $base_url   = '';
	protected $processed_terms      = array();
	protected $processed_posts      = array();
	protected $processed_menu_items = array();
	protected $menu_item_orphans    = array();
	protected $missing_menu_items   = array();
	public $options               = array();
	protected $mapping            = array();
	protected $requires_remapping = array();
	protected $exists             = array();
	protected $user_slug_override = array();

	protected $url_remap       = array();
	protected $featured_images = array();

	/**
	 *
	 * @var WPImporterLogger
	 */
	protected $logger;

	public function __construct( $options = array() ) {
		$empty_types = array(
			'post'    => array(),
			'comment' => array(),
			'term'    => array(),
			'user'    => array(),
		);

		$this->mapping              = $empty_types;
		$this->mapping['user_slug'] = array();
		$this->mapping['term_id']   = array();
		$this->requires_remapping   = $empty_types;
		$this->exists               = $empty_types;

		$this->options = wp_parse_args(
			$options,
			array(
				'prefill_existing_posts'    => true,
				'prefill_existing_comments' => true,
				'prefill_existing_terms'    => true,
				'update_attachment_guids'   => false,
				'fetch_attachments'         => false,
				'aggressive_url_search'     => false,
				'default_author'            => null,
			)
		);
	}

	public function set_logger( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @param string $file Path to the XML file.
	 * @return XMLReader|WP_Error Reader instance on success, error otherwise.
	 */
	protected function get_reader( $file ) {
		$reader = new XMLReader();
		$status = $reader->open( $file );

		if ( ! $status ) {
			return new WP_Error( 'wxr_importer.cannot_parse', __( 'Could not open the file for parsing', 'yomooh' ) );
		}

		return $reader;
	}

	/**
	 * @param string $file Path to the WXR file for importing
	 *
	 * @return WXRImportInfo|WP_Error
	 */
	public function get_preliminary_information( $file ) {
		$reader = $this->get_reader( $file );
		if ( is_wp_error( $reader ) ) {
			return $reader;
		}

		$this->version = '1.0';

		$data = new WXRImportInfo();
		while ( $reader->read() ) {
			if ( XMLReader::ELEMENT !== $reader->nodeType ) {
				continue;
			}

			switch ( $reader->name ) {
				case 'wp:wxr_version':
					$this->version = $reader->readString();

					if ( version_compare( $this->version, self::MAX_WXR_VERSION, '>' ) ) {
						$this->logger->warning( sprintf( __( 'This WXR file (version %1$s) is newer than the importer (version %2$s) and may not be supported. Please consider updating.', 'yomooh' ), $this->version, self::MAX_WXR_VERSION ) );
					}

					$reader->next();
					break;

				case 'generator':
					$data->generator = $reader->readString();
					$reader->next();
					break;

				case 'title':
					$data->title = $reader->readString();
					$reader->next();
					break;

				case sprintf( 'wp:base_site_%s', 'url' ):
					$data->siteurl = $reader->readString();
					$reader->next();
					break;

				case 'wp:base_blog_url':
					$data->home = $reader->readString();
					$reader->next();
					break;

				case 'wp:author':
					$node = $reader->expand();

					$parsed = $this->parse_author_node( $node );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post.
						$reader->next();
						break;
					}

					$data->users[] = $parsed;

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				case 'item':
					$node   = $reader->expand();
					$parsed = $this->parse_post_node( $node );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post
						$reader->next();
						break;
					}

					if ( 'attachment' === $parsed['data']['post_type'] ) {
						$data->media_count++;
					} else {
						$data->post_count++;
					}
					$data->comment_count += count( $parsed['comments'] );

					$reader->next();
					break;

				case 'wp:category':
				case 'wp:tag':
				case 'wp:term':
					$data->term_count++;

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;
			}
		}

		$data->version = $this->version;

		return $data;
	}

	/**
	 * @param string $file Path to the WXR file for importing
	 *
	 * @return array|WP_Error
	 */
	public function parse_authors( $file ) {
		$reader = $this->get_reader( $file );
		if ( is_wp_error( $reader ) ) {
			return $reader;
		}

		$this->version = '1.0';

		// Start parsing!
		$authors = array();
		while ( $reader->read() ) {
			// Only deal with element opens.
			if ( XMLReader::ELEMENT !== $reader->nodeType ) {
				continue;
			}

			switch ( $reader->name ) {
				case 'wp:wxr_version':
					// Upgrade to the correct version.
					$this->version = $reader->readString();

					if ( version_compare( $this->version, self::MAX_WXR_VERSION, '>' ) ) {
						/* translators: 1:version, 2:MAX_WXR_VERSION */
						$this->logger->warning( sprintf( __( 'This WXR file (version %1$s) is newer than the importer (version %2$s) and may not be supported. Please consider updating.', 'yomooh' ), $this->version, self::MAX_WXR_VERSION ) );
					}

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				case 'wp:author':
					$node = $reader->expand();

					$parsed = $this->parse_author_node( $node );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post.
						$reader->next();
						break;
					}

					$authors[] = $parsed;

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;
			}
		}

		return $authors;
	}

	public function import( $file ) {
		add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
		add_filter( 'http_request_timeout', array( &$this, 'bump_request_timeout' ) );
		if ( method_exists( 'Elementor\Compatibility', 'on_wxr_importer_pre_process_post_meta' ) ) {
			remove_action( 'wxr_importer.pre_process.post_meta', array( 'Elementor\Compatibility', 'on_wxr_importer_pre_process_post_meta' ) );
		}

		$result = $this->import_start( $file );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Let's run the actual importer now, woot.
		$reader = $this->get_reader( $file );
		if ( is_wp_error( $reader ) ) {
			return $reader;
		}

		$this->version = '1.0';
		$this->base_url = '';

		// Start parsing!
		while ( $reader->read() ) {
			// Only deal with element opens.
			if ( XMLReader::ELEMENT !== $reader->nodeType ) {
				continue;
			}

			switch ( $reader->name ) {
				case 'wp:wxr_version':
					// Upgrade to the correct version.
					$this->version = $reader->readString();

					if ( version_compare( $this->version, self::MAX_WXR_VERSION, '>' ) ) {
						/* translators: 1:version, 2:MAX_WXR_VERSION */
						$this->logger->warning( sprintf( __( 'This WXR file (version %1$s) is newer than the importer (version %2$s) and may not be supported. Please consider updating.', 'yomooh' ), $this->version, self::MAX_WXR_VERSION ) );
					}

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				case sprintf( 'wp:base_site_%s', 'url' ):
					$this->base_url = $reader->readString();

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				case 'item':
					$node   = $reader->expand();
					$parsed = $this->parse_post_node( $node );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post.
						$reader->next();
						break;
					}

					$this->process_post( $parsed['data'], $parsed['meta'], $parsed['comments'], $parsed['terms'] );

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				case 'wp:author':
					$node = $reader->expand();

					$parsed = $this->parse_author_node( $node );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post.
						$reader->next();
						break;
					}

					$status = $this->process_author( $parsed['data'], $parsed['meta'] );
					if ( is_wp_error( $status ) ) {
						$this->log_error( $status );
					}

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				case 'wp:category':
					$node = $reader->expand();

					$parsed = $this->parse_term_node( $node, 'category' );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post.
						$reader->next();
						break;
					}

					$status = $this->process_term( $parsed['data'], $parsed['meta'] );

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				case 'wp:tag':
					$node = $reader->expand();

					$parsed = $this->parse_term_node( $node, 'tag' );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post.
						$reader->next();
						break;
					}

					$status = $this->process_term( $parsed['data'], $parsed['meta'] );

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				case 'wp:term':
					$node = $reader->expand();

					$parsed = $this->parse_term_node( $node );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post.
						$reader->next();
						break;
					}

					$status = $this->process_term( $parsed['data'], $parsed['meta'] );

					// Handled everything in this node, move on to the next.
					$reader->next();
					break;

				default:
					break;
			}
		}

		$this->post_process();

		if ( $this->options['aggressive_url_search'] ) {
			$this->replace_attachment_urls_in_content();
		}

		$this->remap_featured_images();

		$this->import_end();
	}

	/**
	 *
	 * @param WP_Error $error Error instance to log.
	 */
	protected function log_error( WP_Error $error ) {
		$this->logger->warning( $error->get_error_message() );

		// Log the data as debug info too
		$data = $error->get_error_data();
		if ( ! empty( $data ) ) {
			$this->logger->debug( var_export( $data, true ) );
		}
	}

	protected function import_start( $file ) {
		if ( ! is_file( $file ) ) {
			return new WP_Error( 'wxr_importer.file_missing', __( 'The file does not exist, please try again.', 'yomooh' ) );
		}

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		wp_suspend_cache_invalidation( true );

		// Prefill exists calls if told to.
		if ( $this->options['prefill_existing_posts'] ) {
			$this->prefill_existing_posts();
		}
		if ( $this->options['prefill_existing_comments'] ) {
			$this->prefill_existing_comments();
		}
		if ( $this->options['prefill_existing_terms'] ) {
			$this->prefill_existing_terms();
		}

		do_action( 'import_start' );
	}

	/**
	 * Performs post-import cleanup of files and the cache
	 */
	protected function import_end() {
		// Re-enable stuff in core.
		wp_suspend_cache_invalidation( false );
		wp_cache_flush();

		foreach ( get_taxonomies() as $tax ) {
			delete_option( "{$tax}_children" );
			_get_term_hierarchy( $tax );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		flush_rewrite_rules();

		do_action( 'import_end' );
	}

	public function set_user_mapping( $mapping ) {
		foreach ( $mapping as $map ) {
			if ( empty( $map['old_slug'] ) || empty( $map['old_id'] ) || empty( $map['new_id'] ) ) {
				$this->logger->warning( __( 'Invalid author mapping', 'yomooh' ) );
				$this->logger->debug( var_export( $map, true ) );
				continue;
			}

			$old_slug = $map['old_slug'];
			$old_id   = $map['old_id'];
			$new_id   = $map['new_id'];

			$this->mapping['user'][ $old_id ]        = $new_id;
			$this->mapping['user_slug'][ $old_slug ] = $new_id;
		}
	}

	public function set_user_slug_overrides( $overrides ) {
		foreach ( $overrides as $original => $renamed ) {
			$this->user_slug_override[ $original ] = $renamed;
		}
	}

	/**
	 *
	 * @param DOMNode $node Parent node of post data (typically `item`).
	 * @return array|WP_Error Post data array on success, error otherwise.
	 */
	protected function parse_post_node( $node ) {
		$data     = array();
		$meta     = array();
		$comments = array();
		$terms    = array();

		foreach ( $node->childNodes as $child ) {
			// We only care about child elements.
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			switch ( $child->tagName ) {
				case 'wp:post_type':
					$data['post_type'] = $child->textContent;
					break;
				case 'title':
					$data['post_title'] = $child->textContent;
					break;
				case 'guid':
					$data['guid'] = $child->textContent;
					break;
				case 'dc:creator':
					$data['post_author'] = $child->textContent;
					break;
				case 'content:encoded':
					$data['post_content'] = $child->textContent;
					break;
				case 'excerpt:encoded':
					$data['post_excerpt'] = $child->textContent;
					break;
				case 'wp:post_id':
					$data['post_id'] = $child->textContent;
					break;
				case 'wp:post_date':
					$data['post_date'] = $child->textContent;
					break;
				case 'wp:post_date_gmt':
					$data['post_date_gmt'] = $child->textContent;
					break;
				case 'wp:comment_status':
					$data['comment_status'] = $child->textContent;
					break;
				case 'wp:ping_status':
					$data['ping_status'] = $child->textContent;
					break;
				case 'wp:post_name':
					$data['post_name'] = $child->textContent;
					break;
				case 'wp:status':
					$data['post_status'] = $child->textContent;
					if ( 'auto-draft' === $data['post_status'] ) {
						// Bail now
						return new WP_Error(
							'wxr_importer.post.cannot_import_draft',
							esc_html__( 'Cannot import auto-draft posts', 'yomooh' ),
							$data
						);
					}
					break;
				case 'wp:post_parent':
					$data['post_parent'] = $child->textContent;
					break;
				case 'wp:menu_order':
					$data['menu_order'] = $child->textContent;
					break;
				case 'wp:post_password':
					$data['post_password'] = $child->textContent;
					break;
				case 'wp:is_sticky':
					$data['is_sticky'] = $child->textContent;
					break;
				case 'wp:attachment_url':
					$data['attachment_url'] = $child->textContent;
					break;
				case 'wp:postmeta':
					$meta_item = $this->parse_meta_node( $child );
					if ( ! empty( $meta_item ) ) {
						$meta[] = $meta_item;
					}
					break;
				case 'wp:comment':
					$comment_item = $this->parse_comment_node( $child );
					if ( ! empty( $comment_item ) ) {
						$comments[] = $comment_item;
					}
					break;
				case 'category':
					$term_item = $this->parse_category_node( $child );
					if ( ! empty( $term_item ) ) {
						$terms[] = $term_item;
					}
					break;
			}
		}

		return compact( 'data', 'meta', 'comments', 'terms' );
	}

	protected function process_post( $data, $meta, $comments, $terms ) {
		$data = apply_filters( 'wxr_importer.pre_process.post', $data, $meta, $comments, $terms );

		if ( empty( $data ) ) {
			return false;
		}

		$original_id = isset( $data['post_id'] ) ? (int) $data['post_id'] : 0;
		$parent_id   = isset( $data['post_parent'] ) ? (int) $data['post_parent'] : 0;

		if ( isset( $this->mapping['post'][ $original_id ] ) ) {
			return false;
		}

		$post_type_object = get_post_type_object( $data['post_type'] );

		if ( ! $post_type_object ) {
			/* translators: 1:post_title, 2:post_type */
			$this->logger->warning( sprintf( __( 'Failed to import "%1$s": Invalid post type %2$s', 'yomooh' ), $data['post_title'], $data['post_type'] ) );

			return false;
		}

		$post_exists = $this->post_exists( $data );

		if ( $post_exists ) {
			$this->logger->info( sprintf( __( '%1$s "%2$s" already exists.', 'yomooh' ), $post_type_object->labels->singular_name, $data['post_title'] ) );

			do_action( 'wxr_importer.db.post', $post_exists, $data );
			$this->process_comments( $comments, $original_id, $data, $post_exists );

			return false;
		}

		$requires_remapping = false;
		if ( $parent_id ) {
			if ( isset( $this->mapping['post'][ $parent_id ] ) ) {
				$data['post_parent'] = $this->mapping['post'][ $parent_id ];
			} else {
				$meta[]             = array(
					'key'   => '_wxr_import_parent',
					'value' => $parent_id,
				);
				$requires_remapping = true;

				$data['post_parent'] = 0;
			}
		}
		$author = sanitize_user( $data['post_author'], true );
		if ( empty( $author ) ) {
			// Missing or invalid author, use default if available.
			$data['post_author'] = $this->options['default_author'];
		} elseif ( isset( $this->mapping['user_slug'][ $author ] ) ) {
			$data['post_author'] = $this->mapping['user_slug'][ $author ];
		} else {
			$meta[]             = array(
				'key'   => '_wxr_import_user_slug',
				'value' => $author,
			);
			$requires_remapping = true;

			$data['post_author'] = (int) get_current_user_id();
		}

		// Does the post look like it contains attachment images?
		if ( preg_match( self::REGEX_HAS_ATTACHMENT_REFS, $data['post_content'] ) ) {
			$meta[]             = array(
				'key'   => '_wxr_import_has_attachment_refs',
				'value' => true,
			);
			$requires_remapping = true;
		}

		// Whitelist to just the keys we allow.
		$postdata = array(
			'import_id' => $data['post_id'],
		);
		$allowed  = array(
			'post_author'    => true,
			'post_date'      => true,
			'post_date_gmt'  => true,
			'post_content'   => true,
			'post_excerpt'   => true,
			'post_title'     => true,
			'post_status'    => true,
			'post_name'      => true,
			'comment_status' => true,
			'ping_status'    => true,
			'guid'           => true,
			'post_parent'    => true,
			'menu_order'     => true,
			'post_type'      => true,
			'post_password'  => true,
		);
		foreach ( $data as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$postdata[ $key ] = $data[ $key ];
		}

		$postdata = apply_filters( 'wp_import_post_data_processed', wp_slash( $postdata ), $data );

		if ( 'attachment' === $postdata['post_type'] ) {
			if ( ! $this->options['fetch_attachments'] ) {
				/* translators: Post title */
				$this->logger->notice( sprintf( __( 'Skipping attachment "%s", fetching attachments disabled', 'yomooh' ), $data['post_title'] ) );

				return false;
			}
			$remote_url = ! empty( $data['attachment_url'] ) ? $data['attachment_url'] : $data['guid'];
			$post_id    = $this->process_attachment( $postdata, $meta, $remote_url );
		} else {
			$post_id = wp_insert_post( $postdata, true );

			do_action( 'wp_import_insert_post', $post_id, $original_id, $postdata, $data );

			do_action( 'wxr_importer.db.post', $post_id, $data );
		}

		if ( is_wp_error( $post_id ) ) {
			$this->logger->error( sprintf( __( 'Failed to import "%1$s" (%2$s)', 'yomooh' ), $data['post_title'], $post_type_object->labels->singular_name ) );

			$this->logger->debug( $post_id->get_error_message() );

			/**
			 * Post processing failed.
			 *
			 * @param WP_Error $post_id Error object.
			 * @param array $data Raw data imported for the post.
			 * @param array $meta Raw meta data, already processed by {@see process_post_meta}.
			 * @param array $comments Raw comment data, already processed by {@see process_comments}.
			 * @param array $terms Raw term data, already processed.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wxr_importer.process_failed.post', $post_id, $data, $meta, $comments, $terms );

			return false;
		}

		// Ensure stickiness is handled correctly too.
		if ( '1' === $data['is_sticky'] ) {
			stick_post( $post_id );
		}

		// Map pre-import ID to local ID.
		$this->mapping['post'][ $original_id ] = (int) $post_id;
		if ( $requires_remapping ) {
			$this->requires_remapping['post'][ $post_id ] = true;
		}
		$this->mark_post_exists( $data, $post_id );

		/* translators: 1:post_title, 2:singular_name */
		$this->logger->info( sprintf( __( 'Imported "%1$s" (%2$s)', 'yomooh' ), $data['post_title'], $post_type_object->labels->singular_name ) );

		/* translators: 1:original_id, 2:post_id */
		$this->logger->debug( sprintf( __( 'Post %1$d remapped to %2$d', 'yomooh' ), $original_id, $post_id ) );

		/**
		 * The wp_import_post_terms hook.
		 *
		 * @since 1.0.0
		 */
		$terms = apply_filters( 'wp_import_post_terms', $terms, $post_id, $data );

		if ( ! empty( $terms ) ) {
			$term_ids = array();
			foreach ( $terms as $term ) {
				$taxonomy = $term['taxonomy'];
				$key      = sha1( $taxonomy . ':' . $term['slug'] );

				if ( isset( $this->mapping['term'][ $key ] ) ) {
					$term_ids[ $taxonomy ][] = (int) $this->mapping['term'][ $key ];
				} else {

					/**
					 * Fix for the post format "categories".
					 * The issue in this importer is, that these post formats are misused as categories in WP export
					 * (as the export data <category> item in the post export item), but they are not actually
					 * exported as wp:category items in the XML file, so they need to be inserted on the fly (here).
					 *
					 * Maybe something better can be done in the future?
					 *
					 * Original issue reported here: https://wordpress.org/support/topic/post-format-videoquotegallery-became-format-standard/#post-8447683
					 */
					if ( 'post_format' === $taxonomy ) {
						$term_exists = term_exists( $term['slug'], $taxonomy );
						$term_id     = is_array( $term_exists ) ? $term_exists['term_id'] : $term_exists;

						if ( empty( $term_id ) ) {
							$t = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
							if ( ! is_wp_error( $t ) ) {
								$term_id                       = $t['term_id'];
								$this->mapping['term'][ $key ] = $term_id;
							} else {
								/* translators: 1:taxonomy, 2:term name */
								$this->logger->warning( sprintf( esc_html__( 'Failed to import term: %1$s - %2$s', 'yomooh' ), esc_html( $taxonomy ), esc_html( $term['name'] ) ) );
								continue;
							}
						}

						if ( ! empty( $term_id ) ) {
							$term_ids[ $taxonomy ][] = intval( $term_id );
						}
					} else {
						$meta[]             = array(
							'key'   => '_wxr_import_term',
							'value' => $term,
						);
						$requires_remapping = true;
					}
				}
			}

			foreach ( $term_ids as $tax => $ids ) {
				$tt_ids = wp_set_post_terms( $post_id, $ids, $tax );

				/**
				 * The wp_import_set_post_terms hook.
				 *
				 * @since 1.0.0
				 */
				do_action( 'wp_import_set_post_terms', $tt_ids, $ids, $tax, $post_id, $data );
			}
		}

		$this->process_comments( $comments, $post_id, $data );
		$this->process_post_meta( $meta, $post_id, $data );

		if ( 'nav_menu_item' === $data['post_type'] ) {
			$this->process_menu_item_meta( $post_id, $data, $meta );
		}

		do_action( 'wxr_importer.processed.post', $post_id, $data, $meta, $comments, $terms );
	}

	protected function process_menu_item_meta( $post_id, $data, $meta ) {

		$item_type          = get_post_meta( $post_id, '_menu_item_type', true );
		$original_object_id = get_post_meta( $post_id, '_menu_item_object_id', true );
		$object_id          = null;

		$this->logger->debug( sprintf( 'Processing menu item %s', $item_type ) );

		$requires_remapping = false;
		switch ( $item_type ) {
			case 'taxonomy':
				if ( isset( $this->mapping['term_id'][ $original_object_id ] ) ) {
					$object_id = $this->mapping['term_id'][ $original_object_id ];
				} else {
					add_post_meta( $post_id, '_wxr_import_menu_item', wp_slash( $original_object_id ) );
					$requires_remapping = true;
				}
				break;

			case 'post_type':
				if ( isset( $this->mapping['post'][ $original_object_id ] ) ) {
					$object_id = $this->mapping['post'][ $original_object_id ];
				} else {
					add_post_meta( $post_id, '_wxr_import_menu_item', wp_slash( $original_object_id ) );
					$requires_remapping = true;
				}
				break;

			case 'custom':
				// Custom refers to itself, wonderfully easy.
				$object_id = $post_id;
				break;

			default:
				// Associated object is missing or not imported yet, we'll retry later.
				$this->missing_menu_items[] = $data;
				$this->logger->debug( 'Unknown menu item type' );
				break;
		}

		if ( $requires_remapping ) {
			$this->requires_remapping['post'][ $post_id ] = true;
		}

		if ( empty( $object_id ) ) {
			// Nothing needed here.
			return;
		}

		$this->logger->debug( sprintf( 'Menu item %d mapped to %d', $original_object_id, $object_id ) );
		update_post_meta( $post_id, '_menu_item_object_id', wp_slash( $object_id ) );
	}

	/**
	 * @param array  $post       Attachment post details from WXR.
	 * @param array  $meta       Attachment post meta details.
	 * @param string $remote_url URL to fetch attachment from.
	 *
	 * @return int|WP_Error Post ID on success, WP_Error otherwise
	 */
	protected function process_attachment($post, $meta, $remote_url) {
    // Existing date handling code remains the same
    $post['upload_date'] = $post['post_date'];
    foreach ($meta as $meta_item) {
        if ('_wp_attached_file' !== $meta_item['key']) {
            continue;
        }

        if (preg_match('%^[0-9]{4}/[0-9]{2}%', $meta_item['value'], $matches)) {
            $post['upload_date'] = $matches[0];
        }
        break;
    }

    // Handle relative URLs
    if (preg_match('|^/[\w\W]+$|', $remote_url)) {
        $remote_url = rtrim($this->base_url, '/') . $remote_url;
    }

    // Fetch the remote file
    $upload = $this->fetch_remote_file($remote_url, $post);
    if (is_wp_error($upload)) {
        return $upload;
    }

    // Check file type
    $info = wp_check_filetype($upload['file']);
    if (!$info) {
        return new WP_Error('attachment_processing_error', esc_html__('Invalid file type', 'yomooh'));
    }

    $post['post_mime_type'] = $info['type'];

    // Update GUID if needed
    if ($this->options['update_attachment_guids']) {
        $post['guid'] = $upload['url'];
    }

    // Insert attachment
    $post_id = wp_insert_attachment($post, $upload['file']);
    if (is_wp_error($post_id)) {
        return $post_id;
    }

    // ===== ENHANCED METADATA GENERATION WITH EXIF ERROR HANDLING =====
    $attachment_metadata = array();
    
    try {
        // First attempt normal metadata generation
        $attachment_metadata = wp_generate_attachment_metadata($post_id, $upload['file']);
        
        // If we get empty metadata (can happen with EXIF errors), create basic structure
        if (empty($attachment_metadata)) {
            $attachment_metadata = $this->generate_basic_attachment_metadata($upload['file']);
        }
    } catch (Exception $e) {
        // Fallback to basic metadata if EXIF processing fails
        error_log('EXIF processing failed for ' . $upload['file'] . ': ' . $e->getMessage());
        $attachment_metadata = $this->generate_basic_attachment_metadata($upload['file']);
    }

    // Update metadata
    wp_update_attachment_metadata($post_id, $attachment_metadata);

    // URL remapping remains the same
    $this->url_remap[$remote_url] = $upload['url'];
    if (substr($remote_url, 0, 8) === 'https://') {
        $insecure_url = 'http' . substr($remote_url, 5);
        $this->url_remap[$insecure_url] = $upload['url'];
    }

    return $post_id;
}

/**
 * Generates basic attachment metadata when EXIF processing fails
 */
protected function generate_basic_attachment_metadata($file) {
    $info = wp_check_filetype($file);
    $metadata = array(
        'file' => basename($file),
        'sizes' => array(),
        'image_meta' => array(
            'aperture' => 0,
            'credit' => '',
            'camera' => '',
            'caption' => '',
            'created_timestamp' => 0,
            'copyright' => '',
            'focal_length' => 0,
            'iso' => 0,
            'shutter_speed' => 0,
            'title' => '',
            'orientation' => 0,
            'keywords' => array(),
        )
    );

    // Try to get basic dimensions if possible
    try {
        $size = @getimagesize($file);
        if ($size) {
            $metadata['width'] = $size[0];
            $metadata['height'] = $size[1];
        }
    } catch (Exception $e) {
        error_log('Could not determine image dimensions for ' . $file);
    }

    return $metadata;
}

	/**
	 * @param DOMNode $node Parent node of meta data (typically `wp:postmeta` or `wp:commentmeta`).
	 * @return array|null Meta data array on success, or null on error.
	 */
	protected function parse_meta_node( $node ) {
		foreach ( $node->childNodes as $child ) {
			// We only care about child elements.
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			switch ( $child->tagName ) {
				case 'wp:meta_key':
					$key = $child->textContent;
					break;

				case 'wp:meta_value':
					$value = $child->textContent;
					break;
			}
		}

		if ( empty( $key ) || ! isset( $value ) ) {
			return null;
		}

		return compact( 'key', 'value' );
	}

	/**
	 * @param array $meta List of meta data arrays
	 * @param int   $post_id Post to associate with
	 * @param array $post Post data
	 * @return int|WP_Error Number of meta items imported on success, error otherwise.
	 */
	protected function process_post_meta( $meta, $post_id, $post ) {
		if ( empty( $meta ) ) {
			return true;
		}

		foreach ( $meta as $meta_item ) {
			/**
			 * Pre-process post meta data.
			 *
			 * @param array $meta_item Meta data. (Return empty to skip.)
			 * @param int $post_id Post the meta is attached to.
			 *
			 * @since 1.0.0
			 */
			$meta_item = apply_filters( 'wxr_importer.pre_process.post_meta', $meta_item, $post_id );

			if ( empty( $meta_item ) ) {
				return false;
			}

			$key   = apply_filters( 'import_post_meta_key', $meta_item['key'], $post_id, $post );
			$value = false;

			if ( '_edit_last' === $key ) {
				$value = intval( $meta_item['value'] );
				if ( ! isset( $this->mapping['user'][ $value ] ) ) {
					// Skip!
					continue;
				}

				$value = $this->mapping['user'][ $value ];
			}

			if ( $key ) {
				if ( ! $value ) {
					$value = maybe_unserialize( $meta_item['value'] );
				}

				add_post_meta( $post_id, wp_slash( $key ), wp_slash_strings_only( $value ) );

				do_action( 'import_post_meta', $post_id, $key, $value );

				// if the post has a featured image, take note of this in case of remap
				if ( '_thumbnail_id' === $key ) {
					$this->featured_images[ $post_id ] = (int) $value;
				}
			}
		}

		return true;
	}

	/**
	 * @param DOMNode $node Parent node of comment data (typically `wp:comment`).
	 * @return array Comment data array.
	 */
	protected function parse_comment_node( $node ) {
		$data = array(
			'commentmeta' => array(),
		);

		foreach ( $node->childNodes as $child ) {
			// We only care about child elements
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			switch ( $child->tagName ) {
				case 'wp:comment_id':
					$data['comment_id'] = $child->textContent;
					break;
				case 'wp:comment_author':
					$data['comment_author'] = $child->textContent;
					break;

				case 'wp:comment_author_email':
					$data['comment_author_email'] = $child->textContent;
					break;

				case 'wp:comment_author_IP':
					$data['comment_author_IP'] = $child->textContent;
					break;

				case 'wp:comment_author_url':
					$data['comment_author_url'] = $child->textContent;
					break;

				case 'wp:comment_user_id':
					$data['comment_user_id'] = $child->textContent;
					break;

				case 'wp:comment_date':
					$data['comment_date'] = $child->textContent;
					break;

				case 'wp:comment_date_gmt':
					$data['comment_date_gmt'] = $child->textContent;
					break;

				case 'wp:comment_content':
					$data['comment_content'] = $child->textContent;
					break;

				case 'wp:comment_approved':
					$data['comment_approved'] = $child->textContent;
					break;

				case 'wp:comment_type':
					$data['comment_type'] = $child->textContent;
					break;

				case 'wp:comment_parent':
					$data['comment_parent'] = $child->textContent;
					break;

				case 'wp:commentmeta':
					$meta_item = $this->parse_meta_node( $child );
					if ( ! empty( $meta_item ) ) {
						$data['commentmeta'][] = $meta_item;
					}
					break;
			}
		}

		return $data;
	}

	/**
	 * @param array   $comments    List of comment data arrays.
	 * @param int     $post_id     Post to associate with.
	 * @param array   $post        Post data.
	 * @param boolean $post_exists Boolean if the post already exists.
	 *
	 * @return int|WP_Error Number of comments imported on success, error otherwise.
	 */
	protected function process_comments( $comments, $post_id, $post, $post_exists = false ) {
		/**
		 * The wp_import_post_comments hook.
		 *
		 * @since 1.0.0
		 */
		$comments = apply_filters( 'wp_import_post_comments', $comments, $post_id, $post );
		if ( empty( $comments ) ) {
			return 0;
		}

		$num_comments = 0;

		// Sort by ID to avoid excessive remapping later
		usort( $comments, array( $this, 'sort_comments_by_id' ) );

		foreach ( $comments as $key => $comment ) {
			/**
			 * Pre-process comment data
			 *
			 * @param array $comment Comment data. (Return empty to skip.)
			 * @param int $post_id Post the comment is attached to.
			 *
			 * @since 1.0.0
			 */
			$comment = apply_filters( 'wxr_importer.pre_process.comment', $comment, $post_id );
			if ( empty( $comment ) ) {
				return false;
			}

			$original_id = isset( $comment['comment_id'] ) ? (int) $comment['comment_id'] : 0;
			$parent_id   = isset( $comment['comment_parent'] ) ? (int) $comment['comment_parent'] : 0;
			$author_id   = isset( $comment['comment_user_id'] ) ? (int) $comment['comment_user_id'] : 0;

			// if this is a new post we can skip the comment_exists() check
			// TODO: Check comment_exists for performance
			if ( $post_exists ) {
				$existing = $this->comment_exists( $comment );
				if ( $existing ) {
					$this->mapping['comment'][ $original_id ] = $existing;
					continue;
				}
			}

			// Remove meta from the main array
			$meta = isset( $comment['commentmeta'] ) ? $comment['commentmeta'] : array();
			unset( $comment['commentmeta'] );

			// Map the parent comment, or mark it as one we need to fix
			$requires_remapping = false;
			if ( $parent_id ) {
				if ( isset( $this->mapping['comment'][ $parent_id ] ) ) {
					$comment['comment_parent'] = $this->mapping['comment'][ $parent_id ];
				} else {
					// Prepare for remapping later
					$meta[]             = array(
						'key'   => '_wxr_import_parent',
						'value' => $parent_id,
					);
					$requires_remapping = true;

					// Wipe the parent for now
					$comment['comment_parent'] = 0;
				}
			}

			// Map the author, or mark it as one we need to fix
			if ( $author_id ) {
				if ( isset( $this->mapping['user'][ $author_id ] ) ) {
					$comment['user_id'] = $this->mapping['user'][ $author_id ];
				} else {
					// Prepare for remapping later
					$meta[]             = array(
						'key'   => '_wxr_import_user',
						'value' => $author_id,
					);
					$requires_remapping = true;

					// Wipe the user for now
					$comment['user_id'] = 0;
				}
			}

			// Run standard core filters
			$comment['comment_post_ID'] = $post_id;
			$comment                    = wp_filter_comment( $comment );

			// wp_insert_comment expects slashed data
			$comment_id                               = wp_insert_comment( wp_slash( $comment ) );
			$this->mapping['comment'][ $original_id ] = $comment_id;
			if ( $requires_remapping ) {
				$this->requires_remapping['comment'][ $comment_id ] = true;
			}
			$this->mark_comment_exists( $comment, $comment_id );

			/**
			 * Comment has been imported.
			 *
			 * @param int $comment_id New comment ID
			 * @param array $comment Comment inserted (`comment_id` item refers to the original ID)
			 * @param int $post_id Post parent of the comment
			 * @param array $post Post data
			 *
			 * @since 1.0.0
			 */
			do_action( 'wp_import_insert_comment', $comment_id, $comment, $post_id, $post );

			// Process the meta items
			foreach ( $meta as $meta_item ) {
				$value = maybe_unserialize( $meta_item['value'] );
				add_comment_meta( $comment_id, wp_slash( $meta_item['key'] ), wp_slash( $value ) );
			}

			/**
			 * Post processing completed.
			 *
			 * @param int $post_id New post ID.
			 * @param array $comment Raw data imported for the comment.
			 * @param array $meta Raw meta data, already processed by {@see process_post_meta}.
			 * @param array $post_id Parent post ID.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wxr_importer.processed.comment', $comment_id, $comment, $meta, $post_id );

			$num_comments++;
		}

		return $num_comments;
	}

	/**
	 * Parse the category node.
	 *
	 * @param DOMNode $node The category node.
	 *
	 * @return array|null
	 */
	protected function parse_category_node( $node ) {
		$data = array(
			// Default taxonomy to "category", since this is a `<category>` tag
			'taxonomy' => 'category',
		);
		$meta = array();

		if ( $node->hasAttribute( 'domain' ) ) {
			$data['taxonomy'] = $node->getAttribute( 'domain' );
		}
		if ( $node->hasAttribute( 'nicename' ) ) {
			$data['slug'] = $node->getAttribute( 'nicename' );
		}

		$data['name'] = $node->textContent;

		if ( empty( $data['slug'] ) ) {
			return null;
		}

		// Just for extra compatibility
		if ( 'tag' === $data['taxonomy'] ) {
			$data['taxonomy'] = 'post_tag';
		}

		return $data;
	}

	/**
	 * Callback for `usort` to sort comments by ID
	 *
	 * @param array $a Comment data for the first comment
	 * @param array $b Comment data for the second comment
	 *
	 * @return int
	 */
	public static function sort_comments_by_id( $a, $b ) {
		if ( empty( $a['comment_id'] ) ) {
			return 1;
		}

		if ( empty( $b['comment_id'] ) ) {
			return -1;
		}

		return $a['comment_id'] - $b['comment_id'];
	}

	protected function parse_author_node( $node ) {
		$data = array();
		$meta = array();
		foreach ( $node->childNodes as $child ) {
			// We only care about child elements.
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			switch ( $child->tagName ) {
				case 'wp:author_login':
					$data['user_login'] = $child->textContent;
					break;

				case 'wp:author_id':
					$data['ID'] = $child->textContent;
					break;

				case 'wp:author_email':
					$data['user_email'] = $child->textContent;
					break;

				case 'wp:author_display_name':
					$data['display_name'] = $child->textContent;
					break;

				case 'wp:author_first_name':
					$data['first_name'] = $child->textContent;
					break;

				case 'wp:author_last_name':
					$data['last_name'] = $child->textContent;
					break;
			}
		}

		return compact( 'data', 'meta' );
	}

	/**
	 * Process author.
	 *
	 * @param array $data The author data from WXR file.
	 * @param array $meta The author meta data from WXR file.
	 */
	protected function process_author( $data, $meta ) {
		/**
		 * Pre-process user data.
		 *
		 * @param array $data User data. (Return empty to skip.)
		 * @param array $meta Meta data.
		 *
		 * @since 1.0.0
		 */
		$data = apply_filters( 'wxr_importer.pre_process.user', $data, $meta );

		if ( empty( $data ) ) {
			return false;
		}

		// Have we already handled this user?
		$original_id   = isset( $data['ID'] ) ? $data['ID'] : 0;
		$original_slug = $data['user_login'];

		if ( isset( $this->mapping['user'][ $original_id ] ) ) {
			$existing = $this->mapping['user'][ $original_id ];

			// Note the slug mapping if we need to too
			if ( ! isset( $this->mapping['user_slug'][ $original_slug ] ) ) {
				$this->mapping['user_slug'][ $original_slug ] = $existing;
			}

			return false;
		}

		if ( isset( $this->mapping['user_slug'][ $original_slug ] ) ) {
			$existing = $this->mapping['user_slug'][ $original_slug ];

			// Ensure we note the mapping too
			$this->mapping['user'][ $original_id ] = $existing;

			return false;
		}

		// Allow overriding the user's slug
		$login = $original_slug;
		if ( isset( $this->user_slug_override[ $login ] ) ) {
			$login = $this->user_slug_override[ $login ];
		}

		$userdata = array(
			'user_login' => sanitize_user( $login, true ),
			'user_pass'  => wp_generate_password(),
		);

		$allowed = array(
			'user_email'   => true,
			'display_name' => true,
			'first_name'   => true,
			'last_name'    => true,
		);
		foreach ( $data as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$userdata[ $key ] = $data[ $key ];
		}

		$user_id = wp_insert_user( wp_slash( $userdata ) );
		if ( is_wp_error( $user_id ) ) {

			/* translators: 1:user_login */
			$this->logger->error( sprintf( __( 'Failed to import user "%s"', 'yomooh' ), $userdata['user_login'] ) );

			$this->logger->debug( $user_id->get_error_message() );

			/**
			 * User processing failed.
			 *
			 * @param WP_Error $user_id Error object.
			 * @param array $userdata Raw data imported for the user.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wxr_importer.process_failed.user', $user_id, $userdata );
			return false;
		}

		if ( $original_id ) {
			$this->mapping['user'][ $original_id ] = $user_id;
		}
		$this->mapping['user_slug'][ $original_slug ] = $user_id;

		/* translators: 1:user_login */
		$this->logger->info( sprintf( __( 'Imported user "%s"', 'yomooh' ), $userdata['user_login'] ) );

		/* translators: 1:original_id, 2:user_id */
		$this->logger->debug( sprintf( __( 'User %1$d remapped to %2$d', 'yomooh' ), $original_id, $user_id ) );

		/**
		 * User processing completed.
		 *
		 * @param int $user_id New user ID.
		 * @param array $userdata Raw data imported for the user.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wxr_importer.processed.user', $user_id, $userdata );
	}


	/**
	 * Parse term node.
	 *
	 * @param DOMNode $node The term node from WXR file.
	 * @param string  $type The type of the term node.
	 *
	 * @return array|null
	 */
	protected function parse_term_node( $node, $type = 'term' ) {
		$data = array();
		$meta = array();

		$tag_name = array(
			'id'          => 'wp:term_id',
			'taxonomy'    => 'wp:term_taxonomy',
			'slug'        => 'wp:term_slug',
			'parent'      => 'wp:term_parent',
			'name'        => 'wp:term_name',
			'description' => 'wp:term_description',
		);
		$taxonomy = null;

		// Special casing!
		switch ( $type ) {
			case 'category':
				$tag_name['slug']        = 'wp:category_nicename';
				$tag_name['parent']      = 'wp:category_parent';
				$tag_name['name']        = 'wp:cat_name';
				$tag_name['description'] = 'wp:category_description';
				$tag_name['taxonomy']    = null;

				$data['taxonomy'] = 'category';
				break;

			case 'tag':
				$tag_name['slug']        = 'wp:tag_slug';
				$tag_name['parent']      = null;
				$tag_name['name']        = 'wp:tag_name';
				$tag_name['description'] = 'wp:tag_description';
				$tag_name['taxonomy']    = null;

				$data['taxonomy'] = 'post_tag';
				break;
		}

		foreach ( $node->childNodes as $child ) {
			// We only care about child elements
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			$key = array_search( $child->tagName, $tag_name, true );

			if ( $key ) {
				$data[ $key ] = $child->textContent;
			} elseif ( 'wp:termmeta' === $child->tagName ) {
				$meta_item = $this->parse_meta_node( $child );
				if ( ! empty( $meta_item ) ) {
					$meta[] = $meta_item;
				}
			}
		}

		if ( empty( $data['taxonomy'] ) ) {
			return null;
		}

		// Compatibility with WXR 1.0.
		if ( 'tag' === $data['taxonomy'] ) {
			$data['taxonomy'] = 'post_tag';
		}

		return compact( 'data', 'meta' );
	}

	protected function process_term( $data, $meta ) {
		$data = apply_filters( 'wxr_importer.pre_process.term', $data, $meta );

		if ( empty( $data ) ) {
			return false;
		}

		$original_id = isset( $data['id'] ) ? (int) $data['id'] : 0;

		$term_slug   = isset( $data['slug'] ) ? $data['slug'] : '';
		$parent_slug = isset( $data['parent'] ) ? $data['parent'] : '';

		$mapping_key = sha1( $data['taxonomy'] . ':' . $data['slug'] );
		$existing    = $this->term_exists( $data );
		if ( $existing ) {
			$this->mapping['term'][ $mapping_key ]    = $existing;
			$this->mapping['term_id'][ $original_id ] = $existing;
			$this->mapping['term_slug'][ $term_slug ] = $existing;
			return false;
		}

		// WP really likes to repeat itself in export files
		if ( isset( $this->mapping['term'][ $mapping_key ] ) ) {
			return false;
		}

		$termdata = array();
		$allowed  = array(
			'slug'        => true,
			'description' => true,
			'parent'      => true, 
		);

		$requires_remapping = false;
		if ( $parent_slug ) {
			if ( isset( $this->mapping['term_slug'][ $parent_slug ] ) ) {
				$data['parent'] = $this->mapping['term_slug'][ $parent_slug ];
			} else {
				// Prepare for remapping later
				$meta[]             = array(
					'key'   => '_wxr_import_parent',
					'value' => $parent_slug,
				);
				$requires_remapping = true;

				// Wipe the parent id for now
				$data['parent'] = 0;
			}
		}

		foreach ( $data as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$termdata[ $key ] = $data[ $key ];
		}

		$result = wp_insert_term( $data['name'], $data['taxonomy'], $termdata );
		if ( is_wp_error( $result ) ) {
			/* translators: 1:taxonomy, 2:name */
			$this->logger->warning( sprintf( __( 'Failed to import %1$s %2$s', 'yomooh' ), $data['taxonomy'], $data['name'] ) );

			/* translators: error messag */
			$this->logger->debug( $result->get_error_message() );

			/**
			 * The wp_import_insert_term_failed hook.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wp_import_insert_term_failed', $result, $data );

			/**
			 * Term processing failed.
			 *
			 * @param WP_Error $result Error object.
			 * @param array $data Raw data imported for the term.
			 * @param array $meta Meta data supplied for the term.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wxr_importer.process_failed.term', $result, $data, $meta );

			return false;
		}

		$term_id = $result['term_id'];

		// Now prepare to map this new term.
		$this->mapping['term'][ $mapping_key ]    = $term_id;
		$this->mapping['term_id'][ $original_id ] = $term_id;
		$this->mapping['term_slug'][ $term_slug ] = $term_id;

		if ( $requires_remapping ) {
			$this->requires_remapping['term'][ $term_id ] = $data['taxonomy'];
		}

		/* translators: 1:name, 2:taxonomy */
		$this->logger->info( sprintf( __( 'Imported "%1$s" (%2$s)', 'yomooh' ), $data['name'], $data['taxonomy'] ) );

		/* translators: 1:original_id, 2:term_id */
		$this->logger->debug(sprintf( __( 'Term %1$d remapped to %2$d', 'yomooh' ), $original_id, $term_id ) );

		// Actuall process of the term meta data.
		$this->process_term_meta( $meta, $term_id, $data );

		/**
		 * The wp_import_insert_term hook.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wp_import_insert_term', $term_id, $data );

		/**
		 * Term processing completed.
		 *
		 * @param int $term_id New term ID.
		 * @param array $data Raw data imported for the term.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wxr_importer.processed.term', $term_id, $data );
	}

	protected function process_term_meta( $meta, $term_id, $term ) {
		if ( empty( $meta ) ) {
			return true;
		}

		foreach ( $meta as $meta_item ) {
			$meta_item = apply_filters( 'wxr_importer.pre_process.term_meta', $meta_item, $term_id );

			if ( empty( $meta_item ) ) {
				continue;
			}

			$key   = apply_filters( 'import_term_meta_key', $meta_item['key'], $term_id, $term );
			$value = false;

			if ( $key ) {
				// Export gets meta straight from the DB so could have a serialized string.
				if ( ! $value ) {
					$value = maybe_unserialize( $meta_item['value'] );
				}

				$result = add_term_meta( $term_id, $key, $value );

				if ( is_wp_error( $result ) ) {
					/* translators: 1:key, 2:value, 3: term_id */
					$this->logger->warning( sprintf( __( 'Failed to add metakey: %1$s, metavalue: %2$s to term_id: %3$d', 'yomooh' ), $key, $value, $term_id ) );

					/**
					 * The wxr_importer.process_failed.termmeta hook.
					 *
					 * @since 1.0.0
					 */
					do_action( 'wxr_importer.process_failed.termmeta', $result, $meta_item, $term_id, $term );
				} else {
					/* translators: 1:term_id, 2:key, 3: value */
					$this->logger->debug( sprintf( __( 'Meta for term_id %1$d : %2$s => %3$s ; successfully added!', 'yomooh' ), $term_id, $key, $value ) );
				}

				/**
				 * The import_term_meta hook.
				 *
				 * @since 1.0.0
				 */
				do_action( 'import_term_meta', $term_id, $key, $value );
			}
		}

		return true;
	}

	/**
	 * @param string $url  URL of item to fetch.
	 * @param array  $post Attachment details.
	 *
	 * @return array|WP_Error Local file location details on success, WP_Error otherwise
	 */
	protected function fetch_remote_file( $url, $post ) {
		// extract the file name and extension from the url
		$file_name = basename( $url );

		// get placeholder file in the upload dir with a unique, sanitized filename
		$upload = wp_upload_bits( $file_name, null, '', $post['upload_date'] );
		if ( $upload['error'] ) {
			return new WP_Error( 'upload_dir_error', $upload['error'] );
		}

		// fetch the remote url and write it to the placeholder file
		$response = wp_remote_get(
			$url,
			array(
				'sslverify' => false,
				'stream'    => true,
				'filename'  => $upload['file'],
			)
		);

		// request failed
		if ( is_wp_error( $response ) ) {
			unlink( $upload['file'] );
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// make sure the fetch was successful
		if ( 200 !== $code ) {
			unlink( $upload['file'] );

			/* translators: 1:code, 2:desc, 3: url */
			return new WP_Error( 'import_file_error', sprintf( __( 'Remote server returned %1$d %2$s for %3$s', 'yomooh' ), $code, get_status_header_desc( $code ), $url ) );
		}

		$filesize = filesize( $upload['file'] );
		$headers  = wp_remote_retrieve_headers( $response );

		// OCDI fix!
		// Smaller images with server compression do not pass this rule.
		// More info here: https://github.com/proteusthemes/WordPress-Importer/pull/2
		//
		// if ( isset( $headers['content-length'] ) && $filesize !== (int) $headers['content-length'] ) {
		// unlink( $upload['file'] );
		// return new WP_Error( 'import_file_error', __( 'Remote file is incorrect size', 'yomooh' ) );
		// }

		if ( 0 === $filesize ) {
			unlink( $upload['file'] );
			return new WP_Error( 'import_file_error', esc_html__( 'Zero size file downloaded', 'yomooh' ) );
		}

		$max_size = (int) $this->max_attachment_size();
		if ( ! empty( $max_size ) && $filesize > $max_size ) {
			unlink( $upload['file'] );

			/* translators: 1:max_size */
			$message = sprintf( __( 'Remote file is too large, limit is %s', 'yomooh' ), size_format( $max_size ) );

			return new WP_Error( 'import_file_error', $message );
		}

		return $upload;
	}

	protected function post_process() {
		// Time to tackle any left-over bits
		if ( ! empty( $this->requires_remapping['post'] ) ) {
			$this->post_process_posts( $this->requires_remapping['post'] );
		}
		if ( ! empty( $this->requires_remapping['comment'] ) ) {
			$this->post_process_comments( $this->requires_remapping['comment'] );
		}
		if ( ! empty( $this->requires_remapping['term'] ) ) {
			$this->post_process_terms( $this->requires_remapping['term'] );
		}
	}

	protected function post_process_posts( $todo ) {
		foreach ( $todo as $post_id => $_ ) {
			/* translators: 1:post_id */
			$this->logger->debug( sprintf( __( 'Running post-processing for post %d', 'yomooh' ), $post_id ) );

			$data = array();

			$parent_id = get_post_meta( $post_id, '_wxr_import_parent', true );

			if ( ! empty( $parent_id ) ) {
				// Have we imported the parent now?
				if ( isset( $this->mapping['post'][ $parent_id ] ) ) {
					$data['post_parent'] = $this->mapping['post'][ $parent_id ];
				} else {
					/* translators: 1:title, 2:post_id */
					$this->logger->warning( sprintf( __( 'Could not find the post parent for "%1$s" (post #%2$d)', 'yomooh' ), get_the_title( $post_id ), $post_id ) );
					/* translators: 1:post_id, 2:parent_id */
					$this->logger->debug( sprintf( __( 'Post %1$d was imported with parent %2$d, but could not be found', 'yomooh' ), $post_id, $parent_id ) );
				}
			}

			$author_slug = get_post_meta( $post_id, '_wxr_import_user_slug', true );

			if ( ! empty( $author_slug ) ) {
				// Have we imported the user now?.
				if ( isset( $this->mapping['user_slug'][ $author_slug ] ) ) {
					$data['post_author'] = $this->mapping['user_slug'][ $author_slug ];
				} else {
					/* translators: 1:title, 2:post_id */
					$this->logger->warning( sprintf( __( 'Could not find the author for "%1$s" (post #%2$d)', 'yomooh' ), get_the_title( $post_id ), $post_id ) );
					/* translators: 1:post_id, 2:author_slug */
					$this->logger->debug( sprintf( __( 'Post %1$d was imported with author "%2$s", but could not be found', 'yomooh' ), $post_id, $author_slug ) );
				}
			}

			$has_attachments = get_post_meta( $post_id, '_wxr_import_has_attachment_refs', true );

			if ( ! empty( $has_attachments ) ) {
				$post    = get_post( $post_id );
				$content = $post->post_content;

				// Replace all the URLs we've got
				$new_content = str_replace( array_keys( $this->url_remap ), $this->url_remap, $content );
				if ( $new_content !== $content ) {
					$data['post_content'] = $new_content;
				}
			}

			if ( get_post_type( $post_id ) === 'nav_menu_item' ) {
				$this->post_process_menu_item( $post_id );
			}

			// Do we have updates to make?
			if ( empty( $data ) ) {
				/* translators: 1:post_id */
				$this->logger->debug( sprintf( __( 'Post %d was marked for post-processing, but none was required.', 'yomooh' ), $post_id ) );

				continue;
			}

			// Run the update.
			$data['ID'] = $post_id;

			$result = wp_update_post( $data, true );

			if ( is_wp_error( $result ) ) {
				/* translators: 1:post_id */
				$this->logger->warning( sprintf( __( 'Could not update "%1$s" (post #%2$d) with mapped data', 'yomooh' ), get_the_title( $post_id ), $post_id ) );

				$this->logger->debug( $result->get_error_message() );
				continue;
			}

			// Clear out our temporary meta keys.
			delete_post_meta( $post_id, '_wxr_import_parent' );
			delete_post_meta( $post_id, '_wxr_import_user_slug' );
			delete_post_meta( $post_id, '_wxr_import_has_attachment_refs' );
		}
	}

	protected function post_process_menu_item( $post_id ) {
		$menu_object_id = get_post_meta( $post_id, '_wxr_import_menu_item', true );
		if ( empty( $menu_object_id ) ) {
			// No processing needed!
			return;
		}

		$menu_item_type = get_post_meta( $post_id, '_menu_item_type', true );
		switch ( $menu_item_type ) {
			case 'taxonomy':
				if ( isset( $this->mapping['term_id'][ $menu_object_id ] ) ) {
					$menu_object = $this->mapping['term_id'][ $menu_object_id ];
				}
				break;

			case 'post_type':
				if ( isset( $this->mapping['post'][ $menu_object_id ] ) ) {
					$menu_object = $this->mapping['post'][ $menu_object_id ];
				}
				break;

			default:
				// Cannot handle this.
				return;
		}

		if ( ! empty( $menu_object ) ) {
			update_post_meta( $post_id, '_menu_item_object_id', wp_slash( $menu_object ) );
		} else {
			/* translators: 1:title, 2:post_id */
			$this->logger->warning( sprintf( __( 'Could not find the menu object for "%1$s" (post #%2$d)', 'yomooh' ), get_the_title( $post_id ), $post_id ) );
			/* translators: 1:post_id, 2:menu_object_id */
			$this->logger->debug( sprintf( __( 'Post %1$d was imported with object "%2$d" of type "%3$s", but could not be found', 'yomooh' ), $post_id, $menu_object_id, $menu_item_type ) );
		}

		delete_post_meta( $post_id, '_wxr_import_menu_item' );
	}

	protected function post_process_comments( $todo ) {
		foreach ( $todo as $comment_id => $_ ) {
			$data = array();

			$parent_id = get_comment_meta( $comment_id, '_wxr_import_parent', true );
			if ( ! empty( $parent_id ) ) {
				// Have we imported the parent now?.
				if ( isset( $this->mapping['comment'][ $parent_id ] ) ) {
					$data['comment_parent'] = $this->mapping['comment'][ $parent_id ];
				} else {
					/* translators: 1:comment_id */
					$this->logger->warning( sprintf( __( 'Could not find the comment parent for comment #%d', 'yomooh' ), $comment_id ) );

					/* translators: 1:comment_id, 2:parent_id */
					$this->logger->debug( sprintf( __( 'Comment %1$d was imported with parent %2$d, but could not be found', 'yomooh' ), $comment_id, $parent_id ) );
				}
			}

			$author_id = get_comment_meta( $comment_id, '_wxr_import_user', true );
			if ( ! empty( $author_id ) ) {
				// Have we imported the user now?
				if ( isset( $this->mapping['user'][ $author_id ] ) ) {
					$data['user_id'] = $this->mapping['user'][ $author_id ];
				} else {
					/* translators: 1:comment_id */
					$this->logger->warning( sprintf( __( 'Could not find the author for comment #%d', 'yomooh' ), $comment_id ) );

					/* translators: 1:comment_id, 2:author_id */
					$this->logger->debug( sprintf( __( 'Comment %1$d was imported with author %2$d, but could not be found', 'yomooh' ), $comment_id, $author_id ) );
				}
			}

			// Do we have updates to make?
			if ( empty( $data ) ) {
				continue;
			}

			// Run the update.
			$data['comment_ID'] = $comment_id;
			$result             = wp_update_comment( wp_slash( $data ) );
			if ( empty( $result ) ) {
				/* translators: 1:comment_id */
				$this->logger->warning( sprintf( __( 'Could not update comment #%d with mapped data', 'yomooh' ), $comment_id ) );

				continue;
			}

			// Clear out our temporary meta keys.
			delete_comment_meta( $comment_id, '_wxr_import_parent' );
			delete_comment_meta( $comment_id, '_wxr_import_user' );
		}
	}

	protected function post_process_terms( $terms_to_be_remapped ) {
		$this->mapping['term_slug']['top'] = 0;
		// The term_id and term_taxonomy are passed-in with $this->requires_remapping['term'].
		foreach ( $terms_to_be_remapped as $termid => $term_taxonomy ) {
			// Basic check.
			if ( empty( $termid ) || ! is_numeric( $termid ) ) {
				/* translators: 1:termid */
				$this->logger->warning( sprintf( __( 'Faulty term_id provided in terms-to-be-remapped array %s', 'yomooh' ), $termid ) );

				continue;
			}
			// This cast to integer may be unnecessary.
			$term_id = (int) $termid;

			if ( empty( $term_taxonomy ) ) {
				/* translators: 1:term_id */
				$this->logger->warning( sprintf( __( 'No taxonomy provided in terms-to-be-remapped array for term #%d', 'yomooh' ), $term_id ) );

				continue;
			}

			$parent_slug = get_term_meta( $term_id, '_wxr_import_parent', true );

			if ( empty( $parent_slug ) ) {
				/* translators: 1:term_id */
				$this->logger->warning( sprintf( __( 'No parent_slug identified in remapping-array for term: %d', 'yomooh' ), $term_id ) );

				continue;
			}

			if ( ! isset( $this->mapping['term_slug'][ $parent_slug ] ) || ! is_numeric( $this->mapping['term_slug'][ $parent_slug ] ) ) {
				/* translators: 1:term_id, 2:parent_slug */
				$this->logger->warning( sprintf( __( 'The term(%1$d)"s parent_slug (%2$s) is not found in the remapping-array.', 'yomooh' ), $term_id, $parent_slug ) );

				continue;
			}

			$mapped_parent = (int) $this->mapping['term_slug'][ $parent_slug ];

			$termattributes = get_term_by( 'id', $term_id, $term_taxonomy, ARRAY_A );
			// Note: the default OBJECT return results in a reserved-word clash with 'parent' [$termattributes->parent], so instead return an associative array.

			if ( empty( $termattributes ) ) {
				/* translators: 1:term_id */
				$this->logger->warning( sprintf( __( 'No data returned by get_term_by for term_id #%d', 'yomooh' ), $term_id ) );

				continue;
			}
			// Check if the correct parent id is already correctly mapped.
			if ( isset( $termattributes['parent'] ) && $termattributes['parent'] == $mapped_parent ) {
				// Clear out our temporary meta key.
				delete_term_meta( $term_id, '_wxr_import_parent' );
				continue;
			}

			// Otherwise set the mapped parent and update the term.
			$termattributes['parent'] = $mapped_parent;

			$result = wp_update_term( $term_id, $termattributes['taxonomy'], $termattributes );

			if ( is_wp_error( $result ) ) {
				/* translators: 1:termattributes name, 2:term_id */
				$this->logger->warning( sprintf( __( 'Could not update "%1$s" (term #%2$d) with mapped data', 'yomooh' ), $termattributes['name'], $term_id ) );

				$this->logger->debug( $result->get_error_message() );

				continue;
			}
			// Clear out our temporary meta key.
			delete_term_meta( $term_id, '_wxr_import_parent' );

			/* translators: 1:term_id, 2:mapped_parent */
			$this->logger->debug( sprintf( __( 'Term %1$d was successfully updated with parent %2$d', 'yomooh' ), $term_id, $mapped_parent ) );
		}
	}
	protected function replace_attachment_urls_in_content() {
    global $wpdb;
    uksort($this->url_remap, array($this, 'cmpr_strlen'));

    foreach ($this->url_remap as $from_url => $to_url) {
        // Fixed: Added proper placeholders for both queries
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", 
            $from_url, 
            $to_url
        ));

        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key = %s", 
            $from_url, 
            $to_url,
            'enclosure'
        ));
    }
}
	public function remap_featured_images() {
		if ( empty( $this->featured_images ) ) {
			return;
		}

		$this->logger->info( esc_html__( 'Starting remapping of featured images', 'yomooh' ) );

		// Cycle through posts that have a featured image.
		foreach ( $this->featured_images as $post_id => $value ) {
			if ( isset( $this->mapping['post'][ $value ] ) ) {
				$new_id = $this->mapping['post'][ $value ];

				// Only update if there's a difference.
				if ( $new_id !== $value ) {
					/* translators: 1:value, 2:new_id, 3:post_id */
					$this->logger->info( sprintf( esc_html__( 'Remapping featured image ID %1$d to new ID %2$d for post ID %3$d', 'yomooh' ), $value, $new_id, $post_id ) );

					update_post_meta( $post_id, '_thumbnail_id', $new_id );
				}
			}
		}
	}

	public function is_valid_meta_key( $key ) {
		if ( in_array( $key, array( '_wp_attached_file', '_wp_attachment_metadata', '_edit_lock' ) ) ) {
			return false;
		}

		return $key;
	}

	protected function max_attachment_size() {
		return apply_filters( 'import_attachment_size_limit', 0 );
	}

	public function bump_request_timeout( $val ) {
		return 60;
	}

	// return the difference in length between two strings
	public function cmpr_strlen( $a, $b ) {
		return strlen( $b ) - strlen( $a );
	}
	protected function prefill_existing_posts() {
    global $wpdb;

    // Fixed: Added a dummy placeholder to satisfy wpdb::prepare()
    $posts = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, guid FROM {$wpdb->posts} WHERE 1 = %d", 
        1
    ));

    foreach ($posts as $item) {
        $this->exists['post'][$item->guid] = $item->ID;
    }
}

	protected function post_exists( $data ) {
		$exists_key = $data['guid'];

		if ( $this->options['prefill_existing_posts'] ) {
			// OCDI: fix for custom post types. The guids in the prefilled section are escaped, so these ones should be as well.
			$exists_key = htmlentities( $exists_key );
			return isset( $this->exists['post'][ $exists_key ] ) ? $this->exists['post'][ $exists_key ] : false;
		}

		// No prefilling, but might have already handled it.
		if ( isset( $this->exists['post'][ $exists_key ] ) ) {
			return $this->exists['post'][ $exists_key ];
		}

		$exists                              = post_exists( $data['post_title'], $data['post_content'], $data['post_date'] );
		$this->exists['post'][ $exists_key ] = $exists;

		return $exists;
	}

	protected function mark_post_exists( $data, $post_id ) {
		$exists_key                          = $data['guid'];
		$this->exists['post'][ $exists_key ] = $post_id;
	}

	protected function prefill_existing_comments() {
    global $wpdb;
    
    // Fixed: Added proper prepare() with placeholder
    $posts = $wpdb->get_results($wpdb->prepare(
        "SELECT comment_ID, comment_author, comment_date FROM {$wpdb->comments} WHERE 1 = %d",
        1
    ));

    foreach ($posts as $item) {
        $exists_key = sha1($item->comment_author . ':' . $item->comment_date);
        $this->exists['comment'][$exists_key] = $item->comment_ID;
    }
}

	protected function comment_exists( $data ) {
		$exists_key = sha1( $data['comment_author'] . ':' . $data['comment_date'] );

		// Constant-time lookup if we prefilled.
		if ( $this->options['prefill_existing_comments'] ) {
			return isset( $this->exists['comment'][ $exists_key ] ) ? $this->exists['comment'][ $exists_key ] : false;
		}

		// No prefilling, but might have already handled it.
		if ( isset( $this->exists['comment'][ $exists_key ] ) ) {
			return $this->exists['comment'][ $exists_key ];
		}

		// Still nothing, try comment_exists, and cache it.
		$exists                                 = comment_exists( $data['comment_author'], $data['comment_date'] );
		$this->exists['comment'][ $exists_key ] = $exists;

		return $exists;
	}

	protected function mark_comment_exists( $data, $comment_id ) {
		$exists_key                             = sha1( $data['comment_author'] . ':' . $data['comment_date'] );
		$this->exists['comment'][ $exists_key ] = $comment_id;
	}

	
	protected function prefill_existing_terms() {
    global $wpdb;

    // Fixed: Added placeholder for the query
    $terms = $wpdb->get_results($wpdb->prepare(
        "SELECT t.term_id, tt.taxonomy, t.slug FROM {$wpdb->terms} AS t 
        JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id 
        WHERE 1 = %d",
        1
    ));

    foreach ($terms as $item) {
        $exists_key = sha1($item->taxonomy . ':' . $item->slug);
        $this->exists['term'][$exists_key] = $item->term_id;
    }
}

	protected function term_exists( $data ) {
		$exists_key = sha1( $data['taxonomy'] . ':' . $data['slug'] );

		if ( $this->options['prefill_existing_terms'] ) {
			return isset( $this->exists['term'][ $exists_key ] ) ? $this->exists['term'][ $exists_key ] : false;
		}

		if ( isset( $this->exists['term'][ $exists_key ] ) ) {
			return $this->exists['term'][ $exists_key ];
		}

		$exists = term_exists( $data['slug'], $data['taxonomy'] );
		if ( is_array( $exists ) ) {
			$exists = $exists['term_id'];
		}

		$this->exists['term'][ $exists_key ] = $exists;

		return $exists;
	}
	protected function mark_term_exists( $data, $term_id ) {
		$exists_key                          = sha1( $data['taxonomy'] . ':' . $data['slug'] );
		$this->exists['term'][ $exists_key ] = $term_id;
	}
}
