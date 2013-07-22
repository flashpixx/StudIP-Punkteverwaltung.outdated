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



    require_once("uebung.class.php");
    require_once("interface.class.php");


    /** Klasse für die Übung-Student-Beziehung
     * @note Die Klasse legt bei Änderungen automatisiert ein Log an
     **/
    class UebungStudent implements VeranstaltungsInterface
    {

        private $moUebung      = null;
        private $mcAuth        = null;
        private $moLogPrepare  = null;

        

        /** löscht den Eintrag von einem Studenten zur Übung mit einem ggf vorhandenen Log
         * @param $pxUebung Übung
         * @param $pcAuth Authentifizierungsschlüssel des Users
         **/
        static function delete( $pxUebung, $pcAuth )
        {
            $loUebung = new uebung( $pxUebung );

            $loPrepare = DBManager::get()->prepare( "delete from ppv_uebungstudentlog where uebung = :uebungid and student = :auth" );
            $loPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $pcAuth) );

            $loPrepare = DBManager::get()->prepare( "delete from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
            $loPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $pcAuth) );

        }



        /** Ctor für den Zugriff auf auf die Studenten-Übungsbeziehung 
         * @param $pxUebung Übung
         * @param $pcAuth Authentifizierung
         **/
        function __construct( $pxUebung, $pcAuth )
        {
            if (!is_string($pcAuth))
                throw Exception("Keine korrekten Authentifizierungsdaten übergeben");

            $this->moUebung     = new Uebung( $pxUebung );
            $this->moLogPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudentlog select null, * from ppv_uebungstudentlog where uebung = :uebungid and student = :auth" )
        }


        /** liefert die Authentifizierung
         * @return AuthString
         **/
        function auth()
        {
            return $this->mcAuth;
        }


        /** liefert die Übung
         * @return liefert das Übungsobjekt
         **/
        function uebung()
        {
            return $this->moUebung;
        }


        /** liefert / setzt die erreichten Punkte für den Stundent / Übung
         * und schreibt ggf einen vorhanden Datensatz ins Log
         * @param $pn Punke
         * @return Punkte
         **/
        function erreichtePunkte( $pn = false )
        {
            $ln = 0;
            if (is_numeric($pn))
            {
                $this->moLogPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $pcAuth) );
                
                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, erreichtepunkte) values (:uebungid, :auth, :punkte) on duplicate key update erreichtepunkte = :punkte" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->mcAuth, "punkte" => float($pn)) );

                $ln = $pn;

            } else {
                $loPrepare = DBManager::get()->prepare( "select erreichtepunkte from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->mcAuth) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["erreichtepunkte"];
                }
            }

            return float($ln);
        }


        /** liefert / setzt die Zusatzpunkt zu einer Übung für einen Studenten
         * und schreibt die Daten ins Log sofern vorhanden
         * @param $pn Punkte
         * @return Punkte
         **/
        function zusatzPunkte( $pn = false )
        {
            $ln = 0;
            if (is_numeric($pn))
            {
                $this->moLogPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $pcAuth) );
                
                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, zusatzpunkte) values (:uebungid, :auth, :punkte) on duplicate key update zusatzpunkte = :punkte" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->mcAuth, "punkte" => float($pn)) );

                $ln = $pn;

            } else {
                $loPrepare = DBManager::get()->prepare( "select zusatzpunkte from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->mcAuth) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["zusatzpunkte"];
                }
            }
            
            return float($ln);
        }


        /** liefert / setzt die Bemerkung
         * @param $pc Bemerkung
         * @return Bemerkungstext
         **/
        function bemerkung( $pc = false )
        {
            $lc = null;

            if ( (empty($pc)) || (is_string($pc)) )
            {
                $this->moLogPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $pcAuth) );

                DBManager::get()->prepare( "update ppv_uebungstudent set bemerkung = :bem where seminar = :uebungid and student = :auth" )->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->mcAuth, "bem" => $pc) );

                $lc = $pc;
            } else {
                $loPrepare = DBManager::get()->prepare("select bemerkung from ppv_uebungstudent where seminar = :uebungid and student = :auth", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->mcAuth) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }

            }
            
            return $lc;
        }
        

        /** liefert alle Logeinträge für diese Student-Übungsbeziehung
         * als assoziatives Array
         * @return assoziatives Array
         **/
        function log()
        {
            $la = array();

            $loPrepare = DBManager::get()->prepare("select erreichtepunkte, zusatzpunkte, bemerkung from ppv_uebungstudentlog where uebung = :uebungid and student = :auth", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->mcAuth) );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                array_push($la, $row );

            return $la;
        }

    }

?>
