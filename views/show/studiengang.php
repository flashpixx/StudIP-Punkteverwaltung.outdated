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
    
    
    
    Tools::showMessage($flash["message"]);
    
    try {
        
        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!$loVeranstaltung)
            throw new Exception(_("keine Veranstaltung gefunden"));
        
        $laStudiengaenge = isset($flash["studiengang"]) ? $flash["studiengang"] : null;
        if (empty($laStudiengaenge))
            throw new Exception(_("keine Studiengänge für den User eingetragen"));
        
        
        if (count($laStudiengaenge) == 1)
            echo _("Diese Veranstaltung wird für den Studiengang [%s] anerkannt", $laStudiengaenge[0]["abschluss"]." ".$laStudiengaenge[0]["fach"]);
        else {
            
            echo "<form method=\"post\" action=\"".$controller->url_for("show/studiengangset")."\">\n";
            CSRFProtection::tokenTag();
         
            echo "<select name=\"studiengang\" size=\"1\">";
            foreach ($laStudiengaenge as $item)
                if ( ($item["abschluss_id"]) && ($item["fach_id"]) ) {
                    $lcSelect = null;
                    if ( (!empty($laStudiengang)) && ($laStudiengang["abschluss_id"] == $item["abschluss_id"]) && ($laStudiengang["fach_id"] == $item["fach_id"]) )
                        $lcSelect = "selected=\"selected\"";
         
                    echo "<option value=\"".$item["abschluss_id"]."#".$item["fach_id"]."\" ".$lcSelect.">".trim($item["abschluss"]." ".$item["fach"])."</option>";
                }
            echo "</select>";
         
            echo "<input type=\"submit\" name=\"submitted\" value=\""._("Â¸bernehmen")."\"/>";
            echo "</form>";
         
         }
        
        
    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }
    
?>
