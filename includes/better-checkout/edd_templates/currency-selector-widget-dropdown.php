<?php

/**
 * Widget: Currency Selector
 *
 * Dropdown view.
 *
 * @package   edd-multi-currency
 * @copyright Copyright (c) 2022, Easy Digital Downloads
 * @license   GPL2+
 * @since     1.0
 *
 * @var array                                 $widgetArgs      Widget settings.
 * @var \EDD_Multi_Currency\Models\Currency[] $currencies      Array of available currencies.
 * @var string                                $currentCurrency Currently selected currency.
 */
?>
<form class="edd-multi-currency-switcher" method="GET">
    <label style="display: none;" for="edd-multi-currency-dropdown" class="screen-reader-text">
        <?php esc_html_e('Select a currency', 'edd-multi-currency'); ?>
    </label>
    <select id="edd-multi-currency-dropdown" name="currency">
        <?php foreach ($currencies as $currency) : ?>
            <option value="<?php echo esc_attr($currency->currency); ?>" <?php selected($currency->currency, $currentCurrency); ?>>
                <?php echo esc_html($currency->currency); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button style="display: none;" type="submit" class="button edd-submit edd-multi-currency-button <?php echo esc_attr(sanitize_html_class(edd_get_option('checkout_color', 'blue'))); ?>">
        <?php esc_html_e('Set Currency', 'edd-multi-currency'); ?>
    </button>
</form>