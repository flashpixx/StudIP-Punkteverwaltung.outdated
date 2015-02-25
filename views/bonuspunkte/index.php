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
    require_once(dirname(dirname(__DIR__)) . "/sys/authentification.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltung/veranstaltung.class.php");


    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!Authentification::hasDozentRecht($loVeranstaltung))
            throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));

        echo "<form method=\"post\" action=\"".$controller->url_for("bonuspunkte/update")."\">\n";
        CSRFProtection::tokenTag();

        echo "<div class=\"steel1\">\n";

        echo "<p>"._("Die Bonuspunkte werden auf der Basis der Gesamtpunkte (Summe aller erreichten Punkte) berechnet und gesondert in der Auswertung ausgeführt. Damit können z.B. Bonuspunkte für eine Klausur automatisch generiert werden")."</p><hr width=\"100%\"/>";

        echo "<table width=\"100%\">\n";
        echo "<tr><th>"._("löschen")."</th><th>"._("Prozentzahl, ab der die Bonuspunkte vergeben werden")."</th><th>"._("Punkte")."</th></tr>\n";
        

        $i=0;
        foreach($loVeranstaltung->bonuspunkte()->liste() as $key => $val)
        {
            echo "<tr>";
            echo "<td><input type=\"checkbox\" value=\"1\" name=\"del".$i."\" /></td>";
            echo "<td><input type=\"text\" value=\"".$key."\" name=\"prozent".$i."\" /></td>";
            echo "<td><input type=\"text\" value=\"".$val."\" name=\"punkte".$i."\" /></td>";
            echo "</tr>";

            $i++;
        }

        if ($i > 0)
            echo "<tr><td colspan=\"3\">&nbsp;</td></tr>";
        echo "<tr>";
        echo "<td><label for=\"prozentnew\">"._("neuer Datensatz")."</label></td>";
        echo "<td><input type=\"text\" name=\"prozentnew\" /></td>";
        echo "<td><input type=\"text\" name=\"punktenew\" /></td>";
        echo "</tr>";

        echo "</table>\n";
        echo "</div>\n";

        if (!$loVeranstaltung->isClosed())
            echo "<p><input type=\"hidden\" value=\"".$i."\" name=\"count\"/><input type=\"submit\" name=\"submitted\" value=\""._("Angaben übernehmen")."\"/></p>";

        echo "</form>";



    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }
    
    ?>