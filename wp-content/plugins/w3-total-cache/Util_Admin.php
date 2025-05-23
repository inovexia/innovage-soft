<?php
/**
 * File: Util_Admin.php
 *
 * @package W3TC
 */

namespace W3TC;

/**
 * Class: Util_Admin
 *
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 * phpcs:disable WordPress.WP.AlternativeFunctions
 */
class Util_Admin {
	/**
	 * Redirects when in WP Admin.
	 *
	 * @static
	 *
	 * @param array  $params         Parameters.
	 * @param bool   $check_referrer Check referrer.
	 * @param string $page           Page.
	 */
	public static function redirect( $params = array(), $check_referrer = false, $page = '' ) {
		$url      = Util_Request::get_string( 'redirect' );
		$page_url = Util_Request::get_string( 'page' );

		if ( empty( $url ) ) {
			if ( $check_referrer && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
				$url = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			} else {
				$url = 'admin.php';

				if ( empty( $page ) ) {
					$page = $page_url;
				}

				$params = array_merge( array( 'page' => $page ), $params );
			}
		}

		Util_Environment::redirect( $url, $params );
	}

	/**
	 * Redirect function to current admin page with errors and messages specified.
	 *
	 * @static
	 *
	 * @param array $params         Parameters.
	 * @param array $errors         Errors.
	 * @param array $notes          Notes.
	 * @param bool  $check_referrer Check referrer.
	 * @return void
	 */
	public static function redirect_with_custom_messages( $params, $errors = null, $notes = null, $check_referrer = false ) {
		if ( empty( $errors ) && self::single_system_item( $notes ) ) {
			self::redirect( array_merge( $params, array( 'w3tc_note' => $notes[0] ) ), $check_referrer );
			return;
		}
		if ( self::single_system_item( $errors ) && empty( $notes ) ) {
			self::redirect( array_merge( $params, array( 'w3tc_error' => $errors[0] ) ), $check_referrer );
			return;
		}

		$message_id = uniqid();
		update_option(
			'w3tc_message',
			array(
				$message_id => array(
					'errors' => $errors,
					'notes'  => $notes,
				),
			),
			'yes'
		);

		self::redirect( array_merge( $params, array( 'w3tc_message' => $message_id ) ), $check_referrer );
	}



	/**
	 * Special redirect with ability to pass custom_message_id based on $data.
	 *   query_string
	 *   actions - which actions to call on render
	 *   errors
	 *   notes
	 *
	 * @static
	 *
	 * @param array $data Data.
	 */
	public static function redirect_with_custom_messages2( $data ) {
		if ( ! isset( $data['query_string']['page'] ) ) {
			$data['query_string']['page'] = Util_Request::get_string( 'page' );

			if ( 'w3tc_extensions' === $data['query_string']['page'] ) {
				$data['query_string']['extension'] = Util_Request::get_string( 'extension' );
				$data['query_string']['action']    = Util_Request::get_string( 'action' );
			}
		}

		$message_id = uniqid();

		update_option( 'w3tc_message', array( $message_id => $data ), 'yes' );

		$data['query_string']['w3tc_message'] = $message_id;

		Util_Environment::redirect( 'admin.php', $data['query_string'] );
	}


	/**
	 * Custom message id.
	 *
	 * @static
	 *
	 * @param array $errors Errors.
	 * @param array $notes  Notes.
	 */
	public static function custom_message_id( $errors = null, $notes = null ) {
		$message_id = uniqid();
		update_option(
			'w3tc_message',
			array(
				$message_id => array(
					'errors' => $errors,
					'notes'  => $notes,
				),
			),
			'yes'
		);

		return 'w3tc_message=' . $message_id;
	}

	/**
	 * Checks if contains single message item.
	 *
	 * @static
	 *
	 * @param array $a Array.
	 * @return bool
	 */
	public static function single_system_item( $a ) {
		if ( ! is_array( $a ) || count( $a ) !== 1 ) {
			return false;
		}

		$first_key = array_keys( $a );
		$first_key = $first_key[0];
		$pos       = strpos( $a[ $first_key ], ' ' );

		if ( false === $pos ) {
			return true;
		}

		return false;
	}

