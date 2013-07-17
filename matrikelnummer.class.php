<?php

    /**
    @cond
    ############################################################################
    # GPL License                                                              #
    #                                                                          #
    # This file is part of the StudIP-Punkteverwaltung.                        #
    # Copyright (c) 2013, Philipp Kraus, <philipp.kraus@flashpixx.de>          #
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


    /** Klasse für den Zugriff auf die Matrikelnummer
     * @note Wenn die Konfiguration leer ist oder die Tabelle nicht gefunden wird, dann liefert
     * ein Zugriff auf die Nummer immer einen leeren Wert
     **/
    class MatrikelNummer
    {

        /** Konfiguration **/
        private static $maConfiguration = array(
                                             "tablename"    => "user_matrikel",
                                             "field_userid" => "user_id",
                                             "field_number" => "matrikelnr"
                                            );

        /** Datenbankobjekt, wenn null, dann kein Zugriff auf die Matrikelnummer möglich **/
        private $moDatabase = null;


        
        /** Ctor zur Überprüfung ob die Tabelle existiert **/
        function __construct()
        {
            if (self::$maConfiguration)
            {

                $this->moDatabase = DBManager::get();
                $loPrepare = $this->moDatabase->prepare("show table like :tablename limit 1",  array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("tablename" => self::$maConfiguration["tablename"]) );

                if ( !(($loPrepare) && ($loPrepare->rowCount() > 0)) )
                    $this->moDatabase = null;
            }
        }


        /** liefert die Matrikelnummer oder einen leeren Wert zurück
         * @param $pxUID BenutzerID oder ein Array mit IDs
         * @return Leerwert, Nummer oder Array mit Nummern
         **/
        function get( $pxUID )
        {
            if (!$this->moDatabase)
                return null;

            if (is_string($pxUID))
            {
                $loPrepare = $this->moDatabase->prepare("select :fieldnumber as num from :tablename where :fielduid = :uid limit 1", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array(
                                           "tablename"   => self::$maConfiguration["tablename"],
                                           "fieldnumber" => self::$maConfiguration["field_number"],
                                           "fielduid"    => self::$maConfiguration["field_userid"],
                                           "uid"         => $pxUID
                                          )
                                   );
                $loResult = $loPrepare->fetch(PDO::FETCH_ASSOC);
                if ($loResult)
                    return $loResult["num"];

            } elseif (is_array($pxUID)) {
                $loPrepare = $this->moDatabase->prepare("select :fieldnumber as num from :tablename where :fielduid = :uid limit 1", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );

                $laList = array();
                foreach ($pxUID as $lcUID)
                {
                    $loPrepare->execute( array(
                                               "tablename"   => self::$maConfiguration["tablename"],
                                               "fieldnumber" => self::$maConfiguration["field_number"],
                                               "fielduid"    => self::$maConfiguration["field_userid"],
                                               "uid"         => $lcUID
                                               )
                                        );
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
