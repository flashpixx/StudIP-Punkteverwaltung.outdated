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
                "name"                     => $poStudent->name(),                                       // Name des Studenten
                "matrikelnummer"           => $poStudent->matrikelnummer(),                             // Matrikelnummer des Studenten
                "email"                    => $poStudent->email(),                                      // EMail des Studenten
                // Studiengang für die Anerkennung fehlt noch
                "uebungenbestanden"        => 0,                                                        // Anzahl der Übungen, die bestanden wurden
                "uebungennichtbestanden"   => 0,                                                        // Anzahl der Übungen, die nicht bestanden wurden
                "uebungenpunkte"           => 0,                                                        // Summe über alle erreichten Übungspunkte
                "veranstaltungenbestanden" => false                                                     // Boolean, ob die Veranstaltung als komplett bestanden gilt
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
                 "erreichtepunkte" => $poUebungStudent->erreichtePunkte(),                              // Punkte, die erreicht wurden
                 "zusatzpunkte"    => $poUebungStudent->zusatzPunkte()                                  // Zusatzpunkte
            );

            $data["punktesumme"]      = $data["erreichtepunkte"] + $data["zusatzpunkte"];               // Summe aus Zusatzpunkte + erreichte Punkte
            $data["bestanden"]        = $data["punktesumme"] >= $pnBestandenPunkte;                     // Boolean, ob die Übung bestanden wurde
            $data["erreichteprozent"] = round($data["punktesumme"] / $pnUebungMaxPunkte * 100, 2);      // Prozentzahl der Punktesumme

            return $data;
        }


        /** erzeugt aus einem Übungsobjekt den passenden Eintrag für das Array
         * @param $poUebung Übungsobjekt
         * @return Array
         **/
        private function createUebungsArray( $poUebung )
        {
            $data = array(
                "id"               => $poUebung->id(),                                                  // Hash der Übung
                "maxPunkte"        => $poUebung->maxPunkte(),                                           // maximale Punkteanzahl
                "bestandenProzent" => $poUebung->bestandenProzent(),                                    // Prozentzahl, damit die Übung als bestanden gilt
                "studenten"        => array()                                                           // Array mit Studenten-Punkte-Daten
            );
            $data["bestandenpunkte"] = round($data["maxPunkte"] / 100 * $data["bestandenProzent"], 2);  // Punkte zum Bestehen

            return $data;
        }


        /** liefert eine assoc. Array das für jeden Studenten die Anzahl der Punkt
         * erzeugt und erzeugt die Statistik der Veranstaltung
         * @return assoc. Array
         **/
        function studenttabelle()
        {
            // das globale Array enthält einmal die Liste aller Studenten und eine Liste der übungen
            $main = array( "studenten" => array(), "uebungen" => array(), "gesamtpunkte" = 0 );


            // Iteration über jede Übung und über jeden Teilnehmer
            foreach ( $this->moVeranstaltung->uebungen() as $uebung)
            {
                $main["gesamtpunkte"] += $uebung->maxPunkte();
                $uebungarray = $this->createUebungsArray( $uebung );

                foreach ($uebung->studentenuebung() as $studentuebung )
                {
                    // Student der globalen Namensliste hinzufügen bzw. überschreiben und Punktedaten erzeugen
                    $main["studenten"][$studentuebung->student()->id()]        = $this->createStudentenArray( $studentuebung->student() );
                    $uebungarray["studenten"][$studentuebung->student()->id()] = $this->createStudentenPunkteArray( $studentuebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"] );
                }

                $main["uebungen"][$uebung->name()] = $uebungarray;
            }



            // nun existiert ein Array mit den Basis Informationen zu jedem Studenten & jeder Übung
            // da ein Student sich während des Semesters aus der Veranstaltung austragen kann, in
            // der globalen Liste aber alle Teilnehmer vorhanden sind, müssen nun die Übungen so angepasst
            // werden, dass sie gleich viele Elemente erhalten, d.h. falls Studenten nicht in allen Übungen
            // enthalten sind, werden sie Default mit Null-Werten eingefügt
            foreach ($main["uebungen"] as $item)
            {
                $uebung       = new Uebung( $this->moVeranstaltung, $item["id"] );
                $lcUebungName = $uebung->name();
                $uebungarray  = $this->createUebungsArray( $uebung );

                foreach( array_diff_key( $main["studenten"], array_fill_keys($uebung->studentenuebung(true), null)) as $key => $val )
                {
                    $loStudentUebung                                                                 = new StudentUebung( $uebung, $key );
                    $main["uebungen"][$lcUebungName]["studenten"][$loStudentUebung->student()->id()] = $this->createStudentenPunkteArray( $loStudentUebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"] );
                }
            }


            // jetzt wird für alle Übungen ein bisschen Statistik berechnet (Min / Max / Median / Average / Anzahl)
            foreach ($main["uebungen"] as $key => $val)
            {
                $main["uebungen"][$key]["statistik"] = array(
                    "minpunkte"            => INF,
                    "maxpunkte"            => 0,
                    "mittelwert"           => 0,
                    "median"               => array(),
                    "anzahlbestanden"      => 0,
                    "anzahlnichtbestanden" => 0

                );

                foreach ($val["studenten"] as $lcStudentKey => $laStudent)
                {
                    if ($laStudent["bestanden"])
                    {
                        $main["uebungen"][$key]["statistik"]["anzahlbestanden"]++;
                        $main["studenten"][$lcStudentKey]["uebungenbestanden"]++;
                    } else {
                        $main["uebungen"][$key]["statistik"]["anzahlnichtbestanden"]++;
                        $main["studenten"][$lcStudentKey]["uebungennichtbestanden"]++;
                    }

                    $main["studenten"][$lcStudentKey]["uebungenpunkte"] += $laStudent["erreichtepunkte"];
                    $main["uebungen"][$key]["statistik"]["minpunkte"]    = min($laStudent["erreichtepunkte"], $main["uebungen"][$key]["statistik"]["minpunkte"]);
                    $main["uebungen"][$key]["statistik"]["maxpunkte"]    = max($laStudent["erreichtepunkte"], $main["uebungen"][$key]["statistik"]["maxpunkte"]);
                    $main["uebungen"][$key]["statistik"]["mittelwert"]  += $laStudent["erreichtepunkte"];
                    
                    array_push($main["uebungen"][$key]["statistik"]["median"], $laStudent["erreichtepunkte"]);
                }

                $main["uebungen"][$key]["statistik"]["mittelwert"] = round($main["uebungen"][$key]["statistik"]["mittelwert"] / count($val), 2);
                $main["uebungen"][$key]["statistik"]["median"]     = $main["uebungen"][$key]["statistik"]["median"][intval(count($val)/2)];
            }
            

            // prüfe nun die Studenten, ob sie die Veranstaltung bestanden haben
            foreach ($main["studenten"] as $lcStudentKey => $laStudent)
            {
            }


            return $main;
        }


    }


?>