	/**
	 * Save config, can't decline save process. (difference from action_save).
	 *
	 * Do some actions on config keys update.
	 *
	 * Used in several places such as:
	 *
	 * 1. common config save
	 * 2. import settings
	 *
	 * @static
	 *
	 * @param Config $current_config Current config.
	 * @param Config $new_config     New config.
	 * @return bool
	 * @throws \Exception Exception.
	 */
	public static function config_save( $current_config, $new_config ) {
		$master_config = ( $new_config->is_master() ? $new_config : Dispatcher::config_master() );

		if ( $master_config->get_integer( 'common.instance_id', 0 ) === 0 ) {
			$master_config->set( 'common.instance_id', wp_rand() );

			if ( ! $new_config->is_master() ) {
				$master_config->save();
			}
		}

		$old_config                = new Config();
		$browsercache_dependencies = array();

		if ( $new_config->get_boolean( 'browsercache.enabled' ) ) {
			$browsercache_dependencies = array_merge(
				$browsercache_dependencies,
				array(
					'browsercache.rewrite',
					'browsercache.cssjs.replace',
					'browsercache.html.replace',
					'browsercache.other.replace',
				)
			);

			if ( $new_config->get_boolean( 'browsercache.cssjs.replace' ) ) {
				$browsercache_dependencies = array_merge(
					$browsercache_dependencies,
					array(
						'browsercache.cssjs.compression',
						'browsercache.cssjs.expires',
						'browsercache.cssjs.lifetime',
						'browsercache.cssjs.cache.control',
						'browsercache.cssjs.cache.policy',
						'browsercache.cssjs.etag',
						'browsercache.cssjs.w3tc',
					)
				);
			}

			if ( $new_config->get_boolean( 'browsercache.html.replace' ) ) {
				$browsercache_dependencies = array_merge(
					$browsercache_dependencies,
					array(
						'browsercache.html.compression',
						'browsercache.html.expires',
						'browsercache.html.lifetime',
						'browsercache.html.cache.control',
						'browsercache.html.cache.policy',
						'browsercache.html.etag',
						'browsercache.html.w3tc',
					)
				);
			}

			if ( $new_config->get_boolean( 'browsercache.other.replace' ) ) {
				$browsercache_dependencies = array_merge(
					$browsercache_dependencies,
					array(
						'browsercache.other.compression',
						'browsercache.other.expires',
						'browsercache.other.lifetime',
						'browsercache.other.cache.control',
						'browsercache.other.cache.policy',
						'browsercache.other.etag',
						'browsercache.other.w3tc',
					)
				);
			}

			$old_bc_dependencies_values = array();
			$new_bc_dependencies_values = array();

			foreach ( $browsercache_dependencies as $key ) {
				$old_bc_dependencies_values[] = $old_config->get( $key );
				$new_bc_dependencies_values[] = $new_config->get( $key );
			}

			if ( serialize( $old_bc_dependencies_values ) !== serialize( $new_bc_dependencies_values ) ) {
				$state_note = Dispatcher::config_state_note();
				$state_note->set( 'common.show_note.flush_statics_needed', true );
			}
		}

		/**
		 * Show need empty page cache notification
		 */
		$cache_flush = Dispatcher::component( 'CacheFlush' );
		if ( $cache_flush->flushable_posts() ) {

			$pgcache_dependencies = array_merge(
				$browsercache_dependencies,
				array(
					'pgcache.debug',
					'pgcache.cache.query',
					'pgcache.cache.home',
					'pgcache.cache.feed',
					'pgcache.cache.nginx_handle_xml',
					'pgcache.cache.ssl',
					'pgcache.cache.404',
					'pgcache.cache.headers',
					'pgcache.compatibility',
					'pgcache.remove_charset',
					'pgcache.accept.uri',
					'pgcache.accept.files',
					'pgcache.accept.qs',
					'pgcache.late_init',
					'pgcache.mirrors.enabled',
					'pgcache.reject.front_page',
					'pgcache.reject.logged',
					'pgcache.reject.logged_roles',
					'pgcache.reject.uri',
					'pgcache.reject.ua',
					'pgcache.reject.cookie',
					'pgcache.reject.request_head',
					'dbcache.enabled',
					'objectcache.enabled',
					'minify.enabled',
					'mobile.enabled',
					'referrer.enabled',
				)
			);

			if ( $new_config->get_boolean( 'pgcache.mirrors.enabled' ) ) {
				$pgcache_dependencies = array_merge( $pgcache_dependencies, array( 'pgcache.mirrors.home_urls' ) );
			}
			if ( $new_config->get_boolean( 'dbcache.enabled' ) ) {
				$pgcache_dependencies = array_merge( $pgcache_dependencies, array( 'dbcache.debug' ) );
			}

			if ( $new_config->getf_boolean( 'objectcache.enabled' ) ) {
				$pgcache_dependencies = array_merge( $pgcache_dependencies, array( 'objectcache.debug' ) );
			}

			if ( $new_config->get_boolean( 'minify.enabled' ) ) {
				$pgcache_dependencies = array_merge(
					$pgcache_dependencies,
					array(
						'minify.auto',
						'minify.debug',
						'minify.rewrite',
						'minify.html.enable',
						'minify.html.engine',
						'minify.html.inline.css',
						'minify.html.inline.js',
						'minify.html.strip.crlf',
						'minify.html.comments.ignore',
						'minify.css.enable',
						'minify.css.engine',
						'minify.css.groups',
						'minify.js.enable',
						'minify.js.engine',
						'minify.js.groups',
						'minify.htmltidy.options.clean',
						'minify.htmltidy.options.hide-comments',
						'minify.htmltidy.options.wrap',
						'minify.reject.logged',
						'minify.reject.ua',
						'minify.reject.uri',
					)
				);
			}

			$modules = Dispatcher::component( 'ModuleStatus' );
			if ( $modules->is_running( 'cdn' ) ) {
				$pgcache_dependencies = array_merge(
					$pgcache_dependencies,
					array(
						'cdn.enabled',
						'cdn.debug',
						'cdn.engine',
						'cdn.uploads.enable',
						'cdn.includes.enable',
						'cdn.includes.files',
						'cdn.theme.enable',
						'cdn.theme.files',
						'cdn.minify.enable',
						'cdn.custom.enable',
						'cdn.custom.files',
						'cdn.ftp.domain',
						'cdn.ftp.ssl',
						'cdn.s3.cname',
						'cdn.s3.ssl',
						'cdn.cf.cname',
						'cdn.cf.ssl',
						'cdn.cf2.cname',
						'cdn.cf2.ssl',
						'cdn.rscf.cname',
						'cdn.rscf.ssl',
						'cdn.azure.cname',
						'cdn.azure.ssl',
						'cdn.azuremi.cname',
						'cdn.azuremi.ssl',
						'cdn.mirror.domain',
						'cdn.mirror.ssl',
						'cdn.cotendo.domain',
						'cdn.cotendo.ssl',
						'cdn.edgecast.domain',
						'cdn.edgecast.ssl',
						'cdn.att.domain',
						'cdn.att.ssl',
						'cdn.reject.logged_roles',
						'cdn.reject.roles',
						'cdn.reject.ua',
						'cdn.reject.uri',
						'cdn.reject.files',
					)
				);
			} elseif ( $old_config->get_boolean( 'cdn.enabled' ) && ! $new_config->get_boolean( 'cdn.enabled' ) ) {
				$pgcache_dependencies = array_merge( $pgcache_dependencies, array( 'cdn.enabled' ) );
			}

			if ( $new_config->get_boolean( 'mobile.enabled' ) ) {
				$pgcache_dependencies = array_merge( $pgcache_dependencies, array( 'mobile.rgroups' ) );
			}

			if ( $new_config->get_boolean( 'referrer.enabled' ) ) {
				$pgcache_dependencies = array_merge( $pgcache_dependencies, array( 'referrer.rgroups' ) );
			}

			if ( $new_config->get_boolean( 'browsercache.enabled' ) && $new_config->get_string( 'pgcache.engine' ) === 'file_generic' ) {
				$pgcache_dependencies = array_merge(
					$pgcache_dependencies,
					array(
						'browsercache.html.last_modified',
						'browsercache.other.last_modified',
					)
				);
			}

			$old_pgcache_dependencies_values = array();
			$new_pgcache_dependencies_values = array();

			foreach ( $pgcache_dependencies as $pgcache_dependency ) {
				$old_pgcache_dependencies_values[] = $old_config->get( $pgcache_dependency );
				$new_pgcache_dependencies_values[] = $new_config->get( $pgcache_dependency );
			}

			if ( serialize( $old_pgcache_dependencies_values ) !== serialize( $new_pgcache_dependencies_values ) ) {
				$state_note = Dispatcher::config_state_note();
				$state_note->set( 'common.show_note.flush_posts_needed', true );
			}
		}

		/**
		 * Show need empty minify notification
		 */
		if ( $current_config->get_boolean( 'minify.enabled' ) && $new_config->get_boolean( 'minify.enabled' ) && ( ( $new_config->get_boolean( 'minify.css.enable' ) && ( $new_config->get_boolean( 'minify.auto' ) || count( $new_config->get_array( 'minify.css.groups' ) ) ) ) || ( $new_config->get_boolean( 'minify.js.enable' ) && ( $new_config->get_boolean( 'minify.auto' ) || count( $new_config->get_array( 'minify.js.groups' ) ) ) ) ) ) {
			$minify_dependencies = array_merge(
				$browsercache_dependencies,
				array(
					'minify.auto',
					'minify.debug',
					'minify.options',
					'minify.symlinks',
					'minify.css.enable',
					'minify.js.enable',
				)
			);

			if ( $new_config->get_boolean( 'minify.css.enable' ) && ( $new_config->get_boolean( 'minify.auto' ) || count( $new_config->get_array( 'minify.css.groups' ) ) ) ) {
				$minify_dependencies = array_merge(
					$minify_dependencies,
					array(
						'minify.css.engine',
						'minify.css.method',
						'minify.css.strip.comments',
						'minify.css.strip.crlf',
						'minify.css.imports',
						'minify.css.groups',
						'minify.yuicss.path.java',
						'minify.yuicss.path.jar',
						'minify.yuicss.options.line-break',
						'minify.csstidy.options.remove_bslash',
						'minify.csstidy.options.compress_colors',
						'minify.csstidy.options.compress_font-weight',
						'minify.csstidy.options.lowercase_s',
						'minify.csstidy.options.optimise_shorthands',
						'minify.csstidy.options.remove_last_;',
						'minify.csstidy.options.remove_space_before_important',
						'minify.csstidy.options.case_properties',
						'minify.csstidy.options.sort_properties',
						'minify.csstidy.options.sort_selectors',
						'minify.csstidy.options.merge_selectors',
						'minify.csstidy.options.discard_invalid_selectors',
						'minify.csstidy.options.discard_invalid_properties',
						'minify.csstidy.options.css_level',
						'minify.csstidy.options.preserve_css',
						'minify.csstidy.options.timestamp',
						'minify.csstidy.options.template',
					)
				);
			}

			if ( $new_config->get_boolean( 'minify.js.enable' ) && ( $new_config->get_boolean( 'minify.auto' ) || count( $new_config->get_array( 'minify.js.groups' ) ) ) ) {
				$minify_dependencies = array_merge(
					$minify_dependencies,
					array(
						'minify.js.engine',
						'minify.js.method',
						'minify.js.combine.header',
						'minify.js.combine.body',
						'minify.js.combine.footer',
						'minify.js.strip.comments',
						'minify.js.strip.crlf',
						'minify.js.groups',
						'minify.yuijs.path.java',
						'minify.yuijs.path.jar',
						'minify.yuijs.options.line-break',
						'minify.yuijs.options.nomunge',
						'minify.yuijs.options.preserve-semi',
						'minify.yuijs.options.disable-optimizations',
						'minify.ccjs.path.java',
						'minify.ccjs.path.jar',
						'minify.ccjs.options.compilation_level',
						'minify.ccjs.options.formatting',
					)
				);
			}

			$modules = Dispatcher::component( 'ModuleStatus' );
			if ( $modules->is_running( 'cdn' ) ) {
				$minify_dependencies = array_merge( $minify_dependencies, array( 'cdn.engine', 'cdn.enabled' ) );
			} elseif ( $old_config->get_boolean( 'cdn.enabled' ) && ! $new_config->get_boolean( 'cdn.enabled' ) ) {
				$minify_dependencies = array_merge( $minify_dependencies, array( 'cdn.enabled' ) );
			}

			$old_minify_dependencies_values = array();
			$new_minify_dependencies_values = array();

			foreach ( $minify_dependencies as $minify_dependency ) {
				$old_minify_dependencies_values[] = $old_config->get( $minify_dependency );
				$new_minify_dependencies_values[] = $new_config->get( $minify_dependency );
			}

			if ( serialize( $old_minify_dependencies_values ) !== serialize( $new_minify_dependencies_values ) ) {
				$state_note = Dispatcher::config_state_note();
				$state_note->set( 'minify.show_note.need_flush', true );
			}
		}

		if ( $new_config->get_boolean( 'cdn.enabled' ) && ! Cdn_Util::is_engine_mirror( $new_config->get_string( 'cdn.engine' ) ) ) {
			/**
			 * Show notification when CDN enabled
			 */
			if ( ! $old_config->get_boolean( 'cdn.enabled' ) ) {
				$state = Dispatcher::config_state();
				$state->set( 'cdn.show_note_cdn_upload', true );
				$state->save();
			}

			/**
			 * Show notification when Browser Cache settings changes
			 */
			$cdn_dependencies = array( 'browsercache.enabled' );

			if ( $new_config->get_boolean( 'cdn.enabled' ) ) {
				$cdn_dependencies = array(
					'browsercache.cssjs.compression',
					'browsercache.cssjs.expires',
					'browsercache.cssjs.lifetime',
					'browsercache.cssjs.cache.control',
					'browsercache.cssjs.cache.policy',
					'browsercache.cssjs.etag',
					'browsercache.cssjs.w3tc',
					'browsercache.html.compression',
					'browsercache.html.expires',
					'browsercache.html.lifetime',
					'browsercache.html.cache.control',
					'browsercache.html.cache.policy',
					'browsercache.html.etag',
					'browsercache.html.w3tc',
					'browsercache.other.compression',
					'browsercache.other.expires',
					'browsercache.other.lifetime',
					'browsercache.other.cache.control',
					'browsercache.other.cache.policy',
					'browsercache.other.etag',
					'browsercache.other.w3tc',
				);
			}

			$old_cdn_dependencies_values = array();
			$new_cdn_dependencies_values = array();

			foreach ( $cdn_dependencies as $cdn_dependency ) {
				$old_cdn_dependencies_values[] = $old_config->get( $cdn_dependency );
				$new_cdn_dependencies_values[] = $new_config->get( $cdn_dependency );
			}

			if ( serialize( $old_cdn_dependencies_values ) !== serialize( $new_cdn_dependencies_values ) ) {
				$state = Dispatcher::config_state();
				$state->set( 'cdn.show_note_cdn_reupload', true );
				$state->save();
			}
		}

		/**
		 * Show need empty object cache notification
		 */
		if ( $current_config->getf_boolean( 'objectcache.enabled' ) ) {
			$objectcache_dependencies = array(
				'objectcache.groups.global',
				'objectcache.groups.nonpersistent',
			);

			$old_objectcache_dependencies_values = array();
			$new_objectcache_dependencies_values = array();

			foreach ( $objectcache_dependencies as $objectcache_dependency ) {
				$old_objectcache_dependencies_values[] = $old_config->get( $objectcache_dependency );
				$new_objectcache_dependencies_values[] = $new_config->get( $objectcache_dependency );
			}

			if ( serialize( $old_objectcache_dependencies_values ) !== serialize( $new_objectcache_dependencies_values ) ) {
				$state_note = Dispatcher::config_state_note();
				$state_note->set( 'objectcache.show_note.flush_needed', true );
			}
		}

		do_action( 'w3tc_saved_options', $new_config );

		/**
		 * Save config
		 */
		try {
			$new_config->save();
		} catch ( \Exception $ex ) {
			// try to fix environment, it potentially can be fixed silently don't show error here, it will be called again later in admin_notices.
			try {
				$environment = Dispatcher::component( 'Root_Environment' );
				$environment->fix_in_wpadmin( $new_config );
			} catch ( \Exception $ex ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Do nothing.
			}

			// Retry save process and complain now on failure.
			try {
				$new_config->save();
			} catch ( \Exception $ex ) {
				throw new \Exception(
					\wp_kses_post(
						sprintf(
							// Translators: 1 Opening HTML strong tag, 2 Closing HTML strong tag, 3 Error message.
							\__( '%1$sCan\'t change configuration%2$s: %3$s', 'w3-total-cache' ),
							'<strong>',
							'</strong>',
							$ex->getMessage()
						)
					)
				);
			}
		}

		$w3_plugin_cdn = Dispatcher::component( 'Cdn_Core_Admin' );

		/**
		 * Empty caches on engine change or cache enable/disable
		 */
		if ( $old_config->get_string( 'pgcache.engine' ) !== $new_config->get_string( 'pgcache.engine' ) || $old_config->get_string( 'pgcache.enabled' ) !== $new_config->get_string( 'pgcache.enabled' ) ) {
			$pgcacheflush = Dispatcher::component( 'PgCache_Flush' );
			$v            = $pgcacheflush->flush();
		}

		if ( $old_config->get_string( 'dbcache.engine' ) !== $new_config->get_string( 'dbcache.engine' ) || $old_config->get_string( 'dbcache.enabled' ) !== $new_config->get_string( 'dbcache.enabled' ) ) {
			w3tc_dbcache_flush();
		}

		if ( $old_config->get_string( 'objectcache.engine' ) !== $new_config->get_string( 'objectcache.engine' ) || $old_config->getf_boolean( 'objectcache.enabled' ) !== $new_config->getf_boolean( 'objectcache.enabled' ) ) {
			w3tc_objectcache_flush();
		}

		if ( $old_config->get_string( 'minify.engine' ) !== $new_config->get_string( 'minify.engine' ) || $old_config->get_string( 'minify.enabled' ) !== $new_config->get_string( 'minify.enabled' ) ) {
			w3tc_minify_flush();
		}

		/**
		 * Update CloudFront CNAMEs
		 */
		if ( $new_config->get_boolean( 'cdn.enabled' ) && in_array( $new_config->get_string( 'cdn.engine' ), array( 'cf', 'cf2' ), true ) ) {
			if ( 'cf' === $new_config->get_string( 'cdn.engine' ) ) {
				$old_cnames = $old_config->get_array( 'cdn.cf.cname' );
				$new_cnames = $new_config->get_array( 'cdn.cf.cname' );
			} else {
				$old_cnames = $old_config->get_array( 'cdn.cf2.cname' );
				$new_cnames = $new_config->get_array( 'cdn.cf2.cname' );
			}
		}

		/**
		 * Refresh config
		 */
		$current_config->load();

		/**
		 * React to config changes
		 */
		$environment = Dispatcher::component( 'Root_Environment' );
		$environment->fix_on_event( $new_config, 'config_change', $old_config );

		/**
		 * Auto upload browsercache files to CDN
		 */
		if ( $new_config->get_boolean( 'cdn.enabled' ) && 'ftp' === $new_config->get_string( 'cdn.engine' ) ) {
			self::cdn_delete_browsercache( $current_config );
			self::cdn_upload_browsercache( $current_config );
		}

		return true;
	}



