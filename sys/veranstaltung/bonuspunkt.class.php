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



    require_once("veranstaltung.class.php");


    /** Klasse für die Bonuspunkte zu einer Veranstaltung **/
    class Bonuspunkt
    {
        /** Veranstaltung **/
        private $moVeranstaltung = null;


        /** löscht alle Bonuspunkte zu einer Veranstaltung
         * @param $po Veranstaltung
         **/
        static function delete( $po )
        {
            $lo = Veranstaltung::get( $pxVeranstaltung );
            if ($lo->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen und kann somit nicht mehr gelöscht werden"));
        }


        /** Ctor der Bonuspunkte **/
        function __construct( $pxVeranstaltung )
        {
            $this->moVeranstaltung = Veranstaltung::get( $pxVeranstaltung );
        }


        /** löscht einen Punktedatensatz
         * @param $pn Prozentzahl
         **/
        function remove( $pn )
        {

        }


        /** setzt die Bonuspunkte für einen Range
         * @param Prozentzahl für die die Punkte gesetzt werden sollen
         * @param $pnPunkte Punkte die gesetzt werden
         **/
        function set( $pn, $pnPunkte )
        {
            
        }


        /** liefert zu einem Prozentwert die Punkte
         * @param $pn Prozentwert
         * @return Punkte
         **/
        function get( $pn )
        {
            return 0;
        }

    }

?>
