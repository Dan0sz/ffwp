<?php
defined( 'ABSPATH' ) || exit;
/**
 * @package   FFWP Recurring
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */
class FFWP_Recurring_SummaryWidget {
	/**
	 * Render the stats.
	 *
	 * @return void
	 */
	public function add_stats() {
		?>
		<div class="table table_left table_totals">
			<table>
				<thead>
					<tr>
						<td colspan="2"><?php echo esc_attr( __( 'Upcoming Recurring Sales', 'edd-recurring' ) ); ?></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="t"><?php echo esc_attr( __( 'Tomorrow', 'edd-recurring' ) ); ?></td>
						<td class="last b"><?php echo esc_attr( $this->get_estimated( 'tomorrow', 'sales' ) ); ?></td>
					</tr>
					<tr>
						<td class="t"><?php echo esc_attr( __( 'This Month', 'edd-recurring' ) ); ?></td>
						<td class="last b"><?php echo esc_attr( $this->get_estimated( 'this_month', 'sales' ) ); ?></td>
					</tr>
					<tr>
						<td class="t"><?php echo esc_attr( __( 'Next Month', 'edd-recurring' ) ); ?></td>
						<td class="last b"><?php echo esc_attr( $this->get_estimated( 'next_month', 'sales' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="table table_right table_totals">
			<table>
				<thead>
					<tr>
						<td colspan="2"><?php echo esc_attr( __( 'Upcoming Recurring Revenue', 'edd-recurring' ) ); ?></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="t"><?php echo esc_attr( __( 'Tomorrow', 'edd-recurring' ) ); ?></td>
						<td class="last b"><?php echo esc_attr( $this->get_estimated( 'tomorrow' ) ); ?></td>
					</tr>
					<tr>
						<td class="t"><?php echo esc_attr( __( 'This Month', 'edd-recurring' ) ); ?></td>
						<td class="last b"><?php echo esc_attr( $this->get_estimated( 'this_month' ) ); ?></td>
					</tr>
					<tr>
						<td class="t"><?php echo esc_attr( __( 'Next Month', 'edd-recurring' ) ); ?></td>
						<td class="last b"><?php echo esc_attr( $this->get_estimated( 'next_month' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div style="clear: both"></div>
		<?php
	}

	/**
	 * Get revenue for current or next month.
	 *
	 * @param string $period Allowed values: this_month, next_month, tomorrow
	 */
	private function get_estimated( $period = 'this_month', $type = 'revenue' ) {
		global $wpdb;

		$key    = 'daan_recurring_estimated_' . $type . '_' . $period;
		$amount = get_transient( $key );

		// No transient
		if ( empty( $amount ) ) {
			$command = $type === 'revenue' ? 'SUM(recurring_amount)' : 'COUNT(*)';

			// SQL
			$query = "SELECT $command
						  FROM {$wpdb->prefix}edd_subscriptions
						  WHERE ( expiration >= %s )
                            AND ( expiration <= %s )
                            AND status = 'active'";

			switch ( $period ) {
				case 'this_month':
					$begin = date( 'Y-m-d 00:00:00', strtotime( 'now' ) );
					$end   = date( 'Y-m-t 23:59:59', strtotime( 'now' ) );

					break;
				case 'next_month':
					$begin = date( 'Y-m-d 00:00:00', strtotime( 'first day of +1 month' ) );
					$end   = date( 'Y-m-t 23:59:59', strtotime( 'last day of +1 month' ) );

					break;
				case 'tomorrow':
					$begin = date( 'Y-m-d 00:00:00', strtotime( '+1 day' ) );
					$end   = date( 'Y-m-d 23:59:59', strtotime( '+1 day' ) );

					break;
			}

			// Query the database
			$prepared = $wpdb->prepare( $query, $begin, $end );
			$amount   = $wpdb->get_var( $prepared );

			// Cache
			set_transient( $key, $amount, HOUR_IN_SECONDS * 2 );
		}

		if ( $type === 'revenue' ) {
			return edd_currency_filter( edd_format_amount( edd_sanitize_amount( $amount ) ) );
		}

		return $amount;
	}
}
