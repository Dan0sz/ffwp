<?php

/**
 *  This template is used to display the Checkout page when items are in the cart
 */

global $post; ?>
<table id="edd_checkout_cart" <?php if (!edd_is_ajax_disabled()) {
									echo 'class="ajaxed"';
								} ?>>
	<tbody>
		<?php $cart_items = edd_get_cart_contents(); ?>
		<?php do_action('edd_cart_items_before'); ?>
		<?php if ($cart_items) : ?>
			<?php foreach ($cart_items as $key => $item) : ?>
				<tr class="edd_cart_item" id="edd_cart_item_<?php echo esc_attr($key) . '_' . esc_attr($item['id']); ?>" data-download-id="<?php echo esc_attr($item['id']); ?>">
					<?php do_action('edd_checkout_table_body_first', $item); ?>
					<td class="edd_cart_item_thumbnail">
						<?php
						if (current_theme_supports('post-thumbnails') && has_post_thumbnail($item['id'])) {
							echo '<div class="edd_cart_item_image">';
							echo get_the_post_thumbnail($item['id'], apply_filters('edd_checkout_image_size', array(48, 48)));
							echo '</div>';
						}
						?>
					</td>
					<td class="edd_cart_item_name">
						<div class="edd_cart_item_name_inner">
							<?php
							$item_title = edd_get_cart_item_name($item);
							echo '<span class="edd_checkout_cart_item_title">' . esc_html($item_title) . '</span>';

							/**
							 * Runs after the item in cart's title is echoed
							 * @since 2.6
							 *
							 * @param array $item Cart Item
							 * @param int $key Cart key
							 */
							do_action('edd_checkout_cart_item_title_after', $item, $key);
							?>
						</div>
						<div class="edd_cart_actions">
							<?php if (edd_item_quantities_enabled() && !edd_download_quantities_disabled($item['id'])) : ?>
								<input type="number" min="1" step="1" name="edd-cart-download-<?php echo $key; ?>-quantity" data-key="<?php echo $key; ?>" class="edd-input edd-item-quantity" value="<?php echo edd_get_cart_item_quantity($item['id'], $item['options']); ?>" />
								<input type="hidden" name="edd-cart-downloads[]" value="<?php echo $item['id']; ?>" />
								<input type="hidden" name="edd-cart-download-<?php echo $key; ?>-options" value="<?php echo esc_attr(json_encode($item['options'])); ?>" />
							<?php endif; ?>
							<?php do_action('edd_cart_actions', $item, $key); ?>
							<a class="edd_cart_remove_item_btn" href="<?php echo esc_url(wp_nonce_url(edd_remove_item_url($key), 'edd-remove-from-cart-' . $key, 'edd_remove_from_cart_nonce')); ?>"><?php _e('Remove', 'easy-digital-downloads'); ?></a>
						</div>
					</td>
					<td class="edd_cart_item_price">
						<?php
						echo edd_cart_item_price($item['id'], $item['options']);
						do_action('edd_checkout_cart_item_price_after', $item);
						?>
					</td>
					<?php do_action('edd_checkout_table_body_last', $item); ?>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php do_action('edd_cart_items_middle'); ?>
		<?php do_action('edd_cart_items_after'); ?>
	</tbody>
	<tfoot>

		<?php if (has_action('edd_cart_footer_buttons')) : ?>
			<tr class="edd_cart_footer_row<?php if (edd_is_cart_saving_disabled()) {
												echo ' edd-no-js';
											} ?>">
				<th colspan="<?php echo edd_checkout_cart_columns(); ?>">
					<?php do_action('edd_cart_footer_buttons'); ?>
				</th>
			</tr>
		<?php endif; ?>

		<?php if (edd_use_taxes() && !edd_prices_include_tax()) : ?>
			<tr class="edd_cart_footer_row edd_cart_subtotal_row" <?php if (!edd_is_cart_taxed()) echo ' style="display:none;"'; ?>>
				<?php do_action('edd_checkout_table_subtotal_first'); ?>
				<th colspan="<?php echo edd_checkout_cart_columns(); ?>" class="edd_cart_subtotal">
					<?php _e('Subtotal', 'easy-digital-downloads'); ?>:&nbsp;<span class="edd_cart_subtotal_amount"><?php echo edd_cart_subtotal(); ?></span>
				</th>
				<?php do_action('edd_checkout_table_subtotal_last'); ?>
			</tr>
		<?php endif; ?>

		<!-- Show any cart fees, both positive and negative fees -->
		<?php if (edd_cart_has_fees()) : ?>
			<?php foreach (edd_get_cart_fees() as $fee_id => $fee) : ?>
				<tr class="edd_cart_footer_row edd_cart_fee" id="edd_cart_fee_<?php echo $fee_id; ?>">

					<?php do_action('edd_cart_fee_rows_before', $fee_id, $fee); ?>

					<?php $colspan = sprintf('colspan="%s"', (!empty($fee['type']) && $fee['type'] == 'item') ? edd_checkout_cart_columns() - 1 : edd_checkout_cart_columns()); ?>

					<th <?php echo $colspan; ?> class="edd_cart_fee"><?php echo esc_html($fee['label']); ?>: <?php echo esc_html(edd_currency_filter(edd_format_amount($fee['amount']))); ?></th>

					<?php if (!empty($fee['type']) && 'item' == $fee['type']) : ?>
						<th>
							<a href="<?php echo esc_url(edd_remove_cart_fee_url($fee_id)); ?>"><?php _e('Remove', 'easy-digital-downloads'); ?></a>
						</th>
					<?php endif; ?>

					<?php do_action('edd_cart_fee_rows_after', $fee_id, $fee); ?>

				</tr>
			<?php endforeach; ?>
		<?php endif; ?>

		<tr class="edd_cart_footer_row edd_cart_discount_row" <?php if (!edd_cart_has_discounts())  echo ' style="display:none;"'; ?>>
			<?php do_action('edd_checkout_table_discount_first'); ?>
			<th colspan="<?php echo edd_checkout_cart_columns(); ?>" class="edd_cart_discount">
				<?php edd_cart_discounts_html(); ?>
			</th>
			<?php do_action('edd_checkout_table_discount_last'); ?>
		</tr>

		<?php if (edd_use_taxes()) : ?>
			<tr class="edd_cart_footer_row edd_cart_tax_row" <?php if (!edd_is_cart_taxed()) echo ' style="display:none;"'; ?>>
				<?php do_action('edd_checkout_table_tax_first'); ?>
				<th colspan="<?php echo edd_checkout_cart_columns(); ?>" class="edd_cart_tax">
					<?php _e('Tax', 'easy-digital-downloads'); ?>:&nbsp;<span class="edd_cart_tax_amount" data-tax="<?php echo edd_get_cart_tax(false); ?>"><?php echo esc_html(edd_cart_tax()); ?></span>
				</th>
				<?php do_action('edd_checkout_table_tax_last'); ?>
			</tr>

		<?php endif; ?>

		<tr class="edd_cart_footer_row">
			<?php do_action('edd_checkout_table_footer_first'); ?>
			<th colspan="<?php echo edd_checkout_cart_columns(); ?>" class="edd_cart_total"><?php _e('Total', 'easy-digital-downloads'); ?>: <span class="edd_cart_amount" data-subtotal="<?php echo edd_get_cart_subtotal(); ?>" data-total="<?php echo edd_get_cart_total(); ?>"><?php edd_cart_total(); ?></span></th>
			<?php do_action('edd_checkout_table_footer_last'); ?>
		</tr>
	</tfoot>
</table>