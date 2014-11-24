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
            echo "<p><a href=\"".$controller->url_for("admin/create")."\">"._("Für diese Veranstaltung die Punkteverwaltung aktivieren")."</a></p>";
        else {

            echo "<form method=\"post\" action=\"".$controller->url_for("admin/updatesettings")."\">\n";
            CSRFProtection::tokenTag();

            echo "<div class=\"steel1\">\n";

            echo "<p>"._("Mit diesen Einstellungen können globale Optionen gesetzt werden, um den Übungsbetrieb zu verwalten. Hierzu zählt einmal die Angabe in Prozent (über die Summe aller Punkte aller Übungen), die für das Bestehen der Veranstaltung notwendig sind. Mit der zweiten Option kann eine Anzahl an Übungen festgelegt werden, die unterhalb der individuell für jede Übung definierten Bestehensgrenze liegen dürfen, um noch die Veranstaltung bestehen zu können (z.B. wenn eine Übung ausgelassen / nicht abgegeben werden darf, wäre der Wert 1 einzutragen). Die Bemerkung ist optional und kann nur von Benutzern mit Dozenten oder Tutorrechten gesehen werden.")."</p><p>"._("Die Veranstaltung muss abgeschlossen werden, um vollständige Listen generieren zu können. Durch das Abschließen werden die, von den Studenten ausgewählten Studiengänge, übernommen, damit erscheint dieser Studiengang in der Auswertung für diese Veranstaltung. Sollte kein expliziter Studiengang ausgewählt worden sein, wird der ein verfügbarer Studiengang verwendet.")."</p><hr width=\"100%\"/>";

            echo "<p><table width=\"100%\">\n";

            echo "<tr><td width=\"50%\"><label for=\"bestandenprozent\">"._("Prozentzahl über die Summe aller Punkte, damit die Veranstaltung als bestanden gilt")."</label></td>";
            echo "<td><input type=\"text\" id=\"bestandenprozent\" name=\"bestandenprozent\" value=\"".$loVeranstaltung->bestandenProzent()."\" size=\"35\"/></td></tr>\n";

            echo "<tr><td><label for=\"allow_nichtbestanden\">"._("Anzahl an nicht bestandenen Übungen, um die Veranstaltung trotzdem bei erreichen der Punkte als bestanden zu werten")."</label></td>";
            echo "<td><input type=\"text\" id=\"allow_nichtbestanden\" name=\"allow_nichtbestanden\" value=\"".$loVeranstaltung->allowNichtBestanden()."\" size=\"35\"/></td></tr>\n";

            echo "<tr><td><label for=\"bemerkung\">"._("Bemerkung")."</label></td>";
            echo "<td><textarea id=\"bemerkung\" name=\"bemerkung\" cols=\"37\" rows=\"5\">".$loVeranstaltung->bemerkung()."</textarea></td></tr>\n";

            if (!$loVeranstaltung->isClosed())
            {
                echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
                echo "<tr><td colspan=\"2\"><a href=\"".$controller->url_for("admin/close")."\">Veranstaltung schliessen</a></td></tr>\n";

                echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
                echo "<tr><td colspan=\"2\"><a href=\"".$controller->url_for("admin/delete")."\">alle Einstellungen und Daten zur Punkteverwaltung dieser Veranstaltung entfernen</a></td></tr>\n";

                
            }


            echo "</table>\n";
            echo "</div>\n";
            if ($loVeranstaltung->isClosed())
            {
                echo "<p><strong>Die Veranstaltung wurde am ".$loVeranstaltung->closedDateTime()." geschlossen</strong>";
                if (VeranstaltungPermission::hasAdminRecht())
                    echo " (<a href=\"".$controller->url_for("admin/reopen")."\">Veranstaltung wieder öffnen</a>)";
                echo "</p>";
            } else
                echo "<p><input type=\"submit\" name=\"submitted\" value=\""._("Angaben übernehmen")."\"/></p>";

            echo "</form></p><br/><br/>";
            echo "<p style=\"font-size: xx-small; text-align: center; background:#eee;\"\"><a target=\"_blank\" href=\"https://github.com/flashpixx/StudIP-Punkteverwaltung\">https://github.com/flashpixx/StudIP-Punkteverwaltung</a>";
            echo " - Plugin Version ".$flash["pluginmeta"];
            echo "</p>";
        }
        
    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }

?>
