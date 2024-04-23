    require(['jquery', 'domReady!'], function ($) {
        /* Test Connection Validation */
        $('#upsLtlTestConnBtn').click(function () {
            if ($('#config-edit-form').valid()) {
                var ajaxURL = $(this).attr('connAjaxUrl');
                upsLTLTestConnectionAjaxCall($, ajaxURL);
            }
            return false;
        });
    });
    
    /**
     * Test connection ajax call
     * @param {string} ajaxURL
     * @returns {Success or Error}
     */
    function upsLTLTestConnectionAjaxCall($, ajaxURL){
        let commonId = '#UpsLtlConnSettings_first_';
        let endPoint = $(commonId + 'tforceEndPoint').val();
        let credentials;
        if (endPoint === '1') { 
            credentials = {
                endPoint            : 'legacy',
                accountNumber       : $(commonId+'upsltlAccountNumber').val(),
                username            : $(commonId+'upsltlUsername').val(),
                password            : $(commonId+'upsltlPassword').val(),
                authenticationKey   : $(commonId+'upsltlAuthenticationKey').val(),
                pluginLicenseKey    : $(commonId+'upsltlLicnsKey').val()
            };
        }else{
            credentials = {
                endPoint            : 'tforce',
                clientId            : $(commonId+'tforceClientId').val(),
                clientSecret        : $(commonId+'tforceClientSecret').val(),
                username            : $(commonId+'tforceUsername').val(),
                password            : $(commonId+'tforcePassword').val(),
                pluginLicenseKey    : $(commonId+'upsltlLicnsKey').val()
            };
        }

        upsLtlAjaxRequest(credentials, ajaxURL, upsLTLConnectSuccessFunction);
        
    }
    
    /**
     * 
     * @param {object} data
     * @returns {void}
     */
    function upsLTLConnectSuccessFunction(data){
        if (data.Error) {
            hideShowDiv("upsltl-fail-con","errorText", data.Error);
        } else {
            hideShowDiv("upsltl-sucss-con","succesText", data.Success);
        }
    }
    
    /**
     * @param {type} divId
     * @param {type} textId
     * @param {type} text
     * @returns {undefined}
     */
    function hideShowDiv(divId,textId,text){
        jQuery("#"+textId).text(text);
        jQuery("#"+divId).show('slow');     
        setTimeout(function () {
            jQuery("#"+divId).hide('slow');
        }, 5000);
    }

    /**
     * Test connection ajax call
     * @param {object} $
     * @param {string} ajaxURL
     * @returns {function}
     */
    function upsLTLPlanRefresh(e){
        let ajaxURL = e.getAttribute('planRefAjaxUrl');
        let parameters = {};
        upsLtlAjaxRequest(parameters, ajaxURL, upsLtlPlanRefreshResponse);
    }

    /**
     * Handle response
     * @param {object} data
     * @returns {void}
     */
    function upsLtlPlanRefreshResponse(data){
        document.location.reload(true);
    }
