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

        // Übung aus dem Flash holen und Zugriffsrechte prüfen
        $loUebung = isset($flash["uebung"]) ? $flash["uebung"] : null;
        if (is_object($loUebung))
        {

            if ((!VeranstaltungPermission::hasDozentRecht($loUebung->veranstaltung())) && (!VeranstaltungPermission::hasTutorRecht($loUebung->veranstaltung())))
                throw new Exception(_("Sie haben nicht die notwendigen Rechte, um die Daten einzusehen"));
            else {

                echo "<div>";

                // erzeuge ein Select Menü, da StudIP keine 4. Navigationsebene via Core-API generieren kann
                // @todo Gruppierung von Übungen kann dann mittels "optgroup" eingetragen werden
                echo "<div class=\"steel1\">\n";
                echo "<table width=\"100%\" class=\"ppv headertable\">\n";
                
                echo "<tr><td width=\"50%\"><label for=\"uebungsmenu\">"._("ausgewählte Übung")."</label></td>";
                echo "<td><select id=\"uebungsmenu\">\n";
                foreach($loUebung->veranstaltung()->uebungen() as $loOptUebung)
                    echo "<option value=\"".$loOptUebung->id()."\" ".($loOptUebung->id() == $loUebung->id() ? "selected" : null)." rel=\"".$controller->url_for("uebung", array("ueid" => $loOptUebung->id()))."\">".$loOptUebung->name()."</option>\n";
                echo "</select></td></tr>\n";
                
                echo "</table></div><br/><br/>";
                
                
                
                // der Dozent kann die Daten der Übung ändern
                if (VeranstaltungPermission::hasDozentRecht($loUebung->veranstaltung()))
                {
                    echo "<form method=\"post\" action=\"".$controller->url_for("uebung/updatesetting", array("ueid" => $this->flash["uebung"]->id()))."\">\n";
                    CSRFProtection::tokenTag();

                    echo "<div class=\"steel1\">\n";
                    echo "<h1 class=\"ppv\">"._("Einstellungen")."</h1>\n";
                    echo "<table width=\"100%\">\n";

                    echo "<tr><td width=\"50%\"><label for=\"uebungname\">"._("Name der Übung")."</label></td>";
                    echo "<td><input type=\"text\" id=\"uebungname\" name=\"uebungname\" value=\"".$loUebung->name()."\" size=\"35\"/></td></tr>\n";

                    echo "<tr><td><label for=\"maxpunkte\">"._("maximal zu erreichende Punkte der Übung")."</label></td>";
                    echo "<td><input type=\"text\" id=\"maxpunkte\" name=\"maxpunkte\" value=\"".$loUebung->maxPunkte()."\" size=\"35\"/></td></tr>\n";

                    echo "<tr><td><label for=\"bestandenprozent\">"._("Prozentzahl, mit der die Übung bestanden ist")."</label></td>";
                    echo "<td><input type=\"text\" id=\"bestandenprozent\" name=\"bestandenprozent\" value=\"".$loUebung->bestandenprozent()."\" size=\"35\"/></td></tr>\n";

                    echo "<tr><td><label for=\"abgabedatum\">"._("Abgabedatum (in der Form 'dd.mm.yyyy hh:mm', 'dd.mm.yyyy' oder leer)")."</label></td>";
                    echo "<td><input type=\"text\" id=\"abgabedatum\" name=\"abgabedatum\" value=\"".$loUebung->abgabeDatum()."\" size=\"35\"/></td></tr>\n";

                    echo "<tr><td><label for=\"bemerkung\">"._("Bemerkung (für die Tutoren sichtbar)")."</label></td>";
                    echo "<td><textarea id=\"bemerkung\" name=\"bemerkung\" cols=\"37\" rows=\"5\">".$loUebung->bemerkung()."</textarea></td></tr>\n";

                    if (!$loUebung->veranstaltung()->isClosed())
                        echo "<tr><td colspan=\"2\"><a href=\"".$controller->url_for("uebung/delete", array("ueid" => $loUebung->id()))."\">alle Einstellungen und Daten zu dieser Übung entfernen</a></td></tr>\n";

                    echo "</table>";
                    echo "</div>\n";
                    if (!$loUebung->veranstaltung()->isClosed())
                        echo "<p><input type=\"submit\" name=\"submitted\" value=\""._("Angaben übernehmen")."\"/></p>";
                    echo "</form>";
                    echo "</div>";
                }

                // Tutoren bekommen nur die Bemerkung angezeigt
                elseif ($loUebung->bemerkung())
                    echo "<div class=\"steel1\">".$loUebung->bemerkung()."</div>";
                echo "<br/><br/>";


                // Feld für Masseneingabe (bei Tutoren Ansicht, muss der öffnende Div-Tag entfernt werden, da dies oben unterhalb vom else schon geschieht)
                if (!$loUebung->veranstaltung()->isClosed())
                {
                    if (VeranstaltungPermission::hasDozentRecht($loUebung->veranstaltung()))
                        echo "<div class=\"steel2\">";
                    echo "<h1 class=\"ppv\">"._("Masseneingabe")."</h1>\n";
                    echo "<form method=\"post\" action=\"".$controller->url_for("uebung/massedit", array("ueid" => $this->flash["uebung"]->id()))."\">\n";
                    CSRFProtection::tokenTag();
                    echo "<label for=\"massinput\">"._("zeilenweise Eingabe in der Form (geklammerte Eingaben sind optional und müssen bei Auslassung mit Defaultwerten belegt werden): Matrikelnummer Aufgabenpunkte [Bonuspunkte] [Bemerkung]")."</label><br/><br/>";
                    echo "<textarea name=\"massinput\" id=\"massinput\" cols=\"60\" rows=\"20\" wrap=\"physical\">".$flash["massinput"]."</textarea>";
                    echo "<p><input type=\"submit\" name=\"submitted\" value=\""._("Masseneingabe übernehmen")."\"/></p>";
                    echo "</form>";
                    echo "</div>";
                }

                echo "</div>";


                // jTable für die Punkte erzeugen
                echo "<script type=\"text/javascript\">";
                echo "jQuery(document).ready(function() {";
                echo "jQuery(\"#punktetabelle\").jtable({";

                $abgabe = $loUebung->abgabeDatum();
                if ($abgabe)
                    $abgabe = " ("._("Abgabe").": ".$abgabe.")";

                echo "title          : \"Punktetabelle - ".$loUebung->name().$abgabe."\",";
                echo "paging         : true,";
                echo "pageSize       : 500,";
                echo "sorting        : true,";
                echo "defaultSorting : \"Matrikelnummer ASC\",";
                echo "actions: {";
                echo "listAction   : \"".$listaction."\",";
                if (!$loUebung->veranstaltung()->isClosed())
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
                    echo "title : \"Log für \" + row.record.Name, actions : { listAction : \"".$childlistaction."&aid=\" + row.record.Auth },";

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


                echo "Name : { edit : false, title : \"Name\", width : \"10%\" },";
                echo "EmailAdresse : { visibility : \"hidden\", edit : false, title : \"EMail Adresse\", width : \"10%\" },";
                echo "Matrikelnummer : { edit : false, title : \"Matrikelnummer\", width : \"5%\" },";
                echo "Gruppen : { visibility : \"hidden\", sorting: false, edit : false, title : \"Gruppen\", width : \"25%\" },";
                echo "ErreichtePunkte : { title : \"erreichte Punkte\", width : \"10%\" },";
                echo "ZusatzPunkte : { title : \"Zusatzpunkte\", width : \"5%\" },";
                if (VeranstaltungPermission::hasDozentRecht($loUebung->veranstaltung()))
                {
                    echo "Bemerkung : { title : \"Bemerkung\", type  : \"textarea\", width : \"15%\" },";
                    echo "Korrektor : { title : \"Korrektor\", edit : false, width : \"25%\" }";
                } else
                    echo "Bemerkung : { title : \"Bemerkung\", type  : \"textarea\", width : \"35%\" }";

                echo "}";
                echo "});";

                echo "jQuery(\"#punktetabelle\").jtable(\"load\");";
                echo "});";
                echo "</script>";

                echo "<div id=\"punktetabelle\" style=\"width:100%\" class=\"ppv jtable\"></div>";
            }
        
        }

    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }

?>
