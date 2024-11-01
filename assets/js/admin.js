jQuery(function($){
	$('.datepicker').datepicker();	
	$('.wc-affiliate-chosen').chosen();

	function wf_show_alert( message, className ) {
		$('#wpwrap').addClass( 'wf-alert-container' );
		$('.wf-alert-container').prepend( '\
									<div id="wf-alert-overlay">\
										<div id="wf-alert-popup" class="'+className+'">\
											<span class="wf-alert-dismiss">&times;</span>\
											<div class="wf-alert-content"></div>\
										</div>\
									</div>\
									' );
		$( '.wf-alert-content', '#wf-alert-popup').text(message);
		$('#wf-alert-popup').show();
	}
	function wf_hide_alert() {
		$('#wf-alert-overlay' ).remove();
	}

	$('.wf-remove-affiliate').click(function(e){
		var $id = $(this).data('id')
		$.ajax({
			url : ajaxurl,
			type: 'POST',
			data: { action: 'wf-remove-affiliate', id: $id, nonce: WCAFFILIATE.nonce },
			dataType: 'JSON',
			success: function(resp) {
				alert(resp.message)
				location.reload()
			}
		});
	});

	//download csv report

	function csv_download(filename, text) {
	    var element = document.createElement('a');
	    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
	    element.setAttribute('download', filename);
	    element.style.display = 'none';
	    document.body.appendChild(element);
	    element.click();
	    document.body.removeChild(element);
	}

	//  export all data
	$(document).on('click','#wca-export-button',function(e){
		e.preventDefault();
		var $this = $(this);
		$('.wca-export-button-text', $this).hide();
		$('.wca-expo-ellipsis', $this).show();
		$.ajax({
			url: ajaxurl,
			data: { action: 'wf-export-all' },
			type: 'POST',
			dataType: 'JSON',
			success: function(resp){
				console.log(resp);
				$('.wca-expo-ellipsis', $this).hide();
				$('.wca-export-button-text', $this).show();
				let dataStr = JSON.stringify(resp);
			    let dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

			    let exportFileDefaultName = 'wc-affiliate.json';

			    let linkElement = document.createElement('a');
			    linkElement.setAttribute('href', dataUri);
			    linkElement.setAttribute('download', exportFileDefaultName);
			    linkElement.click();
			}
		});
	});

	//csv rport generate event
	$(document).on( 'click', '#wc-affiliate-export-report-btn', function(e){
		e.preventDefault();
		
		//wf-export-report
		var data = $(this).data('params');
		var headings = $(this).data('headings');		
		var name = $(this).data('name')
		$.ajax({
			url: ajaxurl,
			data: { action: 'wf-export-report', data: data, headings: headings },
			type: 'POST',
			dataType: 'html',
			success: function(resp){
				// console.log(resp);
				var filename = "wc-affiliate-"+name+"-report.csv";
		    	csv_download(filename, resp);
			}
		});
	});

	//csv rport generate event
	$(document).on( 'click', '.wf-export-table-data-btn', function(e){
		e.preventDefault();

		var name = $(this).data('name');
		$.ajax({
			url: ajaxurl,
			data: { action: 'wf-export-table-report', name: name },
			type: 'POST',
			dataType: 'html',
			success: function(resp){
				console.log(resp);
				var filename = "wc-affiliate-"+name+"-report.csv";
		    	csv_download(filename, resp);
			}
		});
	});

	$("#wf-import-report-form").submit(function(e){
		e.preventDefault();

		var $form = $(this);
		var $data = new FormData($form[0]);
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: $data,
		    processData: false,
		    contentType: false,
			dataType: 'JSON',
			success: function(ret) {
				console.log(ret);
			}
		})
	});

	$(document).on('click', '.wca-migration-tab',function(e) {
		e.preventDefault();
		var tab = $(this).data('tab');

		$('.wca-migration-tab').removeClass('active');
		$(this).addClass('active');
		$('.wca-ei-section').hide();
		$('#wca-'+tab+'-section').show()
	})

	$('#wca-import-file').on( 'change', function(e){
		if( $(this).val() == '' ) $('#wca-import-submit').prop('disabled',true);
		else $('#wca-import-submit').prop('disabled',false);
	}).change();

	$(document).on('click', "#wca-import-submit",function(e){
		e.preventDefault();
		var form 		= $('#wca-import-form');
		var property 	= $('#wca-import-file').prop('files')[0];  ;
       	var file_name 	= property.name;
       	var file_extension = file_name.split('.').pop().toLowerCase();


       	var $this = $(this);
       	$this.prop('disabled', true);
       	$('.wca-export-button-text', $this).hide();
       	$('.wca-expo-ellipsis', $this).show();

		if($.inArray(file_extension,['json']) == -1){
		 	alert("Invalid file formate");
			$this.prop('disabled', false);
			$('.wca-export-button-text', $this).show();
			$('.wca-expo-ellipsis', $this).hide();
		 	return;
		}

		var form_data = new FormData();
		form_data.append("file",property);
		form_data.append("_wpnonce", WCAFFILIATE.nonce );
		form_data.append("action", 'wca-import-data' );
		form_data.append("from", $('#wca-import-from',form).val() );
		$.ajax({
			url:ajaxurl,
			dataType: 'json',  // what to expect back from the PHP script, if anything
	        cache: false,
	        contentType: false,
	        processData: false,
	        data: form_data,                         
	        type: 'post',
			success:function(data){
				console.log(data);
				alert( data.message );
				$this.prop('disabled', false);
				$('.wca-export-button-text', $this).show();
				$('.wca-expo-ellipsis', $this).hide();
			}
		});
	});

	$(document).on( 'submit', '#wf-transaction-form', function(e){
		e.preventDefault();
		var $data = $(this).serializeArray();		
		$('#wf-transaction-notice').text('')

		$.ajax({
			url: ajaxurl,
			data: $data,
			type: 'POST',
			dataType: 'JSON',
			success: function(resp){
				console.log(resp)
				if ( resp.status == 1 ) {
					wf_show_alert( resp.message );
					setTimeout(function(){
						window.location.replace( WCAFFILIATE.admin_url + '?page=transactions' );
					}, 2500);
				}else{
					wf_show_alert( resp.message, 'danger' );
				}
			}
		});
	});

	$(document).on("click",".wf-review-action",function(e){
		e.preventDefault()
		var action 	= $(this).data('action');
		var id 	 	= $(this).data('id');
		var message	= $('#wf-affiliate-message').val();
		var email	= $('#wf-affiliate-email').val();
		
		$.ajax({
			url: ajaxurl,
			data: { 'action':'wf-review-action', 
				'nonce' : WCAFFILIATE.nonce, 
				'review_action' : action, 
				'user_id' : id,
				'message' : message,
				'email'   : email,
			},
			type: 'POST',
			dataType: 'JSON',
			success: function(resp){
				// console.log(resp)
				if ( resp.status == 1 ) {
					wf_show_alert( resp.message );
				}else{
					wf_show_alert( resp.message, 'danger' );
				}
			}
		});
	});

	$(document).on("click",".wf-payout",function(e){
		e.preventDefault();

		var $this = $(this)
		var payout_method = $this.siblings('.wf-payout-method').val()
		
		if( payout_method == '' ) {
			wf_show_alert( 'Please select a payout method' );
			return;
		}

		$.ajax({
			url: ajaxurl,
			data: {
				_wpnonce : WCAFFILIATE.nonce, 
				action : 'wf-payout',
				payout_method : payout_method,
				affiliate : $this.data('affiliate'),
			},
			type: 'POST',
			dataType: 'JSON',
			success: function(resp){
				if ( resp.status == 1 ) {
					wf_show_alert( resp.message );
					setTimeout(function(){
						window.location=resp.redirect;
					}, 1500);
				}else{
					wf_show_alert( resp.message, 'danger' );					
				}
			}
		});
	});

	$(document).on("click", ".wf-alert-dismiss, #wf-alert-overlay",function(e){
		wf_hide_alert();
	});

	$(document).on("click", ".wf-import-export-nav-tabs a",function(e){
		e.preventDefault();
		var tab = $(this).attr('data-tab');
		$('.wf-import-export-tabs-content .wf-import-export-tab').slideUp();
		$('#wf-tab-content-' + tab).slideDown();
	});

	$('#wf-view-pass').click(function(e){
		e.preventDefault()
		var pass = $('#password', '#_wc_affiliate_password-wrap');
		// $(this).toggleClass('button-primary');
		if($(pass).attr('type')==='password'){
           $(this).html('<span class="dashicons dashicons-hidden"></span>')
           $(pass).attr('type','text');
       }
       else{
           $(this).html('<span class="dashicons dashicons-visibility"></span>')
            $(pass).attr('type','password');           
       }
	})

	$('#wf-register-user-type .wf-register-user-type-btn').click(function(e){
		e.preventDefault()
		$('#wf-register-user-type .wf-register-user-type-btn').removeClass('button-primary');
		$(this).addClass('button-primary');

		var type = $(this).attr('data-type')

		if ( type == 'existing' ) {
			$('#_wc_affiliate_users-wrap').show()
			$('#_wc_affiliate_users-wrap select').prop( 'disabled', false )
			$('#_wc_affiliate_fname-wrap, #_wc_affiliate_lname-wrap, #_wc_affiliate_email-wrap, #_wc_affiliate_password-wrap').hide()
			$('#first_name, #last_name, #email, #password').prop( 'disabled', true )
		}else{			
			$('#_wc_affiliate_users-wrap').hide()
			$('#_wc_affiliate_users-wrap select').prop( 'disabled', true )
			$('#_wc_affiliate_fname-wrap, #_wc_affiliate_lname-wrap, #_wc_affiliate_email-wrap, #_wc_affiliate_password-wrap').show()
			$('#first_name, #last_name, #email, #password').prop( 'disabled', false )
		}
	});

	$('#wf-register-affiliate-form').submit(function(e){
		e.preventDefault()
		var data = $(this).serializeArray()
		console.log(data)
		$.ajax({
			url: ajaxurl,
			data: data,
			type: 'POST',
			dataType: 'JSON',
			success: function(resp){
				// console.log(resp)
				if ( resp.status ) {
					setTimeout(function(){
						location.reload(true);
					}, 2000);			
					wf_show_alert( resp.message );		
				}else{
					wf_show_alert( resp.message, 'danger' );					
				}
			}
		});
	});

	$(document).on( 'click', '.wl-action-delete', function(e){
		if( !confirm("Are you sure?") ){
			e.preventDefault();
		}
	} )

	$('.wc-affiliate-help-heading').click(function(e){
		var $this = $(this);
		var target = $this.data('target');
		$('.wc-affiliate-help-text:not('+target+')').slideUp();
		if($(target).is(':hidden')){
			$(target).slideDown();
		}
		else {
			$(target).slideUp();
		}
	});

	$('.wc_affiliate_help_tablinks .wc_affiliate_help_tablink').on( 'click', function(e){
        e.preventDefault();
        var tab_id = $(this).attr('id');
        $('.wc_affiliate_help_tablink').removeClass('active');
        $(this).addClass('active');

        $('.wc_affiliate_tabcontent').hide();
        $('#'+tab_id+'_content').show();
    } );

	$('#cx-row-wc_affiliate_payout-enable_payout_methods .cx-field-checkbox').on( 'change', function(e){
        $('.' + $(this).val() + '-field').prop('required',$(this).prop('checked'));
	}).change();

	$(document).on( 'change', '.affiliate_commission, .wf-variation-commission-type', function(e){		
		var parent = $(this).closest('.wf-affiliate_commission')
        if ( $(this).val() == 'custom' ) {
        	$('.commission_type_field, .commission_amount_field', parent).slideDown();
        	$('.wf-variation-commission-type-panel, .wf-variation-commission-amount-panel', parent).slideDown();
        }
        else {
        	$('.commission_type_field, .commission_amount_field', parent).slideUp();
        	$('.wf-variation-commission-type-panel, .wf-variation-commission-amount-panel', parent).slideUp();
        }
	}).change();

	var variation_inputs = $('.affiliate_commission, .wf-variation-commission-type');
	if ( variation_inputs.length > 0 ) {
		variation_inputs.change()
	}

	$(document).ajaxComplete(function() {
		var variation_inputs = $('.affiliate_commission, .wf-variation-commission-type');
		if ( variation_inputs.length > 0 ) {
			var parent = variation_inputs.closest('.wf-affiliate_commission')
			if ( variation_inputs.val() == 'custom' ) {
				$('.commission_type_field, .commission_amount_field', parent).slideDown();
				$('.wf-variation-commission-type-panel, .wf-variation-commission-amount-panel', parent).slideDown();
			}
		}
	});

	$(document).on('click', '.wca-view-acc-info-btn', function(e){
		e.preventDefault();
		var parent = $(this).parent();
		$('.wca-acc-info', parent ).slideToggle();
	})
	
    $('.customer_discount').on( 'change', function(e){
        if ( $(this).val() == 'custom' ) {
        	$('.discount_type_field, .discount_amount_field', $(this).closest('.wf-customer_discount')).slideDown();
        }
        else {
        	$('.discount_type_field, .discount_amount_field', $(this).closest('.wf-customer_discount')).slideUp();
        }
	}).change();
})