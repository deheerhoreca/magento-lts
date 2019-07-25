jQuery.noConflict();
jQuery(document).ready(function($){
    HideControls();
    
    $("#change").click(function() {
        ShowControls();
    });
    
    $("#save").click(function() {
        $("#save").attr('disabled','disabled');
        $.ajax({
            type: 'POST',
            url: change_email_url,
            data: { email: jQuery("#customer_email").val(), 
                order_id: order_id,
                form_key: form_key
            },
            success: function(data){
                if(data.success == 1) {
                    jQuery("#customer_email_link strong").html(jQuery("#customer_email").val());
                    jQuery("#customer_email_link").attr('href', 'mailto:' + jQuery("#customer_email").val());
                    
                    HideControls();
                } else {
                    alert(error_msg);
                }
                $("#save").removeAttr('disabled');
            },
            dataType: 'json'
        });
    });
    
    $("#cancel").click(function() {
        jQuery("#customer_email").val(jQuery("#customer_email_link strong").html());
        HideControls();
    });
});

function HideControls() {
    jQuery("#customer_email").hide();
    jQuery("#save").hide();
    jQuery("#cancel").hide();
    
    jQuery("#customer_email_link").show();
    jQuery("#change").show();
    
}

function ShowControls() {
    jQuery("#customer_email").show();
    jQuery("#save").show();
    jQuery("#cancel").show();
    
    jQuery("#customer_email_link").hide();
    jQuery("#change").hide();
}