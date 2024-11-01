<?php if(!$this->get_option('api_key')): ?>

	<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=integration&section=wc_billingo_plus' ); ?>" class="wc-billingo-plus-metabox-settings">
		<span><?php esc_html_e('To create an invoice, you must enter the API key in the settings', 'wc-billingo-plus'); ?></span>
		<span class="dashicons dashicons-arrow-right-alt2"></span>
	</a>

<?php else: ?>

	<div class="wc-billingo-plus-metabox-content" data-order="<?php echo $order->get_id(); ?>" data-nonce="<?php echo wp_create_nonce( "wc_billingo_generate_invoice" ); ?>">
		<div class="wc-billingo-plus-metabox-messages wc-billingo-plus-metabox-messages-success" style="display:none;">
			<div class="wc-billingo-plus-metabox-messages-content">
				<ul></ul>
				<a href="#"><span class="dashicons dashicons-no-alt"></span></a>
			</div>
		</div>

		<div class="wc-billingo-plus-metabox-disabled <?php if($order->get_meta('_wc_billingo_plus_own')): ?>show<?php endif; ?>">
			<?php $note = $order->get_meta('_wc_billingo_plus_own'); ?>
			<p>
				<?php esc_html_e('Invoicing has been disabled for this order because:', 'wc-billingo-plus'); ?> <span><?php echo esc_html($note); ?></span>
			</p>
			<p>
				<a class="wc-billingo-plus-invoice-toggle on" href="#" data-nonce="<?php echo wp_create_nonce( "wc_billingo_toggle_invoice" ); ?>" data-order="<?php echo $order->get_id(); ?>">
					<?php esc_html_e('Turn back on', 'wc-billingo-plus'); ?>
				</a>
			</p>
		</div>

		<?php
		$has_invoice = $order->get_meta( '_wc_billingo_plus_invoice_id' );
		$has_voidable_invoice = ($has_invoice || $order->get_meta( '_wc_billingo_plus_proform_id' ));
		$is_receipt = $order->get_meta('_wc_billingo_plus_type_receipt');
		$has_receipt = $order->get_meta( '_wc_billingo_plus_receipt_id' );
		$has_void = $order->get_meta( '_wc_billingo_plus_void_id' );
		$has_void_receipt = $order->get_meta( '_wc_billingo_plus_void_id' );
		$document_types = WC_Billingo_Plus_Helpers::get_document_types();
		$is_pro = WC_Billingo_Plus_Pro::is_pro_enabled();
		?>

		<ul class="wc-billingo-plus-metabox-rows">

			<?php foreach ($document_types as $document_type => $document_label): ?>
				<?php $download_link = $this->generate_download_link($order, $document_type); ?>
				<?php $pdf_link = $order->get_meta('_wc_billingo_plus_'.$document_type.'_pdf'); ?>
				<li class="wc-billingo-plus-metabox-rows-invoice wc-billingo-plus-metabox-invoices-<?php echo $document_type; ?> <?php if($order->get_meta('_wc_billingo_plus_'.$document_type.'_id')): ?>show<?php endif; ?><?php if($pdf_link && $pdf_link == 'pending'): ?> pending<?php endif; ?>">
					<a target="_blank" href="<?php echo esc_url($download_link); ?>" title="<?php echo esc_attr($order->get_meta('_wc_billingo_plus_'.$document_type.'_id')); ?>">
						<span><?php echo $document_label; ?></span>
						<strong><?php echo esc_html($order->get_meta('_wc_billingo_plus_'.$document_type.'_name')); ?></strong>
					</a>
					<small class="pending-download"><?php _e('The PDF is being downloaded', 'wc-billingo-plus'); ?><i>.</i><i>.</i><i>.</i></small>
				</li>
			<?php endforeach; ?>

			<li class="wc-billingo-plus-metabox-rows-data wc-billingo-plus-metabox-rows-data-complete <?php if($has_invoice): ?>show<?php endif; ?>">
				<div class="wc-billingo-plus-metabox-rows-data-inside">
					<span><?php esc_html_e('Paid','wc-billingo-plus'); ?></span>
					<a href="#" data-trigger-value="<?php esc_attr_e('Mark as paid ','wc-billingo-plus'); ?>" <?php if($order->get_meta('_wc_billingo_plus_completed')): ?>class="completed"<?php endif; ?>>
						<?php if(!$order->get_meta('_wc_billingo_plus_completed')): ?>
							<?php esc_html_e('Mark as paid','wc-billingo-plus'); ?>
						<?php else: ?>
							<?php if($order->get_meta('_wc_billingo_plus_completed') == 1): ?>
								<?php esc_html_e('Paid','wc-billingo-plus'); ?>
							<?php else: ?>
								<?php
								$paid_date = $order->get_meta('_wc_billingo_plus_completed');
								if (strpos($paid_date, '-') == false) {
									$paid_date = date('Y-m-d', $paid_date);
								}
								?>
								<?php echo esc_html($paid_date); ?>
							<?php endif; ?>
						<?php endif; ?>
					</a>
				</div>
			</li>
			<li class="wc-billingo-plus-metabox-rows-data wc-billingo-plus-metabox-rows-data-email <?php if($has_invoice || $has_receipt): ?>show<?php endif; ?>">
				<div class="wc-billingo-plus-metabox-rows-data-inside">
					<span><?php esc_html_e('Invoice notification', 'wc-billingo-plus'); ?></span>
					<a href="#" data-trigger-value="<?php esc_attr_e('Resend', 'wc-billingo-plus'); ?>" data-done="<?php esc_attr_e('Resent', 'wc-billingo-plus'); ?>"><?php esc_html_e('Resend', 'wc-billingo-plus'); ?></a>
				</div>
			</li>

			<li class="wc-billingo-plus-metabox-rows-data wc-billingo-plus-metabox-rows-data-void-reason plugins">
				<div class="wc-billingo-plus-metabox-rows-data-inside">
					<textarea id="wc_billingo_plus_void_note" placeholder="<?php esc_html_e('Reason for cancelling the invoice(optional)', 'wc-billingo-plus'); ?>"></textarea>
				</div>
			</li>
			<li class="wc-billingo-plus-metabox-rows-data wc-billingo-plus-metabox-rows-data-void plugins <?php if($has_voidable_invoice || $has_receipt): ?>show<?php endif; ?>">
				<div class="wc-billingo-plus-metabox-rows-data-inside">
					<a href="#" data-trigger-value="<?php esc_attr_e('Reverse invoice', 'wc-billingo-plus'); ?>" data-question="<?php echo esc_attr_x('Generate reverse invoice', 'Reverse invoice', 'wc-billingo-plus'); ?>" class="delete"><?php echo esc_html_x('Reverse invoice', 'Action', 'wc-billingo-plus'); ?></a>
					
				</div>
			</li>
		</ul>

		<?php $auto_invoice_statuses = get_option('wc_billingo_plus_auto_invoice_status', array()); ?>
		<?php if(count($auto_invoice_statuses) > 0 && $is_pro): ?>
		<div class="wc-billingo-plus-metabox-auto-msg <?php if(!$order->get_meta('_wc_billingo_plus_own') && !$has_invoice): ?>show<?php endif; ?>">
			<div class="wc-billingo-plus-metabox-auto-msg-text">
				<p><?php esc_html_e( 'The invoice will be created automatically if the status of the order changes to:', 'wc-billingo-plus' ); ?>
					<strong>
						<?php $result = implode(', ', array_map( 'wc_get_order_status_name', $auto_invoice_statuses )); ?>
						<?php echo $result; ?>
					</strong>
				</p>
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
		</div>
		<?php endif; ?>

		<div class="wc-billingo-plus-metabox-generate <?php if(!$order->get_meta('_wc_billingo_plus_own') && !$has_invoice && !$has_receipt): ?>show<?php endif; ?>">
			<?php do_action('wc_billingo_plus_metabox_generate_before'); ?>
			<div class="wc-billingo-plus-metabox-generate-buttons">

				<?php if($is_receipt): ?>
					<?php if(!$has_void_receipt): ?>
						<a href="#" id="wc_billingo_plus_receipt_generate" class="button button-primary" target="_blank" data-question="<?php echo esc_attr_x('Are you sure you want to create the receipt?', 'wc-billingo-plus'); ?>">
							<?php esc_html_e('Create receipt', 'wc-billingo-plus'); ?>
						</a>
					<?php endif; ?>
				<?php else: ?>
					<a href="#" id="wc_billingo_plus_invoice_options"><span class="dashicons dashicons-admin-generic"></span><span><?php esc_html_e('Options','wc-billingo-plus'); ?></span></a>

					<?php if($is_pro): ?>
						<?php $preview_url = add_query_arg('wc_billingo_plus_preview', $order->get_id(), get_admin_url() ); ?>
						<a href="#" data-url="<?php echo esc_url($preview_url); ?>" target="_blank" class="button button-preview tips" id="wc_billingo_plus_invoice_preview" data-tip="<?php echo esc_attr_x('Preview', 'Invoice preview', 'wc-billingo-plus'); ?>" target="_blank"><span class="dashicons dashicons-visibility"></span></a>
					<?php endif; ?>

					<a href="#" id="wc_billingo_plus_invoice_generate" class="button button-primary" target="_blank" data-question="<?php echo esc_attr_x('Are you sure?', 'Invoice', 'wc-billingo-plus'); ?>">
						<?php esc_html_e('Create invoice', 'wc-billingo-plus'); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php do_action('wc_billingo_plus_metabox_generate_after'); ?>

			<ul class="wc-billingo-plus-metabox-generate-options" style="display:none">
				<?php do_action('wc_billingo_plus_metabox_generate_options_before'); ?>
				<?php if(count($this->get_billingo_accounts()) > 1): ?>
				<li>
					<label for="wc_billingo_plus_invoice_account"><?php esc_html_e('Billingo account','wc-billingo-plus'); ?></label>
					<select id="wc_billingo_plus_invoice_account">
						<?php foreach ($this->get_billingo_accounts() as $account_key => $account_name): ?>
							<option value="<?php echo esc_attr($account_key); ?>" <?php selected( $this->get_billingo_keys($order, true), $account_key); ?>><?php echo esc_html($account_name); ?> - <?php echo substr(esc_html($account_key), 0, 16); ?>...</option>
						<?php endforeach; ?>
					</select>
				</li>
				<?php endif; ?>

				<li class="wc-billingo-plus-metabox-generate-options-group">
					<ul>
						<li>
							<label for="wc_billingo_plus_invoice_lang"><?php esc_html_e('Language','wc-billingo-plus'); ?></label>
							<select id="wc_billingo_plus_invoice_lang">
								<?php foreach (WC_Billingo_Plus_Helpers::get_supported_languages() as $language_code => $language_label): ?>
									<option value="<?php echo esc_attr($language_code); ?>" <?php selected( WC_Billingo_Plus_Helpers::get_order_language($order), $language_code); ?>><?php echo esc_html($language_label); ?></option>
								<?php endforeach; ?>
							</select>
						</li>
						<li>
							<label for="wc_billingo_plus_invoice_doc_type"><?php esc_html_e('Type','wc-billingo-plus'); ?></label>
							<select id="wc_billingo_plus_invoice_doc_type">
								<?php $invoice_type = $this->get_invoice_type($order); ?>
								<option value="paper" <?php selected( $invoice_type, false); ?>><?php esc_html_e('Paper','wc-billingo-plus'); ?></option>
								<option value="electronic" <?php selected( $invoice_type, true); ?>><?php esc_html_e('Electronic','wc-billingo-plus'); ?></option>
							</select>
						</li>
					</ul>
				</li>

				<li>
					<label for="wc_billingo_plus_invoice_note"><?php esc_html_e('Note','wc-billingo-plus'); ?></label>
					<textarea id="wc_billingo_plus_invoice_note" placeholder="<?php esc_html_e('Here you can override the note specified in settings. Start with a + sign to append to the exising note.', 'wc-billingo-plus'); ?>"></textarea>
				</li>
				<li class="wc-billingo-plus-metabox-generate-options-group">
					<ul>
						<li>
							<label for="wc_billingo_plus_invoice_deadline"><?php esc_html_e('Payment deadline','wc-billingo-plus'); ?></label>
							<input type="number" id="wc_billingo_plus_invoice_deadline" value="<?php echo absint($this->get_payment_method_deadline($order->get_payment_method())); ?>" />
							<em>nap</em>
						</li>
						<li>
							<label for="wc_billingo_plus_invoice_completed"><?php esc_html_e('Completion date','wc-billingo-plus'); ?></label>
							<input type="text" class="date-picker" id="wc_billingo_plus_invoice_completed" maxlength="10" value="<?php echo esc_attr(WC_Billingo_Plus_Helpers::get_default_complete_date($order)); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
						</li>
					</ul>
				</li>
				<?php if($is_pro): ?>
				<li>
					<label>
						<input type="checkbox" id="wc_billingo_plus_invoice_paid" <?php checked($this->check_payment_method_options($order->get_payment_method(), 'complete')); ?>>
						<?php esc_html_e( 'Mark as paid', 'wc-billingo-plus' ); ?>
					</label>
				</li>
				<?php endif; ?>
				<li class="wc-billingo-plus-metabox-generate-options-type">
					<label><?php esc_html_e('Document type','wc-billingo-plus'); ?></label>
					<label for="wc_billingo_plus_invoice_normal">
						<input type="radio" name="invoice_extra_type" id="wc_billingo_plus_invoice_normal" value="1" <?php checked( $this->get_option('draft', 'no'), 'no' ); ?> />
						<span><?php esc_html_e('Invoice','wc-billingo-plus'); ?></span>
					</label>
					<label for="wc_billingo_plus_invoice_proform">
						<input type="radio" name="invoice_extra_type" id="wc_billingo_plus_invoice_proform" value="1" <?php disabled($this->is_invoice_generated($order->get_id(), 'proform')); ?> />
						<span><?php esc_html_e('Proforma invoice','wc-billingo-plus'); ?></span>
					</label>
					<label for="wc_billingo_plus_invoice_deposit">
						<input type="radio" name="invoice_extra_type" id="wc_billingo_plus_invoice_deposit" value="1" <?php disabled($this->is_invoice_generated($order->get_id(), 'deposit')); ?> />
						<span><?php esc_html_e('Deposit invoice','wc-billingo-plus'); ?></span>
					</label>
					<label for="wc_billingo_plus_invoice_draft">
						<input type="radio" name="invoice_extra_type" id="wc_billingo_plus_invoice_draft" value="1" <?php checked( $this->get_option('draft', 'no'), 'yes' ); ?> />
						<span><?php esc_html_e('Draft','wc-billingo-plus'); ?></span>
					</label>
				</li>
				<li>
					<a class="wc-billingo-plus-invoice-toggle off" href="#"><?php esc_html_e('Disable invoicing','wc-billingo-plus'); ?></a>
				</li>
				<?php do_action('wc_billingo_plus_metabox_generate_options_after'); ?>
			</ul>
		</div>
		<div class="wc-billingo-plus-metabox-receipt-void-note <?php if($is_receipt && $has_void): ?>show<?php endif; ?>"><small><a href="#" id="wc_billingo_plus_reverse_receipt"><?php esc_html_e('Create an invoice instead','wc-billingo-plus'); ?></a></small></div>
	</div>

<?php endif; ?>
