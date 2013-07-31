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
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltungpermission.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltung/veranstaltung.class.php");


    Tools::showMessage($flash["message"]);
    
    try {
        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;

        if ( (!$loVeranstaltung) || ((!VeranstaltungPermission::hasDozentRecht($loVeranstaltung)) && (!VeranstaltungPermission::hasTutorRecht($loVeranstaltung))) )
            throw new Exception(_("Sie haen nicht die notwendigen Rechte, um die Daten einzusehen"));

        else {

            if (VeranstaltungPermission::hasDozentRecht($loVeranstaltung))
            {
                echo "<form method=\"post\" action=\"".$controller->url_for("uebung/updatesetting")."\">\n";
                CSRFProtection::tokenTag();

                echo "<div class=\"steel1\">\n";

                echo "<table width=\"100%\">\n";

                echo "</table>";
                echo "</div>\n";
                echo "<p><input type=\"submit\" name=\"submitted\" value=\""._("Angaben ¸bernehmen")."\"/></p>";
                echo "</form>";
                echo "</div>";
            }


            echo "<script type=\"text/javascript\">";
            echo "jQuery(document).ready(function() {";
            echo "jQuery(\"#punktetabelle\").jtable({";

            echo "title          : \"Punktetabelle - ".$uebungname."\",";
            echo "paging         : true,";
            echo "pageSize       : 25,";
            echo "sorting        : true,";
            echo "defaultSorting : \"Matrikelnummer ASC\",";
            echo "actions: {";
            echo "listAction   : \"".$listaction."\",";
            echo "updateAction : \"".$updateaction."\",";
            echo "},";

            echo "fields: {";

            echo "Auth : { key : true, create : false, edit : false, list : false },";
            echo "Matrikelnummer : { edit : false, title : \"Matrikelnummer\", width : \"10%\" },";
            echo "Name : { edit : false, title : \"Name\", width : \"30%\" },";
            echo "EmailAdresse : { edit : false, title : \"EMail Adresse\", width : \"20%\" },";
            echo "ErreichtePunkte : { title : \"erreichte Punkte\", width : \"15%\" },";
            echo "ZusatzPunkte : { title : \"Zusatzpunkte\", width : \"15%\" },";
            echo "Bemerkung : { title : \"Bemerkung\", type  : \"textarea\", width : \"10%\" }";

            echo "}";
            echo "});";

            echo "jQuery(\"#punktetabelle\").jtable(\"load\");";
            echo "});";
            echo "</script>";

            echo "<div id=\"punktetabelle\"></div>";
        }

    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }

?>
