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



    require_once(dirname(__DIR__) . "/sys/veranstaltung/veranstaltung.class.php");
    require_once(dirname(__DIR__) . "/sys/auswertung.class.php");
    require_once(dirname(__DIR__) . "/sys/student.class.php");
    require_once(dirname(__DIR__) . "/sys/tools.class.php");
    

    /** Controller für die Sicht eines Studenten **/
    class ShowController extends StudipController
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
            PageLayout::setTitle(_($_SESSION["SessSemName"]["header_line"]. " - Punkteverwaltung - Anzeige"));
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
            // die aktuellen Daten bekommt
            $this->flash                  = Trails_Flash::instance();
            $this->flash["veranstaltung"] = Veranstaltung::get();
        }


        /** Default Action **/
        function index_action()
        {
            Tools::addHTMLHeaderElements( $this->plugin );
            
            $this->listaction       = $this->url_for( "show/jsonlist");
        
            //PageLayout::addStyle("tr:nth-child(even) {background: #ccc} tr:nth-child(odd) {background: #eee}");
        }

        /** setzt den Studiengang des Users **/
        function studiengang_action()
        {

            try {
                $laData    = explode("#", Request::quoted("studiengang"));

                if (count($laData) == 2)
                {
                    $loStudent = new Student( $GLOBALS["user"]->id );
                    $loStudent->studiengang($this->flash["veranstaltung"], trim($laData[0]), trim($laData[1]));

                    $this->flash["message"] = Tools::createMessage( "success", _("Anerkennung für den Studiengang für diese Veranstaltung geändert") );
                }
                

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

            $this->redirect("show");
        }
        
        
        /** Action, um die Json-Daten fŸr die jTable zu erzeugen **/
        function jsonlist_action()
        {
            // Daten fŸr das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );
            
            try {
                
                $loStudent    = new Student($GLOBALS["user"]->id);
                
                $loAuswertung = new Auswertung( $this->flash["veranstaltung"] );
                $laAuswertung = $loAuswertung->studentdaten( $loStudent );
                
                
                $la = array();
                foreach( $laAuswertung["uebungen"] as $laUebung )
                    array_push($la, array(
                        "Uebung"        => studip_utf8encode( $laUebung["name"] ),
                        "Punkte "       => $laUebung["studenten"][$loStudent->id()]["punktesumme"],
                        "PunkteProzent" => $laUebung["studenten"][$loStudent->id()]["erreichteprozent"],
                        "Bewertung"     => null
                    ));

                // alles fehlerfrei durchlaufen, setze Result
                $laResult["Records"] = $la;
                $laResult["Result"]  = "OK";
                
                
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
            
            Tools::sendJson( $this, $laResult );
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
