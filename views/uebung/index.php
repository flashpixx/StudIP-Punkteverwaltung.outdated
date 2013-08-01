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

        // �bung aus dem Flash holen und Zugriffsrechte pr�fen
        $loUebung = isset($flash["uebung"]) ? $flash["uebung"] : null;

        if ( (!$loUebung) || ((!VeranstaltungPermission::hasDozentRecht($loUebung->veranstaltung())) && (!VeranstaltungPermission::hasTutorRecht($loUebung->veranstaltung()))) )
            throw new Exception(_("Sie haben nicht die notwendigen Rechte, um die Daten einzusehen"));

        else {

            // der Dozent kann die Daten der �bung �ndern
            if (VeranstaltungPermission::hasDozentRecht($loUebung->veranstaltung()))
            {
                echo "<form method=\"post\" action=\"".$controller->url_for("uebung/updatesetting")."\">\n";
                CSRFProtection::tokenTag();

                echo "<div class=\"steel1\">\n";
                echo "<table width=\"100%\">\n";

                echo "<tr><td width=\"50%\"><label for=\"uebungname\">"._("Name der �bung")."</label></td>";
                echo "<td><input type=\"text\" id=\"uebungname\" name=\"uebungname\" value=\"".$loUebung->name()."\" size=\"35\"/></td></tr>\n";

                echo "<tr><td><label for=\"maxpunkte\">"._("maximal zu erreichende Punkte der �bung")."</label></td>";
                echo "<td><input type=\"text\" id=\"maxpunkte\" name=\"maxpunkte\" value=\"".$loUebung->maxPunkte()."\" size=\"35\"/></td></tr>\n";

                echo "<tr><td><label for=\"bestandenprozent\">"._("Prozentzahl, mit der die �bung bestanden ist")."</label></td>";
                echo "<td><input type=\"text\" id=\"bestandenprozent\" name=\"bestandenprozent\" value=\"".$loUebung->bestandenprozent()."\" size=\"35\"/></td></tr>\n";

                echo "<tr><td><label for=\"abgabedatum\">"._("Abgabedatum (in der Form 'dd.mm.yyyy hh:mm', 'dd.mm.yyyy' oder leer)")."</label></td>";
                echo "<td><input type=\"text\" id=\"abgabedatum\" name=\"abgabedatum\" value=\"".$loUebung->abgabeDatum()."\" size=\"35\"/></td></tr>\n";

                echo "<tr><td><label for=\"bemerkung\">"._("Bemerkung (f�r die Tutoren sichtbar)")."</label></td>";
                echo "<td><textarea id=\"bemerkung\" name=\"bemerkung\" cols=\"37\" rows=\"5\">".$loUebung->bemerkung()."</textarea></td></tr>\n";
                echo "<tr><td colspan=\"2\"><a href=\"".$controller->url_for("uebung/delete")."\">alle Einstellungen und Daten zu dieser �bung entfernen</a></td></tr>\n";

                echo "</table>";
                echo "</div>\n";
                echo "<p><input type=\"hidden\" name=\"ueid\" value=\"".$loUebung->id()."\" /><input type=\"submit\" name=\"submitted\" value=\""._("Angaben �bernehmen")."\"/></p>";
                echo "</form>";
                echo "</div>";
            }

            // Tutoren bekommen nur die Bemerkung angezeigt
            elseif ($loUebung->bemerkung())
                echo "<div class=\"steel1\">".$loUebung->bemerkung()."</div><br/><br/>";


            // jTable f�r die Punkte erzeugen
            echo "<script type=\"text/javascript\">";
            echo "jQuery(document).ready(function() {";
            echo "jQuery(\"#punktetabelle\").jtable({";

            $abgabe = $loUebung->abgabeDatum();
            if ($abgabe)
                $abgabe = " ("._("Abgabe").": ".$abgabe.")";

            echo "title          : \"Punktetabelle - ".$loUebung->name().$abgabe."\",";
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
            if (VeranstaltungPermission::hasDozentRecht($loUebung->veranstaltung()))
            {
                echo "Log : { create : false, sorting: false, edit : false, title : \"\", width : \"3%\",";
                echo "display : function(row) {";
                echo "var \$item = jQuery('<img src=\"".$childiconpath."\" title=\"Log anzeigen\" />');";
                echo "\$item.click(function() {";

                echo "jQuery(\"#punktetabelle\").jtable(\"openChildTable\", \$item.closest(\"tr\"), {";
                echo "title : \"Log f�r \" + row.record.Name, actions : { listAction : \"".$childlistaction."&aid=\" + row.record.Auth },";

                echo "fields : {";
                echo "ID : { key : true, edit : false, list : false }, ";
                echo "ErreichtePunkte : { title : \"erreichte Punkte\", edit : false },";
                echo "ZusatzPunkte : { title : \"Zusatzpunkte\", edit : false },";
                echo "Bemerkung : { title : \"Bemerkung\", edit : false },";
                echo "Korrektor : { title : \"Korrektor\", edit : false }";
                echo "}";

                echo "}, function (data) { data.childTable.jtable('load'); }";
                echo ");";

                echo "});";
                echo "return \$item;";
                echo "}},";
            }


            echo "Matrikelnummer : { edit : false, title : \"Matrikelnummer\", width : \"10%\" },";
            echo "Name : { edit : false, title : \"Name\", width : \"20%\" },";
            echo "EmailAdresse : { edit : false, title : \"EMail Adresse\", width : \"20%\" },";
            echo "ErreichtePunkte : { title : \"erreichte Punkte\", width : \"10%\" },";
            echo "ZusatzPunkte : { title : \"Zusatzpunkte\", width : \"5%\" },";
            if (VeranstaltungPermission::hasDozentRecht($loUebung->veranstaltung()))
            {
                echo "Bemerkung : { title : \"Bemerkung\", type  : \"textarea\", width : \"15%\" },";
                echo "Korrektor : { title : \"Korrektor\", edit : false, width : \"15%\" }";
            } else
                echo "Bemerkung : { title : \"Bemerkung\", type  : \"textarea\", width : \"35%\" }";

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
