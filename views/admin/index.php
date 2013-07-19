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

    $loMatrikelNr = MatrikelNummerFactory::get();

    echo "UID : ".$userseminar->id."<br/>";
    echo "Matrikelnr : ".$loMatrikelNr->get($userseminar->id)."<br/>";
    echo "Dozentenrecht: ".(CoursePermission::hasDozentRecht() ? "ja" : "nein")."<br/>";
    echo "Tutorrecht: ".(CoursePermission::hasTutorRecht() ? "ja" : "nein")."<br/>";
    echo "Autorrecht: ".(CoursePermission::hasAutorRecht() ? "ja" : "nein")."<br/>";

?>
