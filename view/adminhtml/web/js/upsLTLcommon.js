
    var UpsLtlUrl = 'https://eniture.com/magento-2-ups-ltl-freight-quotes/';
    require([
        'jquery',
        'jquery/validate',
        'mage/translate',
        'domReady!'
        ],
        function($){
            hideThirdPartyFields($);
            $('.bootstrap-tagsinput input').bind('keyup keydown',function(event) {
                validateAlphaNumOnly($, this);
            });
            $('#UpsLtlConnSettings_first span, #UpsLtlQuoteSetting_third span').attr('data-config-scope', '');

            $('#UpsLtlQuoteSetting_third_liftGate').on('change', function () {
                changeLiftgateOption('#UpsLtlQuoteSetting_third_OfferLiftgateAsAnOption', this.value);
                if (!$('#UpsLtlQuoteSetting_third_RADforLiftgate').is(":disabled")) {
                    changeLiftgateOption('#UpsLtlQuoteSetting_third_RADforLiftgate', this.value);
                }
            });

            $('#UpsLtlQuoteSetting_third_OfferLiftgateAsAnOption').on('change', function () {
                changeLiftgateOption('#UpsLtlQuoteSetting_third_liftGate', this.value);
            });

            $('#UpsLtlQuoteSetting_third_RADforLiftgate').on('change', function () {
                changeLiftgateOption('#UpsLtlQuoteSetting_third_liftGate', this.value);
            });

            $('#UpsLtlQuoteSetting_third_thirdPartyPostalCode').on('change', function () {
                var zip = this.value;
                zip = zip.replace(/\s+/g, '');
                $(this).val(zip);
                upsLtlGetAddressFromZip(upsLtlOriginAddressUrl, this, upsLtlThirdPartyDataSet)
            });

            $.validator.addMethod(
                'validate-upsLt-decimal-limit-2', function (value) {
                    return (validateDecimal($, value, 2)) ? true : false;
                }, $.mage.__('Maximum 2 digits allowed after decimal point.'));
            $.validator.addMethod(
                'validate-upsLt-decimal-limit-3', function (value) {
                    return (validateDecimal($,value,3)) ? true : false;
                }, $.mage.__('Maximum 3 digits allowed after decimal point.'));
            $.validator.addMethod(
                'validate-upsLt-max-weight-20k', function (value) {
                    return (value < 20000);
                }, $.mage.__('Maximum 20,000 lbs weight allowed.'));

            $.validator.addMethod(
                'validate-upsLt-thirdparty', function (value) {
                    return upsLtcheckThirdParty($);
                }, $.mage.__('Please enter a valid zip code.'));

    });

    function upsLtcheckThirdParty($) {
        let city = $('#UpsLtlQuoteSetting_third_thirdPartyCity').val();
        let state = $('#UpsLtlQuoteSetting_third_thirdPartyState').val();
        return !(city == '' || state == '');
    }

    function validateAlphaNumOnly($, element){
        var value = $(element);
        value.val(value.val().replace(/[^a-z0-9]/g,''));
    }

    /**
     * Get address against zipcode from smart street api
     * @param {string} ajaxUrl
     * @returns {Boolean}
     */
    function upsLtlGetAddressFromZip(ajaxUrl, $this, callfunction) {
        var zipCode = $this.value;
        if (zipCode === '') {
            return false;
        }
        var parameters = { 'origin_zip'  : zipCode };

        upsLtlAjaxRequest(parameters, ajaxUrl, callfunction);
    }

    function upsLtlThirdPartyDataSet(data) {
        /*if (!data.error){
            thirdPartyValidation(data);
        }else{*/
            // Just to remove the auto fill form by Browser
            jQuery("#UpsLtlQuoteSetting_third_thirdPartyCity").val('');
            jQuery("#UpsLtlQuoteSetting_third_thirdPartyState").val('');
            jQuery("#UpsLtlQuoteSetting_third_thirdPartyCountry").val('');

            let countries = ['US', 'CA', 'GU', 'MX', 'PR', 'VI'];
            if( countries.includes(data.country) ) {
                let city = (data.postcode_localities === 1) ? data.first_city : data.city;
                // if (data.postcode_localities === 1) {
                //     upsLtlCreateSelect(data.cityOptions, '');
                // } else {
                //     upsLtlCreateSelect('', data.city);
                // }
                jQuery("#UpsLtlQuoteSetting_third_thirdPartyCity").val(city);
                jQuery("#UpsLtlQuoteSetting_third_thirdPartyState").val(data.state);
                jQuery("#UpsLtlQuoteSetting_third_thirdPartyCountry").val(data.country);
            }else if (data.msg){

            }
            thirdPartyValidation(data);
        //}
    }

    function upsLtlCreateSelect(cityOptions, cityVal) {
        let city = jQuery("#UpsLtlQuoteSetting_third_thirdPartyCity");
        let inputType = city.attr('type');
        if (inputType === 'text' && cityVal !== '') {
            city.val(cityVal);
            return;
        }

        if (inputType === 'select' && cityOptions !== '') {
            city.append(cityOptions);
            return;
        }
        let setOptions = inputType == 'text' ? true : false;
        let selectEle = '<select id="UpsLtlQuoteSetting_third_thirdPartyCity" name="groups[third][fields][thirdPartyCity][value]" class=" select admin__control-select" data-ui-id="select-groups-third-fields-thirdPartyCity-value">'+cityOptions+'</select>';

        let textEle = '<input id="UpsLtlQuoteSetting_third_thirdPartyCity" name="groups[third][fields][thirdPartyCity][value]" data-ui-id="text-groups-third-fields-thirdpartycity-value" value="'+cityVal+'" class="required-entry input-text admin__control-text" type="text">';

        // if element to be changed like Select to Input and vice versa
        cityVal !== '' ? city.replaceWith(textEle) : city.replaceWith(selectEle);
    }

    /*
    * Hide message
     */
    function scrollHideMsg(scrollType, scrollEle, scrollTo, hideEle) {

        if (scrollType == 1){
            jQuery(scrollEle).animate({ scrollTop: jQuery(scrollTo).offset().top - 170 });
        }else if (scrollType == 2){
            jQuery(scrollTo)[0].scrollIntoView({behavior: "smooth"});
        }
        setTimeout(function () {
            jQuery(hideEle).hide('slow');
        }, 5000);
    }

    function validateDecimal($ , value, limit){
        switch (limit) {
            case 4:
            var pattern=/^-?\d*(\.\d{0,4})?$/;
                break;
            case 3:
                var pattern=/^-?\d*(\.\d{0,3})?$/;
                break;
            default:
            var pattern=/^-?\d*(\.\d{0,2})?$/;
                break;
        }
        var regex = new RegExp(pattern, 'g');
        return regex.test(value);
    }


    function upsLtlCurrentPlanNote($, planMsg, carrierdiv) {
        var divafter = '<div class="message message-notice notice upsLtl-plan-note">' +
                        '<div data-ui-id="messages-message-notice">' + planMsg + '</div></div>';

        upsLtlNotesToggleHandling($, divafter, '.upsLtl-plan-note', carrierdiv);
    }

    function upsLtlNotesToggleHandling($, divafter, className, carrierdiv) {

        setTimeout(function () {
                if ($(carrierdiv).attr('class') === 'open') {
                    $(carrierdiv).after(divafter);
                }
        },1000);

        $(carrierdiv).click(function () {
            if ($(carrierdiv).attr('class') === 'open') {
                $(carrierdiv).after(divafter);
            } else if ($(className).length) {
                $(className).remove();
            }
        });
    }

    function changeLiftgateOption(selectId, optionVal) {
        if (optionVal == 1) {
            jQuery(selectId).val(0);
        }
    }


    /**
     * @param canAddWh
     */
    function upsLtlAddWarehouseRestriction(canAddWh) {
        switch (canAddWh) {

            case 0:
                jQuery("#append-warehouse").find("tr").removeClass('inactiveLink');
                jQuery('#upsltl-add-wh-btn').addClass('inactiveLink');
                if (jQuery(".required-plan-msg").length == 0) {
                    jQuery('#upsltl-add-wh-btn').after('<a href='+UpsLtlUrl+' target="_blank" class="required-plan-msg">Standard Plan required</a>');
                }
                jQuery("#append-warehouse").find("tr:gt(1)").addClass('inactiveLink');
                break;

            case 1:
                jQuery('#upsltl-add-wh-btn').removeClass('inactiveLink');
                jQuery('.required-plan-msg').remove();
                jQuery("#append-warehouse").find("tr").removeClass('inactiveLink');
                break;

            default:
                break;
        }

    }


    /**
     * call for warehouse ajax requests
     * @param {array} parameters
     * @param {string} ajaxUrl
     * @param {string} responseFunction
     * @returns {function}
     */
    function upsLtlAjaxRequest(parameters, ajaxUrl, responseFunction){

        new Ajax.Request(ajaxUrl, {
            method:  'POST',
            parameters: parameters,
            onSuccess: function(response){
                var json = response.responseText;
                var data = JSON.parse(json);
                var callbackRes = responseFunction(data);
                return callbackRes;
            }
        });
    }

    function upsLtlSetInspAndLdData(data, eleid) {
        var instore = JSON.parse(data.in_store);
        var localdel = JSON.parse(data.local_delivery);
        //Filling form data
        if(instore != null && instore != 'null'){
            instore.enable_store_pickup == 1 ? jQuery(eleid + 'enable-instore-pickup').prop('checked', true) : '';
            jQuery(eleid + 'within-miles').val(instore.miles_store_pickup);
            jQuery(eleid + 'postcode-match').tagsinput('add', instore.match_postal_store_pickup);
            jQuery(eleid + 'checkout-descp').val(instore.checkout_desc_store_pickup);
            instore.suppress_other == 1 ? jQuery(eleid + 'ld-sup-rates').prop('checked', true) : '';
        }

        if(localdel != null && localdel != 'null'){
            if (localdel.enable_local_delivery == 1) {
                jQuery(eleid + 'enable-local-delivery').prop('checked', true);
                jQuery(eleid + 'ld-fee').addClass('required');
            }
            jQuery(eleid + 'ld-within-miles').val(localdel.miles_local_delivery);
            jQuery(eleid + 'ld-postcode-match').tagsinput('add', localdel.match_postal_local_delivery);
            jQuery(eleid + 'ld-checkout-descp').val(localdel.checkout_desc_local_delivery);
            jQuery(eleid + 'ld-fee').val(localdel.fee_local_delivery);
            localdel.suppress_other == 1 ? jQuery(eleid + 'ld-sup-rates').prop('checked', true) : '';
        }
    }

    function upsLtlGetRowData(data, loc) {
        return '<td>' + data.origin_city + '</td>' +
                '<td>' + data.origin_state + '</td>' +
                '<td>' + data.origin_zip + '</td>' +
                '<td>' + data.origin_country + '</td>' +
                '<td><a href="javascript:;" data-id="' + data.id + '" title="Edit" class="upsltl-edit-'+loc+'">Edit</a>' +
                ' | ' +
                '<a href="javascript:;" data-id="' + data.id + '" title="Delete" class="upsltl-del-'+loc+'">Delete</a>' +
                '</td>';
    }


    //This function serialize complete form data
    function upsLtlGetFormData($, formId) {
        // To initialize the Disabled inputs
        var disabled = $(formId).find(':input:disabled').removeAttr('disabled');
        var formData = $(formId).serialize();
        disabled.attr('disabled','disabled');
        var addData = '';
        $(formId + ' input[type=checkbox]').each(function() {
            if (!$(this).is(":checked")) {
                addData += '&' + $(this).attr('name') + '=';
            }
        });
        return formData+addData;
    }


    function upsLtlModalClose(formId, ele, $) {
        $(formId).validation('clearError');
        $(formId).trigger("reset");
        $($(formId + " .bootstrap-tagsinput").find("span[data-role=remove]")).trigger("click");
        $(formId + ' ' + ele+'ld-fee').removeClass('required');
        $(ele+'edit-form-id').val('');
        $('.city-select').hide();
        $('.city-input').show();
    }

    function hideThirdPartyFields($) {
        // $('#UpsLtlQuoteSetting_third_thirdPartyCity').prop('type','hidden');
        // $('#UpsLtlQuoteSetting_third_thirdPartyState').prop('type','hidden');
    }

    function thirdPartyValidation(data) {
        // console.log(jQuery.validator);
    }
