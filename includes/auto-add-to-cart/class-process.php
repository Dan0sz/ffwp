<?php
/**
 * @package   FFWP/Auto Add To Cart
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_AutoAddToCart_Process
{
    /** @var string $plugin_text_domain */
    private $plugin_text_domain = 'ffwp';

    /** @var string $item_name */
    // TODO: Should be dynamic.
    private $item_name = 'FFWP License Manager';

    /** @var string $item_id */
    // TODO: Should be dynamic.
    private $item_id = '4163';

    /** @var int $quantity */
    private $quantity = 1;

    /** @var array $related_items */
    private $related_items = [ '4027', '3940' ];

    /** @var bool $add_to_cart */
    private $add_to_cart = false;

    /** @var string $action */
    private $action = '';

    /**
     * WooshAutoAddToCart constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Add hooks.
     */
    private function init()
    {
        add_action('edd_pre_add_to_cart', [$this, 'maybe_add_to_cart'], 10, 1);
        add_filter('edd_pre_add_to_cart_contents', [$this, 'add_item_to_cart'], 10, 1);
        add_action('edd_post_remove_from_cart', [$this, 'prevent_removal'], 10, 2);
        add_action('edd_before_checkout_cart', [$this, 'add_notice']);
    }

    /**
     * @param $download_id
     */
    public function maybe_add_to_cart($download_id)
    {
        $this->add_to_cart = in_array($download_id, $this->related_items);
    }

    /**
     * @param $contents
     *
     * @return array
     */
    public function add_item_to_cart($contents)
    {
        if (!$this->add_to_cart) {
            return $contents;
        }

        if (edd_item_in_cart($this->item_id) || $this->action == 'remove') {
            return $contents;
        }

        /**
         * For products with variable pricing you could add a 'price_id' as follows:
         * 'options' => [ 'price_id' => '2' ]
         */
        $contents[] = [
            'id'       => $this->item_id,
            'options'  => [],
            'quantity' => $this->quantity
        ];

        return $contents;
    }

    /**
     * Sets $action to 'remove' to prevent the 'init' option from adding another
     * copy of the specified $item_id.
     *
     * @param $key
     * @param $item_id
     */
    public function prevent_removal($key, $item_id)
    {
        if ($item_id != $this->item_id) {
            return;
        }

        // If no related items are in the cart, there's no need to re-add this plugin.
        foreach ($this->related_items as $item) {
            $re_add = edd_item_in_cart($item);

            if ($re_add) {
                break;
            }
        }

        if ($re_add && !edd_item_in_cart($this->item_id)) {
            $this->action = 'remove';

            edd_add_to_cart($this->item_id);
        }
    }

    /**
     * Adds a notice at the top of the checkout form to clarify why this product has been added.
     *
     * TODO: Make dynamic.
     */
    public function add_notice()
    {
        if (!edd_item_in_cart($this->item_id)) {
            return;
        }

        echo '<div class="edd-alert edd-alert-info">';
        echo '<p><em>' . sprintf(__('%s has been added to your cart, because it is required to activate and manage licenses for FFWP products.', $this->plugin_text_domain), $this->item_name) . '</em></p>';
        echo '</div>';
    }
}
