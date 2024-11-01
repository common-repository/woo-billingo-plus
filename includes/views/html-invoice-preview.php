<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Check for permissions
if ( !current_user_can( 'edit_shop_orders' ) ) {
 wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
	<head>
		<meta charset="utf-8">
		<title><?php _e('Invoice preview', 'wc-billingo-plus'); ?></title>
		<link rel="stylesheet" href="<?php echo esc_url(WC_Billingo_Plus()::$plugin_url); ?>assets/css/preview.css" media="all">
	</head>
	<body>

		<div class="sheet">

			<div class="header">
				<div>
					<h1 class="title"><?php echo esc_html_x('Preview', 'Invoice preview title', 'wc-billingo-plus'); ?></h1>
					<div class="subtitle"><?php _e('Invoice number', 'wc-billingo-plus'); ?></div>
				</div>
				<div class="logo">logo</div>
			</div>

			<div class="sub-header">
				<div class="seller">
					<h3><?php _e('Seller', 'wc-billingo-plus'); ?></h3>
					<h2><?php _e('Seller Name', 'wc-billingo-plus'); ?></h2>
					Város<br>
					Minta u. 1.<br>
					1234<br>
					Magyarország<br>
					<p>
						<strong><?php _e('VAT Number', 'wc-billingo-plus'); ?>:</strong> 11111111-1-11<br>
						<strong><?php _e('Bank account number', 'wc-billingo-plus'); ?>:</strong> 11111111-11111111-11111111<br>
						<strong><?php _e('Bank name', 'wc-billingo-plus'); ?>:</strong> Teszt bank
					</p>
				</div>
				<div class="buyer">
					<h3><?php _e('Buyer', 'wc-billingo-plus'); ?></h3>
					<h2><?php echo $partner['name']; ?></h2>
					<?php echo $partner['address']['city']; ?><br>
					<?php echo $partner['address']['address']; ?><br>
					<?php echo $partner['address']['post_code']; ?><br>
					<?php echo WC()->countries->countries[ $partner['address']['country_code'] ]; ?>
					<?php if($partner['taxcode']): ?><br><?php echo $partner['taxcode']; ?><?php endif; ?>
				</div>

			</div>

			<div class="info">
				<ul class="shop-details">
					<li>
						<strong><?php _e('Issue date', 'wc-billingo-plus'); ?>:</strong> <?php echo date("Y. m. d."); ?>
					</li>
					<li>
						<strong><?php _e('Due date', 'wc-billingo-plus'); ?>:</strong> <?php echo date("Y. m. d.", strtotime($invoice['due_date'])); ?>
					</li>
					<li>
						<strong><?php _e('Completion date', 'wc-billingo-plus'); ?>:</strong> <?php echo date("Y. m. d.", strtotime($invoice['fulfillment_date'])); ?>
					</li>
					<li>
						<strong><?php _e('Payment method', 'wc-billingo-plus'); ?>:</strong> <?php echo WC_Billingo_Plus_Helpers::get_billingo_payment_method_label($invoice['payment_method']); ?>
					</li>
				</ul>
			</div>

			<div class="invoice-body">
				<table class="line-items">
					<thead>
						<tr>
							<th></th>
							<th><?php _e('Description', 'wc-billingo-plus'); ?></th>
							<th><?php _e('Quantity', 'wc-billingo-plus'); ?></th>
							<th><?php _e('Unit price', 'wc-billingo-plus'); ?></th>
							<th><?php _e('Net price', 'wc-billingo-plus'); ?></th>
							<th><?php _e('VAT value', 'wc-billingo-plus'); ?></th>
							<th><?php _e('Gross price', 'wc-billingo-plus'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					$sum_netto = 0;
					$sum_afa = 0;
					$sum_brutto = 0;
					$sum_total = 0;
					$items = $invoice['items'];
					?>
					<?php foreach ($items as $index => $tetel): ?>

						<?php
						$vat_rate = intval($tetel['vat']);
						$qty = $tetel['quantity'];
						if($tetel['unit_price_type'] == 'gross') {
							$price_gross_unit = $tetel['unit_price'];
							$price_net = $price_gross_unit/(1+$vat_rate/100);
							$price_gross = $price_gross_unit*$qty;
						} else {
							$price_net = $tetel['unit_price'];
							$price_gross = $qty*($price_net+$price_net*$vat_rate/100);
						}
						?>

						<tr <?php if(!empty($tetel['comment'])): ?>class="has-note"<?php endif; ?>>
							<td><?php echo $index+1; ?></td>
							<td>
								<span><?php echo $tetel['name']; ?></span>
							</td>
							<td><?php echo $qty; ?> <?php echo $tetel['unit']; ?></td>
							<td><?php echo wc_price($price_net, array('currency' => $invoice['currency'])); ?></td>
							<td><?php echo wc_price($price_net*$qty, array('currency' => $invoice['currency'])); ?></td>
							<td><?php echo $tetel['vat']; ?></td>
							<td><?php echo wc_price($price_gross, array('currency' => $invoice['currency'])); ?></td>
						</tr>
						<?php if(!empty($tetel['comment'])): ?>
							<tr class="item-note">
								<td></td>
								<td colspan="6">
									<div class="note"><?php echo wpautop($tetel['comment']); ?></div>
								</td>
							</tr>
						<?php endif; ?>
						<?php $sum_netto += $price_net*$qty; ?>
						<?php $sum_brutto += $price_gross; ?>
					<?php endforeach; ?>
					</tbody>
				</table>

				<div class="grand-total">
					<strong><?php _e('Total due:', 'wc-billingo-plus'); ?></strong> <span><?php echo wc_price($sum_brutto, array('currency' => $invoice['currency'])); ?></span>
				</div>

				<table class="total">
					<tr>
						<th><?php _e('Net total', 'wc-billingo-plus'); ?></th>
						<td><?php echo wc_price($sum_netto, array('currency' => $invoice['currency'])); ?></td>
					</tr>
					<tr>
						<th><?php _e('Total due', 'wc-billingo-plus'); ?></th>
						<td><?php echo wc_price($sum_brutto, array('currency' => $invoice['currency'])); ?></td>
					</tr>
				</table>
			</div>

			<?php if($invoice['comment']): ?>
				<div class="invoice-note">
					<h3><?php _e('Note', 'wc-billingo-plus'); ?></h3>
					<?php echo wpautop($invoice['comment']); ?>
				</div>
			<?php endif; ?>

		</div>

		<p class="footer"><?php _e('This is just a preview of the invoice and not all parameters will match the real invoice, like the colors, logo, language, and seller details.', 'wc-billingo-plus'); ?></p>
		<?php if(WC_Billingo_Plus()->get_option('debug', 'no') == 'yes'): ?>
			<div class="debug"><textarea><?php _e('Development mode details:', 'wc-billingo-plus'); ?> [<?php echo json_encode($partner, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>,<?php echo json_encode($invoice, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>]</textarea></div>
		<?php endif; ?>

	</body>
</html>
