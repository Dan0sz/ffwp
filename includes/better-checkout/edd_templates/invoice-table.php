<?php

/**
 * Invoice Items Table Template
 *
 * To modify this template, create a folder called `edd_templates` inside of your active theme's directory.
 * Copy this file into that new folder.
 *
 * @version 1.0
 *
 * @var \EDD\Orders\Order|EDD_Payment $order
 */
?>

<header><?php esc_html_e('Invoice Items:', 'edd-invoices'); ?></header>

<table>
	<tbody>
		<?php
		$items = edd_invoices_get_order_items($order);
		if ($items) {
			foreach ($items as $key => $item) {
		?>
				<tr>
					<td class="name"><?php echo wp_kses_post($item['name']); ?></td>
					<td class="price"><?php echo esc_html(edd_currency_filter(edd_format_amount($item['price']), $order->currency)); ?></td>
				</tr>
		<?php
			}
		}
		?>
	</tbody>
	<tfoot>
		<tr>
			<td class="name"><?php esc_html_e('Subtotal:', 'edd-invoices'); ?></td>
			<td class="price"><?php echo esc_html(edd_currency_filter(edd_format_amount($order->subtotal))); ?></td>
		</tr>
		<?php
		$fees = edd_get_payment_fees($order->ID);
		if ($fees) {
		?>
			<!-- Fees -->
			<?php
			foreach ($fees as $key => $fee) {
			?>
				<tr>
					<td class="name"><?php echo !empty($fee['label']) ? esc_html($fee['label']) : esc_html__('Order Fee', 'edd-invoices'); ?></td>
					<td class="price"><?php echo esc_html(edd_currency_filter(edd_format_amount($fee['amount']), $order->currency)); ?></td>
				</tr>
			<?php
			}
		}

		$discounts = edd_invoices_get_order_discounts($order);
		if ($discounts) {
			?>
			<!-- Discounts -->
			<?php
			foreach ($discounts as $discount) {
			?>
				<tr>
					<td class="name"><?php echo esc_html($discount['name']); ?>:</td>
					<td class="price"><?php echo esc_html($discount['amount']); ?></td>
				</tr>
			<?php
			}
		}

		if ($order->tax > 0) {
			$label = __('Tax:', 'edd-invoices');
			$rate  = edd_invoices_get_tax_rate($order);
			if ($rate) {
				/* translators: the order tax rate. */
				$label = sprintf(__('Tax (%s%%):', 'edd-invoices'), $rate);
			}
			?>
			<!-- Tax -->
			<tr>
				<td class="name"><?php echo esc_html($label); ?></td>
				<td class="price"><?php echo esc_html(edd_payment_tax($order->ID)); ?></td>
			</tr>
		<?php
		}
		?>

		<!-- Total -->
		<tr>
			<td class="name"><?php esc_html_e('Total:', 'edd-invoices'); ?></td>
			<td class="price"><?php echo esc_html(edd_payment_amount($order->ID)); ?></td>
		</tr>

		<!-- Paid -->
		<tr>
			<td class="name"><?php esc_html_e('Payment Status:', 'edd-invoices'); ?></td>
			<?php $statuses = edd_get_payment_statuses(); ?>
			<td class="price"><?php echo array_key_exists($order->status, $statuses) ? esc_html($statuses[$order->status]) : esc_html($order->status); ?></td>
		</tr>
		<?php
		$refunds = false;
		if (function_exists('edd_get_orders')) {
			$refunds = edd_get_orders(
				array(
					'parent' => $order->ID,
					'type'   => 'refund',
				)
			);
			if ($refunds) {
		?>
				<tr>
					<td class="name">
						<?php esc_html_e('Refunded:', 'edd-invoices'); ?>
						<br />
						<?php
						foreach ($refunds as $refund) {
							printf(
								'<span class="date">%s</span>',
								esc_html(date_i18n(get_option('date_format'), $refund->date_created))
							);
							echo '<br />';
						}
						?>
					</td>
					<td class="price">
						<br />
						<?php
						foreach ($refunds as $refund) {
							echo esc_html(edd_currency_filter(edd_format_amount($refund->total)));
							echo '<br />';
						}
						?>
					</td>
				</tr>
		<?php
			}
		}
		?>
	</tfoot>
</table>