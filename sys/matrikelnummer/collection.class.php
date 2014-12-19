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
    
    
    /** Klasse für den Zugriff auf die Matrikelnummer mit unterschiedlichen Diensten **/
    class MatrikelNummerCollection implements MatrikelNummerInterface
    {
        
        /** Array mit allen zur Verfügung stehenden Diensten **/
        private $maServices = array();
        
        
        
        /** fügt ein neues Objekt an die Collection an
         * @param $po Objeckt das das MatrikelNummerInterface implementiert
         **/
        function push( $po )
        {
            if (!($po instanceof MatrikelNummerInterface))
                throw new Exception(_("übergebenes Objekt implementiert nicht das MatrikelNummerInterface"));
            
            array_push($this->maServices, $po);
        }
        
        
        /** liefert die Matrikelnummer oder einen leeren Wert zurück
         * @overload
         * @param $px BenutzerID oder ein Array mit IDs / Matrikelnummer bzw. Array mit Matrikelnummern
         * @return Leerwert, Nummer oder Array mit Nummern
         **/
        function get( $px )
        {
            foreach($this->maServices as $loService)
            {
                $lxResult = $loService->get( $px );
                if ( (is_array($lxResult)) || (is_numeric($lxResult)) )
                    return $lxResult;
            }
            
            return null;
        }

    }


?>
