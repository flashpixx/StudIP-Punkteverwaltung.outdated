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

        
        /** zeit an, dass die Möglichkeit besteht die Matrikelnummer abzufragen **/
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
        }


        /** liefert die Matrikelnummer oder einen leeren Wert zurück
         * @overload
         * @param $pxUID BenutzerID oder ein Array mit IDs
         * @return Leerwert, Nummer oder Array mit Nummern
         **/
        function get( $pxUID )
        {
            if (!$this->mlExists)
                return null;

            $loPrepare = DBManager::get()->prepare("select ".self::$maConfiguration["field_number"]." as num from ".self::$maConfiguration["tablename"]." where ".self::$maConfiguration["field_userid"]." = :uid limit 1", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );

            if (is_string($pxUID))
            {
                $loPrepare->execute( array("uid" => $pxUID ) );
                $loResult = $loPrepare->fetch(PDO::FETCH_ASSOC);
                if ($loResult)
                    return $loResult["num"];

            } elseif (is_array($pxUID)) {
                $laList = array();
                foreach ($pxUID as $lcUID)
                {
                    $loPrepare->execute( array( "uid" => $lcUID ) );
                    $loResult = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    if ($loResult)
                        array_push($laList, $loResult["num"]);
                }

                return $laList;
            }

            return null;
        }


    }
    
?>
