jQuery(document).ready(function($){
    function validateForm() {
        var isValid = true;
        var formselector = $('#isms-auth-form-selector').val();
        $(formselector+' input').each(function() {
            if($(this).attr('required')){
                if($(this).val() === ''){
                    isValid = false;
                }
            }
        });
        return isValid;
    }

    if($('body').hasClass('woocommerce-edit-address') || $('body').hasClass('woocommerce-checkout')) {
        var input = document.querySelector("#billing_phone");
        window.intlTelInput(input, {
            //allowDropdown: false,
            // autoHideDialCode: false,
            //autoPlaceholder: "off",
            // dropdownContainer: document.body,
            // excludeCountries: ["us"],
            // formatOnDisplay: false,
            //geoIpLookup: function (callback) {
            //   $.get("http://ipinfo.io", function () {
            //   }, "jsonp").always(function (resp) {
            //       var countryCode = (resp && resp.country) ? resp.country : "";
            //      callback(countryCode);
            //   });
            // },
            hiddenInput: "billing_phone",

            // initialCountry: "auto",
            // localizedCountries: { 'de': 'Deutschland' },
            // nationalMode: false,
            // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
            placeholderNumberType: "MOBILE",
            preferredCountries: ['my', 'jp'],
            separateDialCode: true,
            utilsScript: ismsauthScript.pluginsUrl + "../assets/prefix/js/utils.js?1581331045115",
        });

        $("#billing_phone").keyup(function () {
            $(this).val($(this).val().replace(/^0+/, ''));
        });

    }else {

        setTimeout(function(){
            if($('body').hasClass('woocommerce-account')) {
                var hiddenfield = 'billing_phone';
                var mobilefield = '#isms_reg_billing_phone';
                setInterval(function() {
                    if ($('#isms_reg_billing_phone').val()) {
                        $('input[name="billing_phone"]').val('+'+$('#isms-auth-country-code').val()+$('#isms_reg_mobile_phone').val());
                    }
                },500);

            }else {
                hiddenfield = "isms_hidden_reg_mobile_phone";
                mobilefield = '#isms_reg_mobile_phone';
                if($('#create-mobile').val() == 'no') {
                    mobilefield = $('#create-mobile-selector').val();
                }

                setInterval(function() {
                    $(mobilefield).attr('style','padding-left: 94px !important;');
                    if ($(mobilefield).val()) {
                        var mobilephn = '+'+$('#isms-auth-country-code').val()+$(mobilefield).val();
                        $('input[name="isms_hidden_reg_mobile_phone"]').val(mobilephn.replace(/\s/g,''));
                    }

                },500);
            }

            if($(mobilefield).length) {

                var input = document.querySelector(mobilefield);
                window.intlTelInput(input, {
                    //allowDropdown: false,
                    // autoHideDialCode: false,
                    //autoPlaceholder: "off",
                    // dropdownContainer: document.body,
                    // excludeCountries: ["us"],
                    // formatOnDisplay: false,
                    //geoIpLookup: function (callback) {
                    //   $.get("http://ipinfo.io", function () {
                    //   }, "jsonp").always(function (resp) {
                    //       var countryCode = (resp && resp.country) ? resp.country : "";
                    //      callback(countryCode);
                    //   });
                    // },
                    hiddenInput: hiddenfield,

                    // initialCountry: "auto",
                    // localizedCountries: { 'de': 'Deutschland' },
                    // nationalMode: false,
                    // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
                    placeholderNumberType: "MOBILE",
                    preferredCountries: ['my', 'jp'],
                    separateDialCode: true,
                    utilsScript: ismsauthScript.pluginsUrl + "../assets/prefix/js/utils.js?1581331045115",
                });


                $(document).on('click', 'ul#country-listbox li', function() {
                    $('#isms-auth-country-code').val($(this).attr('data-dial-code'));
                    if($('body').hasClass('woocommerce-account')) {
                        $('input[name="billing_phone"]').val('+'+$(this).attr('data-dial-code')+$(mobilefield).val());
                    }else{
                        $('input[name="isms_hidden_reg_mobile_phone"]').val('+'+$(this).attr('data-dial-code')+$(mobilefield).val());
                    }
                });

                $(mobilefield).keyup(function () {
                    $(this).val($(this).val().replace(/^0+/, ''));
                    if($('body').hasClass('woocommerce-account')) {
                        $('input[name="billing_phone"]').val('+'+$('#isms-auth-country-code').val()+$(this).val().replace(/^0+/, ''));
                        $('#isms_reg_mobile_phone').val($(this).val().replace(/^0+/, ''));

                    }else {
                        $('input[name="isms_hidden_reg_mobile_phone"]').val('+'+$('#isms-auth-country-code').val()+$(this).val().replace(/^0+/, ''));
                    }
                });
            }

            var send_otp = $('#isms-auth-form-selector').val()+' '+$('#isms-auth-btn-selector').val();
            var submit_btn = $('#isms-auth-btn-selector').val();
            var send_interval = $('#isms-auth-send-interval').val();
            var submit_original = $(submit_btn).html();
            var sec_countdown = send_interval * 60000;
            
            if ($(submit_btn).html().length == 0 || $(submit_btn).html() == "" ) {
                submit_original = $(submit_btn).val();
            }
            
            
            
            $(send_otp).click(function (event) {
                $('.isms-auth-response-holder').removeClass('isms-bg-danger');
                $('.isms-auth-response-holder').html('');
                $('.isms-auth-response-holder').fadeOut('slow');
                if($('#isms-otp-validated').val() == 'false'){

                    if(document.querySelector('form').checkValidity()){
                        if(validateForm()) {
                            event.preventDefault();
                            
                            if ($(submit_btn).html().length == 0 || $(submit_btn).html() == "" ) {
                                $(submit_btn).val("Sending OTP code...");
                            }else {
                                $(submit_btn).html("Sending OTP code...");
                            }
                            var mobile_phone = $('#isms_reg_mobile_phone').val();
                            if($('#create-mobile').val() == 'no') {
                               var mobile_selector = $('#create-mobile-selector').val();
                               mobile_phone = $(mobile_selector).val();
                            }

                            if(parseFloat(mobile_phone)){
                                $.ajax({
                                    type : 'POST',
                                    url: isms_auth_public_ajax.ajaxurl,
                                    dataType: 'json',
                                    data : {
                                        action      : 'generate_otp_code',
                                        dst         :  mobile_phone,
                                        countrycode : $('#isms-auth-country-code').val()

                                    },
                                    success:function(data) {
										console.log(data);
                                        if(data) {
                                            $('.isms-auth-response-holder').removeClass('isms-bg-danger');
                                            $('.isms-auth-response-holder').html('');
                                            $('.isms-auth-response-holder').fadeOut('slow');
                                            $(submit_btn).val(submit_original);
                                            $(submit_btn).html(submit_original);
                                            $(submit_btn).attr('disabled','disabled');
                                            $('#isms-otp-tr-holder').fadeIn('slow');
                                            $('#isms-otp-button-holder').fadeIn('slow');
                                            $('#isms_reg_otp').attr('required','required');
                                            $('#isms-resend-otp').prop('disabled', true);

                                            setTimeout(countDown,1000);
                                        }else{
                                            if ($(submit_btn).html().length == 0 || $(submit_btn).html() == "" ) {
                                                $(submit_btn).val(submit_original);
                                            }else {
                                                $(submit_btn).html(submit_original);
                                            }
                                            $('.isms-auth-response-holder').addClass('isms-bg-danger');
                                            $('.isms-auth-response-holder').html('Failed to send OTP code. Please check you mobile number.');
                                            $('.isms-auth-response-holder').fadeIn('slow');
                                        }

                                    },
                                    error: function(errorThrown){
                                        console.log(errorThrown);
                                    }
                                });
                            }else {
                                $('.isms-auth-response-holder').addClass('isms-bg-danger');
                                $('.isms-auth-response-holder').html('Invalid mobile number!');
                                $('.isms-auth-response-holder').fadeIn('slow');
                            }

                        }
                    }
                }
            });
            
            function countDown(){
               sec_countdown = sec_countdown - 1000;
                
               if(sec_countdown > 0){
                  setTimeout(countDown,1000);
                   $('#isms-resend-otp').val('Resend OTP ('+millisToMinutesAndSeconds(sec_countdown)+')');
               }else {
                  $('#isms-resend-otp').prop('disabled', false);
                  $('#isms-resend-otp').val('Resend OTP');
               }    
             
            }
            function millisToMinutesAndSeconds(millis) {
              var minutes = Math.floor(millis / 60000);
              var seconds = ((millis % 60000) / 1000).toFixed(0);
              return minutes + ":" + (seconds < 10 ? '0' : '') + seconds;
            }

            $('#isms-resend-otp').click(function (event) {
                event.preventDefault();
                var send_interval = $('#isms-auth-send-interval').val();
                $(this).val("Re-sending OTP code...");
                var mobile_phone = $('#isms_reg_mobile_phone').val();
                if($('#create-mobile').val() == 'no') {
                    var mobile_selector = $('#create-mobile-selector').val();
                    mobile_phone = $(mobile_selector).val();
                }

                if(parseFloat(mobile_phone)){
                $.ajax({
                    type : 'POST',
                    url: isms_auth_public_ajax.ajaxurl,
                    dataType: 'json',
                    data : {
                        action      : 'generate_otp_code',
                        dst         : mobile_phone,
                        countrycode : $('#isms-auth-country-code').val()

                    },
                    success:function(data) {
                        $('.isms-auth-response-holder').removeClass('.isms-bg-danger');
                        $('.isms-auth-response-holder').html('');
                        $('.isms-auth-response-holder').fadeOut('slow');  
                        if(data) {
                            $('#isms-resend-otp').val("Resend OTP");
                            $('#isms-resend-otp').prop('disabled', true);
                            sec_countdown = send_interval * 60000;
                           setTimeout(countDown,1000);
                        }else {

                        }
                    },
                    error: function(errorThrown){
                        console.log(errorThrown);

                    }
                });
                }else {
                    $('.isms-auth-response-holder').addClass('isms-bg-danger');
                    $('.isms-auth-response-holder').html('Invalid mobile number!');
                    $('.isms-auth-response-holder').fadeIn('slow');
                }

            });

            $('#isms-verify-otp').click(function (event) {
                event.preventDefault();

                $(this).val("Verifying...");
                var mobile_phone = $('#isms_reg_mobile_phone').val();
                
                if($('#create-mobile').val() == 'no') {
                    var mobile_selector = $('#create-mobile-selector').val();
                    mobile_phone = $(mobile_selector).val();
                }
                if(parseFloat(mobile_phone)){
                    $.ajax({
                        type : 'POST',
                        url: isms_auth_public_ajax.ajaxurl,
                        dataType: 'json',
                        data : {
                            action      : 'verify_otp',
                            otp_code    : $('#isms_reg_otp').val(),
                            dst         : mobile_phone,
                            countrycode : $('#isms-auth-country-code').val()

                        },
                        success:function(data) {
                            $('#isms-verify-otp').val("Verify OTP");

                            if(data) {
                                $('.isms-auth-response-holder').removeClass('.isms-bg-danger');
                                $('.isms-auth-response-holder').html('');
                                $('.isms-auth-response-holder').fadeOut('slow');  
                                $('#isms-otp-tr-holder').fadeOut('slow');
                                $('#isms-otp-button-holder').fadeOut('slow');
                                $(submit_btn).removeAttr('disabled');
                                if($('body').hasClass('woocommerce-account')) {
                                    $('#isms-otp-validated').val('true');
                                    $(send_otp).trigger('click');
                                }else {
                                    $('#isms-otp-validated').val('true');
                                    localStorage.setItem("mobilephone", $('isms_hidden_reg_mobile_phone').val());
                                    var formtosubmit = $('#isms-auth-form-selector').val();
                                    $(formtosubmit).submit();
                                }
                            }else {

                                $('.isms-auth-response-holder').removeClass('isms-bg-success');
                                $('.isms-auth-response-holder').addClass('isms-bg-danger');
                                $('.isms-auth-response-holder').html('Invalid OTP code!');
                                $('.isms-auth-response-holder').fadeIn('slow');
                            }
                        },
                        error: function(errorThrown){
                            console.log(errorThrown);

                        }
                    });
                }else {
                    $('.isms-auth-response-holder').addClass('isms-bg-danger');
                    $('.isms-auth-response-holder').html('Invalid mobile number!');
                    $('.isms-auth-response-holder').fadeIn('slow');
                }

            });

        }, 2000);

    }
});