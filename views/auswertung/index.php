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



    require_once(dirname(dirname(__DIR__)) . "/sys/auswertung.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/student.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltungpermission.class.php");


    
    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!VeranstaltungPermission::hasDozentRecht($loVeranstaltung))
            throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


        $loBonuspunkte   = new Bonuspunkt( $loVeranstaltung );
        $laBonuspunkte   = $loBonuspunkte->liste();
        $loAuswertung    = new Auswertung( $loVeranstaltung );
        $laListe         = $loAuswertung->studententabelle();
        
        // Sortierung hart nach Matrikelnummern
        uasort($laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; });

        // erzeuge verschiedene Ausgabeformate
        $laExportformat  = array("pdf", "xlsx");

        echo "<span=\"ppv header\">Datei Export</span>";
        echo "<p><table border=\"0\" width=\"25%\">";

        echo "<tr>";
        echo "<td>"._("vollst‰ndige Liste")."</td>";
        foreach( $laExportformat as $lcType )
            echo "<td><a href=\"".$controller->url_for("auswertung/export", array("type" => $lcType, "target" => "full"))."\">".strtoupper($lcType)."</a></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>"._("bestandene Studenten (Name, Matrikelnummer, Studiengang)")."</td>";
        foreach( $laExportformat as $lcType )
            echo "<td><a href=\"".$controller->url_for("auswertung/export", array("type" => $lcType, "target" => "bestandenshort"))."\">".strtoupper($lcType)."</a></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>"._("bestandene Studenten (mit Aufgaben)")."</td>";
        foreach( $laExportformat as $lcType )
            echo "<td><a href=\"".$controller->url_for("auswertung/export", array("type" => $lcType, "target" => "bestanden"))."\">".strtoupper($lcType)."</a></td>";
        echo "</tr>";

        echo "</table> </p>";


        // Bild erzeugen
        // @see http://bl.ocks.org/mbostock/4061502
        echo "<span=\"ppv header\">Statistik</span>";
        echo "<p><div id=\"boxplot\" style=\"height: 350px; width: ".(80*count($laListe["uebungen"]))."px; background-color: #fafafa; border-color: #555; border-style: solid; border-width:1px; margin: 25px auto;\">";

        echo "<script type=\"text/javascript\">";

        echo "var margin = {top: 20, right: 20, bottom: 20, left: 20}, width = 60 - margin.left - margin.right, height = 300 - margin.top - margin.bottom;";
        echo "var min    = Infinity, max = -Infinity;";
        echo "var chart  = d3.box().whiskers(iqr(1.5)).width(width).height(height);";

        echo "d3.json(\"".$statistikaction."\", function(error, json) {";
        echo "var data = [];";
        echo "var i = 0;";
        echo "json.punkteliste.forEach(function(x) {";
        echo "min = Math.min( min, Math.min.apply(null, x) );";
        echo "max = Math.max( max, Math.max.apply(null, x) );";
        echo "data[i++] = x;";
        echo "});";

        echo "chart.domain([min, max]);";
        echo "var svg = d3.select(\"#boxplot\").selectAll(\"svg\")";
        echo ".data(data)";
        echo ".enter().append(\"svg\")";
        echo ".attr(\"class\", \"ppv box\")";
        echo ".attr(\"width\", width + margin.left + margin.right)";
        echo ".attr(\"height\", height + margin.bottom + margin.top)";
        echo ".append(\"g\")";
        echo ".attr(\"transform\", \"translate(\" + margin.left + \",\" + margin.top + \")\")";
        echo ".call(chart);";

        echo "});";

        // berechnet den Interquartilsabstand
        // @see http://de.wikipedia.org/wiki/Quartilabstand#.28Inter-.29Quartilsabstand
        echo "function iqr(k) {";
        echo "return function(d, i) {";
        echo "var q1 = d.quartiles[0], q3 = d.quartiles[2],  iqr = (q3 - q1) * k, i = -1, j = d.length;";
        echo "while (d[++i] < q1 - iqr);";
        echo "while (d[--j] > q3 + iqr);";
        echo "return [i, j];";
        echo "};";
        echo "}";

        echo "</script>";
        echo "</div></p>";


        // hier muss noch etwas die Auswertung hinein
        echo "<p><table align=\"center\" width=\"38%\">";
        echo "<tr><th align=\"left\">Teilnehmeranzahl</th><td>".$laListe["statistik"]["teilnehmergesamt"]."</td></tr>";
        
        echo "<tr><th align=\"left\">Anzahl bestandenen Studenten (%)</th><td>".                    $laListe["statistik"]["teilnehmerbestanden"]    ." (".  ($laListe["statistik"]["teilnehmergesamt"] == 0    ? 0 : round($laListe["statistik"]["teilnehmerbestanden"] / $laListe["statistik"]["teilnehmergesamt"] * 100, 2))      ."%)</td></tr>";
        echo "<tr><th align=\"left\">Anzahl Studenten mit Bonuspunkten (% der bestanden)</th><td>". $laListe["statistik"]["teilnehmerbonus"]        ." (".  ($laListe["statistik"]["teilnehmerbestanden"] == 0 ? 0 : round($laListe["statistik"]["teilnehmerbonus"] / $laListe["statistik"]["teilnehmerbestanden"] * 100,2))        ."%)</td></tr>";
        echo "<tr><th align=\"left\">Anzahl Studenten mit mehr als null Punkten (%)</th><td>".      $laListe["statistik"]["teilnehmerpunktenotzero"]." (".  ($laListe["statistik"]["teilnehmergesamt"] == 0    ? 0 : round($laListe["statistik"]["teilnehmerpunktenotzero"] / $laListe["statistik"]["teilnehmergesamt"] * 100, 2))  ."%)</td></tr>";
        echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
        echo "<tr><th align=\"left\">Gesamtpunktanzahl</th><td>".$laListe["gesamtpunkte"]."</td></tr>";
        echo "<tr><th align=\"left\">Punkte zur Zulassung</th><td>".$laListe["gesamtpunktebestanden"]."</td></tr>";
        echo "<tr><th align=\"left\">max. erreichte Punkte</th><td>".$laListe["statistik"]["maxpunkte"]."</td></tr>";
        echo "<tr><th align=\"left\">min. erreichte Punkte (min. Punkte > 0)</th><td>".$laListe["statistik"]["minpunkte"]." (".$laListe["statistik"]["minpunktegreaterzero"].")</td></tr>";
        if (!empty($laBonuspunkte))
        {
            echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
            echo "<tr><th align=\"left\">Bonuspunkte</th><th>zu erreichende Punktzahl (%)</th></tr>";
            foreach ($laBonuspunkte as $lnProzent => $lnPunkt)
                echo "<tr><td>".$lnPunkt."</td><td>".round($laListe["gesamtpunkte"] / 100 * $lnProzent,2)." (".$lnProzent."%)</td></tr>";
        }

        // @todo beste Studenten sollen gelistet werden

        echo "</table></p>";





        // jTable für die Punkte erzeugen
        echo "<script type=\"text/javascript\">";
        echo "jQuery(document).ready(function() {";
        echo "jQuery(\"#punktetabelle\").jtable({";
        
        echo "title          : \"Punktetabelle - Gesamt\",";
        echo "paging         : true,";
        echo "pageSize       : 500,";
        echo "sorting        : true,";
        echo "defaultSorting : \"Matrikelnummer ASC\",";
        echo "actions: {";
        echo "listAction   : \"".$listaction."\",";
        echo "},";
        
        echo "fields: {";
        
        echo "Auth : { key : true, create : false, edit : false, list : false },";
        echo "Hinweis : { edit : false, title : \""._("Hinweis")."\", width : \"10%\" },";
        echo "Matrikelnummer : { edit : false, title : \""._("Matrikelnummer")."\", width : \"5%\" },";
        echo "Name : { edit : false, title : \""._("Name")."\", width : \"10%\" },";
        echo "EmailAdresse : { edit : false, title : \""._("EMail Adresse")."\", width : \"10%\" },";
        echo "Studiengang : { edit : false, title : \""._("Studiengang")."\", width : \"10%\" },";
        
        $lnSize = round( (35 / count($laListe["uebungen"]))/3, 1 );
        foreach($laListe["uebungen"] as $laUebung)
        {
            $lcHash = md5($laUebung["name"]);
            echo "ueb_punkte_".$lcHash." : { edit : false, title : \"".$laUebung["name"]." "._("Punkte")."\", width : \"".$lnSize."%\" },";
            echo "ueb_prozent_".$lcHash." : { edit : false, title : \"".$laUebung["name"]." "._("Prozent")."\", width : \"".$lnSize."%\" },";
            echo "ueb_bestanden_".$lcHash." : { edit : false, type : \"checkbox\", values : { \"false\" : \"nein\", \"true\" : \"ja\" }, title : \"".$laUebung["name"]." "._("bestanden")."\", width : \"".$lnSize."%\" },";
        }
        
        echo "Gesamtpunkte : { edit : false, title : \""._("Gesamtpunkte")."\", width : \"5%\" },";
        echo "GesamtpunkteProzent : { edit : false, title : \""._("Gesamtpunkte Prozent")."\", width : \"5%\" },";
        echo "gesamtbestanden : { edit : false, type : \"checkbox\", values : { \"false\" : \"nein\", \"true\" : \"ja\" }, title : \""._("Gesamt bestanden")."\", width : \"5%\" },";
        echo "Bonuspunkte : { edit : false, title : \""._("Bonuspunkte")."\", width : \"5%\" },";
        
        echo "}";
        echo "});";
        
        echo "jQuery(\"#punktetabelle\").jtable(\"load\");";
        echo "});";
        echo "</script>";
        
        echo "<div id=\"punktetabelle\"></div>";


    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }


?>
