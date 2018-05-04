/**
* @author Amasty Team
* @copyright Copyright (c) 2010-2012 Amasty (http://www.amasty.com)
* @package Amasty_Oaction
*/
var amoaction = Class.create(varienGridMassaction, {
    
    apply: function($super) {
        var fields = ['carrier', 'tracking', 'comment'];
        
        for (var i=0; i < fields.length; ++i){
            var vals = [];
            $$('.amasty-' + fields[i]).each(function(s) {
                vals.push (s.readAttribute('rel')+'|'+s.value);
            });        
            new Insertion.Bottom(this.formAdditional, this.fieldTemplate.evaluate({name: fields[i], value: vals}));
        }
        
        return $super();
    }
    
});