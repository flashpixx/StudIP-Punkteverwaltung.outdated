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
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltungpermission.class.php");


    
    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!VeranstaltungPermission::hasDozentRecht($loVeranstaltung))
            throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


        $loAuswertung    = new Auswertung( $loVeranstaltung );
        $laListe         = $loAuswertung->studenttabelle();
        
        // Sortierung hart nach Matrikelnummern
        uasort($laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; });

        // erzeuge Ausgabe ald PDF
        echo "<p>PDF Export: <ul><li><a href=\"".$controller->url_for("auswertung/pdfexport")."\">"._("vollständige Liste")."</a></li> <li><a href=\"".$controller->url_for("auswertung/pdfexport", array("bestandenonly" => true))."\">"._("nur bestandene Studenten")."</a></li></ul> </p>";


        // Bild erzeugen
        echo "<p>";
        echo "<script type=\"text/javascript\">";

        echo "var margin = {top: 10, right: 50, bottom: 20, left: 50}, width = 120 - margin.left - margin.right, height = 500 - margin.top - margin.bottom;";
        echo "var minvalue = Infinity, maxvalue = -Infinity;";
        echo "var chart = d3.box().whiskers(iqr(1.5)).width(width).height(height);";
        echo "d3.json(\"".$statistikaction."\", function(error, json) {";
        echo "var data = [];";
        echo "json.punkteliste.forEach(function(x) {";
        //echo "if (x) {";
        //echo "data.push(x);";
        echo "minvalue = min( minvalue, Math.min.apply(null, x) );";
        //echo "max = x.max();";
        //echo "}";
        echo "console.log(minvalue);";
        echo "});";
        echo "});";
        //echo "chart.domain([min, max]);";

        // Returns a function to compute the interquartile range.
        echo "function iqr(k) {";
        echo "return function(d, i) {";
        echo "var q1 = d.quartiles[0], q3 = d.quartiles[2],  iqr = (q3 - q1) * k, i = -1, j = d.length;";
        echo "while (d[++i] < q1 - iqr);";
        echo "while (d[--j] > q3 + iqr);";
        echo "return [i, j];";
        echo "};";
        echo "}";

        echo "</script>";
        echo "</p>";



        // Tabelle erzeugen
        echo "<table width=\"100%\">";
        echo "<tr><th>"._("Name (EMail)")."</th><th>"._("Matrikelnummer")."</th>";

        foreach($laListe["uebungen"] as $laUebung)
            echo "<th>".$laUebung["name"]."  ("._("bestanden").")</th>";

        echo "<th>"._("bestanden")."</th><th>"._("Bonuspunkte")."</th></tr>";



        // erzeuge Tabelle
        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
        {
            echo "<tr>";
            echo "<td>".$laStudent["name"]." (".$laStudent["email"].")</td>";
            echo "<td>".$laStudent["matrikelnummer"]."</td>";

            foreach($laListe["uebungen"] as $laUebung)
            {
                echo "<td>";
                echo $laUebung["studenten"][$lcStudentKey]["punktesumme"]." (".($laUebung["studenten"][$lcStudentKey]["bestanden"] ? _("ja") : _("nein")).")";
                echo "</td>";
            }
            echo "<td>".($laStudent["veranstaltungenbestanden"] ? "ja" : "nein")."</td>";
            echo "<td>".$laStudent["bonuspunkte"]."</td>";
            echo "</tr>";
        }

        echo "</table>";


    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }


?>
