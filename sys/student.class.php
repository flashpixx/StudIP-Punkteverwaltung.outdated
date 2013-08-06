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
    if (!class_exists("UserModel"))
        require_once(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . "/app/models/user.php");


    /** Klasse um einen Studenten vollstÃ¤ndig abzubilden **/
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
            if ($px instanceof $this)
            {
                $this->mcID    = $px->mcID;
                $this->mcName  = $px->mcName;
                $this->mcEmail = $px->mcEmail;
            }
            elseif (is_string($px))
            {
                $lo            = new User($px);
                // der Name wird in der Form "Nachname, Vorname" ausgegeben
                $this->mcName  = $lo->getFullName("full_rev");
                $this->mcEmail = User::find($px)->email;
                $this->mcID    = $px;
             }
            else
                throw new Exception("Benutzer nicht gefunden");


            if (!UserModel::check($this->mcID))
                throw new Exception(_("Benutzer existiert nicht"));

            $this->mnMatrikelnummer = MatrikelNummerFactory::get()->get( $this->mcID );
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
         * @param $pcStudiengang StudiengangsID
         * @param $pcAbschluss AbschlussID
         * @return Studiengang als Array oder den Eintrag des Studiengangs für die Veranstaltung
         **/
        function studiengang( $poVeranstaltung = null, $pcStudiengang = null, $pcAbschluss = null )
        {
            if (!($poVeranstaltung instanceof Veranstaltung))
                return UserModel::getUserStudycourse($this->mcID);

            $la = array();
            if (($pcStudiengang) && ($pcAbschluss))
            {
                if ($poVeranstaltung->isClosed())
                    throw new Exception(_("Veranstaltung ist geschlossen, eine Änderung des Studiengangs ist nicht möglich."));

                $loPrepare = DBManager::get()->prepare( "insert into ppv_studiengang values (:semid, :student, :abschluss, :studiengang) on duplicate key update abschluss = :abschluss, studiengang = :studiengang" );
                $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID, "abschluss" => $pcAbschluss, "studiengang" => $pcStudiengang) );
            }

            $loPrepare = DBManager::get()->prepare( "select s.abschluss, s.studiengang, a.name as abschlussname, g.name as studiengangname from ppv_studiengang as s left join abschluss as a on a.abschluss_id = s.abschluss left join studiengaenge as g on g.studiengang_id = s.studiengang where student = :student and seminar = :semid" );
            $loPrepare->execute( array("semid" => $poVeranstaltung->id(), "student" => $this->mcID) );

            if ($loPrepare->rowCount() == 1)
            {
                $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                array_push($la, array("fach" => $result["studiengangname"], "abschluss" => $result["abschlussname"], "abschluss_id" => $pcAbschluss, "fach_id" => $pcStudiengang, "semester" => null) );
            }

            return $la;
        }


        /** liefert die Matrikelnummer des Users, sofern vorhanden
         * @return Matrikelnummer oder null
         **/
        function matrikelnummer()
        {
            return $this->mnMatrikelnummer;
        }


        /** liefert den vollstÃ¤ndigen Namen des Users
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
