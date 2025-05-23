<?php

namespace wpforo\classes;

use stdClass;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class PostMeta {
	private $default;

	public function __construct() {
		$this->init_defaults();
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wpforo_after_add_topic', [ $this, 'after_add_topic' ], 10, 2 );
		add_action( 'wpforo_after_edit_topic', [ $this, 'after_edit_topic' ], 10, 3 );
		add_action( 'wpforo_after_add_post', [ $this, 'after_add_post' ], 10, 3 );
		add_action( 'wpforo_after_edit_post', [ $this, 'after_edit_post' ], 10, 4 );
		add_action( 'wpforo_after_move_topic', [ $this, 'after_move_topic' ], 10, 2 );
		add_action( 'wpforo_after_merge_topic', [ $this, 'after_merge_topic' ], 10, 3 );
		add_action( 'wpforo_after_delete_post', [ $this, 'after_delete_post' ] );
		add_action( 'wpforo_post_status_update', [ $this, 'after_post_status_update' ], 10, 2 );
		add_action( 'wpforo_topic_private_update', [ $this, 'after_topic_private_update' ], 10, 2 );
		add_action( 'wpforo_after_is_first_post_update', [ $this, 'after_is_first_post_update' ], 10, 2 );
	}

	private function init_defaults() {
		$this->default                  = new stdClass();
		$this->default->postmeta        = [
			'metaid'        => 0,
			'postid'        => 0,
			'metakey'       => '',
			'metavalue'     => '',
			'forumid'       => 0,
			'topicid'       => 0,
			'status'        => 0,
			'private'       => 0,
			'is_first_post' => 0,
		];
		$this->default->postmeta_format = [
			'metaid'        => '%d',
			'postid'        => '%d',
			'metakey'       => '%s',
			'metavalue'     => '%s',
			'forumid'       => '%d',
			'topicid'       => '%d',
			'status'        => '%d',
			'private'       => '%d',
			'is_first_post' => '%d',
		];
		$this->default->sql_select_args = [
			'include'            => [],
			'exclude'            => [],
			'postids_include'    => [],
			'postids_exclude'    => [],
			'metakeys_include'   => [],
			'metakeys_exclude'   => [],
			'metavalues_include' => [],
			'metavalues_exclude' => [],
			'metavalue_like'     => null,
			'metavalue_notlike'  => null,
			'forumids_include'   => [],
			'forumids_exclude'   => [],
			'topicids_include'   => [],
			'topicids_exclude'   => [],
			'is_first_post'      => null,
			'status'             => null,
			'private'            => null,
			'orderby'            => null,
			'offset'             => null,
			'row_count'          => null,
		];
	}

	public function fix_postmeta( $postmeta ) {
		$postmeta              = wpforo_array_args_cast_and_merge( $postmeta, $this->default->postmeta );
		$postmeta['metavalue'] = wpforo_is_json( $postmeta['metavalue'] ) ? json_decode( $postmeta['metavalue'], true ) : $postmeta['metavalue'];

		return $postmeta;
	}

	/**
	 * @param array $postmeta
	 *
	 * @return bool|int
	 */
	public function add( $postmeta ) {
		if( ! wpfval( $postmeta, 'postid' ) || ! wpfval( $postmeta, 'metakey' ) ) return false;

		if( ! wpfkey( $postmeta, 'topicid' ) || ! wpfkey( $postmeta, 'forumid' ) || ! wpfkey( $postmeta, 'status' ) || ! wpfkey( $postmeta, 'private' ) || ! wpfkey( $postmeta, 'is_first_post' ) ) {
			if( ! $post = WPF()->post->get_post( $postmeta['postid'], false ) ) return false;
			$postmeta['topicid']       = wpforo_bigintval( wpfval( $post, 'topicid' ) );
			$postmeta['forumid']       = intval( wpfval( $post, 'forumid' ) );
			$postmeta['status']        = intval( wpfval( $post, 'status' ) );
			$postmeta['private']       = intval( wpfval( $post, 'private' ) );
			$postmeta['is_first_post'] = intval( wpfval( $post, 'is_first_post' ) );
		}

		$postmeta = wpforo_array_args_cast_and_merge( (array) $postmeta, $this->default->postmeta );
		unset( $postmeta['metaid'] );

		if( is_null( $postmeta['metavalue'] ) ) $postmeta['metavalue'] = '';
		$postmeta['metavalue'] = wp_unslash( $postmeta['metavalue'] );
		if( ! is_scalar( $postmeta['metavalue'] ) ) $postmeta['metavalue'] = json_encode( (array) $postmeta['metavalue'] );

		$postmeta = wpforo_array_ordered_intersect_key( $postmeta, $this->default->postmeta_format );
		if( WPF()->db->insert(
			WPF()->tables->postmeta,
			$postmeta,
			wpforo_array_ordered_intersect_key( $this->default->postmeta_format, $postmeta )
		) ) {
			$metaid = WPF()->db->insert_id;
			do_action( 'wpforo_after_add_postmeta', $postmeta, $metaid );
			return $metaid;
		}

		return false;
	}

	/**
	 * @param array $postmeta
	 * @param array|int $where
	 *
	 * @return bool
	 */
	public function edit( $postmeta, $where ) {
		if( empty( $postmeta ) || empty( $where ) ) return false;
		if( wpforo_is_id( $where ) ) $where = [ 'metaid' => $where ];
		$postmeta = (array) $postmeta;
		$where    = (array) $where;

		if( wpfkey( $postmeta, 'metavalue' ) ) {
			if( is_null( $postmeta['metavalue'] ) ) $postmeta['metavalue'] = '';
			$postmeta['metavalue'] = wp_unslash( $postmeta['metavalue'] );
			if( ! is_scalar( $postmeta['metavalue'] ) ) $postmeta['metavalue'] = json_encode( (array) $postmeta['metavalue'] );
		}

		$postmeta = wpforo_array_ordered_intersect_key( $postmeta, $this->default->postmeta_format );
		$where    = wpforo_array_ordered_intersect_key( $where, $this->default->postmeta_format );
		if( false !== WPF()->db->update(
				WPF()->tables->postmeta,
				$postmeta,
				$where,
				wpforo_array_ordered_intersect_key( $this->default->postmeta_format, $postmeta ),
				wpforo_array_ordered_intersect_key( $this->default->postmeta_format, $where )
			) ) {
			do_action( 'wpforo_after_edit_postmeta', $postmeta, $where );
			return true;
		}

		return false;
	}

	/**
	 * @param array|int $where
	 *
	 * @return bool
	 */
	public function delete( $where ) {
		if( empty( $where ) ) return false;
		if( wpforo_is_id( $where ) ) $where = [ 'metaid' => $where ];
		$where = (array) $where;

		$where = wpforo_array_ordered_intersect_key( $where, $this->default->postmeta_format );
		if( false !== WPF()->db->delete(
				WPF()->tables->postmeta,
				$where,
				wpforo_array_ordered_intersect_key( $this->default->postmeta_format, $where )
			) ) {
			do_action( 'wpforo_after_delete_postmeta', $where );
			return true;
		}

		return false;
	}

	private function parse_args( $args ) {
		$args                       = wpforo_parse_args( $args, $this->default->sql_select_args );
		$args                       = wpforo_array_ordered_intersect_key( $args, $this->default->sql_select_args );
		$args['include']            = wpforo_parse_args( $args['include'] );
		$args['exclude']            = wpforo_parse_args( $args['exclude'] );
		$args['postids_include']    = wpforo_parse_args( $args['postids_include'] );
		$args['postids_exclude']    = wpforo_parse_args( $args['postids_exclude'] );
		$args['metakeys_include']   = wpforo_parse_args( $args['metakeys_include'] );
		$args['metakeys_exclude']   = wpforo_parse_args( $args['metakeys_exclude'] );
		$args['metavalues_include'] = wpforo_parse_args( $args['metavalues_include'] );
		$args['metavalues_exclude'] = wpforo_parse_args( $args['metavalues_exclude'] );
		$args['forumids_include']   = wpforo_parse_args( $args['forumids_include'] );
		$args['forumids_exclude']   = wpforo_parse_args( $args['forumids_exclude'] );
		$args['topicids_include']   = wpforo_parse_args( $args['topicids_include'] );
		$args['topicids_exclude']   = wpforo_parse_args( $args['topicids_exclude'] );

		return $args;
	}

	private function build_sql_select( $args, $select = '' ) {
		$args = $this->parse_args( $args );
		if( ! $select ) $select = '*';

		$wheres = [];

		if( ! is_null( $args['is_first_post'] ) ) $wheres[] = "`is_first_post` = '" . intval( $args['is_first_post'] ) . "'";
		if( ! is_null( $args['status'] ) ) $wheres[] = "`status` = '" . intval( $args['status'] ) . "'";
		if( ! is_null( $args['private'] ) ) $wheres[] = "`private` = " . intval( $args['private'] );

		if( ! empty( $args['include'] ) ) $wheres[] = "`metaid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['include'] ) ) . ")";
		if( ! empty( $args['exclude'] ) ) $wheres[] = "`metaid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['exclude'] ) ) . ")";

		if( ! empty( $args['postids_include'] ) ) $wheres[] = "`postid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postids_include'] ) ) . ")";
		if( ! empty( $args['postids_exclude'] ) ) $wheres[] = "`postid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postids_exclude'] ) ) . ")";

		if( ! empty( $args['metakeys_include'] ) ) $wheres[] = "`metakey` IN('" . implode( "','", array_map( 'trim', $args['metakeys_include'] ) ) . "')";
		if( ! empty( $args['metakeys_exclude'] ) ) $wheres[] = "`metakey` NOT IN('" . implode( "','", array_map( 'trim', $args['metakeys_exclude'] ) ) . "')";

		if( ! empty( $args['metavalues_include'] ) ) $wheres[] = "`metavalue` IN('" . implode( "','", array_map( 'trim', $args['metavalues_include'] ) ) . "')";
		if( ! empty( $args['metavalues_exclude'] ) ) $wheres[] = "`metavalue` NOT IN('" . implode( "','", array_map( 'trim', $args['metavalues_exclude'] ) ) . "')";

		if( ! is_null( $args['metavalue_like'] ) ) $wheres[] = "`metavalue` LIKE '" . esc_sql( $args['metavalue_like'] ) . "'";
		if( ! is_null( $args['metavalue_notlike'] ) ) $wheres[] = "`metavalue` NOT LIKE '" . esc_sql( $args['metavalue_notlike'] ) . "'";

		if( ! empty( $args['forumids_include'] ) ) $wheres[] = "`forumid` IN(" . implode( ',', array_map( 'intval', $args['forumids_include'] ) ) . ")";
		if( ! empty( $args['forumids_exclude'] ) ) $wheres[] = "`forumid` NOT IN(" . implode( ',', array_map( 'intval', $args['forumids_exclude'] ) ) . ")";

		if( ! empty( $args['topicids_include'] ) ) $wheres[] = "`topicid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['topicids_include'] ) ) . ")";
		if( ! empty( $args['topicids_exclude'] ) ) $wheres[] = "`topicid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['topicids_exclude'] ) ) . ")";

		$sql = "SELECT $select FROM " . WPF()->tables->postmeta;
		if( $wheres ) $sql .= " WHERE " . implode( " AND ", $wheres );
		if( $args['orderby'] ) $sql .= " ORDER BY " . $args['orderby'];
		if( $args['row_count'] ) $sql .= " LIMIT " . wpforo_bigintval( $args['offset'] ) . "," . wpforo_bigintval( $args['row_count'] );

		return $sql;
	}

	/**
	 * @param int $metaid
	 *
	 * @return array|mixed
	 */
	public function get_postmeta_by_id( $metaid ) {
		if( ! $metaid = wpforo_bigintval( $metaid ) ) return null;

		$key = 'get_postmeta_by_id_' . $metaid;
		if( WPF()->ram_cache->exists( $key ) ) return WPF()->ram_cache->get( $key );

		if( $postmeta = (array) WPF()->db->get_row( $this->build_sql_select( [ 'include' => $metaid ] ), ARRAY_A ) ) {
			$postmeta = $this->fix_postmeta( $postmeta );
		}

		WPF()->ram_cache->set( $key, $postmeta );

		return $postmeta;
	}

	/**
	 * @param int $postid
	 * @param string $metakey
	 *
	 * @return bool
	 */
	public function exists( $postid, $metakey ) {
		if( ! $metakey || ! ( $postid = wpforo_bigintval( $postid ) ) ) return false;
		$sql = "SELECT EXISTS( 
            SELECT * FROM `" . WPF()->tables->postmeta . "` 
                WHERE `postid` = %d 
                AND `metakey` = %s 
        ) AS is_exists";

		return (bool) WPF()->db->get_var( WPF()->db->prepare( $sql, $postid, $metakey ) );
	}

	/**
	 * @param array $args
	 * @param string $field
	 *
	 * @return array
	 */
	public function get_postmetas_col( $args, $field ) {
		$args = wpforo_array_ordered_intersect_key( (array) $args, $this->default->sql_select_args );
		if( empty( $args ) ) return [];

		$key = 'get_postmetas_' . json_encode( $args ) . '_' . $field;
		if( WPF()->ram_cache->exists( $key ) ) return WPF()->ram_cache->get( $key );

		$postmetas = WPF()->db->get_col( $this->build_sql_select( $args, "`$field`" ) );

		WPF()->ram_cache->set( $key, $postmetas );

		return $postmetas;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_postmetas( $args ) {
		$args = wpforo_array_ordered_intersect_key( (array) $args, $this->default->sql_select_args );
		if( empty( $args ) ) return [];

		$key = 'get_postmetas_' . json_encode( $args );
		if( WPF()->ram_cache->exists( $key ) ) return WPF()->ram_cache->get( $key );

		if( $postmetas = (array) WPF()->db->get_results( $this->build_sql_select( $args ), ARRAY_A ) ) {
			$postmetas = array_map( [ $this, 'fix_postmeta' ], $postmetas );
		}

		WPF()->ram_cache->set( $key, $postmetas );

		return $postmetas;
	}

	/**
	 * @param int $postid
	 * @param string|array $metakeys
	 * @param bool $single
	 *
	 * @return array|mixed|null
	 */
	public function get_postmeta( $postid, $metakeys = '', $single = false ) {
		if( ! $postid = wpforo_bigintval( $postid ) ) return null;
		$metakeys = array_filter( (array) $metakeys );

		$key = 'get_postmeta_' . $postid . '_' . json_encode( $metakeys ) . '_' . $single;
		if( WPF()->ram_cache->exists( $key ) ) return WPF()->ram_cache->get( $key );

		$postmeta = null;

		$args = [
			'postids_include'  => $postid,
			'metakeys_include' => $metakeys,
			'orderby'          => '`metaid` ASC',
			'row_count'        => $single && $metakeys ? 1 : null,
		];
		if( $postmetas = $this->get_postmetas( $args ) ) {
			if( count( $metakeys ) === 1 ) {
				if( $single ) {
					$first    = current( $postmetas );
					$postmeta = $first['metavalue'];
				} else {
					$postmeta = [];
					foreach( $postmetas as $p ) $postmeta[] = $p['metavalue'];
				}
			} else {
				$postmeta = [];
				foreach( $postmetas as $p ) {
					if( $single ) {
						if( ! array_key_exists( $p['metakey'], $postmeta ) ) $postmeta[ $p['metakey'] ] = $p['metavalue'];
					} else {
						$postmeta[ $p['metakey'] ][] = $p['metavalue'];
					}
				}
			}
		}

		WPF()->ram_cache->set( $key, $postmeta );

		return $postmeta;
	}

	public function search( $args ) {
		$args = array_filter( (array) $args );
		if( ! $args ) return [];

		$selects = [];
		foreach( $args as $key => $value ) {
			if( $field = WPF()->post->get_field( $key ) ) {
				$value  = (array) $value;
				$wheres = [];
				if( in_array( $field['type'], [ 'text', 'textarea', 'email', 'search', 'tel' ] ) ) {
					foreach( $value as $v ) $wheres[] = "`metavalue` LIKE '%" . esc_sql( wp_unslash( $v ) ) . "%'";
				} elseif( $field['type'] === 'checkbox' || ( $field['type'] === 'select' && wpfval( $field, 'isMultiChoice' ) ) || ( $field['type'] === 'autocomplete' && wpfval( $field, 'isMultiChoice' ) ) ) {
					foreach( $value as $v ) {
						$v        = preg_quote( preg_quote( wp_unslash( $v ) ) );
						$wheres[] = "`metavalue` REGEXP '[\\\[,]\"" . $v . "\"[,\\\]]'";
					}
				} else {
					foreach( $value as $v ) $wheres[] = "`metavalue` LIKE '" . esc_sql( wp_unslash( $v ) ) . "'";
				}
				if( $wheres ) {
					$selects[] = "SELECT `postid`, `metakey` FROM `" . WPF()->tables->postmeta . "` 
					WHERE `metakey` = '" . esc_sql( $key ) . "' AND " . implode( ' AND ', $wheres );
				}
			}
		}

		if( $selects ) {
			$sql = "SELECT `postid`, COUNT(`postid`) AS pcount FROM
			(" . implode( ' UNION ', $selects ) . ") AS pm
			GROUP BY `postid` HAVING pcount = " . count( $selects );

			return WPF()->db->get_col( $sql );
		}

		return [];
	}

	private function delete_file( $postid, $metakey ) {
		$postid = wpforo_bigintval( $postid );
		if( $postid && $metakey ) {
			if( $postmeta = $this->get_postmeta( $postid, $metakey ) ) {
				foreach( $postmeta as $file ) {
					$mediaid = (int) wpfval( $file, 'mediaid' );
					$fileurl = (string) wpfval( $file, 'fileurl' );
					$filedir = wpforo_fix_upload_dir( $fileurl );
					if( $mediaid ) wp_delete_attachment( $mediaid );
					wp_delete_file( $filedir );
				}
				$this->delete( [ 'postid' => $postid, 'metakey' => $metakey ] );
			}
		}
	}

	private function add_file( $type, $post ) {
		$postid = (int) ( wpfval( $post, 'first_postid' ) ? $post['first_postid'] : wpfval( $post, 'postid' ) );

		if( $wpftcf_delete = array_filter( (array) wpfval( $_POST, 'wpftcf_delete' ) ) ) {
			foreach( $wpftcf_delete as $metakey ) {
				$this->delete_file( $postid, $metakey );
			}
		}

		if( ! empty( wpfval( $_FILES, 'data', 'type' ) ) ) {
			$forum              = WPF()->forum->get_forum( $post['forumid'] );
			$fields_list        = WPF()->post->get_topic_fields_list( false, $forum, ! WPF()->current_userid );
			$mime_types         = wp_get_mime_types();
			$allowed_mime_types = get_allowed_mime_types();
			foreach( $_FILES['data']['type'] as $k => $mime_type ) {
				if( in_array( $k, $fields_list ) ) {
					$field = WPF()->post->get_field( $k, $type, $forum );
					$label = ( $field['label'] ?: $field['fieldKey'] );

					if( $error = intval( wpfval( $_FILES, 'data', 'error', $k ) ) ) {
						$phpFileUploadErrors = [
							0 => 'There is no error, the file uploaded with success',
							1 => 'The uploaded file size is too big',
							2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
							3 => 'The uploaded file was only partially uploaded',
							//							4 => 'No file was uploaded',
							6 => 'Missing a temporary folder',
							7 => 'Failed to write file to disk.',
							8 => 'A PHP extension stopped the file upload.',
						];
						if( $n = wpfval( $phpFileUploadErrors, $error ) ) WPF()->notice->add( $n, 'error' );
					} else {
						$name           = wpfval( $_FILES, 'data', 'name', $k );
						$tmp_name       = wpfval( $_FILES, 'data', 'tmp_name', $k );
						$ext            = pathinfo( $name, PATHINFO_EXTENSION );
						$size           = intval( $field['fileSize'] );
						$fileExtensions = array_filter( (array) is_scalar( $field['fileExtensions'] ) ? explode( ',', trim( (string) $field['fileExtensions'] ) ) : $field['fileExtensions'] );
						if( $fileExtensions ) {
							if( in_array( $ext, $fileExtensions ) ) {
								$extensions = explode( '|', array_search( $mime_type, $mime_types ) );
								$e          = in_array( $ext, $extensions );
							} else {
								$e = false;
							}
						} else {
							$extensions = explode( '|', array_search( $mime_type, $allowed_mime_types ) );
							$e          = in_array( $ext, $extensions );
						}

						if( ! empty( $e ) ) {
							if( wpfval( $_FILES, 'data', 'size', $k ) <= ( $size * 1024 * 1024 ) ) {
								$attach_dir = WPF()->folders['default_attachments']['dir'] . DIRECTORY_SEPARATOR . WPF()->current_userid;
								$attach_url = WPF()->folders['default_attachments']['url//'] . "/" . WPF()->current_userid;
								if( ! is_dir( $attach_dir ) ) wp_mkdir_p( $attach_dir );

								$fnm = pathinfo( $name, PATHINFO_FILENAME );
								$fnm = str_replace( ' ', '-', $fnm );
								while( strpos( (string) $fnm, '--' ) !== false ) $fnm = str_replace( '--', '-', $fnm );
								$fnm       = preg_replace( "/[^-a-zA-Z0-9_]/", "", (string) $fnm );
								$fnm       = trim( (string) $fnm, "-" );
								$fnm_empty = ! $fnm;

								$file_name = $fnm . "." . $ext;

								$attach_fname = time() . ( ! $fnm_empty ? '-' : '' ) . $file_name;
								$attach_path  = $attach_dir . DIRECTORY_SEPARATOR . $attach_fname;

								if( is_dir( $attach_dir ) && move_uploaded_file( $tmp_name, $attach_path ) ) {
									$this->delete_file( $postid, $field['fieldKey'] );

									$attach_id = wpforo_insert_to_media_library( $attach_path, $fnm );
									$file      = [
										'fileurl'  => $attach_url . '/' . $attach_fname,
										'filename' => basename( (string) $name ),
										'mediaid'  => $attach_id,
									];

									$postmeta = [
										'postid'        => $postid,
										'metakey'       => $field['fieldKey'],
										'metavalue'     => $file,
										'forumid'       => $post['forumid'],
										'topicid'       => $post['topicid'],
										'is_first_post' => 1,
										'status'        => $post['status'],
										'private'       => $post['private'],
									];
									$this->add( $postmeta );

								} else {
									WPF()->notice->add( 'Can\'t upload file', 'error' );
								}

							} else {
								WPF()->notice->add( '%1$s - File is too large. Maximum allowed file size is %2$s MB', 'error', [ $label, $size ] );
							}
						} else {
							WPF()->notice->add( '%1$s - File type is not allowed.', 'error', $label );
						}
					}
				}
			}
		}
	}

	public function after_add_topic( $topic, $forum ) {
		$this->add_file( 'topic', $topic );

		if( ! empty( $topic['postmetas'] ) ) {
			$fields_list = WPF()->post->get_topic_fields_list( false, $forum, ! WPF()->current_userid );
			foreach( $topic['postmetas'] as $metakey => $metavalue ) {
				if( in_array( $metakey, $fields_list ) ) {
					$postmeta = [
						'postid'        => $topic['first_postid'],
						'metakey'       => $metakey,
						'metavalue'     => $metavalue,
						'forumid'       => $topic['forumid'],
						'topicid'       => $topic['topicid'],
						'is_first_post' => 1,
						'status'        => $topic['status'],
						'private'       => $topic['private'],
					];
					$this->add( $postmeta );
				}
			}
		}
	}

	public function after_edit_topic( $topic, $args, $forum ) {
		$this->add_file( 'topic', $topic );

		if( ! empty( $args['postmetas'] ) ) {
			$fields_list = WPF()->post->get_topic_fields_list( false, $forum, ! WPF()->current_userid );
			foreach( $args['postmetas'] as $metakey => $metavalue ) {
				if( in_array( $metakey, $fields_list ) ) {
					$postmeta = [
						'metavalue'     => $metavalue,
						'forumid'       => $topic['forumid'],
						'topicid'       => $topic['topicid'],
						'is_first_post' => 1,
						'status'        => $topic['status'],
						'private'       => $topic['private'],
					];
					if( $this->exists( $topic['first_postid'], $metakey ) ) {
						$this->edit( $postmeta, [
							'postid'  => $topic['first_postid'],
							'metakey' => $metakey,
						] );
					} else {
						$postmeta['postid']  = $topic['first_postid'];
						$postmeta['metakey'] = $metakey;
						$this->add( $postmeta );
					}
				}
			}
		}
	}

	public function after_add_post( $post, $topic, $forum ) {
		$this->add_file( 'post', $post );

		if( ! empty( $post['postmetas'] ) ) {
			$fields_list = WPF()->post->get_post_fields_list( false, $forum, ! WPF()->current_userid );
			foreach( $post['postmetas'] as $metakey => $metavalue ) {
				if( in_array( $metakey, $fields_list ) ) {
					$postmeta = [
						'postid'        => $post['postid'],
						'metakey'       => $metakey,
						'metavalue'     => $metavalue,
						'forumid'       => $post['forumid'],
						'topicid'       => $post['topicid'],
						'is_first_post' => 0,
						'status'        => $post['status'],
						'private'       => $post['private'],
					];
					$this->add( $postmeta );
				}
			}
		}
	}

	public function after_edit_post( $post, $topic, $forum, $args ) {
		$this->add_file( 'post', $post );

		if( ! empty( $args['postmetas'] ) ) {
			$fields_list = WPF()->post->get_post_fields_list( false, $forum, ! WPF()->current_userid );
			foreach( $args['postmetas'] as $metakey => $metavalue ) {
				if( in_array( $metakey, $fields_list ) ) {
					$postmeta = [
						'metavalue'     => $metavalue,
						'forumid'       => $post['forumid'],
						'topicid'       => $post['topicid'],
						'is_first_post' => 0,
						'status'        => $post['status'],
						'private'       => $post['private'],
					];
					if( $this->exists( $post['postid'], $metakey ) ) {
						$this->edit( $postmeta, [
							'postid'  => $post['postid'],
							'metakey' => $metakey,
						] );
					} else {
						$postmeta['postid']  = $post['postid'];
						$postmeta['metakey'] = $metakey;
						$this->add( $postmeta );
					}
				}
			}
		}
	}

	public function after_move_topic( $topic, $forumid ) {
		$this->edit( [ 'forumid' => $forumid ], [ 'topicid' => $topic['topicid'] ] );
	}

	public function after_merge_topic( $target, $current, $postids ) {
		$sql = "UPDATE `" . WPF()->tables->postmeta . "` SET `topicid` = %d, `forumid` = %d, `private` = %d, `is_first_post` = 0 WHERE `topicid` = %d";
		$sql = WPF()->db->prepare( $sql, $target['topicid'], $target['forumid'], (int) wpfval( $target, 'private' ), $current['topicid'] );
		if( $postids ) $sql .= " AND `postid` IN(" . implode( ',', array_map( 'wpforo_bigintval', (array) $postids ) ) . ")";
		WPF()->db->query( $sql );
	}

	public function after_delete_post( $post ) {
		$this->delete( [ 'postid' => $post['postid'] ] );
	}

	public function after_post_status_update( $post, $status ) {
		$this->edit( [ 'status' => $status ], [ 'postid' => $post['postid'] ] );
	}

	public function after_topic_private_update( $topicid, $private ) {
		$this->edit( [ 'private' => $private ], [ 'topicid' => $topicid ] );
	}

	public function after_is_first_post_update( $postid, $is_first_post ) {
		$this->edit( [ 'is_first_post' => $is_first_post ], [ 'postid' => $postid ] );
	}
}
