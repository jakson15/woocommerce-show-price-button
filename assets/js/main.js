jQuery('.price').hide();
jQuery('.cart').hide();

jQuery('#submit-price').click(function() {
    jQuery('#submit-price').hide();
    jQuery('.price').show();
    jQuery('.cart').show();
});

jQuery('#submit-price').click(ajaxSubmit);

function ajaxSubmit() {
    var form = jQuery('#spfw_form').serialize();

    jQuery.ajax({
        type: "POST",
        url: "/wordpress/wp-admin/admin-ajax.php",
        data: form,
        success() {
        },
        error() {
        }

    });

    return false;
}
