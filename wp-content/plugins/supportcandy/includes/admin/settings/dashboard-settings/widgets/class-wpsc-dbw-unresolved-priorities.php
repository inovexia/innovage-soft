<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_DBW_Unresolved_Priorities' ) ) :

	final class WPSC_DBW_Unresolved_Priorities {

		/**
		 * Widget slug
		 *
		 * @var string
		 */
		public static $widget = 'unresolved-priorities';

		/**
		 * Initialize this class
		 */
		public static function init() {

			add_action( 'wp_ajax_wpsc_priority_pie_chart', array( __CLASS__, 'priority_pie_chart' ) );
			add_action( 'wp_ajax_nopriv_wpsc_priority_pie_chart', array( __CLASS__, 'priority_pie_chart' ) );
		}

		/**
		 * Statues of unresolved tickets
		 *
		 * @param $slug   $slug - slug name.
		 * @param $widget $widget - widget array.
		 * @return void
		 */
		public static function print_dashboard_widget( $slug, $widget ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_guest ||
				! ( $current_user->is_agent && in_array( $current_user->agent->role, $widget['allowed-agent-roles'] ) )
			) {
				return;
			}
			$db_gs = get_option( 'wpsc-db-gs-settings' );
			?>
			<div class="wpsc-dash-widget wpsc-dash-widget-mid wpsc-<?php echo esc_attr( $slug ); ?>">
				<div class="wpsc-dash-widget-header">
					<div class="wpsc-dashboard-widget-icon-header">
						<?php WPSC_Icons::get( 'pie-chart' ); ?>
						<span>
							<?php
							$title = $widget['title'] ? WPSC_Translations::get( 'wpsc-dashboard-widget-' . $slug, stripslashes( htmlspecialchars( $widget['title'] ) ) ) : stripslashes( htmlspecialchars( $widget['title'] ) );
							echo esc_attr( $title );
							?>
						</span>
					</div>
					<div class="wpsc-dash-widget-actions">
						<select name="" id="date_wise_priority_report" onchange="wpsc_priority_pie_chart();" style="min-height: 18px !important;max-height: 18px !important;line-height: 15px !important;font-size: 12px !important;">
							<option <?php selected( $db_gs['default-date-range'], 'today' ); ?> value="today"><?php esc_attr_e( 'Today', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'yesterday' ); ?> value="yesterday"><?php esc_attr_e( 'Yesterday', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'last-7' ); ?> value="last-7"><?php esc_attr_e( 'Last 7 days', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'this-week' ); ?> value="this-week"><?php esc_attr_e( 'This week', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'last-week' ); ?> value="last-week"><?php esc_attr_e( 'Last week', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'last-30-days' ); ?> value="last-30-days"><?php esc_attr_e( 'Last 30 days', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'this-month' ); ?> value="this-month"><?php esc_attr_e( 'This month', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'last-month' ); ?> value="last-month"><?php esc_attr_e( 'Last month', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'this-quarter' ); ?> value="this-quarter"><?php esc_attr_e( 'This quarter', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'last-quarter' ); ?> value="last-quarter"><?php esc_attr_e( 'Last quarter', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'this-year' ); ?> value="this-year"><?php esc_attr_e( 'This year', 'supportcandy' ); ?></option>
							<option <?php selected( $db_gs['default-date-range'], 'last-year' ); ?> value="last-year"><?php esc_attr_e( 'Last year', 'supportcandy' ); ?></option>
						</select>
					</div>
				</div>
				<div class="wpsc-dash-widget-content wpsc-dbw-line-graph" id="wpsc-dash-widget-content-priority-chart"></div>
			</div>
			<script>
				wpsc_priority_pie_chart();
				function wpsc_priority_pie_chart() {
					jQuery( '#wpsc-dash-widget-content-priority-chart' ).html( supportcandy.loader_html );
					var date_range = jQuery('#date_wise_priority_report').val();
					var data = { action: 'wpsc_priority_pie_chart', date_range, _ajax_nonce: supportcandy.nonce };
					jQuery.post(
						supportcandy.ajax_url,
						data,
						function (response) {
							jQuery('#wpsc-dash-widget-content-priority-chart').html(response.chart);
						}
					);
				}
			</script>
			<?php
		}

		/**
		 * Priority pie chart
		 *
		 * @return void
		 */
		public static function priority_pie_chart() {

			if ( check_ajax_referer( 'general', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$range = isset( $_POST['date_range'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range'] ) ) : '';
			if ( ! $range ) {
				wp_send_json_error( 'Something went wrong', 400 );
			}

			$current_user = WPSC_Current_User::$current_user;
			$widgets = get_option( 'wpsc-dashboard-widgets', array() );
			if ( $current_user->is_guest ||
				! ( $current_user->is_agent && in_array( $current_user->agent->role, $widgets[ self::$widget ]['allowed-agent-roles'] ) )
			) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			// calculate date range.
			$date_range = WPSC_Functions::get_dashboard_date_range( $range );
			$priorities = WPSC_Priority::find( array( 'items_per_page' => 0 ) );

			$priority_names = array();
			$random_color = array();
			$total_tickets = array();
			$filters = array();
			foreach ( $priorities['results'] as $priority ) {
				$priority_names[] = '"' . $priority->name . '"';
				$random_color[] = '"' . $priority->bg_color . '"';

				$args = array(
					'items_per_page' => 0,
					'system_query'   => $current_user->get_tl_system_query( $filters ),
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'slug'    => 'priority',
							'compare' => '=',
							'val'     => $priority->id,
						),
					),
				);

				// remove meta query if filter is selected as 'all'.
				if ( $range != 'all' ) {
					$args['meta_query'][] = array(
						'slug'    => 'date_created',
						'compare' => 'BETWEEN',
						'val'     => array(
							'operand_val_1' => $date_range[0],
							'operand_val_2' => $date_range[1],
						),
					);
				}
				$total_tickets[] = WPSC_Ticket::find( $args )['total_items'];
			}
			ob_start();
			?>
			<div class="graph-container">
				<div id="priority-pie-chart">
					<canvas id="priorityPieChart" style="height: 350px;"></canvas>
				</div>
			</div>
			<script>
				<?php
				if ( $widgets[ self::$widget ]['chart-type'] == 'pie' ) {
					?>
					// Insert dynamic data
					var data = {
						labels: [<?php echo wp_kses_post( implode( ',', $priority_names ) ); ?>],
						datasets: [{
							data: [<?php echo wp_kses_post( implode( ',', $total_tickets ) ); ?>],
							backgroundColor: [<?php echo wp_kses_post( implode( ',', $random_color ) ); ?>]
						}]
					};

					// Check if there is at least one non-zero value in the data array
					if (data.datasets[0].data.some(function (value) {
						return value !== 0;
					})) {
						// Get the canvas element and render the pie chart
						var ctx = document.getElementById("priorityPieChart").getContext('2d');
						var myPieChart = new Chart(ctx, {
							type: 'pie',
							data: data,
							options: {
								responsive: true,
								maintainAspectRatio: false
							}
						});
					} else {
						jQuery('#priority-pie-chart').html('<?php echo esc_attr__( 'Record not found!', 'supportcandy' ); ?>');
					}
					<?php
				} elseif ( $widgets[ self::$widget ]['chart-type'] == 'doughnut' ) {
					?>
					// Insert dynamic data
					var data = {
						labels: [<?php echo wp_kses_post( implode( ',', $priority_names ) ); ?>],
						datasets: [{
							data: [<?php echo wp_kses_post( implode( ',', $total_tickets ) ); ?>],
							backgroundColor: [<?php echo wp_kses_post( implode( ',', $random_color ) ); ?>]
						}]
					};


					// Check if there is at least one non-zero value in the data array
					if (data.datasets[0].data.some(function (value) {
						return value !== 0;
					})) {
						// Get the canvas element and render the pie chart
						var ctx = document.getElementById("priorityPieChart").getContext('2d');
						var myPieChart = new Chart(ctx, {
							type: 'doughnut',
							data: data,
							options: {
								responsive: true,
								maintainAspectRatio: false,
								cutout: '50%',
							}
						});
					} else {
						jQuery('#priority-pie-chart').html('<?php echo esc_attr__( 'Record not found!', 'supportcandy' ); ?>');
					}
					<?php
				} elseif ( $widgets[ self::$widget ]['chart-type'] == 'horizontal-bar' ) {
					?>
					var data   = {
						labels: [<?php echo wp_kses_post( implode( ',', $priority_names ) ); ?>],
						datasets: [
							{
								label: '',
								backgroundColor: [<?php echo wp_kses_post( implode( ',', $random_color ) ); ?>],
								borderColor: [<?php echo wp_kses_post( implode( ',', $random_color ) ); ?>],
								borderWidth: 1,
								data: [<?php echo wp_kses_post( implode( ',', $total_tickets ) ); ?>]
							}
						]
					};
					var config = {
						type: 'bar',
						data,
						options: {
							plugins: {
								legend: {
									display: false
								}
							},
							indexAxis: 'y',
							responsive: true,
							maintainAspectRatio: false,
							scales: {
								x: {
									beginAtZero: true,
								}
							}
						}
					};
					new Chart(
						document.getElementById( 'priorityPieChart' ),
						config
					);
					<?php
				} elseif ( $widgets[ self::$widget ]['chart-type'] == 'vertical-bar' ) {
					?>
					var data   = {
						labels: [<?php echo wp_kses_post( implode( ',', $priority_names ) ); ?>],
						datasets: [
							{
								label: '',
								backgroundColor: [<?php echo wp_kses_post( implode( ',', $random_color ) ); ?>],
								borderColor: [<?php echo wp_kses_post( implode( ',', $random_color ) ); ?>],
								borderWidth: 1,
								data: [<?php echo wp_kses_post( implode( ',', $total_tickets ) ); ?>]
							}
						]
					};
					var config = {
						type: 'bar',
						data,
						options: {
							plugins: {
								legend: {
									display: false
								}
							},
							responsive: true,
							maintainAspectRatio: false,
							scales: {
								y: {
									beginAtZero: true,
								}
							}
						}
					};
					new Chart(
						document.getElementById( 'priorityPieChart' ),
						config
					);
					<?php
				}
				?>
			</script>
			<?php
			$chart = ob_get_clean();
			wp_send_json( array( 'chart' => $chart ) );
		}

		/**
		 * Get edit dashboard widget values
		 *
		 * @param Array $card - card array.
		 * @return void
		 */
		public static function get_edit_dbw_properties( $card ) {

			?>
			<div class="wpsc-input-group">
				<div class="label-container">
					<label for=""><?php esc_attr_e( 'Chart Type', 'supportcandy' ); ?></label>
				</div>
				<select class="wpsc-chart-type" name="chart-type">
					<option <?php selected( $card['chart-type'], 'pie' ); ?> value="pie"><?php esc_attr_e( 'Pie', 'supportcandy' ); ?></option>
					<option <?php selected( $card['chart-type'], 'doughnut' ); ?> value="doughnut"><?php esc_attr_e( 'Doughnut', 'supportcandy' ); ?></option>
					<option <?php selected( $card['chart-type'], 'horizontal-bar' ); ?> value="horizontal-bar"><?php esc_attr_e( 'Horizontal Bar', 'supportcandy' ); ?></option>
					<option <?php selected( $card['chart-type'], 'vertical-bar' ); ?> value="vertical-bar"><?php esc_attr_e( 'Vertical Bar', 'supportcandy' ); ?></option>
				</select>
			</div>
			<?php
		}
	}
endif;
WPSC_DBW_Unresolved_Priorities::init();
