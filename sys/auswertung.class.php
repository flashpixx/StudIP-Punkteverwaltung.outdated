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



    require_once("veranstaltung/veranstaltung.class.php");


    /** Klasse um die Auswertung zentral zu behandeln **/
    class Auswertung
    {

        /** Veranstaltung **/
        private $moVeranstaltung = null;


        /** Ctor, um für eine Veranstaltung alle Auswertungen erzeugen
         * @param $po Veranstaltung
         **/
        function __construct( $po )
        {
            if (!($po instanceof Veranstaltung))
                throw new Exception(_("Es wurde kein Veranstaltungsobjekt übergeben"));

            $this->moVeranstaltung = $po;
        }


        /** liefert die Veranstaltung zurück
         * @return Veranstaltung
         **/
        function veranstaltung()
        {
            return $this->moVeranstaltung;
        }


        /** liefert eine assoc. Array das für jeden Studenten die Anzahl der Punkt
         * erzeugt und gleichzeitig min / max / median / arithm. Mittel bestimmt
         * @return assoc. Array
         **/
        function studenttabelle()
        {
            $la = array();

            $sum = 0;
            foreach ( $this->moVeranstaltung->uebungen() as $uebung)
            {
                // Basis Infos zu jeder Übung
                $uebungdata = array(
                     "maxPunkte"        => $uebung->maxPunkte(),
                     "bestandenProzent" => $uebung->bestandenProzent(),
                     "studenten"        => array(),
                );
                $uebungdata["bestandenpunkte"] = round($uebungdata["maxPunkte"] / 100 * $uebungdata["bestandenProzent"], 2);


                // prüfe jeden Eintrag jedes Studenten
                $min                 = INF;
                $max                 = 0;
                $sum                 = 0;
                $countbestanden      = 0;
                $countnichtbestanden = 0;
                
                foreach ($uebung->studentenuebung() as $student)
                {
                    $studentdata = array(
                        "auth"            => $student->student()->id(),
                        "name"            => $student->student()->name(),
                        "matrikelnummer"  => $student->student()->matrikelnummer(),
                        "email"           => $student->student()->email(),
                        // Studiengang für die Anerkennung fehlt noch
                        "erreichtepunkte" => $student->erreichtePunkte(),
                        "zusatzpunkte"    => $student->zusatzPunkte()
                    );

                    $studentdata["punktesumme"]      = $studentdata["erreichtepunkte"] + $studentdata["zusatzpunkte"];
                    $studentdata["bestanden"]        = $studentdata["punktesumme"] >= $uebungdata["bestandenpunkte"];
                    $studentdata["erreichteprozent"] = round($studentdata["punktesumme"] / $uebungdata["maxPunkte"] * 100, 2);

                    $min                        = min($min, $studentdata["punktesumme"]);
                    $max                        = max($max, $studentdata["punktesumme"]);
                    $sum                        = $sum + $studentdata["punktesumme"];

                    if ($studentdata["bestanden"])
                        $countbestanden++;
                    else
                        $countnichtbestanden++;

                    array_push($uebungdata["studenten"], $studentdata);
                }

                $uebungdata["punktemittel"]          = round($sum / count($uebungdata["studenten"], 2));
                $uebungdata["punkteminimum"]         = $min;
                $uebungdata["punktemaximum"]         = $max;

                $uebungdata["anzahlbestanden"]       = $countbestanden;
                $uebungdata["anzahlnichtbestanden"]  = $countnichtbestanden;
                $uebungdata["prozentbestanden"]      = round($uebungdata["anzahlbestanden"] / ($uebungdata["anzahlbestanden"]+$uebungdata["anzahlnichtbestanden"]) * 100, 2);
                $uebungdata["prozentnichtbestanden"] = round($uebungdata["anzahlnichtbestanden"] / ($uebungdata["anzahlbestanden"]+$uebungdata["anzahlnichtbestanden"]) * 100, 2);


                // füge Daten der Hauptarray hinzu
                $la[$uebung->name()] = $uebungdata;
                
            }


            return $la;
        }


    }


?>
