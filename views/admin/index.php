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
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltungpermission.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltung/veranstaltung.class.php");


    Tools::showMessage($message);


    $loVeranstaltung = Veranstaltung::get();
    if (!$loVeranstaltung)
        echo "<a href=\"".$controller->url_for("admin/create")."\">"._("Für diese Veranstaltung die Punkteverwaltung aktivieren")."</a>";
    else {

        echo "<form method=\"post\" action=\"".$controller->url_for("" , "")."\">\n";
        CSRFProtection::tokenTag();

        echo "<div class=\"steel1\">\n";
        echo "<table width=\"100%\">\n";

        echo "<tr><td width=\"50%\"><label for=\"bestandenprozent\">"._("Prozentzahl über die Summe aller Punkte, damit die Veranstaltung als bestanden gilt")."</label></td>";
        echo "<td><input type=\"text\" id=\"bestandenprozent\" name=\"bestandenprozent\" value=\"\"/></td></tr>\n";

        echo "<tr><td><label for=\"allow_nichtbestanden\">"._("Anzahl an nicht bestandenen Übungen, um die Veranstaltung trotzdem bei erreichen der Punkte als bestanden zu werten")."</label></td>";
        echo "<td><input type=\"text\" id=\"allow_nichtbestanden\" name=\"bestandenprozent\" value=\"\"/></td></tr>\n";

        echo "<tr><td><label for=\"bemerkung\">"._("Bemerkung")."</label></td>";
        echo "<td><textarea id=\"bemerkung\" name=\"bemerkung\"></textarea></td></tr>\n";

        echo "</table>\n";
        echo "</div>\n";
        echo "<p><input type=\"submit\" value=\""._("Angaben übernehmen")."\"/></p>";
        echo "</form>";
    }

?>
