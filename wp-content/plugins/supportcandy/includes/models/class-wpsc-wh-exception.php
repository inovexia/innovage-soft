<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_Wh_Exception' ) ) :

	final class WPSC_Wh_Exception {

		/**
		 * Object data in key => val pair.
		 *
		 * @var array
		 */
		private $data = array();

		/**
		 * Set whether or not current object properties modified
		 *
		 * @var boolean
		 */
		private $is_modified = false;

		/**
		 * Schema for this model
		 *
		 * @var array
		 */
		public static $schema = array();

		/**
		 * Prevent fields to modify
		 *
		 * @var array
		 */
		public static $prevent_modify = array();

		/**
		 * DB object caching
		 *
		 * @var array
		 */
		private static $cache = array();

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Apply schema for this model.
			add_action( 'init', array( __CLASS__, 'apply_schema' ), 2 );

			// Get object of this class.
			add_filter( 'wpsc_load_ref_classes', array( __CLASS__, 'load_ref_class' ) );

			// Settings section.
			add_action( 'wp_ajax_wpsc_get_wh_exceptions', array( __CLASS__, 'get_wh_exceptions' ) );
			add_action( 'wp_ajax_wpsc_get_add_wh_exception', array( __CLASS__, 'get_add_wh_exception' ) );
			add_action( 'wp_ajax_wpsc_set_add_wh_exception', array( __CLASS__, 'set_add_exception' ) );
			add_action( 'wp_ajax_wpsc_get_edit_wh_exception', array( __CLASS__, 'get_edit_wh_exception' ) );
			add_action( 'wp_ajax_wpsc_set_edit_wh_exception', array( __CLASS__, 'set_edit_wh_exception' ) );
			add_action( 'wp_ajax_wpsc_delete_wh_exception', array( __CLASS__, 'delete_wh_exception' ) );
		}

		/**
		 * Apply schema for this model
		 *
		 * @return void
		 */
		public static function apply_schema() {

			$schema       = array(
				'id'             => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'agent'          => array(
					'has_ref'          => true,
					'ref_class'        => 'wpsc_agent',
					'has_multiple_val' => false,
				),
				'title'          => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'exception_date' => array(
					'has_ref'          => true,
					'ref_class'        => 'datetime',
					'has_multiple_val' => false,
				),
				'start_time'     => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'end_time'       => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
			);
			self::$schema = apply_filters( 'wpsc_wh_exception_schema', $schema );

			// Prevent modify.
			$prevent_modify       = array( 'id' );
			self::$prevent_modify = apply_filters( 'wpsc_wh_exception_prevent_modify', $prevent_modify );
		}

		/**
		 * Model constructor
		 *
		 * @param int $id - Optional. Data record id to retrive object for.
		 */
		public function __construct( $id = 0 ) {

			global $wpdb;

			$id = intval( $id );

			if ( isset( self::$cache[ $id ] ) ) {
				$this->data = self::$cache[ $id ]->data;
				return;
			}

			if ( $id > 0 ) {

				$exception = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}psmsc_wh_exceptions WHERE id = " . $id, ARRAY_A );
				if ( ! is_array( $exception ) ) {
					return;
				}

				foreach ( $exception as $key => $val ) {
					$this->data[ $key ] = $val !== null ? $val : '';
				}

				self::$cache[ $id ] = $this;
			}
		}

		/**
		 * Magic get function to use with object arrow function
		 *
		 * @param string $var_name - variable name.
		 * @return mixed
		 */
		public function __get( $var_name ) {

			if ( ! isset( $this->data[ $var_name ] ) ||
				$this->data[ $var_name ] == null ||
				$this->data[ $var_name ] == ''
			) {
				return self::$schema[ $var_name ]['has_multiple_val'] ? array() : '';
			}

			return self::$schema[ $var_name ]['has_ref'] && $this->data[ $var_name ] ?
				WPSC_Functions::get_object( self::$schema[ $var_name ]['ref_class'], $this->data[ $var_name ] ) :
				$this->data[ $var_name ];
		}

		/**
		 * Magic function to use setting object field with arrow function
		 *
		 * @param string $var_name - (Required) property slug.
		 * @param mixed  $value - (Required) value to set for a property.
		 * @return void
		 */
		public function __set( $var_name, $value ) {

			if (
				! isset( $this->data[ $var_name ] ) ||
				in_array( $var_name, self::$prevent_modify )
			) {
				return;
			}

			$data_val = is_object( $value ) ?
				WPSC_Functions::set_object( self::$schema[ $var_name ]['ref_class'], $value ) :
				$value;

			if ( $this->data[ $var_name ] == $data_val ) {
				return;
			}

			$this->data[ $var_name ] = $data_val;
			$this->is_modified       = true;
		}

		/**
		 * Save changes made
		 *
		 * @return boolean
		 */
		public function save() {

			global $wpdb;

			if ( ! $this->is_modified ) {
				return true;
			}

			$data = $this->data;

			unset( $data['id'] );
			$success = $wpdb->update(
				$wpdb->prefix . 'psmsc_wh_exceptions',
				$data,
				array( 'id' => $this->data['id'] )
			);

			$this->is_modified        = false;
			self::$cache[ $this->id ] = $this;
			return $success ? true : false;
		}

		/**
		 * Insert new record
		 *
		 * @param array $data - insert data.
		 * @return WPSC_Wh_Exception
		 */
		public static function insert( $data ) {

			global $wpdb;

			$success = $wpdb->insert(
				$wpdb->prefix . 'psmsc_wh_exceptions',
				$data
			);

			if ( ! $success ) {
				return false;
			}

			$working_hr = new WPSC_Wh_Exception( $wpdb->insert_id );
			return $working_hr;
		}

		/**
		 * Delete record from database
		 *
		 * @param WPSC_Exception $exception - exception object.
		 * @return boolean
		 */
		public static function destroy( $exception ) {

			global $wpdb;

			$success = $wpdb->delete(
				$wpdb->prefix . 'psmsc_wh_exceptions',
				array( 'id' => $exception->id )
			);
			if ( ! $success ) {
				return false;
			}

			unset( self::$cache[ $exception->id ] );
			return true;
		}

		/**
		 * Set data to create new object using direct data. Used in find method
		 *
		 * @param array $data - data to set for object.
		 * @return void
		 */
		private function set_data( $data ) {

			foreach ( $data as $var_name => $val ) {
				$this->data[ $var_name ] = $val !== null ? $val : '';
			}
			self::$cache[ $this->id ] = $this;
		}

		/**
		 * Find records based on given filters
		 *
		 * @param array   $filter - array containing array items like search, where, orderby, order, page_no, items_per_page, etc.
		 * @param boolean $is_object - return data as array or object. Default object.
		 * @return mixed
		 */
		public static function find( $filter = array(), $is_object = true ) {

			global $wpdb;

			$sql   = 'SELECT * FROM ' . $wpdb->prefix . 'psmsc_wh_exceptions ';
			$where = self::get_where( $filter );

			$filter['items_per_page'] = isset( $filter['items_per_page'] ) ? $filter['items_per_page'] : 0;
			$filter['page_no']        = isset( $filter['page_no'] ) ? $filter['page_no'] : 0;
			$filter['orderby']        = isset( $filter['orderby'] ) ? $filter['orderby'] : 'exception_date';
			$filter['order']          = isset( $filter['order'] ) ? $filter['order'] : 'ASC';

			$order = WPSC_Functions::parse_order( $filter );

			$sql = $sql . $where . $order;
			$results = $wpdb->get_results( $sql, ARRAY_A );

			// total results.
			$sql = 'SELECT count(id) FROM ' . $wpdb->prefix . 'psmsc_wh_exceptions ';
			$total_items = $wpdb->get_var( $sql . $where );

			$response = WPSC_Functions::parse_response( $results, $total_items, $filter );

			// Return array.
			if ( ! $is_object ) {
				return $response;
			}

			// create and return array of objects.
			$temp_results = array();
			foreach ( $response['results'] as $working_hr ) {

				$ob   = new WPSC_Wh_Exception();
				$data = array();
				foreach ( $working_hr as $key => $val ) {
					$data[ $key ] = $val;
				}
				$ob->set_data( $data );
				$temp_results[] = $ob;
			}
			$response['results'] = $temp_results;

			return $response;
		}

		/**
		 * Get where for find method
		 *
		 * @param array $filter - user filter.
		 * @return array
		 */
		private static function get_where( $filter ) {

			$where = '';

			// Set user defined filters.
			$meta_query = isset( $filter['meta_query'] ) && $filter['meta_query'] ? $filter['meta_query'] : array();
			if ( $meta_query ) {
				$meta_query = WPSC_Functions::parse_user_filters( __CLASS__, $meta_query );
				$where      = $meta_query . ' ';
			}

			return $where ? 'WHERE ' . $where : '';
		}

		/**
		 * Load current class to reference classes
		 *
		 * @param array $classes - Associative array of class names indexed by its slug.
		 * @return array
		 */
		public static function load_ref_class( $classes ) {

			$classes['wpsc_wh_exception'] = array(
				'class'    => __CLASS__,
				'save-key' => 'id',
			);
			return $classes;
		}

		/**
		 * Get exceptions of agent
		 *
		 * @param int $agent_id - Agent ID. Default is company.
		 * @return array
		 */
		public static function get( $agent_id = 0 ) {

			$exceptions = self::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'agent',
							'compare' => '=',
							'val'     => $agent_id,
						),
					),
				)
			);
			$response   = array();
			foreach ( $exceptions['results'] as $key => $exceptions ) {
				$response[ $key + 1 ] = $exceptions;
			}
			return $response;
		}

		/**
		 * Get exception list
		 *
		 * @return void
		 */
		public static function get_wh_exceptions() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$unique_id  = uniqid( 'wpsc_' );
			$exceptions = self::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'agent',
							'compare' => '=',
							'val'     => 0,
						),
					),
				)
			)['results'];?>

			<div class="wpsc-dock-container">
				<?php
				printf(
					/* translators: Click here to see the documentation */
					esc_attr__( '%s to see the documentation!', 'supportcandy' ),
					'<a href="https://supportcandy.net/docs/working-hours/" target="_blank">' . esc_attr__( 'Click here', 'supportcandy' ) . '</a>'
				);
				?>
			</div>
			<table class="wpsc-setting-tbl wpsc-wh-exceptions">
				<thead>
					<tr>
						<th><?php esc_attr_e( 'Title', 'supportcandy' ); ?></th>
						<th><?php esc_attr_e( 'Date', 'supportcandy' ); ?></th>
						<th><?php esc_attr_e( 'Schedule', 'supportcandy' ); ?></th>
						<th><?php esc_attr_e( 'Actions', 'supportcandy' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $exceptions as $exception ) :
						?>
						<tr data-id="<?php echo esc_attr( $exception->id ); ?>">
							<td><?php echo esc_attr( $exception->title ); ?></td>
							<td><?php echo esc_attr( $exception->exception_date->format( 'F d, Y' ) ); ?></td>
							<td>
								<?php
								$start_time = explode( ':', $exception->start_time );
								$start_time = $start_time[0] . ':' . $start_time[1];
								$end_time   = explode( ':', $exception->end_time );
								$end_time   = $end_time[0] . ':' . $end_time[1];
								echo esc_attr(
									sprintf(
										/* translators: %1$s: start time, %2$s: end time e.g. 04:00 - 05:00 */
										esc_attr__( '%1$s - %2$s', 'supportcandy' ),
										$start_time,
										$end_time
									)
								);
								?>
							</td>
							<td>
								<a href="javascript:wpsc_get_edit_wh_exception(<?php echo esc_attr( $exception->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_edit_wh_exception' ) ); ?>');" class="wpsc-link"><?php esc_attr_e( 'Edit', 'supportcandy' ); ?></a> |
								<a href="javascript:wpsc_delete_wh_exception(<?php echo esc_attr( $exception->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_delete_wh_exception' ) ); ?>');" class="wpsc-link"><?php esc_attr_e( 'Delete', 'supportcandy' ); ?></a>
							</td>
						</tr>
						<?php
					endforeach;
					?>
				</tbody>
			</table>
			<script>
				jQuery('table.wpsc-wh-exceptions').DataTable({
					ordering: false,
					pageLength: 20,
					bLengthChange: false,
					columnDefs: [ 
						{ targets: -1, searchable: false },
						{ targets: '_all', className: 'dt-left' }
					],
					layout: {
						topStart: {
							buttons: [
								{
									text: '<?php esc_attr_e( 'Add new', 'supportcandy' ); ?>',
									className: 'wpsc-button small primary',
									action: function ( e, dt, node, config ) {

										wpsc_show_modal();
										var data = { action: 'wpsc_get_add_wh_exception' };
										jQuery.post(supportcandy.ajax_url, data, function (response) {

											// Set to modal
											jQuery('.wpsc-modal-header').text(response.title);
											jQuery('.wpsc-modal-body').html(response.body);
											jQuery('.wpsc-modal-footer').html(response.footer);
											// Display modal
											wpsc_show_modal_inner_container();
										});
									}
								}
							],
						},
					},
					language: supportcandy.translations.datatables
				});
			</script>
			<?php
			wp_die();
		}

		/**
		 * Get add new exception
		 */
		public static function get_add_wh_exception() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$title     = esc_attr__( 'Add new exception', 'supportcandy' );
			$unique_id = uniqid( 'wpsc_' );

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-frm-add-exception">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Title', 'supportcandy' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input name="title" type="text" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Date', 'supportcandy' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input class="date exception_date <?php echo esc_attr( $unique_id ); ?>" name="exception_date" type="text" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Schedule', 'supportcandy' ); ?></label>
					</div>
					<table class="wpsc-working-hrs">
						<tr>
							<td style="padding: 0 !important;">
								<select class="wpsc-wh-start-time" name="start_time">
									<?php self::get_start_time_slots( '00:00:00' ); ?>
								</select>
							</td>
							<td style="text-align: center; width:45px; padding: 0 !important;">-</td>
							<td style="padding: 0 !important;">
								<select class="wpsc-wh-end-time" name="end_time">
									<?php WPSC_Working_Hour::get_end_time_slots( '00:00:00', '00:15:00' ); ?>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<script type="text/javascript">
					var end_times = [];
					jQuery('.date.<?php echo esc_attr( $unique_id ); ?>').flatpickr({minDate:new Date});
					<?php
					$current_slot     = new DateTime( '2020-01-01 00:15:00' );
					$second_last_slot = new DateTime( '2020-01-01 23:45:00' );
					$last_slot        = new DateTime( '2020-01-01 23:59:59' );

					do {
						$time = $current_slot->format( 'H:i:s' )
						?>
						end_times.push({
							val: '<?php echo esc_attr( $time ); ?>',
							display_val: '<?php echo esc_attr( $current_slot->format( 'H:i' ) ); ?>',
						});
						<?php
						if ( $current_slot == $second_last_slot ) {
							$current_slot->add( new DateInterval( 'PT14M59S' ) );
						} else {
							$current_slot->add( new DateInterval( 'PT15M' ) );
						}
					} while ( $current_slot <= $last_slot );
					?>
					supportcandy.temp = {end_times};

					// Change event
					jQuery('.wpsc-wh-start-time').change(function(){
						var start_time = jQuery(this).val();
						var tempArr = start_time.split(":");
						var startDate = new Date(2020, 0, 1, tempArr[0], tempArr[1], tempArr[2]);
						var cmbEndTime = jQuery('.wpsc-wh-end-time');
						cmbEndTime.find('option').remove();
						jQuery.each(supportcandy.temp.end_times, function(index, end_time){
							var tempArr = end_time.val.split(":");
							var endDate = new Date(2020, 0, 1, tempArr[0], tempArr[1], tempArr[2]);
							if (startDate < endDate) {
								var obj = document.createElement('OPTION');
								var displayVal = document.createTextNode(end_time.display_val);
								obj.setAttribute("value", end_time.val);
								obj.appendChild(displayVal);
								cmbEndTime.append(obj);
							}
						});
					});
				</script>
				<input type="hidden" name="action" value="wpsc_set_add_wh_exception">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_add_wh_exception' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_add_wh_exception(this);">
				<?php esc_attr_e( 'Submit', 'supportcandy' ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php esc_attr_e( 'Cancel', 'supportcandy' ); ?>
			</button>
			<?php
			do_action( 'wpsc_get_add_priority' );
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Save new holiday
		 *
		 * @return void
		 */
		public static function set_add_exception() {

			if ( check_ajax_referer( 'wpsc_set_add_wh_exception', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			// start date.
			$exception_date = isset( $_POST['exception_date'] ) ? sanitize_text_field( wp_unslash( $_POST['exception_date'] ) ) : '';
			if ( ! $exception_date ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$flag = preg_match( '/\w{4}-\w{2}-\w{2}/', $exception_date );
			if ( $flag !== 1 ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			// start time.
			$start_time = isset( $_POST['start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['start_time'] ) ) : '';
			if ( ! $start_time ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$flag = preg_match( '/\w{2}:\w{2}:00/', $start_time );
			if ( $flag !== 1 ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			// end time.
			$default_end_time = new DateTime( '2020-01-01 ' . $start_time );
			$default_end_time->add( new DateInterval( 'PT15M' ) );
			$end_time = isset( $_POST['end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['end_time'] ) ) : $default_end_time->format( 'H:i:s' );
			if ( ! $end_time ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$flag = preg_match( '/\w{2}:\w{2}:00/', $end_time );
			if ( $flag !== 1 ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			// delete existing record for the date if exists.
			$exceptions = self::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'agent',
							'compare' => '=',
							'val'     => 0,
						),
						array(
							'slug'    => 'exception_date',
							'compare' => '=',
							'val'     => $exception_date . ' 00:00:00',
						),
					),
				)
			)['results'];
			if ( $exceptions ) {
				self::destroy( $exceptions[0] );
			}

			self::insert(
				array(
					'agent'          => 0,
					'title'          => $title,
					'exception_date' => $exception_date . ' 00:00:00',
					'start_time'     => $start_time,
					'end_time'       => $end_time,
				)
			);

			wp_die();
		}

		/**
		 * Get edit holiday modal
		 */
		public static function get_edit_wh_exception() {

			if ( check_ajax_referer( 'wpsc_get_edit_wh_exception', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['exception_id'] ) ? intval( $_POST['exception_id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$exception = new WPSC_Wh_Exception( $id );
			if ( ! $exception->id ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$title     = esc_attr__( 'Edit exception', 'supportcandy' );
			$unique_id = uniqid( 'wpsc_' );

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-frm-edit-exception">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Title', 'supportcandy' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input 
						name="title" 
						type="text" 
						value="<?php echo esc_attr( $exception->title ); ?>"
						autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Date', 'supportcandy' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input class="date exception_date <?php echo esc_attr( $unique_id ); ?>" value="<?php echo esc_attr( $exception->exception_date->format( 'Y-m-d' ) ); ?>" name="exception_date" type="text" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Schedule', 'supportcandy' ); ?></label>
					</div>
					<table class="wpsc-working-hrs">
						<tr>
							<td style="padding: 0 !important;">
								<select class="wpsc-wh-start-time" name="start_time">
									<?php self::get_start_time_slots( $exception->start_time ); ?>
								</select>
							</td>
							<td style="text-align: center; width:45px; padding: 0 !important;">-</td>
							<td style="padding: 0 !important;">
								<select class="wpsc-wh-end-time" name="end_time">
									<?php WPSC_Working_Hour::get_end_time_slots( $exception->start_time, $exception->end_time ); ?>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<script>
					var end_times = [];
					jQuery('.date.<?php echo esc_attr( $unique_id ); ?>').flatpickr();
					<?php
					$current_slot     = new DateTime( '2020-01-01 00:15:00' );
					$second_last_slot = new DateTime( '2020-01-01 23:45:00' );
					$last_slot        = new DateTime( '2020-01-01 23:59:59' );

					do {
						$time = $current_slot->format( 'H:i:s' )
						?>
						end_times.push({
							val: '<?php echo esc_attr( $time ); ?>',
							display_val: '<?php echo esc_attr( $current_slot->format( 'H:i' ) ); ?>',
						});
						<?php
						if ( $current_slot == $second_last_slot ) {
							$current_slot->add( new DateInterval( 'PT14M59S' ) );
						} else {
							$current_slot->add( new DateInterval( 'PT15M' ) );
						}
					} while ( $current_slot <= $last_slot );
					?>
					supportcandy.temp = {end_times};

					// Change event
					jQuery('.wpsc-wh-start-time').change(function(){
						var start_time = jQuery(this).val();
						var tempArr = start_time.split(":");
						var startDate = new Date(2020, 0, 1, tempArr[0], tempArr[1], tempArr[2]);
						var cmbEndTime = jQuery('.wpsc-wh-end-time');
						cmbEndTime.find('option').remove();
						jQuery.each(supportcandy.temp.end_times, function(index, end_time){
							var tempArr = end_time.val.split(":");
							var endDate = new Date(2020, 0, 1, tempArr[0], tempArr[1], tempArr[2]);
							if (startDate < endDate) {
								var obj = document.createElement('OPTION');
								var displayVal = document.createTextNode(end_time.display_val);
								obj.setAttribute("value", end_time.val);
								obj.appendChild(displayVal);
								cmbEndTime.append(obj);
							}
						});
					});
				</script>
				<input type="hidden" name="action" value="wpsc_set_edit_wh_exception">
				<input type="hidden" name="exception_id" value="<?php echo esc_attr( $exception->id ); ?>">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_edit_wh_exception' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_edit_wh_exception(this);">
				<?php esc_attr_e( 'Submit', 'supportcandy' ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php esc_attr_e( 'Cancel', 'supportcandy' ); ?>
			</button>
			<?php
			do_action( 'wpsc_get_add_priority' );
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Set edit holiday
		 */
		public static function set_edit_wh_exception() {

			if ( check_ajax_referer( 'wpsc_set_edit_wh_exception', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['exception_id'] ) ? intval( $_POST['exception_id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$exception = new WPSC_Wh_Exception( $id );
			if ( ! $exception->id ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$exception->title = $title;

			// start date.
			$exception_date = isset( $_POST['exception_date'] ) ? sanitize_text_field( wp_unslash( $_POST['exception_date'] ) ) : '';
			if ( ! $exception_date ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$flag = preg_match( '/\w{4}-\w{2}-\w{2}/', $exception_date );
			if ( $flag !== 1 ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$exception->exception_date = $exception_date . ' 00:00:00';

			// start time.
			$start_time = isset( $_POST['start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['start_time'] ) ) : '';
			if ( ! $start_time ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$flag = preg_match( '/\w{2}:\w{2}:00/', $start_time );
			if ( $flag !== 1 ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$exception->start_time = $start_time;

			// end time.
			$default_end_time = new DateTime( '2020-01-01 ' . $start_time );
			$default_end_time->add( new DateInterval( 'PT15M' ) );
			$end_time = isset( $_POST['end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['end_time'] ) ) : $default_end_time->format( 'H:i:s' );
			if ( ! $end_time ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$flag = preg_match( '/\w{2}:\w{2}:00/', $end_time );
			if ( $flag !== 1 ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$exception->end_time = $end_time;

			$exception->save();
			wp_die();
		}

		/**
		 * Delete holiday
		 */
		public static function delete_wh_exception() {

			if ( check_ajax_referer( 'wpsc_delete_wh_exception', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['exception_id'] ) ? intval( $_POST['exception_id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$exception = new WPSC_Wh_Exception( $id );
			if ( ! $exception->id ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			self::destroy( $exception );
			wp_die();
		}

		/**
		 * Get start time slots
		 *
		 * @param string $start_time - start time string.
		 * @return void
		 */
		public static function get_start_time_slots( $start_time ) {

			$current_slot = new DateTime( '2020-01-01 00:00:00' );
			$last_slot    = new DateTime( '2020-01-01 23:45:00' );
			do {

				$time = $current_slot->format( 'H:i:s' );
				?>
				<option <?php selected( $time, $start_time ); ?> value="<?php echo esc_attr( $time ); ?>"><?php echo esc_attr( $current_slot->format( 'H:i' ) ); ?></option>
				<?php
				$current_slot->add( new DateInterval( 'PT15M' ) );

			} while ( $current_slot <= $last_slot );
		}

		/**
		 * Get exception by date and agent id
		 *
		 * @param DateTime $date - datetime object.
		 * @param integer  $agent_id - agent id.
		 * @return mixed
		 */
		public static function get_exception_by_date( $date, $agent_id = 0 ) {

			$exceptions = self::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'agent',
							'compare' => '=',
							'val'     => $agent_id,
						),
						array(
							'slug'    => 'exception_date',
							'compare' => '=',
							'val'     => $date->format( 'Y-m-d H:i:s' ),
						),
					),
				)
			);

			return $exceptions['results'] ? $exceptions['results'][0] : false;
		}
	}
endif;

WPSC_Wh_Exception::init();
