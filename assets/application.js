(function ($) {
 
    jQuery(document).ready(function($) {
 
        jQuery("#uebungsmenu").change(function() {
             window.location = $(":selected",this).attr("rel")
        });
                           
    }

}(jQuery));

