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



    require_once(dirname(__DIR__) . "/sys/tools.class.php");
    require_once(dirname(__DIR__) . "/sys/student.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltungpermission.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/veranstaltung.class.php");
    require_once(dirname(__DIR__) . "/sys/extensions/markdown/MarkdownExtra.inc.php");
    
    
    use \Michelf\Markdown;
    


    /** Controller für die Sicht eines Studenten **/
    class HilfeController extends StudipController
    {

        /** Ctor, um aus dem Dispatcher die Referenz auf das Pluginobjekt
         * zu bekommen
         * @param $poDispatch
         **/
        function __construct( $poDispatch )
        {
            parent::__construct($poDispatch);
            $this->plugin   = $poDispatch->plugin;
        }
    
    
        /** Before-Aufruf zum setzen von Defaultvariablen
         * @warn da der StudIPController keine Session initialisiert, muss die
         * Eigenschaft "flash" händisch initialisiert werden, damit persistent die Werte
         * übergeben werden kˆnnen
         **/
        function before_filter( &$action, &$args )
        {
            PageLayout::setTitle(_($_SESSION["SessSemName"]["header_line"]. " - Punkteverwaltung - Hilfe"));
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
            // die aktuellen Daten bekommt
            $this->flash                  = Trails_Flash::instance();
            $this->flash["veranstaltung"] = Veranstaltung::get();
            
            
            // Hilfe Basis Informationen setzen
            $basepath         = $this->plugin->getPluginPath() . "/assets/hilfe";
            $this->hilfe      = null;
            
            
            // Hilfedatei ermitteln
            $lcMarkdownfile     = null;
            if (VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                $lcMarkdownfile = $basepath . "/dozent/index.md";
            elseif (VeranstaltungPermission::hasTutorRecht($this->flash["veranstaltung"]))
                $lcMarkdownfile = $basepath . "/tutor/index.md";
            
            // @todo hier muss der Pfad zum Bildordner gesetzt werden und geprüft werden, ob die Datei
            // die als Parameter übergeben wird, vorhanden ist, wenn mšglich sollten alle bestätigten Funktionen
            // via Flash-Variable übergeben werden
            
            
            // Markdownfile lesen und rendern
            if ( (!empty($lcMarkdownfile)) && (file_exists($lcMarkdownfile)) )
                $this->hilfe = Markdown::defaultTransform( file_get_contents( $lcMarkdownfile ) );
        }


        /** Default Action **/
        function index_action()
        {
            Tools::addHTMLHeaderElements( $this->plugin );
        }

    

        /** URL Aufruf **/
        function url_for($to)
        {
            $args = func_get_args();

            # find params
            $params = array();
            if (is_array(end($args)))
                $params = array_pop($args);

            # urlencode all but the first argument
            $args    = array_map("urlencode", $args);
            $args[0] = $to;
            
            return PluginEngine::getURL($this->dispatcher->plugin, $params, join("/", $args));
        }
        
    }
