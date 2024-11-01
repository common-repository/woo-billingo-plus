<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/template" id="tmpl-wc-billingo-plus-modal-grouped-generate">
	<div class="wc-backbone-modal wc-billingo-plus-modal-grouped-generate">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Create a combined invoice', 'wc-billingo-plus'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'wc-billingo-plus' ); ?></span>
					</button>
				</header>
				<# if ( data.orderIds ) { #>
					<article>
						<div class="wc-billingo-plus-metabox-messages wc-billingo-plus-metabox-messages-success wc-billingo-plus-modal-grouped-generate-results" style="display:none;">
							<div class="wc-billingo-plus-metabox-messages-content">
								<ul></ul>
							</div>
						</div>
						<div class="wc-billingo-plus-modal-grouped-generate-download">
							<a href="#" class="wc-billingo-plus-modal-grouped-generate-download-invoice"><span><?php esc_html_e('Invoice', 'wc-billingo-plus'); ?></span> <strong></strong></a>
							<a href="#" class="wc-billingo-plus-modal-grouped-generate-download-order"><?php esc_html_e('Go to the order', 'wc-billingo-plus'); ?></a>
						</div>
						<div class="wc-billingo-plus-modal-grouped-generate-form">
							<p><?php esc_html_e('By combining the items in the orders below, you can create a single invoice. Select the order that will be the basis of the combined invoice: it will use the shipping and billing address of this order when creating the invoice.', 'wc-billingo-plus'); ?></p>
							{{{ data.orders }}}
							<p>
								<?php esc_html_e('The combined invoice will be displayed on the selected order.', 'wc-billingo-plus'); ?>
								<?php if(WC_Billingo_Plus()->get_option('grouped_invoice_status', 'no') != 'no'): ?>
									<br><?php esc_html_e('The status of the orders above will change to this after the invoice is created:', 'wc-billingo-plus'); ?> <strong><?php echo wc_get_order_status_name(WC_Billingo_Plus()->get_option('grouped_invoice_status')); ?></strong>
								<?php endif; ?>
							</p>
						</div>
					</article>
					<footer>
						<div class="inner">
							<a class="button button-primary button-large" href="#" id="generate_grouped_invoice" data-orders="{{{ data.orderIds }}}" data-nonce="<?php echo wp_create_nonce( "wc_billingo_plus_generate_grouped_invoice" ); ?>"><?php esc_html_e( 'Create a combined invoice', 'wc-billingo-plus' ); ?></a>
						</div>
					</footer>
				<# } else { #>
					<article>
						<p><?php esc_html_e('You need to select at least two orders.', 'wc-billingo-plus'); ?></p>
					</article>
				<# } #>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/template" id="tmpl-wc-billingo-plus-modal-mark-paid">
	<div class="wc-backbone-modal wc-billingo-plus-modal-mark-paid">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('This invoice was paid by the customer', 'wc-billingo-plus'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'wc-billingo-plus' ); ?></span>
					</button>
				</header>
				<article>
					<label for="wc_billingo_plus_mark_paid_date"><?php esc_html_e('Payment date','wc-billingo-plus'); ?></label>
					<input type="text" class="date-picker" id="wc_billingo_plus_mark_paid_date" maxlength="10" value="<?php echo date('Y-m-d'); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
				</article>
				<footer>
					<div class="inner">
						<a class="button button-primary button-large" href="#" id="wc_billingo_plus_mark_paid" data-order="{{{ data.order_id }}}" data-nonce="<?php echo wp_create_nonce( "wc_billingo_generate_invoice" ); ?>"><?php esc_html_e( 'Mark as paid', 'wc-billingo-plus' ); ?></a>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>