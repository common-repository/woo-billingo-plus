<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pro_required = false;
$pro_icon = false;
if(!WC_Billingo_Plus_Pro::is_pro_enabled()) {
	$pro_required = true;
	$pro_icon = '<i class="wc_billingo_pro_label">PRO</i>';
}

//Get saved values
$saved_values = get_option('wc_billingo_plus_notes');

//Setup conditions
$conditions = WC_Billingo_Plus_Conditions::get_conditions('notes');

?>

<tr valign="top">
	<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
	<td class="forminp <?php echo esc_attr( $data['class'] ); ?>">
		<div class="wc-billingo-plus-settings-notes">
			<?php if($saved_values): ?>
				<?php foreach ( $saved_values as $note_id => $note ): ?>
					<div class="wc-billingo-plus-settings-note wc-billingo-plus-settings-repeat-item">
						<textarea placeholder="Megjegyzés szövege…" data-name="wc_billingo_plus_notes[X][note]"><?php echo esc_textarea($note['comment']); ?></textarea>
						<div class="wc-billingo-plus-settings-note-if">
							<div class="wc-billingo-plus-settings-note-if-header">
								<label>
									<input type="checkbox" data-name="wc_billingo_plus_notes[X][condition_enabled]" <?php checked( $note['conditional'] ); ?> class="condition" value="yes">
									<span><?php _e('Add note to invoice, if', 'wc-billingo-plus'); ?></span>
								</label>
								<select data-name="wc_billingo_plus_notes[X][logic]">
									<option value="and" <?php if(isset($note['logic'])) selected( $note['logic'], 'and' ); ?>><?php _e('All', 'wc-billingo-plus'); ?></option>
									<option value="or" <?php if(isset($note['logic'])) selected( $note['logic'], 'or' ); ?>><?php _e('One', 'wc-billingo-plus'); ?></option>
								</select>
								<span><?php _e('of the following match', 'wc-billingo-plus'); ?></span>
								<a href="#" class="delete-note"><?php _e('delete', 'wc-billingo-plus'); ?></a>
							</div>
							<ul class="wc-billingo-plus-settings-note-if-options conditions" <?php if(!$note['conditional']): ?>style="display:none"<?php endif; ?> <?php if(isset($note['conditions'])): ?>data-options="<?php echo esc_attr(json_encode($note['conditions'])); ?>"<?php endif; ?>></ul>
							<div class="wc-billingo-plus-settings-note-if-header wc-billingo-plus-settings-note-if-append" <?php if(!$note['conditional']): ?>style="display:none"<?php endif; ?>>
								<label>
									<input type="checkbox" data-name="wc_billingo_plus_notes[X][append]" <?php if(isset($note['append'])) { checked( $note['append'] ); } ?> value="yes">
									<span><?php _e('Add to the end of an existing note', 'wc-billingo-plus'); ?></span>
								</label>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="wc-billingo-plus-settings-note-add">
			<a href="#" <?php if($pro_icon): ?>data-disabled="true" class="wc-billingo-plus-settings-note-add-disabled" title="<?php _e('You can create multiple notes with the PRO version', 'wc-billingo-plus'); ?>"<?php endif; ?>><span class="dashicons dashicons-plus-alt"></span> <span><?php _e('Add a new note', 'wc-billingo-plus'); ?></span><?php if($pro_icon) echo $pro_icon; ?></a>
		</div>
		<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
	</td>
</tr>

<script type="text/html" id="wc_billingo_plus_note_sample_row">
	<div class="wc-billingo-plus-settings-notes">
		<div class="wc-billingo-plus-settings-note wc-billingo-plus-settings-repeat-item">
			<textarea placeholder="Megjegyzés szövege…" data-name="wc_billingo_plus_notes[X][note]"><?php if(!get_option('wc_billingo_plus_notes')) { echo esc_textarea($this->get_option('note')); } ?></textarea>
			<div class="wc-billingo-plus-settings-note-if">

				<div class="wc-billingo-plus-settings-note-if-header">
					<label>
						<input type="checkbox" data-name="wc_billingo_plus_notes[X][condition_enabled]" class="condition" value="yes">
						<span><?php _e('Add note to invoice, if', 'wc-billingo-plus'); ?></span>
					</label>
					<select data-name="wc_billingo_plus_notes[X][logic]">
						<option value="and"><?php _e('All', 'wc-billingo-plus'); ?></option>
						<option value="or"><?php _e('One', 'wc-billingo-plus'); ?></option>
					</select>
					<span><?php _e('of the following match', 'wc-billingo-plus'); ?></span>
					<a href="#" class="delete-note"><?php _e('delete', 'wc-billingo-plus'); ?></a>
				</div>
				<ul class="wc-billingo-plus-settings-note-if-options conditions" style="display:none"></ul>
				<div class="wc-billingo-plus-settings-note-if-header wc-billingo-plus-settings-note-if-append" style="display:none">
					<label>
						<input type="checkbox" data-name="wc_billingo_plus_notes[X][append]" value="yes">
						<span><?php _e('Add to the end of an existing note', 'wc-billingo-plus'); ?></span>
					</label>
				</div>
			</div>
		</div>
	</div>
</script>

<?php echo WC_Billingo_Plus_Conditions::get_sample_row('notes'); ?>
