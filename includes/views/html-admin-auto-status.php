<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_values = get_option('wc_billingo_plus_'.$key);

//Backward compat
if(!$saved_values && $this->get_option('auto_generate', 'no') == 'yes') {
	$saved_values = array();
	if($this->get_option($key)) {
		$saved_values[] = $this->get_option($key);
	}
}

if(!$saved_values) {
	$saved_values = array();
}

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html($data['title']); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">

		<ul class="wc-billingo-plus-settings-checkbox-group">
		<?php foreach ($data['options'] as $option_id => $option): ?>
			<li>
				<label>
					<input <?php disabled( $data['disabled'] ); ?> type="checkbox" name="wc_billingo_plus_<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($option_id); ?>" <?php checked(in_array($option_id, $saved_values)); ?> />
					<?php echo esc_html($option); ?>
				</label>
			</li>
		<?php endforeach; ?>
		</ul>

		<p class="description"><?php echo esc_html($data['description']); ?></p>
	</td>
</tr>
