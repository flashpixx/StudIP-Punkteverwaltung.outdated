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



    require_once(dirname(dirname(__DIR__)) . "/sys/extensions/jpgraph/jpgraph.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/auswertung.class.php");


    
    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        $loAuswertung    = new Auswertung( $loVeranstaltung );

        $laListe         = $loAuswertung->studenttabelle();
        $laUebungen      = array();
        foreach($loVeranstaltung->uebungen() as $uebung)
        array_push($laUebungen, $uebung->name());



        echo "<table width=\"100%\" style=\"border-width:thin;border-left-style:solid;border-left-color:black;\">";
        echo "<tr><th>Name (EMail)</th><th>Matrikelnummer</th>";

        foreach($laUebungen as $name)
            echo "<th>".$name."  (bestanden)</th>";

        echo "<th>bestanden</th><th>Bonuspunkte</th></tr>";

        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
        {
            echo "<tr>";
            echo "<td>".$laStudent["name"]." (".$laStudent["email"].")</td>";
            echo "<td>".$laStudent["matrikelnummer"]."</td>";

            foreach($laUebungen as $lcUebung)
            {
                echo "<td>";
                echo $laListe["uebungen"][$lcUebung][$laStudent["id"]]["erreichtepunkte"]." (".($laListe["uebungen"][$lcUebung][$laStudent["id"]]["bestanden"] ? "ja" : "nein").")";
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


/*
    echo "<pre>";

    $x = new Auswertung( (isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null) );
    var_dump( $x->studenttabelle() );

    echo "</pre>";
*/

?>
