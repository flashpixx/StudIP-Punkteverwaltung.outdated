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



    require_once("matrikelnummer/factory.class.php");
    require_once("veranstaltung/veranstaltung.class.php");
    require_once("baseinclude.php");
    

    // exception to define an not-found exception
    class UserNotFound extends Exception {}


    /** Klasse um einen Studenten vollständig abzubilden **/
    class Student
    {

        /** speichert die UserID des Studenten **/
        private $mcID     = null;

        /** speichert den Namen des Users **/
        private $mcName   = null;

        /** speichert die EMail des Users **/
        private $mcEmail  = null;

        /** speichert die Matrikelnummer des users **/
        private $mnMatrikelnummer = null;
        


        
        /** Ctor um einen Studenten zu erzeugen
         * @param $px Studentenobjekt oder AuthentifizierungsID
         **/
        function __construct( $px )
        {
            $loUser = null;
        
            if ($px instanceof $this)
            {
                $this->mcID             = $px->mcID;
                $this->mcName           = $px->mcName;
                $this->mcEmail          = $px->mcEmail;
                $this->mnMatrikelnummer = $pc->mnMatrikelnummer;
            
                $loUser                 = new User($this->mcID);
            }
            elseif (is_string($px))
            {
                $loUser        = new User($px);
                // der Name wird in der Form "Nachname, Vorname" ausgegeben
                $this->mcName  = $loUser->getFullName("full_rev");
                $this->mcEmail = User::find($px)->email;
                $this->mcID    = $px;

                $la = MatrikelNummerFactory::get()->get( $this->mcID );
                if (is_array($la))
                    $this->mnMatrikelnummer = $la["num"];

            }
            elseif (is_numeric($px))
            {
                $la = MatrikelNummerFactory::get()->get( $px );
                if (is_array($la))
                {
                    $this->mnMatrikelnummer = $px;

                    $loUser        = new User($la["uid"]);
                    // der Name wird in der Form "Nachname, Vorname" ausgegeben
                    $this->mcName  = $loUser->getFullName("full_rev");
                    $this->mcEmail = User::find($la["uid"])->email;
                    $this->mcID    = $la["uid"];
                }
            }
            else
                throw new UserNotFound(_("Userdaten-Eingabe inkorrekt"));

            if ( (!is_object($loUser)) || (empty($loUser)) )
                throw new UserNotFound(_("Userdaten sind fehlerhafte"));
            if (!UserModel::check($this->mcID))
                throw new UserNotFound(_("Userdaten zum Login: [".$loUser->username."] / EMail: [".$loUser->email."] konnten nicht ermittelt werden"));
            if (empty($this->mnMatrikelnummer))
                throw new UserNotFound(print_r($this,true). " --- ".print_r($px, true));
            
                //throw new UserNotFound(_("Matrikelnummer zum Login: [".$loUser->username."] / EMail: [".$loUser->email."] konnten nicht ermittelt werden"));
            
        }


        /** liefert die ID des Users
         * @return ID
         **/
        function id()
        {
            return $this->mcID;
        }


        /** liefert den Studiengang des Users inkl. dem Abschluss
         * @param $poVeranstaltung Veranstaltungsobjekt
         * @param $pcAbschluss AbschlussID
         * @param $pcStudiengang StudiengangsID
         * @return Studiengang als Array oder den Eintrag des Studiengangs für die Veranstaltung
         **/
        function studiengang( $poVeranstaltung = null, $pcAbschluss = null, $pcStudiengang = null )
        {
            $laStudiengaenge = UserModel::getUserStudycourse($this->mcID);
            if (!($poVeranstaltung instanceof Veranstaltung))
                return $laStudiengaenge;

            $la = array();
            if ( (($pcStudiengang) && (!$pcAbschluss)) || ((!$pcStudiengang) && ($pcAbschluss)) )
            {
                throw new Exception(_("Für den StudentenIn ".$this->mcName." (".$this->mcEmail.") stimmen Studiengang- und/oder Abschlusszuordnung nicht"));
            } elseif (($pcStudiengang) && ($pcAbschluss)) {
                if ($poVeranstaltung->isClosed())
                    throw new Exception(_("Veranstaltung ist geschlossen, eine Änderung des Studiengangs ist nicht möglich."));

                $llFound = false;
                foreach ( $laStudiengaenge as $item )
                    if ( ($item["abschluss_id"] == $pcAbschluss) && ($item["fach_id"] == $pcStudiengang) )
                    {
                        $llFound = true;
                        break;
                    }
                if (!$llFound)
                    throw new Exception(_("Der Studiengang / Abschluss wurde nicht in der Liste der eingetragenen Studiengänge gefunden"));

                $loPrepare = DBManager::get()->prepare( "insert into ppv_studiengang values (:semid, :student, :abschluss, :studiengang) on duplicate key update abschluss = :abschluss, studiengang = :studiengang" );
                $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID, "abschluss" => $pcAbschluss, "studiengang" => $pcStudiengang) );
            }

            $loPrepare = DBManager::get()->prepare( "select s.abschluss, s.studiengang, a.name as abschlussname, g.name as studiengangname from ppv_studiengang as s left join abschluss as a on a.abschluss_id = s.abschluss left join studiengaenge as g on g.studiengang_id = s.studiengang where student = :student and seminar = :semid" );
            $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID) );

            if ($loPrepare->rowCount() == 1)
            {
                $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                array_push($la, array("fach" => $result["studiengangname"], "abschluss" => $result["abschlussname"], "abschluss_id" => $result["abschluss"], "fach_id" => $result["studiengang"], "semester" => null) );
            }

            return $la;
        }


        /** prüft, ob für den Studenten der Studiengang korrekt hinterlegt ist
         * @retrun boolean ob ein Fehler vorhanden ist
         **/
        function checkStudiengangAbschlussFehler()
        {
            foreach ( UserModel::getUserStudycourse($this->mcID) as $item )
                if ( (empty($item["abschluss_id"])) || (empty($item["fach_id"])) )
                    return true;

            return false;
        }


        /** liefert die Information, ob für den Studenten eine manuelle Zulassung hinterlegt wurde
         * @param $poVeranstaltung Veranstaltungsobjekt
         * @param $pcBemerkung Bemerkungsstring, der gesetzt werden soll
         * @return String mit einer Bemerkung oder null
         **/
        function manuelleZulassung( $poVeranstaltung, $pcBemerkung = false )
        {
            if (!($poVeranstaltung instanceof Veranstaltung))
                throw new Exception(_("kein Veranstaltungsobjekt übergeben"));

            $lc = null;
            if ( (!is_bool($pcBemerkung)) || (is_string($pc)) )
            {

                if ($poVeranstaltung->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es können keine Änderungen mehr durchgeführt werden"));

                if (empty($pcBemerkung))
                {
                    $loPrepare = DBManager::get()->prepare( "delete from ppv_seminarmanuellezulassung where seminar=:semid and student=:student limit 1" );
                    $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID) );
                } else {
                    $loPrepare = DBManager::get()->prepare( "insert into ppv_seminarmanuellezulassung (seminar, student, bemerkung) values (:semid, :student, :bemerkung) on duplicate key update bemerkung = :bemerkung" );
                    $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID, "bemerkung" => $pcBemerkung) );
                }

                $lc = $pcBemerkung;

            } else {

                $loPrepare = DBManager::get()->prepare( "select bemerkung from ppv_seminarmanuellezulassung where seminar=:semid and student=:student" );
                $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }
                
            }

            return $lc;
        }


        /** liefert die Matrikelnummer des Users, sofern vorhanden
         * @return Matrikelnummer oder null
         **/
        function matrikelnummer()
        {
            return $this->mnMatrikelnummer;
        }


        /** liefert den vollständigen Namen des Users
         * @return Name
         **/
        function name()
        {
            return $this->mcName;
        }


        /** liefert die EMail Adresse des Users 
         * @return EMail
         **/
        function email()
        {
            return $this->mcEmail;
        }

    }




?>
