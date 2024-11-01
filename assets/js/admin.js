jQuery(document).ready(function($) {
	var wc_billingo_plus_settings = {
		settings_groups: ['accounts', 'coupon', 'vatnumber', 'vatnumber-nav', 'emails', 'vat-override', 'automation', 'download', 'advanced', 'sync', 'receipt'],
		$additional_account_table: $('.wc-billingo-plus-settings–inline-table-accounts'),
		$rounding_table: $('.wc-billingo-plus-settings–inline-table-rounding'),
		$notes_table: $('.wc-billingo-plus-settings-notes'),
		$vat_overrides_table: $('.wc-billingo-plus-settings-vat-overrides'),
		$automations_table: $('.wc-billingo-plus-settings-automations'),
		$advanced_table: $('.wc-billingo-plus-settings-advanced-options'),
		activation_nonce: '',
		init: function() {
			this.init_toggle_groups();
			this.index_rounding_fields();
			this.toggle_sections();

			//Show and hide settings sections
			$('.wc-billingo-plus-settings-section-title').on('click', this.toggle_section);

			//Rounding table add and remove buttons
			this.$rounding_table.on('click', '.add-row', this.rounding_add);
			this.$rounding_table.on('click', '.delete-row', this.rounding_remove);

			//Activate/deactivate pro version
			this.activation_nonce = $('.wc-billingo-plus-settings-sidebar').data('nonce');
			$('#woocommerce_wc_billingo_pro_email').keypress(this.submit_pro_on_enter);
			$('#wc_billingo_plus_activate_pro').on('click', this.submit_activate_form);
			$('#wc_billingo_plus_deactivate_pro').on('click', this.submit_deactivate_form);
			$('#wc_billingo_plus_validate_pro').on('click', this.submit_validate_form);

			//Reload buttons
			var nonce = $('#wc_billingo_plus_load_email_ids_nonce').data('nonce');
			var reloadableFields = ['block_uid', 'bank_account', 'receipt_block'];
			reloadableFields.forEach(function(field){
				var $field = $('#woocommerce_wc_billingo_plus_'+field);
				var self = this;

				if($field.length) {
					$field.parent().find('p.description').before('<a href="#" id="woocommerce_wc_billingo_plus_'+field+'_reload"><span class="dashicons dashicons-update"></span></a>');
					$field.parent().on('click', '#woocommerce_wc_billingo_plus_'+field+'_reload', function(){
						var $button = $(this);
						wc_billingo_plus_settings.refresh_field(field, $button, nonce);
						return false;
					});

					if($field.find('option').length < 1) {
						$field.parent().find('.wc-billingo-plus-settings-error').removeClass('hidden');
					}
				}

			});

			//Hide the rate request box
			$('.wc-billingo-plus-settings-widget-rating .button-secondary').on('click', this.hide_rate_request);

			//Show loading indicators
			var document_types = ['invoice', 'proform', 'deposit', 'void'];
			document_types.forEach(function(type){
				var $select = $('#woocommerce_wc_billingo_plus_email_attachment_'+type).parent();
				$select.block({
					message: null,
					overlayCSS: {
						background: '#ffffff url(' + wc_billingo_plus_params.loading + ') no-repeat center',
						backgroundSize: '16px 16px',
						opacity: 0.6
					}
				});
			});

			//Load email id values
			var data = {
				action: 'wc_billingo_plus_get_email_ids',
				nonce: nonce
			};

			$.post(ajaxurl, data, function(response) {
				response.data.forEach(function(select){
					var selectField = $('#woocommerce_wc_billingo_plus_email_attachment_'+select.field);
					select.options.forEach(function(field){
						var option = new Option(field.label, field.id, false, field.selected);
						selectField.append(option).trigger('change');
					});
					selectField.parent().unblock();
				});

			});

			//Load additional accounts table
			this.$additional_account_table.find('tfoot a').on('click', this.add_new_account_row);
			this.$additional_account_table.on('click', 'a.delete-row', this.delete_account_row);
			this.$additional_account_table.on('change', 'select', this.change_account_select_class);
			if(this.$additional_account_table.find('tbody tr').length < 1) {
				this.add_new_account_row();
			}

			//Conditional logic controls
			var conditional_fields = [this.$notes_table, this.$vat_overrides_table, this.$automations_table, this.$advanced_table];
			var conditional_fields_ids = ['notes', 'vat_overrides', 'automations', 'advanced_options'];

			//Setup conditional fields for notes, vat rates and automations
			conditional_fields.forEach(function(table, index){
				var id = conditional_fields_ids[index];
				var singular = id.slice(0, -1);
				singular = singular.replace('_', '-');
				table.on('change', 'select.condition', {group: id}, wc_billingo_plus_settings.change_x_condition);
				table.on('change', 'select.wc-billingo-plus-settings-repeat-select', function(){wc_billingo_plus_settings.reindex_x_rows(id)});
				table.on('click', '.add-row', {group: id}, wc_billingo_plus_settings.add_new_x_condition_row);
				table.on('click', '.delete-row', {group: id}, wc_billingo_plus_settings.delete_x_condition_row);
				table.on('change', 'input.condition', {group: id}, wc_billingo_plus_settings.toggle_x_condition);
				table.on('click', '.delete-'+singular, {group: id}, wc_billingo_plus_settings.delete_x_row);
				$('.wc-billingo-plus-settings-'+singular+'-add a:not([data-disabled]').on('click', {group: id, table: table}, wc_billingo_plus_settings.add_new_x_row);

				//If we already have some notes, append the conditional logics
				table.find('ul.conditions[data-options]').each(function(){
					var saved_conditions = $(this).data('options');
					var ul = $(this);

					saved_conditions.forEach(function(condition){
						var sample_row = $('#wc_billingo_plus_'+id+'_condition_sample_row').html();
						sample_row = $(sample_row);
						sample_row.find('select.condition').val(condition.category);
						sample_row.find('select.comparison').val(condition.comparison);
						sample_row.find('select.value').removeClass('selected');
						sample_row.find('select[data-condition="'+condition.category+'"]').val(condition.value).addClass('selected').attr('disabled', false);
						ul.append(sample_row);
					});
				});

				if(table.find('.wc-billingo-plus-settings-'+singular).length < 1) {
					$('.wc-billingo-plus-settings-'+singular+'-add a:not([data-disabled]').trigger('click');
				}

				//Reindex the fields
				wc_billingo_plus_settings.reindex_x_rows(id);

			});

		},
		init_toggle_groups: function() {
			$.each(wc_billingo_plus_settings.settings_groups, function( index, value ) {
				var checkbox = $('.wc-billingo-plus-toggle-group-'+value);
				var group_items = $('.wc-billingo-plus-toggle-group-'+value+'-item').parents('tr');
				var group_items_hide = $('.wc-billingo-plus-toggle-group-'+value+'-item-hide').parents('tr');
				var single_items_hide = $('.wc-billingo-plus-toggle-group-'+value+'-cell-hide');
				var checked = checkbox.is(":checked");

				if(value == 'emails' && $('.wc-billingo-plus-toggle-group-'+value+':checked').length) {
					checked = true;
				}

				if(checked) {
					group_items.show();
					group_items_hide.hide();
					single_items_hide.hide();
				} else {
					group_items.hide();
					group_items_hide.show();
					single_items_hide.show();
				}
				checkbox.change(function(e){
					e.preventDefault();

					var checked = $(this).is(":checked");
					if(value == 'emails' && $('.wc-billingo-plus-toggle-group-'+value+':checked').length) {
						checked = true;
					}

					//If vat number field is not checked, uncheck vat number validation field
					if(value == 'vatnumber' && !checked) {
						$('.wc-billingo-plus-toggle-group-vatnumber-nav').attr('checked', false);
						$('.wc-billingo-plus-toggle-group-vatnumber-nav-item').parents('tr').hide();
					}

					//If vat number field is not checked, uncheck vat number validation field
					if(value == 'download' && !checked) {
						$('#woocommerce_wc_billingo_plus_email_attachment_file').prop('checked', false).change();
					}

					if(checked) {
						group_items.show();
						group_items_hide.hide();
						single_items_hide.hide();
					} else {
						group_items.hide();
						group_items_hide.show();
						single_items_hide.show();
					}
				});
			});
		},
		rounding_add: function() {
			var table = $(this).closest('tbody');
			var row = $(this).closest('tr');
			table.append(row.clone());
			wc_billingo_plus_settings.index_rounding_fields();
			return false;
		},
		rounding_remove: function() {
			var row = $(this).closest('tr');
			row.remove();
			wc_billingo_plus_settings.index_rounding_fields();
			return false;
		},
		index_rounding_fields: function() {
			$('.wc-billingo-plus-settings–inline-table-rounding tbody tr').each(function(index, row){
				$(this).find('.wc-billingo-plus-rounding-table-currency select').attr('name', 'wc_billingo_plus_rounding_options['+index+'][currency]');
				$(this).find('.wc-billingo-plus-rounding-table-rounding select').attr('name', 'wc_billingo_plus_rounding_options['+index+'][rounding]');
			});
		},
		submit_pro_on_enter: function(e) {
			if (e.which == 13) {
				$('#wc_billingo_plus_activate_pro').click();
				return false;
			}
		},
		submit_activate_form: function() {
			var key = $('#woocommerce_wc_billingo_pro_key').val();
			var button = $(this);
			var form = button.parents('.wc-billingo-plus-settings-widget');

			var data = {
				action: 'wc_billingo_plus_license_activate',
				key: key,
				nonce: wc_billingo_plus_settings.activation_nonce
			};

			console.log(data);

			form.block({
				message: null,
				overlayCSS: {
					background: '#ffffff url(' + wc_billingo_plus_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			form.find('.wc-billingo-plus-settings-widget-pro-notice').hide();

			$.post(ajaxurl, data, function(response) {
				//Remove old messages
				if(response.success) {
					window.location.reload();
					return;
				} else {
					form.find('.wc-billingo-plus-settings-widget-pro-notice p').html(response.data.message);
					form.find('.wc-billingo-plus-settings-widget-pro-notice').show();
				}
				form.unblock();
			});

			return false;
		},
		submit_deactivate_form: function() {
			var button = $(this);
			var form = button.parents('.wc-billingo-plus-settings-widget');

			var data = {
				action: 'wc_billingo_plus_license_deactivate',
				nonce: wc_billingo_plus_settings.activation_nonce
			};

			form.block({
				message: null,
				overlayCSS: {
					background: '#ffffff url(' + wc_billingo_plus_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			form.find('.notice').hide();

			$.post(ajaxurl, data, function(response) {
				//Remove old messages
				if(response.success) {
					window.location.reload();
					return;
				} else {
					form.find('.notice p').html(response.data.message);
					form.find('.notice').show();
				}
				form.unblock();
			});
			return false;
		},
		submit_validate_form: function() {
			var button = $(this);
			var form = button.parents('.wc-billingo-plus-settings-widget');

			var data = {
				action: 'wc_billingo_plus_license_validate',
				nonce: wc_billingo_plus_settings.activation_nonce
			};

			form.block({
				message: null,
				overlayCSS: {
					background: '#ffffff url(' + wc_billingo_plus_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			form.find('.notice').hide();

			$.post(ajaxurl, data, function(response) {
				window.location.reload();
			});
			return false;
		},
		hide_rate_request: function() {
			var nonce = $(this).data('nonce');
			var form = $(this).parents('.wc-billingo-plus-settings-widget');
			var data = {
				action: 'wc_billingo_plus_hide_rate_request',
				nonce: nonce
			};

			form.block({
				message: null,
				overlayCSS: {
					background: '#ffffff url(' + wc_billingo_plus_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});

			$.post(ajaxurl, data, function(response) {
				form.slideUp();
			});
		},
		toggle_section: function() {
			$(this).toggleClass('open');

			//Remember selection
			var sections = [];
			$('.wc-billingo-plus-settings-section-title.open').each(function(){
				sections.push($(this).find('h3').attr('id'));
			});
			localStorage.setItem('wc_billingo_plus_settings_open_sections_v3', JSON.stringify(sections));
		},
		toggle_sections: function() {
			var data = JSON.parse(localStorage.getItem('wc_billingo_plus_settings_open_sections_v3'));
			if(data) {
				data.forEach(function(section_id){
					$('#'+section_id).parent().addClass('open');
				});
			} else {
				$('#woocommerce_wc_billingo_plus_section_auth').parent().addClass('open');
				$('#woocommerce_wc_billingo_plus_section_invoice').parent().addClass('open');
			}

			//If theres no api key, hide other fields for now
			if($('#woocommerce_wc_billingo_plus_api_key').val() == '') {
				$('.wc-billingo-plus-settings-section-title:not(.open)').each(function(){
					$(this).hide();
				});
				$('.wc-billingo-plus-settings-widget:not(.v3-update-notice)').hide();
			}

		},
		refresh_field: function(field, button, nonce) {
			var $this = button;
			var data = {
				action: 'wc_billingo_plus_reload_'+field,
				nonce: nonce
			};

			if(!$this.hasClass('loading')) {
				$this.addClass('loading');
				$.post(ajaxurl, data, function(response) {
					$this.removeClass('loading');
					$this.addClass('loaded');
					setTimeout(function() {
						$this.removeClass('loaded');
					}, 1000);
					if(response.data) {
						var $select = $('#woocommerce_wc_billingo_plus_'+field);
						var currentOption = $select.val();
						$select.val(null).empty();
						response.data.forEach(function(block){
							var newOption = new Option(block.name, block.id, true, true);
							$select.append(newOption).trigger('change');
						});
						$select.val(currentOption);
						$select.trigger('change');
					}
				});
			}

			return false;
		},
		add_new_account_row: function() {
			var sample_row = $('#wc_billingo_plus_additional_accounts_sample_row').html();
			wc_billingo_plus_settings.$additional_account_table.find('tbody').append(sample_row);
			wc_billingo_plus_settings.reindex_account_rows();
			return false;
		},
		delete_account_row: function() {
			var row = $(this).closest('tr').remove();
			wc_billingo_plus_settings.reindex_account_rows();

			//Add empty row if no rows left
			if(wc_billingo_plus_settings.$additional_account_table.find('tbody tr').length < 1) {
				wc_billingo_plus_settings.add_new_account_row();
			}

			return false;
		},
		reindex_account_rows: function() {
			var sample_row = $('#wc_billingo_plus_additional_accounts_sample_row').html();
			wc_billingo_plus_settings.$additional_account_table.find('tbody tr').each(function(index){
				$(this).find('input, select').each(function(){
					var name = $(this).data('name');
					name = name.replace('X', index);
					$(this).attr('name', name);
				});
			});
			return false;
		},
		change_account_select_class: function() {
			if(this.selectedIndex === 0) {
				$(this).addClass('placeholder');
			} else {
				$(this).removeClass('placeholder');
			}
		},
		change_x_condition: function(event) {
			var condition = $(this).val();

			//Hide all selects and make them disabled(so it won't be in $_POST)
			$(this).parent().find('select.value').removeClass('selected').prop('disabled', true);
			$(this).parent().find('select.value[data-condition="'+condition+'"]').addClass('selected').prop('disabled', false);
		},
		add_new_x_condition_row: function(event) {
			var sample_row = $('#wc_billingo_plus_'+event.data.group+'_condition_sample_row').html();
			$(this).closest('ul').append(sample_row);
			wc_billingo_plus_settings.reindex_x_rows(event.data.group);
			return false;
		},
		delete_x_condition_row: function(event) {
			$(this).parent().remove();
			wc_billingo_plus_settings.reindex_x_rows(event.data.group);
			return false;
		},
		reindex_x_rows: function(group) {
			var group = group.replace('_', '-');
			$('.wc-billingo-plus-settings-'+group).find('.wc-billingo-plus-settings-repeat-item').each(function(index){
				$(this).find('textarea, select, input').each(function(){
					var name = $(this).data('name');
					name = name.replace('X', index);
					$(this).attr('name', name);
				});

				//Reindex conditions too
				$(this).find('li').each(function(index_child){
					$(this).find('select').each(function(){
						var name = $(this).data('name');
						name = name.replace('Y', index_child);
						name = name.replace('X', index);
						$(this).attr('name', name);
					});
				});

				$(this).find('.wc-billingo-plus-settings-repeat-select').each(function(){
					var val = $(this).val();
					if($(this).hasClass('wc-billingo-plus-settings-advanced-option-property')) {
						var $value_select = $(this).parents('.wc-billingo-plus-settings-advanced-option-title').find('.wc-billingo-plus-settings-advanced-option-value');
						$value_select.find('option').hide();
						$value_select.find('option[value^="'+val+'"]').show();
						if(!$value_select.val().includes(val)) {
							$value_select.find('option[value^="'+val+'"]').first().prop('selected', true);
						}
					}

					var label = $(this).find('option:selected').text();
					$(this).parent().find('label span').text(label);
					$(this).parent().find('label span').text(label);
					$(this).parent().find('label i').removeClass().addClass(val);
				});

			});
			return false;
		},
		add_new_x_row: function(event) {
			var group = event.data.group;
			var table = event.data.table;
			var singular = group.slice(0, -1);
			var sample_row = $('#wc_billingo_plus_'+singular+'_sample_row').html();
			var sample_row_conditon = $('#wc_billingo_plus_'+group+'_condition_sample_row').html();
			sample_row = $(sample_row);
			sample_row.find('ul').append(sample_row_conditon);
			table.append(sample_row);
			wc_billingo_plus_settings.reindex_x_rows(group);
			return false;
		},
		toggle_x_condition: function(event) {
			var group = event.data.group;
			var checked = $(this).is(":checked");
			var note = $(this).closest('.wc-billingo-plus-settings-repeat-item').find('ul.conditions');
			if(checked) {
				//Add empty row if no condtions exists
				if(note.find('li').length < 1) {
					var sample_row = $('#wc_billingo_plus_'+group+'_condition_sample_row').html();
					note.append(sample_row);
				}
				note.show();
			} else {
				note.hide();
			}

			//Slightly different for notes
			if(group == 'notes') {
				var append = $(this).closest('.wc-billingo-plus-settings-note').find('.wc-billingo-plus-settings-note-if-append');
				if(checked) {
					append.show();
				} else {
					append.hide();
				}
			}

			//Slightly different for automations
			if(group == 'automations') {
				var automation = $(this).closest('.wc-billingo-plus-settings-automation').find('.wc-billingo-plus-settings-automation-if');
				if(checked) {
					automation.show();
				} else {
					automation.hide();
				}
			}

			wc_billingo_plus_settings.reindex_x_rows(event.data.group);
		},
		delete_x_row: function(event) {
			$(this).closest('.wc-billingo-plus-settings-repeat-item').remove();
			wc_billingo_plus_settings.reindex_x_rows(event.data.group);
			return false;
		},
		add_new_vat_overrides_row: function() {
			var sample_row = $('#wc_billingo_plus_vat_override_sample_row').html();
			var sample_row_conditon = $('#wc_billingo_plus_vat_overrides_condition_sample_row').html();
			sample_row = $(sample_row);
			sample_row.find('ul').append(sample_row_conditon);
			console.log(sample_row);
			wc_billingo_plus_settings.$vat_overrides_table.append(sample_row);
			wc_billingo_plus_settings.reindex_x_rows('vat_overrides');
			return false;
		},
	}

	// Hide notice
	$( '.wc-billingo-plus-notice .wc-billingo-plus-hide-notice').on('click', function(e) {
		e.preventDefault();
		var el = $(this).closest('.wc-billingo-plus-notice');
		$(el).find('.wc-billingo-plus-wait').remove();
		$(el).append('<div class="wc-billingo-plus-wait"></div>');
		if ( $('.wc-billingo-plus-notice.updating').length > 0 ) {
			var button = $(this);
			setTimeout(function(){
				button.triggerHandler( 'click' );
			}, 100);
			return false;
		}
		$(el).addClass('updating');
		$.post( ajaxurl, {
				action: 'wc_billingo_plus_hide_notice',
				security: $(this).data('nonce'),
				notice: $(this).data('notice'),
				remind: $(this).hasClass( 'remind-later' ) ? 'yes' : 'no'
		}, function(){
			$(el).removeClass('updating');
			$(el).fadeOut(100);
		});
	});

	$( '.wc-billingo-plus-notice .wc-billingo-plus-migrate-button').on('click', function(e) {
		e.preventDefault();
		var $this = $(this);
		var $el = $(this).closest('.wc-billingo-plus-notice');
		$el.find('.wc-billingo-plus-wait').remove();
		$el.append('<div class="wc-billingo-plus-wait"></div>');
		$el.addClass('updating');
		$.post( ajaxurl, {
			action: 'wc_billingo_plus_migrate',
			security: $(this).data('nonce')
		}, function(){
			window.location.href = $this.attr('href');
		});
	});

	//Metabox functions
	var wc_billingo_plus_metabox = {
		prefix: 'wc_billingo_plus_',
		prefix_id: '#wc_billingo_plus_',
		prefix_class: '.wc-billingo-plus-',
		$metaboxContent: $('#wc_billingo_plus_metabox .inside'),
		$disabledState: $('.wc-billingo-plus-metabox-disabled'),
		$optionsContent: $('.wc-billingo-plus-metabox-generate-options'),
		$autoMsg: $('.wc-billingo-plus-metabox-auto-msg'),
		$generateContent: $('.wc-billingo-plus-metabox-generate'),
		$optionsButton: $('#wc_billingo_plus_invoice_options'),
		$previewButton: $('#wc_billingo_plus_invoice_preview'),
		$generateButton: $('#wc_billingo_plus_invoice_generate'),
		$generateButtonReceipt: $('#wc_billingo_plus_receipt_generate'),
		$receiptRowVoidNote: $('.wc-billingo-plus-metabox-receipt-void-note'),
		$invoiceRow: $('.wc-billingo-plus-metabox-invoices-invoice'),
		$receiptRow: $('.wc-billingo-plus-metabox-invoices-receipt'),
		$proformRow: $('.wc-billingo-plus-metabox-invoices-proform'),
		$depositRow: $('.wc-billingo-plus-metabox-invoices-deposit'),
		$draftRow: $('.wc-billingo-plus-metabox-invoices-draft'),
		$waybillRow: $('.wc-billingo-plus-metabox-invoices-waybill'),
		$offerRow: $('.wc-billingo-plus-metabox-invoices-offer'),
		$voidedRow: $('.wc-billingo-plus-metabox-invoices-void'),
		$voidedReceiptRow: $('.wc-billingo-plus-metabox-invoices-void_receipt'),
		$completeRow: $('.wc-billingo-plus-metabox-rows-data-complete'),
		$emailRow: $('.wc-billingo-plus-metabox-rows-data-email'),
		$voidRow: $('.wc-billingo-plus-metabox-rows-data-void'),
		$voidReasonRow: $('.wc-billingo-plus-metabox-rows-data-void-reason'),
		$messages: $('.wc-billingo-plus-metabox-messages'),
		$reverseReceiptButton: $('#wc_billingo_plus_reverse_receipt'),
		nonce: $('.wc-billingo-plus-metabox-content').data('nonce'),
		order: $('.wc-billingo-plus-metabox-content').data('order'),
		optionsTocuhed: false,
		is_receipt: false,
		init: function() {
			this.$optionsButton.on( 'click', this.show_options );
			$(this.prefix_class+'invoice-toggle').on( 'click', this.toggle_invoice );

			this.$previewButton.on( 'click', this.show_preview);
			this.$generateButton.on( 'click', this.generate_invoice );
			this.$generateButtonReceipt.on( 'click', this.generate_invoice );
			this.$completeRow.find('a').on( 'click', this.mark_completed );
			this.$emailRow.find('a').on( 'click', this.resend_email );
			this.$voidRow.find('a').on( 'click', this.void_invoice );
			this.$voidReasonRow.find('textarea').on( 'focus', this.void_invoice_reason_focus );
			this.$voidReasonRow.find('textarea').on( 'blur', this.void_invoice_reason_blur );

			this.$messages.find('a').on( 'click', this.hide_message );

			this.$reverseReceiptButton.on( 'click', this.reverse_receipt );

			if(this.$generateButtonReceipt.length) {
				this.is_receipt = true;
			}

			$(document).on( 'heartbeat-tick', function ( event, data ) {
				if (data.wc_billingo_plus_pdf_download_link_invoice) {
					if(wc_billingo_plus_metabox.$invoiceRow.find('a').attr('href') != data.wc_billingo_plus_pdf_download_link_invoice) {
						wc_billingo_plus_metabox.$invoiceRow.removeClass('pending');
						wc_billingo_plus_metabox.$invoiceRow.find('a').attr('href', data.wc_billingo_plus_pdf_download_link_invoice);
					}
				}

				if (data.wc_billingo_plus_pdf_download_link_void) {
					if(wc_billingo_plus_metabox.$voidedRow.find('a').attr('href') != data.wc_billingo_plus_pdf_download_link_void) {
						wc_billingo_plus_metabox.$voidedRow.removeClass('pending');
						wc_billingo_plus_metabox.$voidedRow.find('a').attr('href', data.wc_billingo_plus_pdf_download_link_void);
					}
				}

				if (data.wc_billingo_plus_pdf_download_link_draft) {
					if(wc_billingo_plus_metabox.$draftRow.find('a').attr('href') != data.wc_billingo_plus_pdf_download_link_draft) {
						wc_billingo_plus_metabox.$draftRow.removeClass('pending');
						wc_billingo_plus_metabox.$draftRow.find('a').attr('href', data.wc_billingo_plus_pdf_download_link_draft);
					}
				}

				if (data.wc_billingo_plus_pdf_download_link_proform) {
					if(wc_billingo_plus_metabox.$proformRow.find('a').attr('href') != data.wc_billingo_plus_pdf_download_link_proform) {
						wc_billingo_plus_metabox.$proformRow.removeClass('pending');
						wc_billingo_plus_metabox.$proformRow.find('a').attr('href', data.wc_billingo_plus_pdf_download_link_proform);
					}
				}

				if (data.wc_billingo_plus_pdf_download_link_waybill) {
					if(wc_billingo_plus_metabox.$waybillRow.find('a').attr('href') != data.wc_billingo_plus_pdf_download_link_waybill) {
						wc_billingo_plus_metabox.$waybillRow.removeClass('pending');
						wc_billingo_plus_metabox.$waybillRow.find('a').attr('href', data.wc_billingo_plus_pdf_download_link_waybill);
					}
				}

				if (data.wc_billingo_plus_pdf_download_link_offer) {
					if(wc_billingo_plus_metabox.$offerRow.find('a').attr('href') != data.wc_billingo_plus_pdf_download_link_offer) {
						wc_billingo_plus_metabox.$offerRow.removeClass('pending');
						wc_billingo_plus_metabox.$offerRow.find('a').attr('href', data.wc_billingo_plus_pdf_download_link_offer);
					}
				}
			});

			wc_billingo_plus_metabox.$optionsContent.find('input, select').change(function(){
				wc_billingo_plus_metabox.optionsTocuhed = true;
			});

		},
		loading_indicator: function(button, color) {
			wc_billingo_plus_metabox.hide_message();
			button.block({
				message: null,
				overlayCSS: {
					background: color+' url(' + wc_billingo_plus_params.loading + ') no-repeat center',
					backgroundSize: '16px 16px',
					opacity: 0.6
				}
			});
		},
		show_options: function() {
			wc_billingo_plus_metabox.$optionsButton.toggleClass('active');
			wc_billingo_plus_metabox.$optionsContent.slideToggle();
			return false;
		},
		toggle_invoice: function() {
			var note = '';

			//Ask for message
			if($(this).hasClass('off')) {
				note = prompt("Számlakészítés kikapcsolása. Mi az indok?", "Ehhez a rendeléshez nem kell számla.");
				if (!note) {
					return false;
				}
			}

			//Create request
			var data = {
				action: wc_billingo_plus_metabox.prefix+'toggle_invoice',
				nonce: wc_billingo_plus_metabox.nonce,
				order: wc_billingo_plus_metabox.order,
				note: note
			};

			//Show loading indicator
			wc_billingo_plus_metabox.loading_indicator(wc_billingo_plus_metabox.$metaboxContent, '#fff');

			//Make request
			$.post(ajaxurl, data, function(response) {

				//Replace text
				wc_billingo_plus_metabox.$disabledState.find('span').text(note);

				//Hide loading indicator
				wc_billingo_plus_metabox.$metaboxContent.unblock();

				//Show/hide divs based on response
				if (response.data.state == 'off') {
					wc_billingo_plus_metabox.$disabledState.slideDown();
					wc_billingo_plus_metabox.$optionsContent.slideUp();
					wc_billingo_plus_metabox.$autoMsg.slideUp();
					wc_billingo_plus_metabox.$generateContent.slideUp();
					wc_billingo_plus_metabox.$voidedRow.slideUp();
				} else {
					wc_billingo_plus_metabox.$disabledState.slideUp();
					wc_billingo_plus_metabox.$autoMsg.slideDown();
					wc_billingo_plus_metabox.$generateContent.slideDown();
				}
			});

			return false;
		},
		add_to_heartbeat: function(document_type) {
			$( document ).on( 'heartbeat-send', function ( event, data ) {
				data.wc_billingo_plus_pdf_download = document_type;
				data.wc_billingo_plus_order_id = wc_billingo_plus_metabox.order;
			});
		},
		generate_invoice: function() {
			var $this = $(this);
			var r = confirm($this.data('question'));
			var type = 'invoice';
			if (r != true) {
				return false;
			}

			var account = $('#wc_billingo_plus_invoice_account').val();
			var lang = $('#wc_billingo_plus_invoice_lang').val();
			var doc_type = $('#wc_billingo_plus_invoice_doc_type').val();
			var note = $('#wc_billingo_plus_invoice_note').val();
			var deadline = $('#wc_billingo_plus_invoice_deadline').val();
			var completed = $('#wc_billingo_plus_invoice_completed').val();
			var proform = $('#wc_billingo_plus_invoice_proform').is(':checked');
			var draft = $('#wc_billingo_plus_invoice_draft').is(':checked');
			var deposit = $('#wc_billingo_plus_invoice_deposit').is(':checked');
			var offer = $('#wc_billingo_plus_invoice_offer').is(':checked');
			var waybill = $('#wc_billingo_plus_invoice_waybill').is(':checked');
			if (proform) type = 'proform';
			if (draft) type = 'draft';
			if (deposit) type = 'deposit';
			if (offer) type = 'offer';
			if (waybill) type = 'waybill';

			//If order is a receipt, set type to fixed receipt
			if (wc_billingo_plus_metabox.is_receipt) type = 'receipt';

			//Create request
			var data = {
				action: wc_billingo_plus_metabox.prefix+'generate_invoice',
				nonce: wc_billingo_plus_metabox.nonce,
				order: wc_billingo_plus_metabox.order,
				account: account,
				lang: lang,
				doc_type: doc_type,
				note: note,
				deadline: deadline,
				completed: completed,
				type: type,
				custom: wc_billingo_plus_metabox.optionsTocuhed,
			};

			//Check for paid status
			if($('#wc_billingo_plus_invoice_paid').length) {
				var paid = $('#wc_billingo_plus_invoice_paid').is(':checked');
				data.paid = paid;
			}

			//Show loading indicator
			wc_billingo_plus_metabox.loading_indicator(wc_billingo_plus_metabox.$metaboxContent, '#fff');

			//Make request
			$.post(ajaxurl, data, function(response) {

				//Hide loading indicator
				wc_billingo_plus_metabox.$metaboxContent.unblock();

				//Show success/error messages
				wc_billingo_plus_metabox.show_messages(response);

				//On success and error
				if(response.data.error) {

				} else {

					var is_pdf_pending = (response.data.pdf && response.data.pdf == 'pending');

					if(response.data.type == 'invoice') {
						wc_billingo_plus_metabox.$autoMsg.slideUp();
						wc_billingo_plus_metabox.$generateContent.slideUp();
						wc_billingo_plus_metabox.$voidedRow.slideUp();

						wc_billingo_plus_metabox.$invoiceRow.find('strong').text(response.data.name);
						wc_billingo_plus_metabox.$invoiceRow.find('a').attr('href', response.data.link);
						wc_billingo_plus_metabox.$invoiceRow.slideDown();
						wc_billingo_plus_metabox.$completeRow.slideDown();
						wc_billingo_plus_metabox.$emailRow.slideDown();
						wc_billingo_plus_metabox.$voidRow.slideDown();

						if(is_pdf_pending) {
							wc_billingo_plus_metabox.$invoiceRow.addClass('pending');
							wc_billingo_plus_metabox.add_to_heartbeat('invoice');
						}

						if(response.data.paid) {
							wc_billingo_plus_metabox.$completeRow.find('a').text(response.data.paid);
							wc_billingo_plus_metabox.$completeRow.find('a').addClass('completed');
						}
					}

					if(response.data.type == 'receipt') {
						wc_billingo_plus_metabox.$autoMsg.slideUp();
						wc_billingo_plus_metabox.$generateContent.slideUp();
						wc_billingo_plus_metabox.$voidedRow.slideUp();

						wc_billingo_plus_metabox.$receiptRow.find('strong').text(response.data.name);
						wc_billingo_plus_metabox.$receiptRow.find('a').attr('href', response.data.link);
						wc_billingo_plus_metabox.$receiptRow.slideDown();
						wc_billingo_plus_metabox.$completeRow.slideDown();
						wc_billingo_plus_metabox.$emailRow.slideDown();
						wc_billingo_plus_metabox.$voidRow.slideDown();

						if(is_pdf_pending) {
							wc_billingo_plus_metabox.$receiptRow.addClass('pending');
							wc_billingo_plus_metabox.add_to_heartbeat('receipt');
						}

						if(response.data.paid) {
							wc_billingo_plus_metabox.$completeRow.find('a').text(response.data.paid);
							wc_billingo_plus_metabox.$completeRow.find('a').addClass('completed');
						}
					}

					if(response.data.type == 'proform') {
						$('#wc_billingo_plus_invoice_normal').prop('checked', true);
						wc_billingo_plus_metabox.$optionsContent.slideUp();
						wc_billingo_plus_metabox.$proformRow.find('strong').text(response.data.name);
						wc_billingo_plus_metabox.$proformRow.find('a').attr('href', response.data.link);
						wc_billingo_plus_metabox.$proformRow.slideDown();
						wc_billingo_plus_metabox.$voidedRow.slideUp();

						if(is_pdf_pending) {
							wc_billingo_plus_metabox.$proformRow.addClass('pending');
							wc_billingo_plus_metabox.add_to_heartbeat('proform');
						}
					}

					if(response.data.type == 'draft') {
						$('#wc_billingo_plus_invoice_normal').prop('checked', true);
						wc_billingo_plus_metabox.$optionsContent.slideUp();
						wc_billingo_plus_metabox.$draftRow.find('strong').text(response.data.name);
						wc_billingo_plus_metabox.$draftRow.find('a').attr('href', response.data.link);
						wc_billingo_plus_metabox.$draftRow.slideDown();
						wc_billingo_plus_metabox.$voidedRow.slideUp();

						if(is_pdf_pending) {
							wc_billingo_plus_metabox.$draftRow.addClass('pending');
							wc_billingo_plus_metabox.add_to_heartbeat('draft');
						}
					}

					if(response.data.type == 'deposit') {
						$('#wc_billingo_plus_invoice_normal').prop('checked', true);
						wc_billingo_plus_metabox.$optionsContent.slideUp();
						wc_billingo_plus_metabox.$depositRow.find('strong').text(response.data.name);
						wc_billingo_plus_metabox.$depositRow.find('a').attr('href', response.data.link);
						wc_billingo_plus_metabox.$depositRow.slideDown();
						wc_billingo_plus_metabox.$voidedRow.slideUp();
					}

					if(response.data.type == 'waybill') {
						$('#wc_billingo_plus_invoice_normal').prop('checked', true);
						wc_billingo_plus_metabox.$optionsContent.slideUp();
						wc_billingo_plus_metabox.$waybillRow.find('strong').text(response.data.name);
						wc_billingo_plus_metabox.$waybillRow.find('a').attr('href', response.data.link);
						wc_billingo_plus_metabox.$waybillRow.slideDown();
						wc_billingo_plus_metabox.$voidedRow.slideUp();

						if(is_pdf_pending) {
							wc_billingo_plus_metabox.$voidedRow.addClass('pending');
							wc_billingo_plus_metabox.add_to_heartbeat('waybill');
						}
					}

					if(response.data.type == 'offer') {
						$('#wc_billingo_plus_invoice_normal').prop('checked', true);
						wc_billingo_plus_metabox.$optionsContent.slideUp();
						wc_billingo_plus_metabox.$offerRow.find('strong').text(response.data.name);
						wc_billingo_plus_metabox.$offerRow.find('a').attr('href', response.data.link);
						wc_billingo_plus_metabox.$offerRow.slideDown();
						wc_billingo_plus_metabox.$voidedRow.slideUp();

						if(is_pdf_pending) {
							wc_billingo_plus_metabox.$offerRow.addClass('pending');
							wc_billingo_plus_metabox.add_to_heartbeat('offer');
						}
					}

				}

			});

			return false;
		},
		mark_completed_timeout: false,
		mark_completed: function() {
			var $this = $(this);

			//Do nothing if already marked completed
			if($this.hasClass('completed')) return false;

			if($this.hasClass('confirm')) {

				//Reset timeout
				clearTimeout(wc_billingo_plus_metabox.mark_completed_timeout);

				//Show loading indicator
				wc_billingo_plus_metabox.loading_indicator(wc_billingo_plus_metabox.$completeRow, '#fff');

				//Create request
				var data = {
					action: wc_billingo_plus_metabox.prefix+'mark_completed',
					nonce: wc_billingo_plus_metabox.nonce,
					order: wc_billingo_plus_metabox.order,
				};

				$.post(ajaxurl, data, function(response) {

					//Hide loading indicator
					wc_billingo_plus_metabox.$completeRow.unblock();

					//Show success/error messages
					wc_billingo_plus_metabox.show_messages(response);

					if(response.data.error) {
						//On success and error
						$this.fadeOut(function(){
							$this.text($this.data('trigger-value'));
							$this.removeClass('confirm');
							$this.fadeIn();
						});
					} else {
						//On success and error
						$this.fadeOut(function(){
							$this.text(response.data.completed);
							$this.addClass('completed');
							$this.fadeIn();
							$this.removeClass('confirm');
						});
					}

				});

			} else {
				wc_billingo_plus_metabox.mark_completed_timeout = setTimeout(function(){
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});
				}, 5000);

				$this.addClass('confirm');
				$this.fadeOut(function(){
					$this.text('Biztos?')
					$this.fadeIn();
				});
			}

		},
		resend_email: function() {
			var $this = $(this);

			//Show loading indicator
			wc_billingo_plus_metabox.loading_indicator(wc_billingo_plus_metabox.$emailRow, '#fff');

			//Create request
			var data = {
				action: wc_billingo_plus_metabox.prefix+'resend_email',
				nonce: wc_billingo_plus_metabox.nonce,
				order: wc_billingo_plus_metabox.order,
			};

			$.post(ajaxurl, data, function(response) {

				//Hide loading indicator
				wc_billingo_plus_metabox.$emailRow.unblock();

				//Show success/error messages
				wc_billingo_plus_metabox.show_messages(response);

				//On success and error
				$this.fadeOut(function(){
					if(response.data.error) {
						$this.removeClass('completed');
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
					} else {
						$this.text($this.data('done'));
						$this.addClass('completed');
						$this.fadeIn();
						setTimeout(function(){
							$this.fadeOut(function(){
								$this.removeClass('completed');
								$this.text($this.data('trigger-value'));
								$this.fadeIn();
							});
						}, 3000);
					}
				});

			});


		},
		void_invoice_timeout: false,
		void_invoice: function() {
			var $this = $(this);

			//Do nothing if already marked completed
			if($this.hasClass('confirm')) {

				//Reset timeout
				clearTimeout(wc_billingo_plus_metabox.void_invoice_timeout);
				wc_billingo_plus_metabox.$voidReasonRow.slideUp();

				//Show loading indicator
				wc_billingo_plus_metabox.loading_indicator(wc_billingo_plus_metabox.$voidRow, '#fff');

				//Get textarea vale
				var reason = $('#wc_billingo_plus_void_note').val();

				//Create request
				var data = {
					action: wc_billingo_plus_metabox.prefix+'void_invoice',
					nonce: wc_billingo_plus_metabox.nonce,
					order: wc_billingo_plus_metabox.order,
					reason: reason
				};

				$.post(ajaxurl, data, function(response) {

					//Hide loading indicator
					wc_billingo_plus_metabox.$voidRow.unblock();

					//Show success/error messages
					wc_billingo_plus_metabox.show_messages(response);

					//On success and error
					if(response.data.error) {

					} else {
						var is_pdf_pending = (response.data.pdf && response.data.pdf == 'pending');

						wc_billingo_plus_metabox.$invoiceRow.slideUp();
						wc_billingo_plus_metabox.$proformRow.slideUp();
						wc_billingo_plus_metabox.$draftRow.slideUp();
						wc_billingo_plus_metabox.$completeRow.slideUp();
						wc_billingo_plus_metabox.$emailRow.slideUp();
						wc_billingo_plus_metabox.$voidRow.slideUp(function(){
							$this.text(response.data.completed);
							$this.removeClass('confirm');
						});

						if(is_pdf_pending) {
							wc_billingo_plus_metabox.$voidedRow.addClass('pending');
							wc_billingo_plus_metabox.add_to_heartbeat('void');
						}

						if(response.data.name) {
							wc_billingo_plus_metabox.$voidedRow.find('strong').text(response.data.name);
							wc_billingo_plus_metabox.$voidedRow.find('a').attr('href', response.data.link);
							wc_billingo_plus_metabox.$voidedRow.slideDown();
						}

						wc_billingo_plus_metabox.$generateContent.slideDown();
						wc_billingo_plus_metabox.$autoMsg.slideDown();

					}

					//On success and error
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});

				});

			} else {
				wc_billingo_plus_metabox.void_invoice_timeout = setTimeout(function(){
					$this.fadeOut(function(){
						$this.text($this.data('trigger-value'));
						$this.fadeIn();
						$this.removeClass('confirm');
					});
					wc_billingo_plus_metabox.$voidReasonRow.slideUp();
				}, 5000);

				$this.addClass('confirm');
				$this.fadeOut(function(){
					$this.text($this.data('question'))
					$this.fadeIn();
				});

				wc_billingo_plus_metabox.$voidReasonRow.slideDown();
			}

			return false;
		},
		void_invoice_reason_focus: function(){
			console.log('void_invoice_reason_focus');
			clearTimeout(wc_billingo_plus_metabox.void_invoice_timeout);
		},
		void_invoice_reason_blur: function(){
			clearTimeout(wc_billingo_plus_metabox.void_invoice_timeout);
			var $this = wc_billingo_plus_metabox.$voidRow.find('a');
			wc_billingo_plus_metabox.void_invoice_timeout = setTimeout(function(){
				$this.fadeOut(function(){
					$this.text($this.data('trigger-value'));
					$this.fadeIn();
					$this.removeClass('confirm');
				});
				wc_billingo_plus_metabox.$voidReasonRow.slideUp();
			}, 5000);
		},
		show_messages: function(response) {
			if(response.data.messages && response.data.messages.length > 0) {
				this.$messages.removeClass('wc-billingo-plus-metabox-messages-success');
				this.$messages.removeClass('wc-billingo-plus-metabox-messages-error');

				if(response.data.error) {
					this.$messages.addClass('wc-billingo-plus-metabox-messages-error');
				} else {
					this.$messages.addClass('wc-billingo-plus-metabox-messages-success');
				}

				$ul = this.$messages.find('ul');
				$ul.html('');

				$.each(response.data.messages, function(i, value) {
					var li = $('<li>')
					li.append(value);
					$ul.append(li);
				});
				this.$messages.slideDown();
			}
		},
		hide_message: function() {
			wc_billingo_plus_metabox.$messages.slideUp();
			return false;
		},
		reverse_receipt: function() {
			//Create request
			var data = {
				action: wc_billingo_plus_metabox.prefix+'reverse_receipt',
				nonce: wc_billingo_plus_metabox.nonce,
				order: wc_billingo_plus_metabox.order
			};

			//Show loading indicator
			wc_billingo_plus_metabox.loading_indicator(wc_billingo_plus_metabox.$metaboxContent, '#fff');

			//Make request
			$.post(ajaxurl, data, function(response) {
				window.location.reload();
			});
		},
		show_preview: function() {
			var note = $('#wc_billingo_plus_invoice_note').val();
			var deadline = $('#wc_billingo_plus_invoice_deadline').val();
			var completed = $('#wc_billingo_plus_invoice_completed').val();
			var account = $('#wc_billingo_plus_invoice_account').val();
			var url = $(this).data('url');
			var params = {'note': note, 'deadline': deadline, 'completed': completed, 'account': account};
			url += '&' + $.param(params);

			//Change url to include options
			$(this).attr('href', url);

			return true;
		}
	}

	//Bulk actions
	var wc_billingo_plus_bulk_actions = {
		init: function() {
			var printAction = $('#wc-billingo-plus-bulk-print');
			var downloadAction = $('#wc-billingo-plus-bulk-download');
			printAction.on( 'click', this.printInvoices );
			if(printAction.length) {
				printAction.trigger('click');
			}

			$( '#wpbody' ).on( 'click', '#doaction', function() {
				if($('#bulk-action-selector-top').val() == 'wc_billingo_plus_bulk_grouped_generate') {
					wc_billingo_plus_bulk_actions.show_grouped_modal();
					return false;
				}

				if($('#bulk-action-selector-top').val() == 'wc_billingo_plus_bulk_generator') {
					wc_billingo_plus_bulk_actions.show_generator_modal();
					return false;
				}
			});

			$( '#wpbody' ).on( 'click', '#doaction2', function() {
				if($('#bulk-action-selector-bottom').val() == 'wc_billingo_plus_bulk_grouped_generate') {
					wc_billingo_plus_bulk_actions.show_grouped_modal();
					return false;
				}

				if($('#bulk-action-selector-top').val() == 'wc_billingo_plus_bulk_generator') {
					wc_billingo_plus_bulk_actions.show_generator_modal();
					return false;
				}
			});

			$(document).on( 'click', '#generate_grouped_invoice', this.generate_grouped_invoices );
			$(document).on( 'click', '#wc_billingo_plus_bulk_generator', this.bulk_generator );
			$(document).on( 'change', '.wc-billing-plus-modal-bulk-generator-form input[name="bulk_invoice_extra_type"]', this.toggle_bulk_generator_options );

			//Listen for keyboard shortcuts
			var mPressed = false;
			$(window).keydown(function(evt) {
				if (evt.which == 77) { //m
					mPressed = true;
				}
			}).keyup(function(evt) {
				if (evt.which == 77) { //m
					mPressed = false;
				}
			});

			//Mark order as paid in order manager
			$( '#wpbody' ).on( 'click', 'a.wc-billingo-plus-mark-paid-button', function() {
				if($(this).hasClass('paid')) return false;
				var order_id = $(this).data('order');
				var nonce = $(this).data('nonce');
				var today = $.datepicker.formatDate('yy-mm-dd', new Date());

				if(mPressed) {
					$(this).addClass('paid');
					$(this).tipTip({ content: 'Fizetve: '+today });
					$('#tiptip_content').text('Fizetve: '+today);

					//Create request
					var data = {
						action: wc_billingo_plus_metabox.prefix+'mark_completed',
						nonce: nonce,
						order: order_id,
					};

					//Make an ajax call in the background. No error handling, since this usually works just fine
					$.post(ajaxurl, data, function(response) { });

				} else {
					$(this).WCBackboneModal({
						template: 'wc-billingo-plus-modal-mark-paid',
						variable : {order_id: order_id}
					});

					$('#wc_billingo_plus_mark_paid_date').datepicker({
						dateFormat: 'yy-mm-dd',
						numberOfMonths: 1,
						showButtonPanel: true,
						maxDate: 0
					});
				}

				return false;
			});

			//Mark order as paid in order manager
			$( 'body' ).on( 'click', '#wc_billingo_plus_mark_paid', function() {
				var order_id = $(this).data('order');
				var nonce = $(this).data('nonce');
				var date = $('#wc_billingo_plus_mark_paid_date').val();

				//Create request
				var data = {
					action: wc_billingo_plus_metabox.prefix+'mark_completed',
					nonce: nonce,
					order: order_id,
					date: date
				};

				//Change to a green checkmark and update tooltip text
				$('a.wc-billingo-plus-mark-paid-button[data-order="'+order_id+'"]').addClass('paid');
				$('a.wc-billingo-plus-mark-paid-button[data-order="'+order_id+'"]').tipTip({ content: 'Fizetve: '+date });

				//Make an ajax call in the background. No error handling, since this usually works just fine
				$.post(ajaxurl, data, function(response) { });

				//Close modal
				$('.modal-close-link').trigger('click');

				return false;
			});

			//Run bank sync manually
			$('.wc-billingo-plus-start-sync a').click(function(){
				var nonce = $(this).data('nonce');
				var $button = $(this);

				//Create request
				var data = {
					action: wc_billingo_plus_metabox.prefix+'run_sync',
					nonce: nonce
				};

				//Loading indicator
				$button.addClass('loading');

				//Make an ajax call in the background. No error handling, since this usually works just fine
				$.post(ajaxurl, data, function(response) {
					$button.removeClass('loading');
					window.location.reload();
				});

				return false;
			});

		},
		printInvoices: function() {
			var pdf_url = $(this).data('pdf');
			if (typeof printJS === 'function') {
				printJS(pdf_url);
				return false;
			}
		},
		show_grouped_modal: function() {
			var checkedOrders = jQuery("#the-list input[name='post[]']:checked");
			var orderIds = [];
			var ul = $('<ul/>');
			ul.addClass('wc-billingo-plus-modal-grouped-generate-list');

			$(checkedOrders).each(function(i) {
				var order_id = $(checkedOrders[i]).val();
				var column_name = $(checkedOrders[i]).parents('.type-shop_order').find('a.order-view').text();
				ul.append('<li><label><input type="radio" name="main_order_id" value="'+order_id+'"> '+column_name+'</label></li>');
				orderIds.push(order_id);
			});

			if(checkedOrders.length === 0) {
				orderIds = false;
			}

			$(this).WCBackboneModal({
				template: 'wc-billingo-plus-modal-grouped-generate',
				variable : {orders: ul.prop("outerHTML"), orderIds: orderIds}
			});
			return false;
		},
		generate_grouped_invoices: function() {
			var orderIds = $(this).data('orders');
			var nonce = $(this).data('nonce');
			var mainOrder = $('input[name=main_order_id]:checked', '.wc-billingo-plus-modal-grouped-generate-list').val();

			if(!mainOrder) {
				$('.wc-billingo-plus-modal-grouped-generate-list').addClass('validate');
				setTimeout(function(){
					$('.wc-billingo-plus-modal-grouped-generate-list').removeClass('validate');
				}, 1000);
				return false;
			}

			//Show loading indicator
			wc_billingo_plus_metabox.loading_indicator($('.wc-billingo-plus-modal-grouped-generate-form'), '#fff');

			//Create request
			var data = {
				action: wc_billingo_plus_metabox.prefix+'generate_grouped_invoice',
				nonce: nonce,
				orders: orderIds,
				main_order: mainOrder
			};

			$.post(ajaxurl, data, function(response) {

				//Hide loading indicator
				$('.wc-billingo-plus-modal-grouped-generate-form').unblock();

				//Show success/error messages
				wc_billingo_plus_metabox.show_messages(response);

				if(response.data.error) {

				} else {
					$('.wc-billingo-plus-modal-grouped-generate-download').slideDown();
					$('.wc-billingo-plus-modal-grouped-generate-download-invoice').find('strong').text(response.data.name);
					$('.wc-billingo-plus-modal-grouped-generate-download-invoice').attr('href', response.data.link);
					$('.wc-billingo-plus-modal-grouped-generate-download-order').attr('href', response.data.order_link);
					$('.wc-billingo-plus-modal-grouped-generate-form, .wc-billingo-plus-modal-grouped-generate footer').slideUp();
				}

			});

			return false;
		},
		show_messages: function(response) {
			$messages = $('.wc-billingo-plus-modal-grouped-generate-results');
			if(response.data.messages && response.data.messages.length > 0) {
				$messages.removeClass('wc-billingo-plus-metabox-messages-success');
				$messages.removeClass('wc-billingo-plus-metabox-messages-error');

				if(response.data.error) {
					$messages.addClass('wc-billingo-plus-metabox-messages-error');
				} else {
					$messages.addClass('wc-billingo-plus-metabox-messages-success');
				}

				$ul = $messages.find('ul');
				$ul.html('');

				$.each(response.data.messages, function(i, value) {
					var li = $('<li>')
					li.append(value);
					$ul.append(li);
				});
				$messages.slideDown();
			}
		},

		show_generator_modal: function() {
			var checkedOrders = jQuery("#the-list input[name='post[]']:checked");
			var orderIds = [];
			var ul = $('<ul/>');

			$(checkedOrders).each(function(i) {
				var order_id = $(checkedOrders[i]).val();
				var column_name = $(checkedOrders[i]).parents('.type-shop_order').find('a.order-view').text();
				ul.append('<li>'+column_name+'</li>');
				orderIds.push(order_id);
			});

			if(checkedOrders.length === 0) {
				orderIds = false;
			}

			$(this).WCBackboneModal({
				template: 'wc-billingo-plus-modal-bulk-generator',
				variable : {orders: ul.prop("outerHTML"), orderIds: orderIds}
			});

			$('#wc_billingo_plus_bulk_invoice_completed').datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showButtonPanel: true
			});
			return false;
		},
		bulk_generator: function() {
			var orderIds = $(this).data('orders');
			var nonce = $(this).data('nonce');

			//Show loading indicator
			wc_billingo_plus_metabox.loading_indicator($('.wc-billingo-plus-modal-bulk-generator-form'), '#fff');

			//Pass other options too
			var type = 'invoice';
			var account = $('#wc_billingo_plus_bulk_invoice_account').val();
			var lang = $('#wc_billingo_plus_bulk_invoice_lang').val();
			var doc_type = $('#wc_billingo_plus_bulk_invoice_doc_type').val();
			var note = $('#wc_billingo_plus_bulk_invoice_note').val();
			var deadline = $('#wc_billingo_plus_bulk_invoice_deadline').val();
			var completed = $('#wc_billingo_plus_bulk_invoice_completed').val();
			var proform = $('#wc_billingo_plus_bulk_invoice_proform').is(':checked');
			var deposit = $('#wc_billingo_plus_bulk_invoice_deposit').is(':checked');
			var type_void = $('#wc_billingo_plus_bulk_invoice_void').is(':checked');
			if (proform) type = 'proform';
			if (deposit) type = 'deposit';
			if (type_void) type = 'void';

			//Create request
			var data = {
				action: wc_billingo_plus_metabox.prefix+'bulk_generator',
				nonce: nonce,
				orders: orderIds,
				options: {
					account: account,
					lang: lang,
					doc_type: doc_type,
					note: note,
					deadline: deadline,
					completed: completed,
					document_type: type
				}
			};

			//Submit ajax request
			$.post(ajaxurl, data, function(response) {

				//Hide loading indicator
				$('.wc-billingo-plus-modal-bulk-generator-form').unblock();

				//Show success/error messages
				wc_billingo_plus_bulk_actions.show_messages(response, 'bulk-generator-results');

				if(response.data.error) {

				} else {
					if(response.data.generated) {
						response.data.generated.forEach(function(generated){
							var row = '';
							console.log(generated);
							if(generated.error || (generated.link && generated.link == 'proform_deleted')) {
								row = '<div class="wc-billingo-plus-modal-bulk-generator-download-error"><span>'+generated.order_number+'</span> <em>'+generated.messages[0]+'</em></div>';
							} else {
								row = '<a target="_blank" href="'+generated.link+'" class="wc-billingo-plus-modal-bulk-generator-download-document document-'+type+'"><span>'+generated.order_number+'</span> <strong>'+generated.name+'</strong></a>';
							}
							$('.wc-billingo-plus-modal-bulk-generator-download').append(row);
						});
					}

					$('.wc-billingo-plus-modal-bulk-generator-form, .wc-billingo-plus-modal-bulk-generator footer').slideUp();
					$('.wc-billingo-plus-modal-bulk-generator-download').slideDown();
				}

			});

			return false;
		},
		toggle_bulk_generator_options: function() {
			if($('#wc_billingo_plus_bulk_invoice_void').is(':checked')) {
				$('.hidden-if-void').hide();
			} else {
				$('.hidden-if-void').show();
			}
		}
	}

	//Background generate actions
	var wc_billingo_plus_background_actions = {
		$menu_bar_item: $('#wp-admin-bar-woo-billingo-plus-bg-generate-loading'),
		$link_stop: $('#woo-billingo-plus-bg-generate-stop'),
		$link_refresh: $('#woo-billingo-plus-bg-generate-refresh'),
		finished: false,
		nonce: '',
		init: function() {
			this.$link_stop.on( 'click', this.stop );
			this.$link_refresh.on( 'click', this.reload_page );

			//Store nonce
			this.nonce = this.$link_stop.data('nonce');

			//Refresh status every 5 second
			var refresh_action = this.refresh;
			setTimeout(refresh_action, 5000);

		},
		reload_page: function() {
			location.reload();
			return false;
		},
		stop: function() {
			var data = {
				action: wc_billingo_plus_metabox.prefix+'bg_generate_stop',
				nonce: wc_billingo_plus_background_actions.nonce,
			}

			$.post(ajaxurl, data, function(response) {
				wc_billingo_plus_background_actions.mark_stopped();
			});
			return false;
		},
		refresh: function() {
			var data = {
				action: wc_billingo_plus_metabox.prefix+'bg_generate_status',
				nonce: wc_billingo_plus_background_actions.nonce,
			}

			if(!wc_billingo_plus_background_actions.finished) {
				$.post(ajaxurl, data, function(response) {
					if(response.data.finished) {
						wc_billingo_plus_background_actions.mark_finished();
					} else {
						//Repeat after 5 seconds
						setTimeout(wc_billingo_plus_background_actions.refresh, 5000);
					}

				});
			}
		},
		mark_finished: function() {
			this.finished = true;
			this.$menu_bar_item.addClass('finished');
		},
		mark_stopped: function() {
			this.mark_finished();
			this.$menu_bar_item.addClass('stopped');
		}
	}

	if($('#wc_billingo_plus_metabox').length) {
		wc_billingo_plus_metabox.init();
	}

	if($('#woocommerce_wc_billingo_plus_section_auth').length) {
		wc_billingo_plus_settings.init();
	}

	if($('.wc-billingo-plus-bulk-actions').length || $('#tmpl-wc-billingo-plus-modal-grouped-generate').length) {
		wc_billingo_plus_bulk_actions.init();
	}

	if($('#wp-admin-bar-woo-billingo-plus-bg-generate-loading').length) {
		wc_billingo_plus_background_actions.init();
	}

	//Store management links
	if(window.location.search.indexOf('page=wc-admin') > -1) {
		var waitForEl = function(selector, callback) {
			if (!jQuery(selector).size()) {
				setTimeout(function() {
					window.requestAnimationFrame(function(){ waitForEl(selector, callback) });
				}, 100);
			}else {
				callback();
			}
		};

		waitForEl('.woocommerce-quick-links__category', function() {
			var sampleLink = $('.woocommerce-quick-links__item').last();
			var category = sampleLink.parent();
			var newLink = sampleLink.clone();
			newLink.find('div').text('Woo Billingo Plus');
			newLink.find('a').attr('href', wc_billingo_plus_params.settings_link);
			newLink.find('svg').html('<path d="M12.0343082,0 C18.6550698,0.0191048779 24.009494,5.39673067 23.9998973,12.0175125 C23.9902487,18.6382944 18.6202155,24.0003333 11.9994266,24.0000922 C5.35799889,23.9855985 -0.0142689467,18.5900518 -0.000102749027,11.9486235 C0.0283390863,5.3278955 5.41354658,-0.0189034005 12.0343082,0 Z M15.5212315,5.26059858 L9.74665195,5.26035672 C8.72090474,5.2865489 7.83672571,5.98938077 7.57980676,6.98277698 L5.89679363,13.7640884 C5.56124459,14.9610343 5.79607372,16.2458303 6.53332835,17.2466941 C7.27472842,18.1757371 8.41025143,18.7015248 9.59832787,18.6658984 L12.6085953,18.6658984 C14.8608784,18.5770482 16.792551,17.0312663 17.3730275,14.8532579 C17.6653802,13.8034253 17.4578303,12.6774471 16.8103812,11.8008468 C16.7665955,11.7461146 16.7184312,11.6941191 16.6719089,11.6426709 C17.4873396,11.0260188 18.0728851,10.1541553 18.3352185,9.16604182 C18.5995274,8.21280727 18.4103804,7.19096896 17.8223784,6.39550118 C17.257564,5.69611309 16.4151966,5.28281103 15.5212315,5.26059858 Z M16.2373358,7.6422992 C16.4417205,7.94622868 16.4951923,8.32719 16.3823759,8.67564189 C16.082346,9.69565048 15.1994368,10.4359392 14.142737,10.5535014 L14.1224861,10.5535014 L14.06447,10.5567853 L11.4220026,10.5567853 L10.9217509,12.5714752 L10.9217509,12.5807797 L14.0633754,12.5807797 C14.0890995,12.5807797 14.1142763,12.5807797 14.139453,12.5774957 C14.5542791,12.5577198 14.9549386,12.7308742 15.2247913,13.0465501 C15.4897295,13.4318303 15.5614667,13.9180267 15.4190903,14.3634053 C15.0593092,15.6402013 13.9344003,16.5520111 12.6107845,16.6397147 L9.59887519,16.6413567 C9.03345423,16.6702507 8.48693879,16.4336298 8.12110757,16.0015381 C7.76543127,15.4907287 7.66620773,14.8440579 7.85237279,14.2501098 L9.53538591,7.47153494 C9.57390076,7.38030506 9.65152685,7.31132643 9.74665195,7.28380372 L15.3796832,7.28380372 C15.7061399,7.26115359 16.0241084,7.39406322 16.2373358,7.6422992 Z"></path>');
			category.append(newLink);
		});
	}

});
