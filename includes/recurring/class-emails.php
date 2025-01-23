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
class FFWP_Recurring_Emails {
	/**
	 * Registers a custom email tag to display a URL allowing readers to renew their license.
	 *
	 * @return void
	 */
	public function add_tag() {
		edd_add_email_tag(
			'daan_renewal_link',
			__( 'Display a URL allowing readers to renew their license.', 'ffwp' ),
			[ $this, 'replace_tag' ],
			__( 'Renewal Link', 'ffwp' ),
			[ 'subscription' ],
			[]
		);
	}

	/**
	 * Retrieves the renewal URL for a given subscription.
	 *
	 * @param int|string            $subscription_id The ID of the subscription to retrieve the renewal URL for.
	 * @param EDD_Subscription|null $subscription    Optional. An EDD_Subscription object. Default is null.
	 *
	 * @return string The renewal URL for the subscription's license, or an empty string if not found.
	 */
	public function replace_tag( $subscription_id, $subscription = null ) {
		$subscription = new EDD_Subscription( $subscription_id );

		if ( ! $subscription ) {
			return '';
		}

		$license = edd_software_licensing()->get_license_by_purchase(
			$subscription->parent_payment_id,
			$subscription->product_id
		);

		if ( ! $license ) {
			return '';
		}

		return $license->get_renewal_url();
	}
}
