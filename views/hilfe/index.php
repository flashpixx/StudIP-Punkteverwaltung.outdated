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
    require_once(dirname(dirname(__DIR__)) . "/sys/extensions/markdown/MarkdownExtra.inc.php");

    
    use \Michelf\Markdown;
    
    
    
    
    try {
        
        if (empty($hilfeindex))
            throw new Exception( _("Sie haben nicht die notwendigen Berechtigung für die Anzeige") );
        

        // Encoding muss in WINDOWS-1252 / ISO-8859-1 umgewandelt werdem, da das Default von Stud.IP ist,
        // das Default-Encoding des Markdown-Parsers ist UTF-8
        // @todo hier muss eine Callback Funktion gesetzt werden, um Links passend zu strukturieren
        echo "<span class=\"ppv hilfe\">\n";
        echo mb_convert_encoding( Markdown::defaultTransform( file_get_contents( $hilfeindex ) ), "ISO-8859-1", "auto" );
        echo "</span>";

        
    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }


?>