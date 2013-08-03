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
    require_once("veranstaltung/studentuebung.class.php");


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


        /** erzeugt ein Array mit den informationen eines Studenten
         * @param $poStudent Studentenobjekt
         * @return Array
         **/
        private function createStudentenArray( $poStudent )
        {
            return array(
                "name"            => $poStudent->name(),
                "matrikelnummer"  => $poStudent->matrikelnummer(),
                "email"           => $poStudent->email(),
                // Studiengang für die Anerkennung fehlt noch
            );
        }


        /** erzeugt aus einem StudentÜbungsobjekt das passende
         * Array mit den Informationen 
         * @param $poUebungStudent ÜbungStudent Objekt
         * @param $pnBestandenPunkte Punkteanzahl, die für das Bestehen notwendig sind
         * @param $pnUebungMaxPunkte maximal zu erreichende Punkte der Übung
         * @return Array mit Daten
         **/
        private function createStudentenPunkteArray( $poUebungStudent, $pnBestandenPunkte, $pnUebungMaxPunkte )
        {
            $data = array(
                 "erreichtepunkte" => $poUebungStudent->erreichtePunkte(),
                 "zusatzpunkte"    => $poUebungStudent->zusatzPunkte()
            );

            $data["punktesumme"]      = $data["erreichtepunkte"] + $data["zusatzpunkte"];
            $data["bestanden"]        = $data["punktesumme"] >= $pnBestandenPunkte;
            $data["erreichteprozent"] = round($data["punktesumme"] / $pnUebungMaxPunkte * 100, 2);

            return $data;
        }


        /** erzeugt aus einem Übungsobjekt den passenden Eintrag für das Array
         * @param $poUebung Übungsobjekt
         * @return Array
         **/
        private function createUebungsArray( $poUebung )
        {
            $data = array(
                "id"               => $poUebung->id(),
                "maxPunkte"        => $poUebung->maxPunkte(),
                "bestandenProzent" => $poUebung->bestandenProzent(),
                "studenten"        => array(),
            );
            $data["bestandenpunkte"] = round($data["maxPunkte"] / 100 * $data["bestandenProzent"], 2);

            return $data;
        }


        /** liefert eine assoc. Array das für jeden Studenten die Anzahl der Punkt
         * erzeugt und gleichzeitig min / max / median / arithm. Mittel bestimmt
         * @return assoc. Array
         **/
        function studenttabelle()
        {
            // das globale Array enthält einmal die Liste aller Studenten und eine Liste der übungen
            $main = array( "studenten" => array(), "uebungen" => array() );


            // Iteration über jede Übung und über jeden Teilnehmer
            foreach ( $this->moVeranstaltung->uebungen() as $uebung)
            {
                $uebungarray = $this->createUebungsArray( $uebung );

                foreach ($uebung->studentenuebung() as $studentuebung )
                {
                    // Student der globalen Namensliste hinzufügen bzw. überschreiben und Punktedaten erzeugen
                    $main["studenten"][$studentuebung->student()->id()]       = $this->createStudentenArray( $studentuebung->student() );
                    $uebungarray["studenten"][$studentuebung->student()->id()] = $this->createStudentenPunkteArray( $studentuebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"] );
                }

                $main["uebungen"][$uebung->name()] = $uebungarray;
            }



            // nun existiert ein Array mit den Basis Informationen zu jedem Studenten & jeder Übung
            // da ein Student sich während des Semesters aus der Veranstaltung austragen kann, in
            // der globalen Liste aber alle Teilnehmer vorhanden sind, müssen nun die übungen so angepasst
            // werden, dass sie gleich viele Elemente erhalten, d.h. falls Studenten nicht in allen Übungen
            // enthalten sind, werden sie Default mit Null-Werten eingefügt
            foreach ($main["uebungen"] as $item)
            {
                $uebung       = new Uebung( $this->moVeranstaltung, $item["id"] );
                $lcUebungName = $uebung->name();
                $uebungarray  = $this->createUebungsArray( $uebung );

                foreach( array_diff_key($main["studenten"], array_fill_keys($uebung->studentenuebung(true), null)) as $key )
                {
                    $loStudentUebung = new StudentUebung( $uebung, $key );
                    $main["uebungen"][$lcUebungName]["studenten"][$studentuebung->student()->id()] = $this->createStudentenPunkteArray( $loStudentUebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"] );
                    var_dump($loStudentUebung);
                    die(" ");
                }
                    
            }



            /*
             $min                        = min($min, $studentdata["punktesumme"]);
             $max                        = max($max, $studentdata["punktesumme"]);
             $sum                        = $sum + $studentdata["punktesumme"];

             if ($studentdata["bestanden"])
             $countbestanden++;
             else
             $countnichtbestanden++;
             */


            /*
             $min                 = INF;
             $max                 = 0;
             $sum                 = 0;
             $countbestanden      = 0;
             $countnichtbestanden = 0;
             */

            /*
             $uebungdata["punktemittel"]          = round($sum / count($uebungdata["studenten"], 2));
             $uebungdata["punkteminimum"]         = $min;
             $uebungdata["punktemaximum"]         = $max;

             $uebungdata["anzahlbestanden"]       = $countbestanden;
             $uebungdata["anzahlnichtbestanden"]  = $countnichtbestanden;
             $uebungdata["prozentbestanden"]      = round($uebungdata["anzahlbestanden"] / ($uebungdata["anzahlbestanden"]+$uebungdata["anzahlnichtbestanden"]) * 100, 2);
             $uebungdata["prozentnichtbestanden"] = round($uebungdata["anzahlnichtbestanden"] / ($uebungdata["anzahlbestanden"]+$uebungdata["anzahlnichtbestanden"]) * 100, 2);
             */


            return $main;
        }


    }


?>
