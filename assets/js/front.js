jQuery(function($){
	$('.datepicker').datepicker();

	$('.wf-fancybox').fancybox();
	$('#login').show();

	function loader( display ) {
		if( display == 'show' ) $('#wca-loader').addClass('flex');
		if( display == 'hide' ) $('#wca-loader').removeClass('flex');
	}

	function wf_show_alert( message, className ) {
		$('#wf-alert-popup').addClass(className);
		$('#wf-alert-popup .wf-alert-content').text(message);
		$('#wf-alert-overlay').show();
	}
	function wf_hide_alert() {
		$('#wf-alert-overlay').hide();
		$('#wf-alert-popup').removeClass();
	}

	$('.wf-tab-btn').on('click', function(e){
		e.preventDefault();
		var tab = $(this).attr('data-tab');
		$('.wf-tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.wf-tab-content').removeClass('active');
		$('#wf-'+tab+'-form').addClass('active');
		
	});

	//affiliate application form
	$('#wf-application-form').on( 'submit', function (e) {
		e.preventDefault();
		var $data = $(this).serializeArray();
		$('.wf-error').text('');
		if ( WCAFFILIATE.enable_recaptcha ) {
			var response = grecaptcha.getResponse();
			if ( response.length == 0 ) {
			    alert( WCAFFILIATE.recaptcha_message ); 
			    e.preventDefault();
			    return false;
			}
		}

		loader('show');
		$.ajax({
			url : WCAFFILIATE.ajaxurl,
			type: 'POST',
			data: $data,
			dataType: 'JSON',
			success: function(resp) {
				loader('hide');
				if (resp.password) {
					$('#wf-pass-error').text(resp.password)
				}

				if (resp.email) {
					$('#wf-email-error').text(resp.email)
				}

				if (resp.user_name) {
					$('#wf-uname-error').text(resp.user_name)
				}

				if ( resp.verify ) {
					$('#wf-setting-notice').slideDown()
					$('#wf-setting-notice').text(resp.message)
				}

				if (resp.status) {
					wf_show_alert( resp.message )
					setTimeout(function(){
						location.reload();
					}, 3000);
				}
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

	//csv rport generate event
	$(document).on( 'click', '#wc-affiliate-export-report-btn', function(e){
		e.preventDefault();//wf-export-report
		loader('show');
		var data = $(this).data('params');
		var headings = $(this).data('headings');		
		var name = $(this).data('name')
		$.ajax({
			url: WCAFFILIATE.ajaxurl,
			data: { action: 'wf-export-report', data: data, headings: headings },
			type: 'POST',
			dataType: 'html',
			success: function(resp){
				// console.log(resp);
				loader('hide');
				var filename = "wc-affiliate-"+name+"-report.csv";
		    	csv_download(filename, resp);
			}
		});
	});

	//show hide shortlink input fields
	$('.wc-affiliate-sw-dashboard-new-page-name').hide();
	$('#wf-enable-shortlink').change(function(e) {
		if(this.checked) {
			$('.wf-shortlink-inputs').slideDown();
		}
		else{
			$('.wf-shortlink-inputs').slideUp();
		}
	}).change();

	//generate affiliate url and shortlink
	$('#wf-url-generator-form').submit(function(e) {
	    e.preventDefault();
	    loader('show');
	    $('.wf-long-affiliate-url, .wf-short-affiliate-url').slideUp();
		var $form = $(this);
		var formData = $form.serialize();
		$.ajax({
			url: WCAFFILIATE.ajaxurl,
			data: formData,
			type: 'POST',
			dataType: 'JSON',
			success: function(resp) {
				console.log(resp)
				loader('hide');
				if( resp.status == 1 ) {
					$('#wf_long_url').val(resp.affiliate_link);
					$('.wf-long-affiliate-url').slideDown();
					$('#wf-shortlinks-list').html(resp.link_table);
					if( resp.shortlink.status == 1 ) {
						$('#wf_short_url').val(resp.shortlink);
						$('.wf-short-affiliate-url').slideDown();					
					}else{
						wf_show_alert( resp.message, 'danger' )
					}
				}else{
					wf_show_alert( resp.message, 'danger' )
				}
			},
			error: function(err) {

			}
		})
	});

	//remove shortling from data base
	$('.wf-remove-shortlink sapn').submit(function(e) {
	    e.preventDefault();
	    $('.wf-long-affiliate-url').hide();
	    $('.wf-short-affiliate-url').hide();
		var $form = $(this);
		var formData = $form.serialize();
		$.ajax({
			url: WCAFFILIATE.ajaxurl,
			data: formData,
			type: 'POST',
			dataType: 'JSON',
			success: function(resp) {
				console.log(resp)
				if( resp.status == 1 ) {
					$('#wf_long_url').val(resp.affiliate_link);
					$('.wf-long-affiliate-url').show();
					$('#wf-shortlinks-list').html(resp.link_table);
					if( resp.shortlink ) {
						$('#wf_short_url').val(resp.shortlink);
						$('.wf-short-affiliate-url').show()	;					
						$('#wf-shortlink-error').html('');
					}

				}else{
					$('#wf-shortlink-error').text( resp.message )
				}
			},
			error: function(err) {

			}
		})
	});

	//copy urls
	if( $('.wf-url-copy').length ){
		$('.wf-url-copy').click(function(e) {
			e.preventDefault();
			var par = $(this).closest( '.wf-urls' )
			$('input', par).select();

			try {
				var successful = document.execCommand('copy');
				if( successful ){
					$('.wf-url-copy').html('<i class="far fa-copy"></i>');
					$('.wf-url-copy').removeClass('success');

					$(this).html('<i class="fas fa-check"></i>');
					$(this).addClass('success');
				}
			} catch (err) {
				console.log('Oops, unable to copy!');
			}
		})
	}

	if ( WCAFFILIATE.has_pro != 1 ) {
		$('.wf-copy-banner-btn').click(function(e) {
			e.preventDefault();
			var parent = $(this).closest('.wf-banner-code-area');

			$( '.wf-copy-banner-content', parent ).select();

			try {
				var successful = document.execCommand('copy');
				if( successful ){
					$('.wf-copy-banner-btn').html('<i class="far fa-copy"></i>');
					$('.wf-copy-banner-btn').removeClass('success')

					$(this).html('<i class="fas fa-check"></i>');
					$(this).addClass('success')
				}
			} catch (err) {
				console.log('Oops, unable to copy!');
			}
		});

		$(document).on( 'click', '.wf-delete-shortlink span', function(e){
		e.preventDefault();
		
		var par = $(this).closest('tr');
		if ( confirm( 'Are you sure to delete this ?' ) ) {
	        par.remove();
		}
	});
	}

	$(document).on( 'click', '#request_payout', function(e){
		e.preventDefault()
		var $user_id = $(this).attr('data-uid')
		$.ajax({
			url : WCAFFILIATE.ajaxurl,
			type: 'POST',
			data: { 'action' : 'wf-request-payout', _nonce : WCAFFILIATE._nonce, user_id : $user_id },
			dataType: 'JSON',
			success: function(resp) {
				
				console.log(resp);
			}
		});
	} );

	$(document).on( 'click', '#wf-setting-password-toggl', function(e) {
		e.preventDefault()
		$('#wf-setting-password-area').toggle()
	});

	$(document).on( 'submit', '#wf-user-settings', function(e) {
		e.preventDefault()
		var $data = $(this).serializeArray()
		$('#wf-upass-error').text('')
		loader('show');
		$.ajax({
			url : WCAFFILIATE.ajaxurl,
			type: 'POST',
			data: $data,
			dataType: 'JSON',
			success: function(resp) {
				loader('hide');
				if (resp.password) {
					$('#wf-upass-error').text(resp.password)
				}
				if ( resp.status == 1 ) {
					wf_show_alert( resp.message );
				}
				console.log(resp);
			}
		});
	});

	$(document).on('click','#wf-upload-btn',function(e) {
        e.preventDefault();
        var image = wp.media({ 
            title: 'Upload Image',
            multiple: false
        }).open()
        .on('select', function(e){
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            $('#image_url').val( image_url );
            $('#wf-avatar img').attr('src', image_url );
            $('.wf-dashboard-profile-image img').attr('src', image_url );
        });
    });

   	// $('#wf-transaction-notice').hide();
	$(document).on("click",".wf-request-payout",function(e){
		e.preventDefault()
		
		$.ajax({
			url : WCAFFILIATE.ajaxurl,
			data: { 'action':'wf-request-payout', '_nonce' : WCAFFILIATE._nonce },
			type: 'POST',
			dataType: 'JSON',
			success: function(resp){
				console.log(resp)
				if ( resp.status == 1 ) {
					wf_show_alert( resp.message );
					setTimeout(function(){
						location.reload();
					}, 2500);
				}
				else{
					wf_show_alert( resp.message, 'danger' );
				}
			}
		});
	});

	$(document).on("click", ".wf-alert-dismiss, #wf-alert-overlay",function(e){
		wf_hide_alert();
	});

	$(document).on( "change","#pay_with_credit",function(e){

		var pay_with_credit = 'no';
		var cart_total 		= $('#wf_cart_total').val();

		if ( $(this).is(':checked') ) {
			pay_with_credit = 'yes';
		}

        $.ajax({
            type: 'POST',
            url: WCAFFILIATE.ajaxurl,
            data: {
                'action': 'wf-pay-with-credit',
                'pay_with_credit': pay_with_credit,
                'cart_total': cart_total,
            },
            success: function (result) {
            	console.log(result)
            	$("[name='update_cart']").prop("disabled", false);
				$("[name='update_cart']").trigger("click");
            },
        });
	})

	var method = $('#wf-payout-method option:selected').val();
	$('.wf-setting-panel-content-' + method).show();

	$(document).on('change', '#wf-payout-method', function() {
		var method = $(this).val();
		$('.wf-setting-payout-method').slideUp();
		$('.wf-setting-panel-content-' + method).slideDown();
	});

	$(document).on('click', '#wca-resend-varify-url-btn', function(e) {
		e.preventDefault();
		loader('show');
        $.ajax({
            type: 'POST',
            url: WCAFFILIATE.ajaxurl,
            data: { 'action': 'wca-resend-varify-url' },
            success: function (result) {
            	loader('hide');
            	alert( result.message )
            	console.log(result)
            },
        });
	});

	$(document).ready(function () {
        if (window.matchMedia("(max-width: 767px)").matches) {
            $("#content").addClass("mobile");            
        } else {
            $("#content").removeClass("mobile");                
        }
    });
})