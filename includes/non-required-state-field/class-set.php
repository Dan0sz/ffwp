<?php
/**
 * @package   FFWP/WooshNonRequiredStateField
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_NonRequiredStateField_Set
{
    /**
     * WooshNonRequiredStateField constructor.
     */
    public function __construct()
    {
        add_filter('edd_purchase_form_required_fields', [$this, 'remove_state_from_required_fields']);
    }

    /**
     * @param $fields
     *
     * @return array
     */
    public function remove_state_from_required_fields($fields)
    {
        if (array_key_exists('card_state', $fields)) {
            unset($fields['card_state']);
        }

        return $fields;
    }
}
