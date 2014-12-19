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


        //$loBonuspunkte   = new Bonuspunkt( $loVeranstaltung );
        //$laBonuspunkte   = $loBonuspunkte->liste();
        $loAuswertung    = new Auswertung( $loVeranstaltung );
        $laListe         = $loAuswertung->studententabelle();
        
        // Sortierung hart nach Matrikelnummern
        uasort($laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; });

        // erzeuge verschiedene Ausgabeformate
        $laExportformat  = array("pdf", "xlsx");

        echo "<h1 class=\"ppv\">Datei Export</h1>";
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
        echo "<h1 class=\"ppv\">Statistik</h1>";
        echo "<p><div id=\"boxplot\" class=\"ppv statistikplot\" style=\"height: 350px; width: ".(80*count($laListe["uebungen"]))."px; float:right\">";

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
        echo "</div>";
        
        echo "<script type=\"text/javascript\">";
        echo "jQuery(document).ready(function() {";
        echo "jQuery(\"#auswertungstabelle\").jtable({";
        
        echo "title          : \"Auswertung\",";
        echo "paging         : false,";
        echo "sorting        : false,";
        echo "actions: {";
        echo "listAction   : \"".$auswertungaction."\",";
        echo "},";
        
        echo "fields: {";
        
        echo "Titel : { edit : false, title : \""._("Titel")."\", width : \"40%\" },";
        echo "Data : { edit : false, title : \""._("Daten")."\", width : \"30%\" },";
        echo "DataProzent : { edit : false, title : \""._("Daten Prozent")."\", width : \"30%\" },";
        
        echo "}";
        echo "});";
        
        echo "jQuery(\"#auswertungstabelle\").jtable(\"load\");";
        
        echo "});";
        echo "</script>";
        
        echo "<div id=\"auswertungstabelle\" style=\"width:60%; float:left\" class=\"ppv jtable\"></div></p>";

        
        



        // jTable für die Punkte erzeugen
        echo "<h1 class=\"ppv\">Punkteliste</h1>";
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
        echo "EmailAdresse : { visibility : \"hidden\", edit : false, title : \""._("EMail Adresse")."\", width : \"10%\" },";
        echo "Studiengang : { visibility : \"hidden\", edit : false, title : \""._("Studiengang")."\", width : \"10%\" },";
        
        $lnSize = round( (35 / count($laListe["uebungen"]))/3, 1 );
        foreach($laListe["uebungen"] as $laUebung)
        {
            $lcHash = md5($laUebung["name"]);
            echo "ueb_punkte_".$lcHash." : { edit : false, title : \"".$laUebung["name"]." "._("Punkte")."\", width : \"".$lnSize."%\" },";
            echo "ueb_prozent_".$lcHash." : { visibility : \"hidden\", edit : false, title : \"".$laUebung["name"]." "._("Prozent")."\", width : \"".$lnSize."%\" },";
            echo "ueb_bestanden_".$lcHash." : { visibility : \"hidden\", edit : false, type : \"checkbox\", values : { \"false\" : \"nein\", \"true\" : \"ja\" }, title : \"".$laUebung["name"]." "._("bestanden")."\", width : \"".$lnSize."%\" },";
        }
        
        echo "Gesamtpunkte : { edit : false, title : \""._("Gesamtpunkte")."\", width : \"5%\" },";
        echo "GesamtpunkteProzent : { edit : false, title : \""._("Gesamtpunkte Prozent")."\", width : \"5%\" },";
        echo "gesamtbestanden : { visibility : \"hidden\", edit : false, type : \"checkbox\", values : { \"false\" : \"nein\", \"true\" : \"ja\" }, title : \""._("Gesamt bestanden")."\", width : \"5%\" },";
        echo "Bonuspunkte : { edit : false, title : \""._("Bonuspunkte")."\", width : \"5%\" },";
        
        echo "}";
        echo "});";
        
        echo "jQuery(\"#punktetabelle\").jtable(\"load\");";
        
        echo "});";
        echo "</script>";
        
        echo "<div id=\"punktetabelle\" style=\"width:45%\" class=\"ppv jtable\"></div>";


    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }


?>
