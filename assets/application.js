(function ($) {
 
    jQuery(document).ready(function($) {
 
        $("#uebungsmenu").change(function() {
             window.location = $(":selected",this).attr("rel")
        });
                           
        $(".ppv.score").raty({
            number   : 5,
            readOnly : true,
            score    : function() { return $(this).attr("data-score"); }
        });
                           
    });

}(jQuery));

