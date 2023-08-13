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
				<div class="table table_right table_totals">
			<table>
				<thead>
					<tr>
						<td colspan="2"><?php echo esc_attr( __( 'Estimated Recurring Revenue', 'edd-recurring' ) ); ?></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="t"><?php echo esc_attr( __( 'This Month', 'edd-recurring' ) ); ?></td>
						<td class="last b"><?php echo esc_attr( $this->get_estimated_revenue( date( 'm' ) ) ); ?></td>
					</tr>
					<tr>
						<td class="t"><?php echo esc_attr( __( 'Next Month', 'edd-recurring' ) ); ?></td>
						<td class="last b"><?php echo esc_attr( $this->get_estimated_revenue( date( 'm', strtotime( 'first day of +1 month' ) ) ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div style="clear: both"></div>
		<?php
	}

	/**
	 * Get revenue for current or next month.
	 */
	private function get_estimated_revenue( $month ) {
		global $wpdb;

		$this_month = $month === date( 'm' );
		$key        = 'daan_recurring_estimated_revenue_next_month';

		if ( $this_month ) {
			$key = 'daan_recurring_estimated_revenue_this_month';
		}

		$amount = get_transient( $key );

		// No transient
		if ( empty( $amount ) ) {

			// SQL
			$query = "SELECT SUM(recurring_amount)
						  FROM {$wpdb->prefix}edd_subscriptions
						  WHERE ( expiration >= %s )
                            AND ( expiration <= %s )
                            AND status = 'active'";

			if ( $this_month ) {
				$begin = date( 'Y-m-d 00:00:00', strtotime( '+1 day' ) );
				$end   = date( 'Y-m-t 00:00:00', strtotime( 'now' ) );
			} else {
				$begin = date( 'Y-m-d 00:00:00', strtotime( 'first day of +1 month' ) );
				$end   = date( 'Y-m-t 00:00:00', strtotime( '+1 month' ) );
			}

			// Query the database
			$prepared = $wpdb->prepare( $query, $begin, $end );
			$amount   = $wpdb->get_var( $prepared );

			// Cache
			set_transient( $key, $amount, DAY_IN_SECONDS );
		}

		return edd_currency_filter( edd_format_amount( edd_sanitize_amount( $amount ) ) );
	}
}
