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
        
        /** Property, das den Teilpfad enthält, in dem die Hilfsdokumente zu finden sind **/
        private static $documentpath = "/assets/hilfe";
        
        /** Name des URL Parameters, über das Dokumentname übergeben werden **/
        private static $urlparameter = "doc";
        

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
         * übergeben werden können
         **/
        function before_filter( &$action, &$args )
        {
            PageLayout::setTitle( sprintf("%s - Punkteverwaltung - Hilfe", $_SESSION["SessSemName"]["header_line"]) );
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
            // die aktuellen Daten bekommt
            $this->flash                  = Trails_Flash::instance();
            $this->flash["veranstaltung"] = Veranstaltung::get();
            
            
            // Hilfe Basis Informationen setzen
            // @todo für Multilanguage Support einfach ein entsprechendes Verzeichnis für die Sprache hinzufügen
            $this->hilfe      = null;

            $this->basepath         = self::$documentpath;
            if (VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                $this->basepath    .= "/dozent/";
            elseif (VeranstaltungPermission::hasTutorRecht($this->flash["veranstaltung"]))
                $this->basepath    .= "/tutor/";
            elseif (VeranstaltungPermission::hasAutorRecht($this->flash["veranstaltung"]))
                $this->basepath    .= "/autor/";

            
                
            // Dokumentnamen ermitteln (mit passendem Encoding, so dass Dateinamen nur ASCII Buchstaben enthalten dürfen)
            $lcFilename     = strtolower(Request::quoted(self::$urlparameter));
            $lcMarkdownfile = $this->plugin->getPluginPath() . $this->basepath . (empty($lcFilename ) ? "index" : iconv(mb_detect_encoding($lcFilename), "ASCII//IGNORE", $lcFilename)) . ".md";

            
            
            // Markdownfile lesen und rendern
            if ( (!empty($lcMarkdownfile)) && (file_exists($lcMarkdownfile)) )
            {
                $loMarkdown  = new Markdown();
                
                // Link Funktion definieren
                $loMarkdown->url_filter_func= function( $lcLink ) {
                    
                    // sofern eine externe URL angegeben wurde, direkt liefern
                    if (filter_var($lcLink, FILTER_VALIDATE_URL))
                        return $lcLink;
                    
                    // falls es kein externer Link ist, kann es nur noch ein interner Link oder ein Bild sein (Dateiname in ASCII umcodieren)
                    // wobei das Bild entweder durch die Dateiendung oder den Pfad "img/" erkennat wird
                    if (Tools::foundCISubStr($lcLink, array("img/")))
                        return $this->plugin->getPluginURL() . $this->basepath . iconv(mb_detect_encoding($lcLink), "ASCII//IGNORE", strtolower($lcLink));
                    if (Tools::foundCISubStr($lcLink, array(".png", ".jpg", ".jpeg", ".svg")))
                        return $this->plugin->getPluginURL() . $this->basepath . "img/" . iconv(mb_detect_encoding($lcLink), "ASCII//IGNORE", strtolower($lcLink));
                    
                    // wenn in dem Link ein # enthalten ist, dann wird ein Tag im Dokument angesprungen
                    if (Tools::foundCISubStr($lcLink, array("#")))
                    {
                        $la     = explode("#");
                        $lcLink = array_shift($la);
                        return $this->url_for("hilfe", array(self::$urlparameter => $lcLink)) . "#" . implode("", $la);
                    }
                        
                    // alle anderen Dokumente werden als Markdown Dokumente verlinkt
                    return $this->url_for("hilfe", array(self::$urlparameter => $lcLink));
                };
                
                // Markdown rendern
                $this->hilfe = $loMarkdown->transform( file_get_contents( $lcMarkdownfile ) );
            }
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
