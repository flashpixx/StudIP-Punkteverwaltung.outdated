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

    

    /** Klasse für die Veranstaltungsdaten **/
    class Veranstaltung
    {
        /** Database Connection **/
        private $moDatabase = null;

        /** ID der Veranstaltung **/
        private $mcID = null;



        /* statische Methode für die Überprüfung, ob Übungsdaten zu einer Veranstaltung existieren
         * @param $pcID VeranstaltungsID (SeminarID) [leer für aktuelle ID, sofern vorhanden]
         * @return liefert null (false) bei Nicht-Existenz, andernfalls das Veranstaltungsobject
         **/
        static function get( $pcID = null )
        {
            try
            {
                if ( (empty($pcID)) && (isset($GLOBALS["SessionSeminar"])) )
                    return new Veranstaltung($GLOBALS["SessionSeminar"]);

                return new Veranstaltung($pcID);
            } catch (Exception $e) {}

            return null;
        }


        /** erzeugt einen neuen Eintrag für die Veranstaltung
         * @param $pcID VeranstaltungsID (SeminarID) [leer für aktuelle ID, sofern vorhanden]
         **/
        static function create( $pcID = null )
        {
            if ( (empty($pcID)) && (isset($GLOBALS["SessionSeminar"])) )
                $pcID = $GLOBALS["SessionSeminar"];

            $loPrepare = DBManager::get()->prepare( "insert into ppv_seminar (id, bestehenprozent, allow_nichtbestanden) values (:semid, 100, 0)" );
            $loPrepare->execute( array("semid" => $pcID) );
        }


        

        /** privater Ctor, um das Objekt nur durch den statischen Factory (get) erzeugen zu können
         * @param $pcID VeranstaltungsID (SeminarID)
         **/
        private function __construct($pcID)
        {
            if (empty($pcID))
                throw new Exception("Veranstaltungsid nicht gesetzt");

            $this->moDatabase = DBManager::get();

            $loPrepare = $this->moDatabase->prepare("select id from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("semid" => $pcID) );
            if ($loPrepare->rowCount() != 1)
                throw new Exception("Veranstaltung nicht gefunden");

            $this->mcID       = $pcID;
        }


        /** liefert die ID der Veranstaltung
         * @return ID
         **/
        function id()
        {
            return $this->id;
        }


        /** liefert die Prozentzahl (über alle Übungen) ab wann eine Veranstaltung als bestanden gilt
         * @param $pn Wert zum setzen der Prozentzahl
         * @return Prozentwert
         **/
        function BestandenProzent( $pn = null )
        {
            $ln = 0;

            if (is_numeric($pn))
            {
                
                if (($pn < 0) || ($pn > 100))
                    throw new Exception("Parameter Prozentzahl für das Bestehen liegt nicht im Interval [0,100]");

                $this->moDatabase->prepare( "update ppv_seminar set bestandenprozent = :prozent where id = :semid" )->execute( array("semid" => $this->id, "prozent" => floatval($pn)) );

                $ln = $pn;

            } else {

                $loPrepare = $this->moDatabase->prepare("select bestandenprozent from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->id) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["bestandenprozent"];
                }

            }
                
            return floatval($ln);
        }


        /** liefert die Anzahl an Übungen, die als nicht-bestanden
         * gewertet werden dürfen, um die Veranstaltung trotzdem zu bestehen
         * @param $pn Anzahl der Übungen, die als nicht-bestanden akzeptiert werden
         * @return Anzahl
         **/
        function allowNichtBestanden( $pn = null )
        {
            $ln = 0;

            if (is_numeric($pn))
            {

                if ($pn < 0)
                    throw new Exception("Parameter muss größer gleich null sein");

                $this->moDatabase->prepare( "update ppv_seminar set allow_nichtbestanden = :anzahl where id = :semid" )->execute( array("semid" => $this->id, "anzahl" => intval($pn)) );

                $ln = $pn;

            } else {

                $loPrepare = $this->moDatabase->prepare("select allow_nichtbestanden from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->id) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["allow_nichtbestanden"];
                }
                
            }
            
            return intval($ln);

        }


        /** liefert die Bemerkung 
         * @param $pc Bemerkung 
         * @return Bemerkungstext
         **/
        function bemerkung( $pc = false )
        {
            $lc = null;

            if ( (empty($pc)) || (is_string($pc)) )
            {
                $this->moDatabase->prepare( "update ppv_seminar set bemerkung = :bem where id = :semid" )->execute( array("semid" => $this->id, "bem" => $pc) );

                $lc = $pc;
            }

            return $lc;
        }
        
    }
    
    
?>