	/**
	 * Uploads minify files to CDN.
	 *
	 * @static
	 *
	 * @return void
	 */
	public static function cdn_upload_minify() {
		$w3_plugin_cdn = Dispatcher::component( 'Cdn_Plugin' );
		$common        = Dispatcher::component( 'Cdn_Core' );

		$files = $w3_plugin_cdn->get_files_minify();

		$upload  = array();
		$results = array();

		foreach ( $files as $file ) {
			$upload[] = $common->build_file_descriptor(
				$common->docroot_filename_to_absolute_path( $file ),
				$common->uri_to_cdn_uri( $common->docroot_filename_to_uri( $file ) )
			);
		}

		$common->upload( $upload, true, $results );
	}

	/**
	 * Uploads Browser Cache .htaccess to FTP.
	 *
	 * @static
	 *
	 * @param Config $config Config.
	 * @return void
	 */
	public static function cdn_upload_browsercache( $config ) {
		$common = Dispatcher::component( 'Cdn_Core' );

		Dispatcher::component( 'Cdn_Core_Admin' );

		$ce    = Dispatcher::component( 'Cdn_Environment' );
		$rules = $ce->rules_generate_for_ftp( $config );

		if ( $config->get_boolean( 'browsercache.enabled' ) ) {
			$be     = Dispatcher::component( 'BrowserCache_Environment' );
			$rules .= $be->rules_cache_generate_for_ftp( $config );
		}

		$cdn_path = Util_Rule::get_cdn_rules_path();
		$tmp_path = W3TC_CACHE_TMP_DIR . '/' . $cdn_path;

		if ( @file_put_contents( $tmp_path, $rules ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			$results = array();
			$upload  = array( $common->build_file_descriptor( $tmp_path, $cdn_path ) );

			$common->upload( $upload, true, $results );
		}
	}

	/**
	 * Deletes Browser Cache .htaccess from FTP.
	 *
	 * @static
	 *
	 * @return void
	 */
	public static function cdn_delete_browsercache() {
		$common   = Dispatcher::component( 'Cdn_Core' );
		$cdn_path = Util_Rule::get_cdn_rules_path();
		$tmp_path = W3TC_CACHE_TMP_DIR . '/' . $cdn_path;
		$results  = array();
		$delete   = array( $common->build_file_descriptor( $tmp_path, $cdn_path ) );

		$common->delete( $delete, false, $results );
	}


	/**
	 * Returns cookie domain.
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_cookie_domain() {
		$site_url  = get_option( 'siteurl' );
		$parse_url = @wp_parse_url( $site_url );

		if ( $parse_url && ! empty( $parse_url['host'] ) ) {
			return $parse_url['host'];
		}

		return isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	}

	/**
	 * Returns current w3tc admin page.
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_current_page() {
		$page = Util_Request::get_string( 'page' );

		if ( substr( $page, 0, 5 ) === 'w3tc_' ) {
			return $page;
		}

		return 'w3tc_dashboard';
	}

	/**
	 * Returns current w3tc extension id.
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_current_extension() {
		$page      = Util_Request::get_string( 'page' );
		$extension = Util_Request::get_string( 'extension' );

		if ( substr( $page, 0, 5 ) === 'w3tc_' && ! empty( $extension ) ) {
			return $extension;
		}

		return '';
	}

	/**
	 * Check if current page is a W3TC admin page.
	 *
	 * @static
	 *
	 * @return bool
	 */
	public static function is_w3tc_admin_page() {
		$page_val = Util_Request::get_string( 'page' );
		if ( ! empty( $page_val ) && 'w3tc_' === substr( $page_val, 0, 5 ) ) {
			return true;
		}

		$action_val = Util_Request::get_string( 'action' );
		if ( ! empty( $action_val ) && 'w3tc_' === substr( $action_val, 0, 5 ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Returns current WordPress page.
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_current_wp_page() {
		return Util_Request::get_string( 'page' );
	}

	/**
	 * Fix environment once an event occurs.
	 *
	 * @since  2.8.1
	 * @static
	 *
	 * @param  Config $config W3TC configuration object.
	 * @param  string $event Event name (optional).
	 * @return void
	 */
	public static function fix_on_event( Config $config, ?string $event = '' ): void {
		$environment = Dispatcher::component( 'Root_Environment' );
		$environment->fix_on_event( $config, $event );
	}
}
