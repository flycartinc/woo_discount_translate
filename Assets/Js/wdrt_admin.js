/**
 * @author      Flycart
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.flycart.org
 * */

if (typeof (wdrt_jquery) == 'undefined') {
    wdrt_jquery = jQuery.noConflict();
}
wdrt = window.wdrt || {};

(function (wdrt) {
    wdrt_jquery(document).on('click', '#wdrt_update_wpml_string', function (e) {
        alertify.set('notifier', 'position', 'top-right');
        let data = {
            action: 'wdrt_add_dynamic_string',
            wdrt_nonce: wdrt_localize_data.common_nonce
        };
        wdrt_jquery('#wdrt_update_wpml_string').attr('disabled', true);
        wdrt_jquery.ajax({
            type: "POST",
            url: wdrt_localize_data.ajax_url,
            data: data,
            dataType: "json",
            before: function () {

            },
            success: function (json) {
                if (json.success) {
                    alertify.success(json.data.message);
                } else {
                    alertify.error(json.data.message);
                }
                wdrt_jquery('#wdrt_update_wpml_string').removeAttr('disabled');
            }
        });
    });
})(wdrt_jquery);