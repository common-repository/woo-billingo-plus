<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php foreach ($billingo_invoices as $invoice): ?>
<div style="margin-bottom: 40px;">
	<?php if($invoice['type'] == 'invoice'): ?>
		<h2><?php esc_html_e('Invoice', 'wc-billingo-plus'); ?></h2>
		<p><?php esc_html_e('The invoice for the order can be downloaded from here:', 'wc-billingo-plus'); ?> <a href="<?php echo esc_url($invoice['link']); ?>" target="_blank"><?php echo esc_html($invoice['name']); ?></a></p>
	<?php endif; ?>

	<?php if($invoice['type'] == 'proform'): ?>
		<h2><?php esc_html_e('Proforma invoice', 'wc-billingo-plus'); ?></h2>
		<p><?php esc_html_e('The proforma invoice for the order can be downloaded from here:', 'wc-billingo-plus'); ?> <a href="<?php echo esc_url($invoice['link']); ?>" target="_blank"><?php echo esc_html($invoice['name']); ?></a></p>
	<?php endif; ?>

	<?php if($invoice['type'] == 'void'): ?>
		<h2><?php esc_html_e('Reverse invoice', 'wc-billingo-plus'); ?></h2>
		<p><?php esc_html_e('The previous invoice has been canceled. The reverse invoice for the order can be downloaded from here:', 'wc-billingo-plus'); ?> <a href="<?php echo esc_url($invoice['link']); ?>" target="_blank"><?php echo esc_html($invoice['name']); ?></a></p>
	<?php endif; ?>

	<?php if($invoice['type'] == 'deposit'): ?>
		<h2><?php esc_html_e('Deposit invoice', 'wc-billingo-plus'); ?></h2>
		<p><?php esc_html_e('The deposit invoice for the order can be downloaded from here:', 'wc-billingo-plus'); ?> <a href="<?php echo esc_url($invoice['link']); ?>" target="_blank"><?php echo esc_html($invoice['name']); ?></a></p>
	<?php endif; ?>
</div>
<?php endforeach; ?>
