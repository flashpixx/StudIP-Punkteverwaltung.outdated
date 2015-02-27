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



    require_once(dirname(dirname(__DIR__)) . "/sys/tools.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/authentification.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltung/veranstaltung.class.php");


    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!Authentification::hasDozentRecht($loVeranstaltung))
            throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));

        echo "<div class=\"steel1\">\n";
        echo "<p>"._("Die Bonuspunkte werden auf der Basis der Gesamtpunkte (Summe aller erreichten Punkte) berechnet und gesondert in der Auswertung ausgeführt. Damit können z.B. Bonuspunkte für eine Klausur automatisch generiert werden")."</p>";
        
        
        echo "<script type=\"text/javascript\">";
        echo "jQuery(document).ready(function() {";
        echo "jQuery(\"#punktetabelle\").jtable({";
        
        echo "title          : \"Bonuspunktetabelle\",";
        echo "paging         : true,";
        echo "pageSize       : 10,";
        echo "sorting        : false,";
        echo "defaultSorting : \"Prozent DESC\",";
        echo "actions: {";
        echo "listAction     : \"".$listaction."\",";
        if (!$loVeranstaltung->isClosed())
        {
            echo "createAction : \"".$createaction."\",";
            echo "deleteAction : \"".$deleteaction."\",";
            echo "updateAction : \"".$updateaction."\",";
        }
        echo "},";
        
        echo "fields: {";
        
        echo "Prozent : { key : true, edit : true, create : true, title : \""._("Prozent")."\" },";
        echo "Punkte  : { title : \""._("Punkte")."\" },";
        
        echo "}";
        echo "});";
        
        echo "jQuery(\"#punktetabelle\").jtable(\"load\");";
        
        echo "});";
        echo "</script>";
        
        
        echo "<div id=\"punktetabelle\" style=\"width:100%\" class=\"ppv jtable\"></div>";
        echo "</div>";


    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }
    
    ?>