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



    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltungpermission.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/student.class.php");

    
    echo "info<br/>";
    echo "Dozentenrecht: ".(VeranstaltungPermission::hasDozentRecht() ? "ja" : "nein")."<br/>";
    echo "Tutorrecht: ".(VeranstaltungPermission::hasTutorRecht() ? "ja" : "nein")."<br/>";
    echo "Autorrecht: ".(VeranstaltungPermission::hasAutorRecht() ? "ja" : "nein")."<br/>";

    
    $x = new Student();
    var_dump($x);

?>