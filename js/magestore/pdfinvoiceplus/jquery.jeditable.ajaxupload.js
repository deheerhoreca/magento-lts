/*
 * Ajaxupload for Jeditable
 *
 * Copyright (c) 2008-2009 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Depends on Ajax fileupload jQuery plugin by PHPLetter guys:
 *   http://www.phpletter.com/Our-Projects/AjaxFileUpload/
 *
 * Project home:
 *   http://www.appelsiini.net/projects/jeditable
 *
 * Revision: $Id$
 *
 */
 
$.editable.addInputType('ajaxupload', {
    /* create input element */
    element : function(settings) {
        settings.onblur = 'ignore';
        var input = $('<input type="file" id="'+settings.id+'" name="upload" />');
        $(this).append(input);
        if(settings.id == 'change-background'){
            var deletebutton = $('<input style="margin-left:10px;color:red" type="button" id="delete_'+settings.id+'" onclick="$(\'#container-inner\').css(\'background-image\',\'none\')" value="Delete"/>');
            $(this).append(deletebutton);
        }
        return(input);
    },
    content : function(string, settings, original) {
        /* do nothing */
    },
    plugin : function(settings, original) {
        var form = this;
        form.attr("enctype", "multipart/form-data");
        $("button:submit", form).bind('click', function() {
            //$(".message").show();
            $.ajaxFileUpload({
                url: settings.target,
                secureuri:false,
                fileElementId: settings.id,
                dataType: 'html',
                success: function (data, status) {
                    if($(original).hasClass('changebackground') == true){
                            if(data){
                                $('#container-inner').css('background-image', 'url("'+data+'")');
                                //$(original).html('<a class="changebackground" id="changebackground" style="text-decoration: none; " >Background Image</a>');
//                                $(".changebackground").editable(settings.target, {
//                                        indicator: "",
//                                        type: 'ajaxupload',
//                                        submit: 'Upload',
//                                        cancel: 'Cancel',
//                                        tooltip: "Click to upload..."
//                                });
                            }
                    }else{
                        if(data){
                            $(original).html(data);
                            original.editing = false;
                        }
                    }
                },
                error: function (data, status, e) {
                    alert(e);
                }
            });
            return(false);
        });
    }
});
