<?php
defined('ABSPATH') || exit;

/**
 * @package   FFWP Recurring
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */
class FFWP_Recurring_Emails
{
    /**
     * Hooks and filters
     * 
     * @return void 
     */
    public function filter_tag($text, $subscription_id)
    {
        // We can't do anything here, unless we have a subscription ID.
        if (!$subscription_id) {
            return $text;
        }

        $subscription = new EDD_Subscription($subscription_id);

        if (!$subscription) {
            return $text;
        }

        $license = edd_software_licensing()->get_license_by_purchase($subscription->parent_payment_id, $subscription->product_id);

        if (!$license) {
            return $text;
        }

        $text = str_replace('{daan_renewal_link}', $license->get_renewal_url(), $text);

        return $text;
    }
}
