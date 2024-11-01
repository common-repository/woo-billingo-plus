<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_value = $this->get_option($key);
if(!$saved_value && isset($data['default'])) {
	$saved_value = $data['default'];
}

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html($data['title']); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<ul class="wc-billingo-plus-settings-checkbox-group">
		<?php foreach ($data['options'] as $option_id => $option): ?>
			<li>
				<label>
					<input <?php disabled( $data['disabled'] ); ?> type="radio" name="woocommerce_wc_billingo_plus_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($option_id); ?>" <?php checked($option_id, $saved_value); ?> />
					<?php echo esc_html($option); ?>
				</label>
			</li>
		<?php endforeach; ?>
		</ul>
		<p class="description"><?php echo esc_html($data['description']); ?></p>
	</td>
</tr>
