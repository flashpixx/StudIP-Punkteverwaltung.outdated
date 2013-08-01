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



    /** Klasse für zentrale Funktionen **/
    class Tools
    {

        /** Methode, die eine Messagebox generiert, sofern Daten vorhanden sind
         * @see http://docs.studip.de/develop/Entwickler/ModalerDialog
         * @see http://docs.studip.de/develop/Entwickler/Messagebox
         * @param $paMessage Message-Array
         * @return Booleanwert, ob die Nachricht eine Information / Success war
         **/
        static function showMessage( $paMessage )
        {
            if ( (empty($paMessage)) || (!is_array($paMessage)) || (!isset($paMessage["type"])) || (!isset($paMessage["msg"])) )
                return true;

            $la = array();
            if ( (isset($paMessage["info"])) && (is_array($paMessage["info"])) )
                $la = $paMessage["info"];

            if (($paMessage) && (strcasecmp($paMessage["type"], "error") == 0))
            {
                echo MessageBox::error($paMessage["msg"], $la);
                return false;
            } elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "success") == 0))
                echo MessageBox::success($paMessage["msg"], $la);
            elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "info") == 0))
                echo MessageBox::info($paMessage["msg"], $la);
            elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "question") == 0) && (!empty($la)) )
                echo createQuestion($paMessage["msg"], $la);

            return true;
        }


        /** Methode um einen Messagetext zu generieren
         * @param $pcTyp ist der Messagetyp, Werte sind: error, success, info, question
         * @param $pcText Text der Nachricht
         * @param $paInfo weitere Texte oder für den Question-Dialog das return Array
         * @return Array mit Messagedaten
         **/

        static function createMessage( $pcType, $pcText, $paInfo = array() )
        {
            return array("type" => $pcType, "msg" => $pcText, "info" => $paInfo );
        }
        
    }
    
    ?>
