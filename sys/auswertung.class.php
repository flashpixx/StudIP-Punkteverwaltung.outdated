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
    require_once("student.class.php");


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


        /** erzeugt ein Array mit den Informationen eines Studenten
         * @param $poStudent Studentenobjekt
         * @return Array
         **/
        private function createStudentenArray( $poStudent )
        {
            $laStudiengang = $poStudent->studiengang( $this->moVeranstaltung );
            if (empty($laStudiengang))
                $laStudiengang = $poStudent->studiengang();
            
            $la = array();
            foreach( $laStudiengang as $laItem )
                array_push($la, trim($laItem["abschluss"]." ".$laItem["fach"]));
            $lcStudiengang = implode(", ", $la);


            $lcZulassungsBemerkung = $poStudent->manuelleZulassung($this->moVeranstaltung);

            return array(
                "id"                       => $poStudent->id(),                  // Auth Hash des Studenten
                "name"                     => $poStudent->name(),                // Name des Studenten
                "matrikelnummer"           => $poStudent->matrikelnummer(),      // Matrikelnummer des Studenten
                "email"                    => $poStudent->email(),               // EMail des Studenten
                "studiengang"              => $lcStudiengang,                    // Studiengang (wenn nicht gesetzt, dann null)
                "uebungenbestanden"        => 0,                                 // Anzahl der Übungen, die bestanden wurden
                "uebungennichtbestanden"   => 0,                                 // Anzahl der Übungen, die nicht bestanden wurden
                "uebungenpunkte"           => 0,                                 // Summe über alle erreichten Übungspunkte
                "veranstaltungenbestanden" => false,                             // Boolean, ob die Veranstaltung als komplett bestanden gilt
                "bonuspunkte"              => 0,                                 // Bonuspunkte, die auf die Gesamtpunktzahl angerechnet werden
                "manuelleZulassung"        => !empty($lcZulassungsBemerkung),    // Boolean für die manuelle Zulassung
            );
        }


        /** erzeugt aus einem StudentÜbungsobjekt das passende
         * Array mit den Informationen 
         * @param $poUebungStudent ÜbungStudent Objekt
         * @param $pnBestandenPunkte Punkteanzahl, die für das Bestehen notwendig sind
         * @param $pnUebungMaxPunkte maximal zu erreichende Punkte der Übung
         * @param $paScore Score-Array
         * @return Array mit Daten
         **/
        private function createStudentenPunkteArray( $poUebungStudent, $pnBestandenPunkte, $pnUebungMaxPunkte, $paScore )
        {
            $data = array(
                 "erreichtepunkte"  => $poUebungStudent->erreichtePunkte(),                              // Punkte, die erreicht wurden
                 "zusatzpunkte"     => $poUebungStudent->zusatzPunkte(),                                 // Zusatzpunkte
                 "punktesumme"      => 0,                                                                // Summe aus Zusatzpunkte + erreichte Punkte
                 "bestanden"        => false,                                                            // Boolean, ob die Übung bestanden wurde
                 "erreichteprozent" => 0,                                                                // Prozentzahl der Punktesumme
                 "score"            => 0                                                                 // Score-Wert, um die Bewertung dem Studenten anzuzeigen (Ranking) - zulässige Scorewerte liegen in [1,7]
            );

            $data["punktesumme"]      = $data["erreichtepunkte"] + $data["zusatzpunkte"];
            $data["bestanden"]        = $data["punktesumme"] >= $pnBestandenPunkte;
            $data["erreichteprozent"] = round($data["punktesumme"] / $pnUebungMaxPunkte * 100, 2);
            $data["score"]            = $this->getScore( $paScore, $data["punktesumme"] );

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
                "name"             => $poUebung->name(),                                                // Name der Übung
                "maxPunkte"        => $poUebung->maxPunkte(),                                           // maximale Punkteanzahl
                "bestandenProzent" => $poUebung->bestandenProzent(),                                    // Prozentzahl, damit die Übung als bestanden gilt
                "studenten"        => array()                                                           // Array mit Studenten-Punkte-Daten
            );
            $data["bestandenpunkte"] = round($data["maxPunkte"] / 100 * $data["bestandenProzent"], 2);  // Punkte zum Bestehen

            return $data;
        }

        
        /** erzeugt das Score-Array
         * @param $poUebung Übungsobject
         * @return Score-Array (Key-Value Struktur, wobei der Key den Punktewert
         * enthält der Überprüft wird und der Value den Score
         **/
        private function createScore( $poUebung )
        {
            $laPunkte = array();
            foreach( $poUebung->studentenuebung() as $loData )
                array_push($laPunkte, $loData->zusatzPunkte()+$loData->erreichtePunkte() );
            sort($laPunkte, SORT_NUMERIC);
            
            // 0.25 & 0.75 Quantil ermitteln und dann innerhalb aller Teile äquidistant verteilen:
            // min bis < 0.25 Quantil (2 Teile) / 0.25 Quantil bis < 0.75 Quantil (3 teile) / 0.75 Quantil bis max (2 Teile)
            $lnQ25   = $laPunkte[ intval(count($laPunkte) * 0.25) ];
            $lnQ75   = $laPunkte[ intval(count($laPunkte) * 0.75) ];
            $lnMin   = $laPunkte[0];
            $lnMax   = $laPunkte[count($laPunkte)-1];
            
            // Score-Werte erzeugen
            $laScore = array();
            
            $ln                    = round( 0.5 * ($lnQ25 - $lnMin), 2);
            $laScore[$lnMin+$ln]   = 1;
            $laScore[$lnMin+2*$ln] = 2;
            
            $ln                    = round( 1/3 * ($lnQ75-$lnQ25), 2);
            $laScore[$lnQ25+$ln]   = 3;
            $laScore[$lnQ25+2*ln]  = 4;
            $laScore[$lnQ25+3*ln]  = 5;
            
            $ln                    = round( 0.5 * ($lnMax-$lnQ75), 2);
            $laScore[$lnQ75+$ln]   = 6;
            $laScore[$lnQ75+2*$ln] = 7;
            
            ksort($laScore);
            return $laScore;
        }
        
        
        /** ermittelt aus dem Score-Array die passende Punkte
         * @param $paScore Array mit Scores
         * @param $pnPunkte Punktwert
         * @return Score-Wert
         **/
        private function getScore( $paScore, $pnPunkte )
        {
            $lnReturn = 0;
            foreach($paScore as $lnKey => $lnScore)
            {
                $lnReturn = $lnScore;
                if ($lnKey >= $pnPunkte)
                    return $lnReturn;
            }
            return $lnReturn;
        }



        /** liefert die Daten für einen Studenten
         * @param $px Studentenobjekt
         * @return Array mit den Daten eines Studenten
         **/
        function studentdaten( $px )
        {
            $loStudent = new Student( $px );

            // Daten erzeugen (das Array ist ähnlich strukturiert wie in der Methode "studententabelle()")
            $main = array( "gesamtpunkte" => 0, "gesamtpunktebestanden" => 0, "uebungen" => array(), "studenten" => array($loStudent->id() => $this->createStudentenArray($loStudent)) );

            // Iteration über jede Übung und über jeden Teilnehmer
            $uebungscore = array();
            foreach ( $this->moVeranstaltung->uebungen() as $uebung)
            {
                $main["gesamtpunkte"]            += $uebung->maxPunkte();
                $uebungarray                      = $this->createUebungsArray( $uebung );
                $uebungscore[$uebung->id()]       = $this->createScore($uebung);

                foreach ($uebung->studentenuebung(false, $loStudent) as $studentuebung )
                    $uebungarray["studenten"][$studentuebung->student()->id()] = $this->createStudentenPunkteArray( $studentuebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"], $uebungscore[$uebung->id()] );
                
                $main["uebungen"][$uebung->name()] = $uebungarray;
            }
            // berechne wie viel Gesamtpunkte zum Bestehen notwendig sind
            $main["gesamtpunktebestanden"] = ($this->moVeranstaltung->bestandenProzent() == 0 ? 0 : round( $main["gesamtpunkte"] / 100 * $this->moVeranstaltung->bestandenProzent(), 2));



            // nun existiert ein Array mit den Basis Informationen zu jedem Studenten & jeder Übung
            // da ein Student sich während des Semesters aus der Veranstaltung austragen kann, in
            // der globalen Liste aber alle Teilnehmer vorhanden sind, müssen nun die Übungen so angepasst
            // werden, dass sie gleich viele Elemente erhalten, d.h. falls Studenten nicht in allen Übungen
            // enthalten sind, werden sie Default mit Null-Werten eingefügt
            foreach ($main["uebungen"] as $uebungitem)
            {
                $uebung       = new Uebung( $this->moVeranstaltung, $uebungitem["id"] );
                $uebungarray  = $this->createUebungsArray( $uebung );

                foreach( array_diff_key( $main["studenten"], array_fill_keys($uebung->studentenuebung(true), null)) as $key => $val )
                {
                    $loStudentUebung                                                                   = new StudentUebung( $uebung, $key );
                    $main["uebungen"][$uebung->name()]["studenten"][$loStudentUebung->student()->id()] = $this->createStudentenPunkteArray( $loStudentUebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"], $uebungscore[$uebung->id()] );
                }

                // berechne die kummulierten Daten
                foreach( $uebungitem["studenten"] as $lcStudentKey => $laStudent)
                {
                    $main["studenten"][$lcStudentKey]["uebungenpunkte"] += $laStudent["punktesumme"];
                    if ($laStudent["bestanden"])
                        $main["studenten"][$lcStudentKey]["uebungenbestanden"]++;
                    else
                        $main["studenten"][$lcStudentKey]["uebungennichtbestanden"]++;
                }
            }



            // prüfe nun die Studenten, ob sie die Veranstaltung bestanden haben
            $loBonuspunkte = $this->moVeranstaltung->bonuspunkte();
            foreach ($main["studenten"] as $lcStudentKey => $laStudent)
            {
                $main["studenten"][$lcStudentKey]["veranstaltungenbestanden"] = ($laStudent["uebungenpunkte"] >= $main["gesamtpunktebestanden"]) && ($laStudent["uebungennichtbestanden"] <= $this->moVeranstaltung->allowNichtBestanden()) || $main["studenten"][$lcStudentKey]["manuelleZulassung"];;
                $main["studenten"][$lcStudentKey]["bonuspunkte"]              = ($main["gesamtpunkte"] == 0 ? 0 : $loBonuspunkte->get( $laStudent["uebungenpunkte"] / $main["gesamtpunkte"] * 100 ));
            }
            

            return $main;
        }

        /** liefert eine assoc. Array das für jeden Studenten die Anzahl der Punkt erzeugt
         * @return assoc. Array
         **/
        function studententabelle()
        {
            // das globale Array enthält einmal die Liste aller Studenten und eine Liste der übungen
            $main = array( "studenten" => array(), "statistik" => array(), "uebungen" => array(), "gesamtpunkte" => 0, "gesamtpunktebestanden" => 0 );


            // Iteration über jede Übung und über jeden Teilnehmer
            $uebungscore = array();
            foreach ( $this->moVeranstaltung->uebungen() as $uebung)
            {
                $main["gesamtpunkte"]         += $uebung->maxPunkte();
                $uebungarray                   = $this->createUebungsArray( $uebung );
                $uebungscore[$uebung->id()]    = $this->createScore($uebung);

                foreach ($uebung->studentenuebung() as $studentuebung )
                {
                    // Student der globalen Namensliste hinzufügen bzw. überschreiben und Punktedaten erzeugen
                    $main["studenten"][$studentuebung->student()->id()]        = $this->createStudentenArray( $studentuebung->student() );
                    $uebungarray["studenten"][$studentuebung->student()->id()] = $this->createStudentenPunkteArray( $studentuebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"], $uebungscore[$uebung->id()] );
                }

                $main["uebungen"][$uebung->name()] = $uebungarray;
            }
            // berechne wie viel Gesamtpunkte zum Bestehen notwendig sind
            $main["gesamtpunktebestanden"] = round( $main["gesamtpunkte"] / 100 * $this->moVeranstaltung->bestandenProzent(), 2);



            // nun existiert ein Array mit den Basis Informationen zu jedem Studenten & jeder Übung
            // da ein Student sich während des Semesters aus der Veranstaltung austragen kann, in
            // der globalen Liste aber alle Teilnehmer vorhanden sind, müssen nun die Übungen so angepasst
            // werden, dass sie gleich viele Elemente erhalten, d.h. falls Studenten nicht in allen Übungen
            // enthalten sind, werden sie Default mit Null-Werten eingefügt
            foreach ($main["uebungen"] as $uebungitem)
            {
                $uebung       = new Uebung( $this->moVeranstaltung, $uebungitem["id"] );
                $uebungarray  = $this->createUebungsArray( $uebung );
                $score        = $this->createScore($uebung);

                foreach( array_diff_key( $main["studenten"], array_fill_keys($uebung->studentenuebung(true), null)) as $key => $val )
                {
                    $loStudentUebung                                                                   = new StudentUebung( $uebung, $key );
                    $main["uebungen"][$uebung->name()]["studenten"][$loStudentUebung->student()->id()] = $this->createStudentenPunkteArray( $loStudentUebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"], $uebungscore[$uebung->id()] );
                }


                // berechne die kummulierten Daten
                foreach( $uebungitem["studenten"] as $lcStudentKey => $laStudent)
                {
                    $main["studenten"][$lcStudentKey]["uebungenpunkte"] += $laStudent["punktesumme"];
                    if ($laStudent["bestanden"])
                        $main["studenten"][$lcStudentKey]["uebungenbestanden"]++;
                    else
                        $main["studenten"][$lcStudentKey]["uebungennichtbestanden"]++;
                }
            }


            // prüfe nun die Studenten, ob sie die Veranstaltung bestanden haben
            $loBonuspunkte = $this->moVeranstaltung->bonuspunkte();
            foreach ($main["studenten"] as $lcStudentKey => $laStudent)
            {
                $main["studenten"][$lcStudentKey]["veranstaltungenbestanden"] = ($laStudent["uebungenpunkte"] >= $main["gesamtpunktebestanden"]) && ($laStudent["uebungennichtbestanden"] <= $this->moVeranstaltung->allowNichtBestanden()) || $main["studenten"][$lcStudentKey]["manuelleZulassung"];
                $main["studenten"][$lcStudentKey]["bonuspunkte"]              = ($main["gesamtpunkte"] == 0 ? 0 : $loBonuspunkte->get( $laStudent["uebungenpunkte"] / $main["gesamtpunkte"] * 100 ));
            }

            // @todo Sortierung nach Punkten muss noch hinzugefügt werden


            // berechnet die Statistik
            $main["statistik"]["teilnehmergesamt"]        = count($main["studenten"]);
            $main["statistik"]["teilnehmerbestanden"]     = 0;
            $main["statistik"]["teilnehmerbonus"]         = 0;
            $main["statistik"]["teilnehmerpunktenotzero"] = 0;
            $main["statistik"]["minpunktegreaterzero"]    = INF;
            $main["statistik"]["minpunkte"]               = 0;
            $main["statistik"]["maxpunkte"]               = 0;
            foreach ($main["studenten"] as $lcStudentKey => $laStudent)
            {
                if ($laStudent["veranstaltungenbestanden"])
                    $main["statistik"]["teilnehmerbestanden"]++;

                if ($laStudent["bonuspunkte"] > 0)
                    $main["statistik"]["teilnehmerbonus"]++;

                if ($laStudent["uebungenpunkte"] > 0)
                    $main["statistik"]["teilnehmerpunktenotzero"]++;

                
                if ($laStudent["uebungenpunkte"] > 0)
                    $main["statistik"]["minpunktegreaterzero"] = min($main["statistik"]["minpunktegreaterzero"], $laStudent["uebungenpunkte"]);
                $main["statistik"]["minpunkte"]            = min($main["statistik"]["minpunkte"], $laStudent["uebungenpunkte"]);
                $main["statistik"]["maxpunkte"]            = max($main["statistik"]["maxpunkte"], $laStudent["uebungenpunkte"]);

            }

            return $main;
        }


    }


?>
