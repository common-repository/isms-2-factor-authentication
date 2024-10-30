window.addEventListener('load', function () {
    if(jQuery('#mobile-field-selector').val() == "") {
        jQuery('#mobile-field-selector').closest('tr').hide();  
    }
});

jQuery(document).ready(function($){
    $('#create-mobile-field-no').click(function () {
        $('#mobile-field-selector').attr('required','required');
        $('#mobile-field-selector').closest('tr').fadeIn('slow');
    });
    
    $('#create-mobile-field-yes').click(function () {
        $('#mobile-field-selector').val("");
        $('#mobile-field-selector').closest('tr').fadeOut('slow');
        $('#mobile-field-selector').removeAttr('required');
    });

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

    if($('body').hasClass('toplevel_page_isms-auth-setting')){
		
        var input = document.querySelector("#ismsauthphone");
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
            hiddenInput: "ismsauthphone",

            // initialCountry: "auto",
            // localizedCountries: { 'de': 'Deutschland' },
            // nationalMode: false,
            // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
            placeholderNumberType: "MOBILE",
            preferredCountries: ['my', 'jp'],
            separateDialCode: true,
            utilsScript: ismsauthScript.pluginsUrl + "../assets/prefix/js/utils.js?1581331045115",
        });

        $("#ismsauthphone").keyup(function () {
            $(this).val($(this).val().replace(/^0+/, ''));
        });
    }
});