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



    require_once(dirname(dirname(__DIR__)) . "/sys/tools.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltung/veranstaltung.class.php");


    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!$loVeranstaltung)
            throw new Exception(_("keine Veranstaltung gefunden"));


        echo "<ul>";

        $loStudent = null;
        foreach( $loVeranstaltung->uebungen() as $loUebung )
            foreach ( $loUebung->studentenuebung( false, $GLOBALS["user"]->id ) as $item )
            {
                if (!$loStudent)
                    $loStudent = $item->student();

                echo "<li><strong>".$item->uebung()->name().": </strong> ".($item->erreichtePunkte()+$item->zusatzPunkte())." "._("Punkt(e)")."</li>";
            }

        if ($loStudent)
            echo "<li><strong>Anerkennung für den Studiengang:</strong></li>";

        echo "</ul>";
        

    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }

?>