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
	 * @var string[] $timespans
	 */
	private $timespans = [
		'Tomorrow',
		'This Week',
		'Next Week',
		'This Month',
		'Next Month',
		'This Quarter',
		'Next Quarter',
		'This Year',
	];

	/**
	 * Render the stats.
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
				<?php foreach ( $this->timespans as $timespan ): ?>
                    <tr>
                        <td class="t"><?php echo esc_attr( $timespan ); ?></td>
                        <td class="last b"><?php echo esc_attr(
								$this->get_estimated( str_replace( ' ', '_', strtolower( $timespan ) ), 'sales' )
							); ?></td>
                    </tr>
				<?php endforeach; ?>
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
				<?php foreach ( $this->timespans as $timespan ): ?>
                    <tr>
                        <td class="t"><?php echo esc_attr( $timespan ); ?></td>
                        <td class="last b"><?php echo esc_attr(
								$this->get_estimated( str_replace( ' ', '_', strtolower( $timespan ) ) )
							); ?></td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="clear: both"></div>
		<?php
	}

	/**
	 * Get revenue for current or next month.
	 *
	 * @param string $period  Allowed values: this_month, this_week, next_month, tomorrow
	 * @param string $revenue revenue | sales
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
				case 'tomorrow':
					$begin = date( 'Y-m-d 00:00:00', strtotime( '+1 day' ) );
					$end   = date( 'Y-m-d 23:59:59', strtotime( '+1 day' ) );

					break;
				case 'this_week':
					$begin = date( 'Y-m-d 00:00:00', strtotime( 'now ' ) );
					$end   = date( 'Y-m-d 23:59:59', strtotime( 'next sunday' ) );

					break;
				case 'next_week':
					$begin = date( 'Y-m-d 00:00:00', strtotime( 'next monday' ) );
					$end   = date( 'Y-m-d 23:59:59', strtotime( 'next monday +1 week' ) );

					break;
				case 'this_month':
					$begin = date( 'Y-m-d 00:00:00', strtotime( 'now' ) );
					$end   = date( 'Y-m-t 23:59:59', strtotime( 'now' ) );

					break;
				case 'next_month':
					$begin = date( 'Y-m-d 00:00:00', strtotime( 'first day of +1 month' ) );
					$end   = date( 'Y-m-t 23:59:59', strtotime( 'last day of +1 month' ) );

					break;
				case 'this_quarter':
					$current_quarter = ceil( date( 'n' ) / 3 );
					$begin           = date( 'Y-m-d 00:00:00', strtotime( 'now' ) );
					$end             = date(
						'Y-m-t 23:59:59',
						strtotime( date( 'Y' ) . '-' . ( ( $current_quarter * 3 ) ) . '-1' )
					);

					break;
				case 'next_quarter':
					$next_quarter = (int) ceil( date( 'n' ) / 3 ) + 1;
					$year         = date( 'Y', strtotime( 'now' ) );

					if ( $next_quarter === 5 ) {
						$next_quarter = 1;
						$year ++;
					}

					$begin = date( "$year-m-d 00:00:00", strtotime( date( 'Y' ) . '-' . ( ( $next_quarter * 3 ) - 2 ) . '-1' ) );
					$end   = date( "$year-m-t 23:59:59", strtotime( date( 'Y' ) . '-' . ( ( $next_quarter * 3 ) ) ) );

					break;
				case 'this_year':
					$begin = date( 'Y-m-d 00:00:00', strtotime( 'now' ) );
					$end   = date( 'Y-12-31 23:59:59', strtotime( 'now' ) );

					break;
			}

			// Query the database
			$prepared = $wpdb->prepare( $query, $begin, $end );
			$amount   = $wpdb->get_var( $prepared );

			// Cache
			set_transient( $key, $amount, 300 );
		}

		if ( $type === 'revenue' ) {
			return edd_currency_filter( edd_format_amount( edd_sanitize_amount( $amount ) ) );
		}

		return $amount;
	}
}
