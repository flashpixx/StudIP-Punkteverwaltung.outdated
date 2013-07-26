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


    /** Klasse um einen Studenten vollständig abzubilden **/
    class Student
    {

        /** speichert die UserID des Studenten **/
        private $mcID     = null;

        /** speichert den Namen des Users **/
        private $mcName   = null;

        /** speichert die EMail des users **/
        private $mcEmail  = null;
        


        
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
                $this->mcName  = $lo->getFullName();
                $this->mcEmail = User::find($px)->email;
             }
            else
                throw new Exception("Benutzer nicht gefunden");
        }


        /** liefert die ID des Users
         * @return ID
         **/
        function id()
        {
            return $this->mcID;
        }


        /** liefert den Studiengang des Users inkl. dem Abschluss
         * @return Studiengang als Array
         **/
        function studiengang()
        {
            $la = array;

            $loPrepare = DBManager::get()->prepare("select g.name as studiengang, a.name as abschluss from user_studiengang as u join studiengaenge as g on g.studiengang_id = u.studiengang_id join abschluss as a on a.abschluss_id = u.abschluss_id where u.user_id = :uid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("uid" => $this->mcID) );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                array_push( $la,  $row["abschluss"]." ".$row["studiengang"] );

            return $la;
        }


        /** liefert die Matrikelnummer des Users, sofern vorhanden
         * @return Matrikelnummer oder null
         **/
        function matrikelnummer()
        {
            return MatrikelNummerFactory::get()->get( $this->mcID );
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


        /** liefert die Fachsemesteranzahl des Users
         * @return Semesteranzahl
         **/
        function semesteranzahl()
        {

        }

    }




?>
