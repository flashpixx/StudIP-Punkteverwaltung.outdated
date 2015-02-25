<?php
    
    /**
    @cond
    ############################################################################
    # GPL License                                                              #
    #                                                                          #
    # This file is part of the StudIP-Punkteverwaltung.                        #
    # Copyright (c) 2013, Philipp Kraus, <philipp.kraus@tu-clausthal.de>       #
    # This program is free software: you can redistribute it and/or modify     #
    # it under the terms of the GNU General Public License as                  #
    # published by the Free Software Foundation, either version 3 of the       #
    # License, or (at your option) any later version.                          #
    #                                                                          #
    # This program is distributed in the hope that it will be useful,          #
    # but WITHOUT ANY WARRANTY; without even the implied warranty of           #
    # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            #
    # GNU General Public License for more details.                             #
    #                                                                          #
    # You should have received a copy of the GNU General Public License        #
    # along with this program. If not, see <http://www.gnu.org/licenses/>.     #
    ############################################################################
    @endcond
    **/
    
    
    
    Tools::showMessage($flash["message"]);
    
    try {
        
        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
    
    
        echo "<h1 class=\"ppv\">Teilnehmer aktualisieren</h1>";
    
        echo "<a href=\"".$controller->url_for("admin/updateteilnehmer")."\">Teilnehmer aktualisieren</a>";
    
    
    
    
    
        echo "<h1 class=\"ppv\">Teilnehmer ignorieren</h1>";
        echo "<script type=\"text/javascript\">";
        echo "jQuery(document).ready(function() {";
        echo "jQuery(\"#ignoretabelle\").jtable({";
        
        echo "title          : \"ignorierte Benutzer\",";
        echo "paging         : true,";
        echo "pageSize       : 500,";
        echo "sorting        : true,";
        echo "defaultSorting : \"Name ASC\",";
        echo "actions: {";
        echo "listAction   : \"".$ignorelistaction."\",";
        if (!$loVeranstaltung->isClosed())
        {
            echo "deleteAction : \"".$ignoreremoveaction."\",";
            echo "updateAction : \"".$ignoreupdateaction."\",";
        }
        echo "},";
        
        if (!$loVeranstaltung->isClosed())
            echo "deleteConfirmation: function(pxData) { pxData.deleteConfirmMessage = \"".sprintf(_("Nach Freigabe des Datensatzes [%s] muss die Teilnehmerliste aktualisiert werden"), "\"+pxData.record.EMailAdresse+\"")."\"; }, ";
        
        echo "fields: {";
        
        echo "Auth : { key : true, create : false, edit : false, list : false },";
        echo "Name : { edit : false, title : \""._("Name")."\", width : \"10%\" },";
        echo "EMailAdresse : { edit : false, title : \""._("EMail Adresse")."\", width : \"10%\" },";
        echo "Matrikelnummer : { edit : false, title : \""._("Matrikelnummer")."\", width : \"5%\" },";
        echo "Bemerkung : { edit : true, title : \""._("Bemerkung")."\", width : \"75%\" },";
        
        
        echo "}";
        echo "});";
        
        echo "jQuery(\"#ignoretabelle\").jtable(\"load\");";
        
        echo "});";
        echo "</script>";
        
        echo "<div id=\"ignoretabelle\" style=\"width:100%\" class=\"ppv jtable\"></div>";

        
    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }

    
?>
