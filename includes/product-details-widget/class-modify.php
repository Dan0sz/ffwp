<?php
defined('ABSPATH') || exit;

/**
 * @package   FFWP DownloadInfo Shortcodes
 * @author    Daan van den Bergh
 *            https://ffw.press
 *            https://daan.dev
 * @copyright © 2021 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

class FFWP_ProductDetailsWidget_Modify
{
    /**
     * @var Downloads_As_Service $das 
     */
    private $das;

    /**
     * @var string $changelog 
     */
    private $changelog;

    /**
     * @var array $recurring_amounts Array containing amounts that belong to a recurring license. 
     */
    private $recurring_amounts;

    /**
     * Stores the amount of signup discounts shown on current product page.
     *
     * @var int
     */
    private $signup_discount = 0;

    /**
     * @var string $plugin_text_domain 
     */
    private $plugin_text_domain = 'ffwp';

    /**
     * Build class properties
     * 
     * @return void 
     */
    public function __construct()
    {
        if (class_exists('EDD_Downloads_As_Services')) {
            $this->das = new EDD_Downloads_As_Services();
        }

        if ($this->das) {
            $this->init();
        }
    }

    /**
     * Add hooks and filters.
     * 
     * @return void 
     */
    private function init()
    {
        add_filter('widget_title', [$this, 'modify_widget_title'], 10);

        add_filter('edd_purchase_variable_prices', [$this, 'save_recurring_license_amounts'], 10, 2);
        add_filter('edd_format_amount', [$this, 'add_recurring_label_to_price'], 10, 5);
        add_action('edd_after_price_options', [$this, 'add_vat_notice']);

        // Begin table
        add_action(
            'edd_product_details_widget_before_categories_and_tags', function () {
                if (!$this->das->is_service(get_the_ID())) {
                    echo '<table class="ffw-download-details"><tbody>';
                }
            }, 9
        );

        // Table content
        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_current_version'], 10, 2);
        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_changelog_link'], 11, 2);
        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_date_last_updated'], 12, 2);

        // End table
        add_action(
            'edd_product_details_widget_before_categories_and_tags', function () {
                if (!$this->das->is_service(get_the_ID())) {
                    echo '</tbody></table>';
                }
            }, 12
        );

        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_changelog_popup'], 13);

        add_action('wp_footer', [$this, 'add_inline_script']);
    }

    /**
     * Modifies widget title when download is a service.
     */
    public function modify_widget_title($title)
    {
        if ($title != 'Choose Your License') {
            return $title;
        }

        /**
         * Don't display a title if download is a service.
         */
        if ($this->das->is_service(get_the_ID())) {
            $title = __('Get a Quote', $this->plugin_text_domain);
        }

        return $title;
    }

    /**
     * Save all amounts belonging to recurring licenses for later processing.
     * 
     * @see self::add_recurring_label_to_price()
     * 
     * @param array $prices 
     * @param int   $download_id 
     * 
     * @return array 
     */
    public function save_recurring_license_amounts($prices, $download_id)
    {
        foreach ($prices as $key => $price) {
            if (!isset($price['amount']) || $price['amount'] <= 0
                || !isset($price['recurring']) || $price['recurring'] == 'no'
            ) {
                continue;
            }

            $this->recurring_amounts[$key]['amount']          = $price['amount'];
            $this->recurring_amounts[$key]['period']          = $price['period'];
            $this->recurring_amounts[$key]['signup_discount'] = $price['signup_fee'];
        }

        return $prices;
    }

    /**
     * Modify price label to display 
     * 
     * @param mixed $formatted 
     * @param mixed $amount 
     * @param mixed $decimals 
     * @param mixed $decimal_sep 
     * @param mixed $thousands_sep 
     * 
     * @return mixed 
     */
    public function add_recurring_label_to_price($formatted, $amount, $decimals, $decimal_sep, $thousands_sep)
    {
        /**
         * Do not run this in the admin area.
         */
        if (is_admin()) {
            return $formatted;
        }

        /**
         * Replace ',00' with ',-'.
         */
        $formatted = str_replace($decimal_sep . '00', $decimal_sep . '-', $formatted);

        if (!$this->recurring_amounts) {
            return $formatted;
        }

        $current_amount = [];

        foreach ($this->recurring_amounts as $recurring_amount) {
            if ($amount == $recurring_amount['amount']) {
                $current_amount = $recurring_amount;

                break;
            }
        }

        if (empty($current_amount)) {
            return $formatted;
        }

        /**
         * This isn't a discount, it's a fee. So, we're not going to show it up front.
         * We're still going to add the renewal period though.
         */
        if ((float) $current_amount['signup_discount'] > 0) {
            return $formatted . '<small>/' . $current_amount['period'] . '*</small>';
        }
        
        $no_discount = (float) $current_amount['signup_discount'] == 0;
        
        if (!$no_discount) {
            $this->signup_discount++;

            $formatted = "<span class='edd-former-price'>$formatted</span> ";
            $amount    = (float) $amount + (float) $current_amount['signup_discount'];
            $formatted .= edd_currency_filter(number_format($amount, $decimals, $decimal_sep, $thousands_sep));
            return str_replace($decimal_sep . '00', $decimal_sep . '-', $formatted) . '<small>/' . $current_amount['period'] . '*</small>';
        }

        return str_replace($decimal_sep . '00', $decimal_sep . '-', $formatted) . '<small>/' . $current_amount['period'] . '</small>';
    }

    /**
     * Insert custom VAT notice above 'Add to cart' button
     * 
     * @return void 
     */
    public function add_vat_notice()
    {
        ?>
        <span class="edd_price_additional_info">
            <small>
                <?php if ($this->signup_discount > 0) : ?>
                * <?php echo __('Renews at regular rate', 'ffwp'); ?><br />
                <?php endif; ?>
                <?php echo __('excl. VAT for EU residents', 'ffwp'); ?>
            </small>
        </span>
        <?php
    }

    /**
     * Add current version to Product Details widget.
     * 
     * @param  mixed $instance 
     * @param  mixed $download_id 
     * @return void 
     */
    public function add_current_version($instance, $download_id)
    {
        $current_version = get_post_meta($download_id, '_edd_sl_version', true) ?? '';
        if ($current_version) : ?>
            <tr>
                <td><?php echo __('Current version', $this->plugin_text_domain); ?></td>
                <td><span itemscope itemtype="https://schema.org/version"><?php echo sprintf(__('%s', $this->plugin_text_domain), $current_version); ?></span></td>
            </tr>
        <?php endif;
    }

    /**
     * @param  mixed $instance 
     * @param  mixed $download_id 
     * @return void 
     */
    public function add_changelog_link($instance, $download_id)
    {
        $this->changelog = get_post_meta($download_id, '_edd_sl_changelog', true) ?? '';

        if ($this->changelog && !$this->das->is_service($download_id)) : ?>
            <tr>
                <td><?php echo __('Changelog', $this->plugin_text_domain); ?></td>
                <td><?php echo __('<a href="#" id="ffw-changelog-link">View</a>', $this->plugin_text_domain); ?></td>
            </tr>
        <?php endif;
    }

    /**
     * Get Readme Location defined in EDD Download.
     * 
     * @param  mixed $download_id 
     * @return mixed 
     */
    public function get_changelog_url($download_id)
    {
        return get_post_meta($download_id, '_edd_readme_location', true) ?? '';
    }

    /**
     * Add Last Updated to Widget
     * 
     * @param  mixed $instance 
     * @param  mixed $download_id 
     * @return void 
     */
    public function add_date_last_updated($instance, $download_id)
    {
        $readme_url   = get_post_meta($download_id, '_edd_readme_location', true) ?? '';

        if (!$readme_url) {
            return;
        }

        $headers      = get_headers($readme_url);
        $last_updated = '';

        if (empty($headers)) {
            return;
        }

        foreach ($headers as $header) {
            if (strpos($header, 'Last-Modified') !== false) {
                $timestamp = strtotime(str_replace('Last-Modified: ', '', $header));

                $last_updated = gmdate('Y-m-d', $timestamp);
            }
        }

        if ($last_updated) : ?>
            <tr>
                <td><?php echo __('Last updated:', $this->plugin_text_domain); ?></td>
                <td><span itemscope itemtype="https://schema.org/dateModified"><?php echo sprintf(__('%s'), $last_updated); ?></span></td>
            </tr>
        <?php endif;
    }

    /**
     * 
     * @return void 
     */
    public function add_changelog_popup()
    {
        if (!$this->das->is_service(get_the_ID())) : ?>
            <div style="display: none;" id="ffw-changelog-popup">
                <div class="ffw-changelog-popup-inner">
                    <a href="#" id="ffw-changelog-close"><?php echo '⮿ ' . __('close', $this->plugin_text_domain); ?></a>
                    <div class="ffw-changelog-wrapper">
                        <?php echo $this->changelog; ?>
                    </div>
                </div>
            </div>
        <?php endif;
    }

    /**
     * @return void 
     */
    public function add_inline_script()
    {
        if (get_post_type() !== 'download') {
            return;
        }
        ?>
        <style>
            .edd-former-price {
                position: relative;
            }

            .edd-former-price::before {
                border-top: solid 2px #FF4136;
                transform: rotate(-15deg);
                content: "";
                position: absolute;
                top: 50%;
                left: -50%;
                right: 0;
                width: 150%;
            }


            .edd_price_additional_info {
                display: block;
                margin: 1em 0 0;
                text-align: center;
            }
        </style>

        <script>
            var changelogLink = document.getElementById('ffw-changelog-link');
            var changelogClose = document.getElementById('ffw-changelog-close');

            if (changelogLink !== null) {
                changelogLink.addEventListener('click', toggleChangelog);
            }

            if (changelogClose !== null) {
                changelogClose.addEventListener('click', toggleChangelog);
            }

            function toggleChangelog() {
                var changelog = document.getElementById('ffw-changelog-popup');

                if (changelog.style.display === 'none') {
                    changelog.style.display = 'block';
                } else {
                    changelog.style.display = 'none';
                }
            }
        </script>
        <?php
    }
}
