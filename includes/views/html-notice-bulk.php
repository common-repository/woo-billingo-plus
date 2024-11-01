<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info wc-billingo-plus-notice wc-billingo-plus-bulk-actions wc-billingo-plus-print">
	<?php if($type == 'print'): ?>
		<p>
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#FF6767" d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/><path d="M0 0h24v24H0z" fill="none"/></svg>
			<span><?php echo sprintf( esc_html__( '%s invoice(s) selected for printing.', 'wc-billingo-plus' ), $print_count); ?></span>
			<a href="<?php echo $pdf_file_url; ?>" id="wc-billingo-plus-bulk-print" data-pdf="<?php echo $pdf_file_url; ?>"><?php esc_html_e('Print', 'wc-billingo-plus'); ?></a>
		</p>
	<?php elseif($type == 'download'): ?>
		<p>
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path fill="#FF6767" d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
			<span><?php echo sprintf( esc_html__( '%s invoice(s) selected for download.', 'wc-billingo-plus' ), $print_count); ?></span>
			<a href="<?php echo $pdf_file_url; ?>" id="wc-billingo-plus-bulk-download" download data-pdf="<?php echo $pdf_file_url; ?>"><?php esc_html_e('Download', 'wc-billingo-plus'); ?></a>
		</p>
	<?php else: ?>
		<?php if(count($invoices) > apply_filters('wc_billingo_plus_bulk_generate_defer_limit', 2)): ?>
			<p>
				<svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m9.375 0 5.625 5.4v10.8c0 .99-.84375 1.8-1.875 1.8h-11.259375c-1.03125 0-1.865625-.81-1.865625-1.8v-14.4c0-.99.84375-1.8 1.875-1.8zm4.125 16.5v-10.3125h-5v-4.6875h-7v15zm-9.06-5.4458599 1.72666667 1.6433122 4.39333333-4.1974523.94.9044586-5.33333333 5.0955414-2.66666667-2.5477708z" fill="#FF6767"/></svg>
				<span><?php echo sprintf( esc_html__( '%s order(s) selected to create invoices. Documents are being created.', 'wc-billingo-plus' ), count($invoices)); ?></span>
			</p>
		<?php else: ?>
			<?php if($type == 'invoice'): ?>
				<p>
					<svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m9.375 0 5.625 5.4v10.8c0 .99-.84375 1.8-1.875 1.8h-11.259375c-1.03125 0-1.865625-.81-1.865625-1.8v-14.4c0-.99.84375-1.8 1.875-1.8zm4.125 16.5v-10.3125h-5v-4.6875h-7v15zm-9.06-5.4458599 1.72666667 1.6433122 4.39333333-4.1974523.94.9044586-5.33333333 5.0955414-2.66666667-2.5477708z" fill="#FF6767"/></svg>
					<span><?php echo sprintf( esc_html__( '%s order(s) selected to create invoices. Documents are being created.', 'wc-billingo-plus' ), count($invoices)); ?></span>
				</p>
				<?php foreach ($invoices as $order_id): ?>
					<?php $temp_order = wc_get_order($order_id); ?>
					<?php if($temp_order): ?>
						<p>
							<a href="<?php echo esc_url($temp_order->get_edit_order_url()); ?>"><?php echo esc_html($temp_order->get_order_number()); ?></a> -
							<?php if($temp_order->get_meta('_wc_billingo_plus_'.$type.'_id')): ?>
								<?php esc_html_e('Invoice', 'wc-billingo-plus'); ?>: <?php echo esc_html($temp_order->get_meta('_wc_billingo_plus_'.$type.'_name')); ?>
							<?php else: ?>
								<?php esc_html_e('No invoice has been made', 'wc-billingo-plus'); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php else: ?>
				<p>
					<svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m9.375 0 5.625 5.4v10.8c0 .99-.84375 1.8-1.875 1.8h-11.259375c-1.03125 0-1.865625-.81-1.865625-1.8v-14.4c0-.99.84375-1.8 1.875-1.8zm4.125 16.5v-10.3125h-5v-4.6875h-7v15zm-9.25-2.8431458 5.65685425-5.6568542 1.06066015 1.06066017-5.65685423 5.65685423zm1.06066017-5.6568542 5.65685423 5.6568542-1.06066015 1.0606602-5.65685425-5.65685423z" fill="#FF6767"/></svg>
					<span><?php echo sprintf( esc_html__( '%s order(s) selected to create reverse invoices. Documents are being created.', 'wc-billingo-plus' ), count($invoices)); ?></span>
				</p>
				<?php foreach ($invoices as $order_id): ?>
					<?php $temp_order = wc_get_order($order_id); ?>
					<?php if($temp_order): ?>
						<p>
							<a href="<?php echo esc_url($temp_order->get_edit_order_url()); ?>"><?php echo esc_html($temp_order->get_order_number()); ?></a> -
							<?php if($temp_order->get_meta('_wc_billingo_plus_void_id')): ?>
								<?php esc_html_e('Reverse invoice', 'wc-billingo-plus'); ?>: <?php echo esc_html($temp_order->get_meta('_wc_billingo_plus_void_name')); ?>
							<?php else: ?>
								<?php esc_html_e('No reverse invoice has been made', 'wc-billingo-plus'); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
