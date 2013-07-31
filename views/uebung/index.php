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

                echo "<tr><td width=\"50%\"><label for=\"uebungname\">"._("Name der Übung")."</label></td>";
                echo "<td><input type=\"text\" id=\"uebungname\" name=\"uebungname\" value=\"".$uebungname."\" size=\"35\"/></td></tr>\n";

                echo "<tr><td><label for=\"maxpunkte\">"._("maximal zu erreichende Punkte der Übung")."</label></td>";
                echo "<td><input type=\"text\" id=\"maxpunkte\" name=\"maxpunkte\" value=\"".$maxpunkte."\" size=\"35\"/></td></tr>\n";

                echo "<tr><td><label for=\"bestandenprozent\">"._("Prozentzahl, mit der die Übung bestanden ist")."</label></td>";
                echo "<td><input type=\"text\" id=\"bestandenprozent\" name=\"bestandenprozent\" value=\"".$bestandenprozent."\" size=\"35\"/></td></tr>\n";

                echo "<tr><td><label for=\"abgabedatum\">"._("Abgabedatum (in der Form dd.mm.yyyy hh:mm, dd.mm.yyyy oder leer)")."</label></td>";
                echo "<td><input type=\"text\" id=\"abgabedatum\" name=\"abgabedatum\" value=\"".$abgabedatum."\" size=\"35\"/></td></tr>\n";

                echo "<tr><td><label for=\"bemerkung\">"._("Bemerkung")."</label></td>";
                echo "<td><textarea id=\"bemerkung\" name=\"bemerkung\" cols=\"37\" rows=\"5\">".$bemerkung."</textarea></td></tr>\n";
                echo "<tr><td colspan=\"2\"><a href=\"".$controller->url_for("uebung/delete")."\">alle Einstellungen und Daten zu dieser Übung entfernen</a></td></tr>\n";

                echo "</table>";
                echo "</div>\n";
                echo "<p><input type=\"submit\" name=\"submitted\" value=\""._("Angaben übernehmen")."\"/></p>";
                echo "</form>";
                echo "</div>";
            }
            elseif (!empty($bemerkung))
                echo "<div class=\"steel1\">".$bemerkung."</div>";


            echo "<script type=\"text/javascript\">";
            echo "jQuery(document).ready(function() {";
            echo "jQuery(\"#punktetabelle\").jtable({";

            echo "title          : \"Punktetabelle - ".$uebungname.(empty($abgabedatum) ? null : " (".$abgabedatum.")")."\",";
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
