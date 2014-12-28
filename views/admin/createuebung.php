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



    echo "<form method=\"post\" action=\"".$controller->url_for("admin/adduebung")."\">\n";
    CSRFProtection::tokenTag();

    echo "<div class=\"steel1\">\n";

    echo "<label for=\"uebungname\">"._("Bitte geben Sie einen Namen für diese Übung ein (der Name darf nicht mehrfach verwendet werden)")."</label> ";
    echo "<input type=\"text\" id=\"uebungname\" name=\"uebungname\" size=\"35\"/>\n";

    echo "</div>\n";
    echo "<p><input type=\"submit\" name=\"submitted\" value=\""._("Angaben übernehmen")."\"/></p>";
    echo "</form>";
    
?>
