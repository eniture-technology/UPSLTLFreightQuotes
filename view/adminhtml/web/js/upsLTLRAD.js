/**
 * Document load function
 */

require([
        'jquery',
        'domReady!'
    ],
    function($){

        if($("#suspend-rad-use").length > 0 && $("#suspend-rad-use").is(":disabled") == false) {
            if (($('#suspend-rad-use:checkbox:checked').length)>0) {
                $("#UpsLtlQuoteSetting_third_residentialDlvry").prop({disabled: false});
                $("#UpsLtlQuoteSetting_third_RADforLiftgate").prop({disabled: true});
            } else {
                $("#UpsLtlQuoteSetting_third_residentialDlvry").prop({disabled: true});
                $("#UpsLtlQuoteSetting_third_RADforLiftgate").prop({disabled: false});
            }
        }

        $("#suspend-rad-use").on('click', function () {
            if (this.checked) {
                $("#UpsLtlQuoteSetting_third_residentialDlvry").prop({disabled: false});
                $("#UpsLtlQuoteSetting_third_RADforLiftgate").prop({disabled: true});
            } else {
                $("#UpsLtlQuoteSetting_third_residentialDlvry").prop({disabled: true});
                $("#UpsLtlQuoteSetting_third_RADforLiftgate").prop({disabled: false});
            }
        });
});
