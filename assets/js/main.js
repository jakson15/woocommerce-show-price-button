jQuery('#submit-price').click(function() {
    jQuery('#submit-price').hide();
});

jQuery('#submit-price').click(ajaxSubmit);

function ajaxSubmit() {

    var form = 'jQuery(this).serialize()';

    jQuery.ajax({
        type: "POST",
        url: admin_url.ajax_url,
        data: {
            action: 'show_price',
            form: form
        },
        success: function(result) {
            alert(result);
        }
    });

    return false;
}
