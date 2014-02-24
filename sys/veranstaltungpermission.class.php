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



    require_once("veranstaltung/veranstaltung.class.php");

    
    /** Klasse um Zugriffsrechte zu einer Veranstaltung für einen User zu prüfen **/
    class VeranstaltungPermission
    {

        /** prüft, ob der aktuelle Benutzer Admin Rechte auf der Veranstaltung hat
         * @param px null, String oder Veranstaltungsobjekt
         * @return Boolean für die Rechte
         **/
        static function hasAdminRecht( $px = null )
        {
            return $GLOBALS["perm"]->have_perm("admin")
        }

        
        /** prüft, ob der aktuelle Benuter auf der aktuellen Veranstaltung Dozentenrechte hat
         * @param px null, String oder Veranstaltungsobjekt
         * @return Boolean für die Rechte
         **/
        static function hasDozentRecht( $px = null )
        {
            if ((empty($px)) && (isset($GLOBALS["SessionSeminar"])))
                return $GLOBALS["perm"]->have_studip_perm("dozent", $GLOBALS["SessionSeminar"]);
            elseif ($px instanceof Veranstaltung)
                return $GLOBALS["perm"]->have_studip_perm("dozent", $px->id());
            elseif (is_string($px))
                return $GLOBALS["perm"]->have_studip_perm("dozent", $px);

            return false;
        }


        /** prüft, ob der aktuelle Benuter auf der aktuellen Veranstaltung Tutorenrechte hat
         * @param px null, String oder Veranstaltungsobjekt
         * @return Boolean für die Rechte
         **/
        static function hasTutorRecht( $px = null )
        {
            if ((empty($px)) && (isset($GLOBALS["SessionSeminar"])))
                return $GLOBALS["perm"]->have_studip_perm("tutor", $GLOBALS["SessionSeminar"]);
            elseif ($px instanceof Veranstaltung)
                return $GLOBALS["perm"]->have_studip_perm("tutor", $px->id());
            elseif (is_string($px))
                return $GLOBALS["perm"]->have_studip_perm("tutor", $px);

            return false;
        }


        /* prüft, ob der aktuelle User Autorenrechte in der Veranstaltung hat
         * @param px null, String oder Veranstaltungsobjekt
         * @return Boolean für die Rechte
         **/
        static function hasAutorRecht( $px = null )
        {
            if ((empty($px)) && (isset($GLOBALS["SessionSeminar"])))
                return $GLOBALS["perm"]->have_studip_perm("autor", $GLOBALS["SessionSeminar"]);
            elseif ($px instanceof Veranstaltung)
                return $GLOBALS["perm"]->have_studip_perm("autor", $px->id());
            elseif (is_string($px))
                return $GLOBALS["perm"]->have_studip_perm("autor", $px);

            return false;
        }

    }

?>
