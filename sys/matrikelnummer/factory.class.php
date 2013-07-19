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

    

    require_once("database.class.php");



    /** Factoryklasse, die in Abhängigkeit der Konfiguration das passende
     * Objekt für den Zugriff auf die Matrikelnummer liefert
     * @warn aktuell nur Zugriff auf die Datenbank vorhanden
     **/
    class MatrikelNummerFactory
    {

        /** liefert das passende Zugriffobjekt für die Matrikelnummer oder null
         * @return Viewobjekt
         **/
        static function get()
        {
            return new MatrikelNummerDatabase();
        }
        
    }
    
?>
