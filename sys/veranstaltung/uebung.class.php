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



    require_once("veranstaltung.class.php");
    require_once("studentuebung.class.php");
    require_once("interface.class.php");


    /** Klasse für die Übungsdaten **/
    class Uebung implements VeranstaltungsInterface
    {

        /** Veranstaltungsobjekt auf das sich die Übung bezieht */
        private $moVeranstaltung = null;

        /** ÜbungsID **/
        private $mcID = null;

        /** maximale Punktanzahl der Übung (für schnelles Caching) **/
        private $mnMaxPunkte = 0;



        /** erzeugt eine neue Übung
         * @warn der PK der Tabelle wird, wie es in StudIP üblich ist, application-side erzeugt, hier wird aber ein MD5 Hash verwendet inkl als Prefix die ID der Veranstaltung
         * @param $pxVeranstaltung Veranstaltungsobjekt oder -ID
         * @param $pcName name der Übung
         **/
        static function create( $pxVeranstaltung, $pcName )
        {
            if ((!is_string($pcName)) || (empty($pcName)))
                throw new Exception(_("Für die Erzeugung der Übung muss ein Name vergeben werden"));

            $lo = Veranstaltung::get( $pxVeranstaltung );
            $lcID = md5( uniqid($lo->id(), true) );

            $loPrepare = DBManager::get()->prepare( "insert into ppv_uebung (seminar, id, uebungsname, bestandenprozent, maxpunkte) values (:semid, :id, :name, :prozent, :maxpunkte)" );
            $loPrepare->execute( array("semid" => $lo->id(), "id" => $lcID, "name" => $pcName, "prozent" => 50, "maxpunkte" => 1) );

            $lcClassName = __CLASS__;
            return new $lcClassName( $pxVeranstaltung, $lcID );
        }


        /** löscht eine Übung mit allen dazugehörigen Daten
         * @param $pxVeranstaltung Veranstaltungsobjekt oder -ID
         * @param $pxID Übungsobjekt oder -ID
         **/
        static function delete( $pxVeranstaltung, $pxID )
        {
            $lcClassName = __CLASS__;
            $loUebung = new $lcClassName( $pxVeranstaltung, $pxID );

            foreach( $loUebung->studentenuebung() as $item )
                StudentUebung::delete( $item->uebung(), $item->student() );

            $loPrepare = DBManager::get()->prepare( "delete from ppv_uebung where seminar = :semid and id = :id" );
            $loPrepare->execute( array("semid" => $loUebung->veranstaltung()->id(), "id" => $loUebung->id()) );
        }



        /** Ctor für die Übungen
         * @param $pxVeranstaltung VeranstaltungsID oder Veranstaltungsobjekt oder Übungsobjekt um Copy-Ctor abzubilden
         * @param $pxUebung Übungsobjekt oder ÜbungsID
         **/
        function __construct( $pxVeranstaltungUebung, $pxUebung = null )
        {
            if ($pxVeranstaltungUebung instanceof $this)
            {
                $this->moVeranstaltung = $pxVeranstaltungUebung->moVeranstaltung;
                $this->mcID            = $pxVeranstaltungUebung->mcID;
                $this->mnMaxPunkte     = $pxVeranstaltungUebung->mnMaxPunkte;
            } else {
                $this->moVeranstaltung = Veranstaltung::get( $pxVeranstaltung );

                if (is_string($pxUebung))
                {
                    $loPrepare = DBManager::get()->prepare("select id, maxpunkte from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                    $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $pxUebung) );
                    if ($loPrepare->rowCount() != 1)
                        throw new Exception(_("Übung nicht gefunden"));

                    $result            = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $this->mnMaxPunkte = $result["maxpunkte"];
                    $this->mcID        = $result["id"];
                }
                elseif ($pxUebung instanceof $this)
                {
                    $this->mcID            = $pxUebung->mcID;
                    $this->mnMaxPunkte     = $pxUebung->mnMaxPunkte;
                }
            }

            if (!$this->mcID)
                throw new Exception(_("Übungsparameter nicht definiert"));
        }


        /** liefert die Veranstaltung der Übung 
         * @return Veranstaltungsobjekt
         **/
        function veranstaltung()
        {
            return $this->moVeranstaltung;
        }


        /** liefert die ID der Veranstaltung
         * @return ID
         **/
        function id()
        {
            return $this->mcID;
        }


        /** liefert den Namen der Übung zurück bzw. setzt ihn neu
         * @param $pc neuer Name
         * @return Name
         **/
        function name( $pc = null )
        {
            $lc = null;
            
            if ((!empty($pc)) && (is_string($pc)) )
            {

                DBManager::get()->prepare( "update ppv_uebung set uebungsname = :name where seminar = :semid and id = :i" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "name" => $pc) );
                $lc = $pc;

            } else {

                $loPrepare = DBManager::get()->prepare("select uebungsname from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["uebungsname"];
                }

            }

            return $lc;
        }

        
        /** liefert die Prozentzahl (über alle Übungen) ab wann eine Veranstaltung als bestanden gilt
         * @param $pn Wert zum setzen der Prozentzahl
         * @return Prozentwert
         **/
        function bestandenProzent( $pn = null )
        {
            $ln = 0;

            if (is_numeric($pn))
            {

                if (($pn < 0) || ($pn > 100))
                    throw new Exception(_("Parameter Prozentzahl für das Bestehen liegt nicht im Interval [0,100]"));

                DBManager::get()->prepare( "update ppv_uebung set bestandenprozent = :prozent where seminar = :semid and id = :i" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "prozent" => floatval($pn)) );

                $ln = $pn;

            } else {

                $loPrepare = DBManager::get()->prepare("select bestandenprozent from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["bestandenprozent"];
                }
                
            }
            
            return floatval($ln);
        }


        /** liefert Anzahl an Punkten für die Übung
         * @param $pn Wert zum setzen der Punkte
         * @return Punkte
         **/
        function maxPunkte( $pn = null )
        {
            if (is_numeric($pn))
            {
                if ($pn < 0)
                    throw new Exception(_("Parameter für die Punkte muss größer gleich Null sein"));

                $this->mnMaxPunkte = floatval($pn);
                DBManager::get()->prepare( "update ppv_uebung set maxpunkte = :pt where seminar = :semid and id = :i" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "pt" => $this->mnMaxPunkte) );
            }

            return $this->mnMaxPunkte;
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
                DBManager::get()->prepare( "update ppv_uebung set bemerkung = :bem where seminar = :semid and id = :id" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "bem" => (empty($pc) ? null : $pc)) );

                $lc = $pc;
            } else {
                $loPrepare = DBManager::get()->prepare("select bemerkung from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }

            }
            
            return $lc;
        }


        /** liefert eine Liste mit allen Studenten
         * für diese Übung zurück
         * @return Array mit Objekten von Student-Übung
         **/
        function studentenuebung()
        {
            $la = array();

            $loPrepare = DBManager::get()->prepare("select user_id from seminar_user where status = :status and Seminar_id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "status" => "autor") );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                array_push($la, new StudentUebung( $this, $row["user_id"] ) );

            return $la;
        }


        /** liefert / setzt das Abgabedatum 
         * @param $pc Abgabedatum order null
         * @return Datum
         **/
        function abgabeDatum( $pn = false )
        {
            return null;
        }


    }

?>