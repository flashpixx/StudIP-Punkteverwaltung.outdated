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
                $lo = new User($px);
                $this->mcName = $lo->getFullName();
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
         * @return Studiengang
         **/
        function studiengang()
        {
            abschluss ziehen aus: studiengänge, studiengänge_abschluss und user_studiengang ggf user_studiengang_back
        }


        /** liefert die Matrikelnummer des Users, sofern vorhanden
         * @return Matrikelnummer oder null
         **/
        function matrikelnummer()
        {
            return MatrikelNummerFactory::get()->get( $this->id );
        }


        /** liefert den vollständigen Namen des Users
         * @return Name
         **/
        function name()
        {

        }


        /** liefert die EMail Adresse des Users 
         * @return EMail
         **/
        function email()
        {

        }


        /** liefert die Fachsemesteranzahl des Users
         * @return Semesteranzahl
         **/
        function semesteranzahl()
        {

        }

    }




?>
