<?php
/**
 * @package   FFWP Login Fields Legend
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_LoginFieldsLegend_Insert
{
    /** @var string $plugin_text_domain */
    private $plugin_text_domain = 'ffwp';

    /**
     * FFWP_LoginFieldsLegend_Insert constructor.
     */
    public function __construct()
    {
        add_action('edd_checkout_login_fields_before', [$this, 'insert']);
    }

    /**
     *
     */
    public function insert()
    {
        ?>
        <legend><?= __('Have an Account?', $this->plugin_text_domain); ?></legend>
        <p>
            <span class="edd-description"><?= __("Don't have an account? No worries. An account will be automatically created for you upon completion of your purchase.", $this->plugin_text_domain); ?></span>
        </p>
        <?php
    }
}