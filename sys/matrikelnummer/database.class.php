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



    require_once("interface.class.php");


    /** Klasse für den Zugriff auf die Matrikelnummer **/
    class MatrikelNummerDatabase implements MatrikelNummerInterface
    {

        /** Konfiguration **/
        private static $maConfiguration = array(
                                             "tablename"    => "user_matrikel",
                                             "field_userid" => "user_id",
                                             "field_number" => "matrikelnr"
                                            );

        /** Prepare Statement für Abfragen anhand der UID **/
        private $moPrepareUID = null;

        /** Prepare Statement für Abfragen anhand der Matrikelnummer **/
        private $moPrepareNUM = null;
        
        /** zeigt an, dass die Möglichkeit besteht die Matrikelnummer abzufragen **/
        private $mlExists = false;


        
        /** Ctor zur Überprüfung ob die Tabelle existiert **/
        function __construct()
        {
            if (self::$maConfiguration)
            {
                $loPrepare = DBManager::get()->prepare("show tables like :tablename", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("tablename" => self::$maConfiguration["tablename"]) );
                $this->mlExists = $loPrepare->rowCount() == 1;
            }

            if ($this->mlExists)
            {
                $this->moPrepareUID = DBManager::get()->prepare("select ".self::$maConfiguration["field_number"]." as num, ".self::$maConfiguration["field_userid"]." as uid from ".self::$maConfiguration["tablename"]." where ".self::$maConfiguration["field_userid"]." = :uid limit 1", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );

                $this->moPrepareNUM = DBManager::get()->prepare("select ".self::$maConfiguration["field_number"]." as num, ".self::$maConfiguration["field_userid"]." as uid from ".self::$maConfiguration["tablename"]." where ".self::$maConfiguration["field_number"]." = :num limit 1", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            }
        }


        /** liefert die Matrikelnummer oder einen leeren Wert zurück
         * @overload
         * @param $px BenutzerID oder ein Array mit IDs / Matrikelnummer bzw. Array mit Matrikelnummern
         * @return Leerwert, Nummer oder Array mit Nummern
         **/
        function get( $px )
        {
            if (!$this->mlExists)
                return null;

            if (is_string($px))
            {
                error_log("blub "+$px);
                $this->moPrepareUID->execute( array("uid" => $px ) );
                $loResult = $this->moPrepareUID->fetch(PDO::FETCH_ASSOC);
                if ($loResult)
                    return array( $loResult["uid"] => intval($loResult["num"]) );


            } elseif (is_numeric($px)) {

                $this->moPrepareNUM->execute( array("num" => $px ) );
                $loResult = $this->moPrepareNUM->fetch(PDO::FETCH_ASSOC);
                if ($loResult)
                    return array( $loResult["uid"] => intval($loResult["num"]) );


            } elseif (is_array($px)) {
                $laList = array();
                foreach ($px as $lx)
                {
                    $lxData = $this->get($lx);
                    if (is_array($lxData))
                        $laList = array_merge($laList, $lxData);
                }
                return $laList;

            }

            return null;
        }


    }
    
?>
