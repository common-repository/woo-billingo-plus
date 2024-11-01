<?php
/**
 * Admin View: Page - Status Report.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<table class="wc_status_table widefat" cellspacing="0">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Woo Billingo Plus">
			<h2><?php _e( 'Woo Billingo Plus', 'wc-billingo-plus' ); ?><?php echo wc_help_tip( __( 'Woo Billingo Plus Settings', 'wc-billingo-plus' ) ); ?></h2>
		</th>
	</tr>
	</thead>
	<tbody>
		<?php foreach ($debug_info as $info): ?>
			<?php if($info['value'] && $info['value'] != 'no'): ?>
			<tr>
				<td data-export-label="<?php echo $info['label']; ?>"><?php echo $info['label']; ?>:</td>
				<td class="help">&nbsp;</td>
				<td>
					<?php if($info['value'] == 1): ?>
						<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
					<?php else: ?>
						<?php if(is_array($info['value'])): ?>
							<?php echo implode($info['value'], ', '); ?>
						<?php else: ?>
							<?php echo $info['value']; ?>
						<?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
			<?php endif; ?>
		<?php endforeach; ?>
	</tbody>
</table>
