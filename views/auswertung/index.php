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


    // BoxPlot fehlt noch http://jpgraph.net/download/manuals/chunkhtml/ch15s04.html
    //require_once(dirname(dirname(__DIR__)) . "/sys/extensions/jpgraph/jpgraph.php");
    //require_once (dirname(dirname(__DIR__)) . "/sys/extensions/jpgraph/jpgraph_stock.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/auswertung.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltungpermission.class.php");


    
    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!VeranstaltungPermission::hasDozentRecht())
            throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


        $loAuswertung    = new Auswertung( $loVeranstaltung );
        $laListe         = $loAuswertung->studenttabelle();
        
        // Sortierung hart nach Matrikelnummern
        uasort($laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; });

        // erzeuge Array für die Namen der Übungen
        $laUebungen      = array();
        $laBoxPlot       = array();
        foreach($loVeranstaltung->uebungen() as $uebung)
            array_push($laUebungen, $uebung->name());



        // erzeuge Ausgabe
        echo "<a href=\"".$controller->url_for("auswertung/pdfexport")."\">"._("Liste als PDF exportieren")."</a></p>";
        echo "<table width=\"100%\">";
        echo "<tr><th>"._("Name (EMail)")."</th><th>"._("Matrikelnummer")."</th>";

        foreach($laUebungen as $name)
            echo "<th>".$name."  ("._("bestanden").")</th>";

        echo "<th>"._("bestanden")."</th><th>"._("Bonuspunkte")."</th></tr>";



        // erzeuge Tabelle
        $i=0;
        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
        {
            echo "<tr>";
            echo "<td>".$laStudent["name"]." (".$laStudent["email"].")</td>";
            echo "<td>".$laStudent["matrikelnummer"]."</td>";

            foreach($laUebungen as $lcUebung)
            {
                echo "<td>";
                echo $laListe["uebungen"][$lcUebung]["studenten"][$lcStudentKey]["punktesumme"]." (".($laListe["uebungen"][$lcUebung]["studenten"][$lcStudentKey]["bestanden"] ? _("ja") : _("nein")).")";
                echo "</td>";
            }
            echo "<td>".($laStudent["veranstaltungenbestanden"] ? "ja" : "nein")."</td>";
            echo "<td>&nbsp;</td>";
            echo "</tr>";
        }

        echo "</table>";


    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }


?>
