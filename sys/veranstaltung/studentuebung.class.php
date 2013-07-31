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
    require_once(dirname(__DIR__) . "/student.class.php");



    /** Klasse für die Übung-Student-Beziehung
     * @note Die Klasse legt bei Änderungen automatisiert ein Log an
     **/
    class StudentUebung implements VeranstaltungsInterface
    {

        /** Übungsobjekt **/
        private $moUebung      = null;

        /** Studentenobjekt **/
        private $moStudent     = null;

        /** Prepare Statement für das Log zu erezugen **/
        private $moLogPrepare  = null;

        

        /** löscht den Eintrag von einem Studenten zur Übung mit einem ggf vorhandenen Log
         * @param $pxUebung Übung
         * @param $pxAuth Authentifizierungsschlüssel des Users oder Studentenobjekt
         **/
        static function delete( $pxUebung, $pxAuth )
        {
            $loUebung  = new Uebung( $pxUebung );
            $loStudent = new Student( $pxAuth );


            $loPrepare = DBManager::get()->prepare( "delete from ppv_uebungstudentlog where uebung = :uebungid and student = :auth" );
            $loPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $loStudent->id()) );

            $loPrepare = DBManager::get()->prepare( "delete from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
            $loPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $loStudent->id()) );

        }



        /** Ctor für den Zugriff auf auf die Studenten-Übungsbeziehung 
         * @param $pxUebung Übung
         * @param $pcAuth Authentifizierung
         **/
        function __construct( $pxUebung, $pxAuth )
        {
            if (is_string($pxAuth))
                $this->moStudent = new Student($pxAuth);
            elseif ($pxAuth instanceof Student)
                $this->moStudent = $pxAuth;
            else
                throw new Exception(_("Keine korrekten Authentifizierungsdaten übergeben"));

            $this->moUebung     = new Uebung( $pxUebung );
            $this->moLogPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudentlog select NULL as id, d.* from ppv_uebungstudent as d where uebung = :uebungid and student = :auth" );
        }


        /** liefert die Authentifizierung
         * @return AuthString
         **/
        function student()
        {
            return $this->moStudent;
        }


        /** liefert die Übung
         * @return liefert das Übungsobjekt
         **/
        function uebung()
        {
            return $this->moUebung;
        }

        
        /** liefert die IDs des Datensatzes
         * @return ID als Array
         **/
        function id()
        {
            return array( "uebung" => $this->moUebung->id(), "uid" => $this->moStudent->id() );
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
                if ($pn > $this->moUebung->maxPunkte())
                    throw new Exception(_("Erreichte Punkte sind sind größer als die möglichen Punkte, die bei der Übung vergeben werden können. Bitte Zusatzpunkte verwenden"));

                $this->moLogPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );
                
                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, erreichtepunkte, korrektor) values (:uebungid, :auth, :punkte, :korrektor) on duplicate key update erreichtepunkte = :punkte" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "punkte" => floatval($pn), "korrektor" => $GLOBALS["user"]->id) );

                $ln = $pn;

            } else {
                $loPrepare = DBManager::get()->prepare( "select erreichtepunkte from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["erreichtepunkte"];
                }
            }

            return floatval($ln);
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
                if ($pn < 0)
                    throw new Exception(_("Zusatzpunkte müssen größer gleich Null sein"));

                $this->moLogPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );
                
                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, zusatzpunkte, korrektor) values (:uebungid, :auth, :punkte, :korrektor) on duplicate key update zusatzpunkte = :punkte" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "punkte" => floatval($pn), "korrektor" => $GLOBALS["user"]->id) );

                $ln = $pn;

            } else {
                $loPrepare = DBManager::get()->prepare( "select zusatzpunkte from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["zusatzpunkte"];
                }
            }
            
            return floatval($ln);
        }


        /** liefert / setzt die Bemerkung
         * @param $pc Bemerkung
         * @return Bemerkungstext
         **/
        function bemerkung( $pc = false )
        {
            $lc = null;

            if ( (!is_bool($pc)) && ((empty($pc)) || (is_string($pc))) )
            {
                $this->moLogPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, bemerkung, korrektor) values (:uebungid, :auth, :bemerkung, :korrektor) on duplicate key update bemerkung = :bemerkung" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "bemerkung" => (empty($pc) ? null : $pc), "korrektor" => $GLOBALS["user"]->id) );

                $lc = $pc;
            } else {
                $loPrepare = DBManager::get()->prepare("select bemerkung from ppv_uebungstudent where uebung = :uebungid and student = :auth", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }

            }
            
            return $lc;
        }


        /** updated den Datensatz für alle drei Felder
         * @param $pnErreichtePunkte numerischer Wert der erreichten Punkte
         * @param $pnZusatzPunkte numerischer Wert der zusätzlichen Punkte
         * @param $pcBemerkung Stringwert für die Bemerkung
         **/
        function update( $pnErreichtePunkte, $pnZusatzPunkte, $pcBemerkung )
        {
            if (!is_numeric($pnErreichtePunkte))
                throw new Exception(_("Erreichte Punkte sind nicht numerisch"));
            if (!is_numeric($pnZusatzPunkte))
                throw new Exception(_("zusätzliche Punkte sind nicht numerisch"));
            if ( (!empty($pcBemerkung)) && (!is_string($pcBemerkung)) )
                throw new Exception(_("Bemerkung ist nicht leer oder ist kein Text"));

            if ($pnErreichtePunkte > $this->moUebung->maxPunkte())
                throw new Exception(_("Erreichte Punkte sind sind größer als die möglichen Punkte, die bei der Übung vergeben werden können. Bitte Zusatzpunkte verwenden"));
            if ($pnZusatzPunkte < 0)
                throw new Exception(_("Zusatzpunkte müssen größer gleich Null sein"));



            $this->moLogPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

            $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, bemerkung, korrektor, zusatzpunkte, erreichtepunkte) values (:uebungid, :auth, :bemerkung, :korrektor, :zusatzpunkte, :erreichtepunkte) on duplicate key update bemerkung = :bemerkung" );
            $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "bemerkung" => (empty($pcBemerkung) ? null : $pcBemerkung), "korrektor" => $GLOBALS["user"]->id, "zusatzpunkte" => $pnZusatzPunkte, "erreichtepunkte" => $pnErreichtePunkte) );
        }
        

        /** liefert alle Logeinträge für diese Student-Übungsbeziehung
         * als assoziatives Array
         * @return assoziatives Array
         **/
        function log()
        {
            $la = array();

            $loPrepare = DBManager::get()->prepare("select erreichtepunkte, zusatzpunkte, bemerkung from ppv_uebungstudentlog where uebung = :uebungid and student = :auth", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                array_push($la, $row );

            return $la;
        }

    }

?>
