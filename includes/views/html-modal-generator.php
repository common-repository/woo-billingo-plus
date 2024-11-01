<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/template" id="tmpl-wc-billingo-plus-modal-bulk-generator">
	<div class="wc-backbone-modal wc-billingo-plus-modal-bulk-generator">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e('Billingo document generator', 'wc-billingo-plus'); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'wc-billingo-plus' ); ?></span>
					</button>
				</header>
				<# if ( data.orderIds ) { #>
					<article>
						<div class="wc-billingo-plus-metabox-messages wc-billingo-plus-metabox-messages-success wc-billingo-plus-modal-bulk-generator-results" style="display:none;">
							<div class="wc-billingo-plus-metabox-messages-content">
								<ul></ul>
							</div>
						</div>
						<div class="wc-billingo-plus-modal-bulk-generator-download"></div>
						<div class="wc-billingo-plus-modal-bulk-generator-form">
							<p><?php esc_html_e('Selected orders:', 'wc-billingo-plus'); ?></p>
							<div class="wc-billingo-plus-modal-bulk-generator-selected">{{{ data.orders }}}</div>

							<ul class="wc-billingo-plus-metabox-generate-options">
								<li class="wc-billingo-plus-metabox-generate-options-type">
									<label><?php esc_html_e('Document type','wc-billingo-plus'); ?></label>
									<label for="wc_billingo_plus_bulk_invoice_normal">
										<input type="radio" name="bulk_invoice_extra_type" id="wc_billingo_plus_bulk_invoice_normal" value="1" checked="checked" />
										<span><?php esc_html_e('Invoice','wc-billingo-plus'); ?></span>
									</label>
									<label for="wc_billingo_plus_bulk_invoice_proform">
										<input type="radio" name="bulk_invoice_extra_type" id="wc_billingo_plus_bulk_invoice_proform" value="1" />
										<span><?php esc_html_e('Proforma invoice','wc-billingo-plus'); ?></span>
									</label>
									<label for="wc_billingo_plus_bulk_invoice_deposit">
										<input type="radio" name="bulk_invoice_extra_type" id="wc_billingo_plus_bulk_invoice_deposit" value="1" />
										<span><?php esc_html_e('Deposit invoice','wc-billingo-plus'); ?></span>
									</label>
									<label for="wc_billingo_plus_bulk_invoice_void">
										<input type="radio" name="bulk_invoice_extra_type" id="wc_billingo_plus_bulk_invoice_void" value="1" />
										<span><?php esc_html_e('Reverse invoice','wc-billingo-plus'); ?></span>
									</label>
								</li>
								<?php if(count(WC_Billingo_Plus()->get_billingo_accounts()) > 1): ?>
								<li>
									<label for="wc_billingo_plus_bulk_invoice_account"><?php esc_html_e('Billingo account','wc-billingo-plus'); ?></label>
									<select id="wc_billingo_plus_bulk_invoice_account">
										<?php foreach (WC_Billingo_Plus()->get_billingo_accounts() as $account_key => $account_name): ?>
											<option value="<?php echo esc_attr($account_key); ?>" <?php selected( wc_billingo_plus()->get_billingo_keys($order, true), $account_key); ?>><?php echo esc_html($account_name); ?> - <?php echo substr(esc_html($account_key), 0, 16); ?>...</option>
										<?php endforeach; ?>
									</select>
								</li>
								<?php endif; ?>
								<li class="wc-billingo-plus-metabox-generate-options-group hidden-if-void">
									<ul>
										<li>
											<label for="wc_billingo_plus_bulk_invoice_lang"><?php esc_html_e('Language','wc-billingo-plus'); ?></label>
											<select id="wc_billingo_plus_bulk_invoice_lang">
												<?php foreach (wc_billingo_plus_Helpers::get_supported_languages() as $language_code => $language_label): ?>
													<option value="<?php echo esc_attr($language_code); ?>" <?php selected( wc_billingo_plus()->get_option('language', 'hu'), $language_code); ?>><?php echo esc_html($language_label); ?></option>
												<?php endforeach; ?>
											</select>
										</li>
										<li>
											<label for="wc_billingo_plus_bulk_invoice_doc_type"><?php esc_html_e('Type','wc-billingo-plus'); ?></label>
											<select id="wc_billingo_plus_bulk_invoice_doc_type">
												<?php $invoice_type = wc_billingo_plus()->get_option('invoice_type', 'paper'); ?>
												<option value="paper" <?php selected( $invoice_type, 'paper'); ?>><?php esc_html_e('Paper','wc-billingo-plus'); ?></option>
												<option value="electronic" <?php selected( $invoice_type, 'electronic'); ?>><?php esc_html_e('Electronic','wc-billingo-plus'); ?></option>
											</select>
										</li>
									</ul>
								</li>
								<li class="hidden-if-void">
									<label for="wc_billingo_plus_bulk_invoice_note"><?php esc_html_e('Note','wc-billingo-plus'); ?></label>
									<textarea id="wc_billingo_plus_bulk_invoice_note" placeholder="<?php esc_html_e('Here you can override the note specified in settings','wc-billingo-plus'); ?>"></textarea>
								</li>
								<li class="wc-billingo-plus-metabox-generate-options-group hidden-if-void">
									<ul>
										<li>
											<label for="wc_billingo_plus_bulk_invoice_deadline"><?php esc_html_e('Payment deadline','wc-billingo-plus'); ?></label>
											<input type="number" id="wc_billingo_plus_bulk_invoice_deadline" value="<?php echo absint(WC_Billingo_Plus()->get_option('payment_deadline', '0')); ?>" />
											<em>nap</em>
										</li>
										<li>
											<label for="wc_billingo_plus_bulk_invoice_completed"><?php esc_html_e('Completion date','wc-billingo-plus'); ?></label>
											<input type="text" class="date-picker" id="wc_billingo_plus_bulk_invoice_completed" maxlength="10" value="<?php echo date('Y-m-d'); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
										</li>
									</ul>
								</li>
							</ul>
						</div>
					</article>
					<footer>
						<div class="inner">
							<a class="button button-primary button-large" href="#" id="wc_billingo_plus_bulk_generator" data-orders="{{{ data.orderIds }}}" data-nonce="<?php echo wp_create_nonce( "wc_billingo_plus_bulk_generator" ); ?>"><?php esc_html_e( 'Generate documents', 'wc-billingo-plus' ); ?></a>
						</div>
					</footer>
				<# } else { #>
					<article>
						<p><?php esc_html_e('You need to select at least one order.', 'wc-billingo-plus'); ?></p>
					</article>
				<# } #>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
