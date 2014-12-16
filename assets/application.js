(function ($) {
 
    jQuery(document).ready(function($) {
 
        $("#uebungsmenu").change(function() {
             window.location = $(":selected",this).attr("rel")
        });
                           
                           
       $("#punktetabelle").contextmenu({
             delegate: ".hasmenu",
             menu: [
                    {title: "Copy", cmd: "copy", uiIcon: "ui-icon-copy"},
                    {title: "----"},
                    {title: "More", children: [
                    {title: "Sub 1", cmd: "sub1"},
                    {title: "Sub 2", cmd: "sub1"}
             ]}
                                                              ],
             select: function(event, ui) {
                    alert("select " + ui.cmd + " on " + ui.target.text());
             }
       });
                           
    });

}(jQuery));

