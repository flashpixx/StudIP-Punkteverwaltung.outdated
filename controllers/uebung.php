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
    require_once(dirname(__DIR__) . "/sys/veranstaltung/uebung.class.php");


    /** Controller für die Administration der Übungen **/
    class UebungController extends StudipController
    {

        /** Ctor, um aus dem Dispatcher die Referenz auf das Pluginobjekt 
         * zu bekommen
         * @param $poDispatch
         **/
        function __construct( $poDispatch )
        {
            parent::__construct($poDispatch);
            $this->plugin = $poDispatch->plugin;
        }


        /** Before-Aufruf zum setzen von Defaultvariablen
         * @warn da der StudIPController keine Session initialisiert, muss die
         * Eigenschaft "flash" händisch initialisiert werden, damit persistent die Werte
         * übergeben werden können
         **/
        function before_filter( &$action, &$args )
        {
            // PageLayout::setTitle("");
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
            // die aktuellen Daten bekommt
            $this->flash                  = Trails_Flash::instance();
            $this->flash["veranstaltung"] = Veranstaltung::get();
        }


        /** Default Action **/
        function index_action()
        {
            // setze URLs für jTable 
            PageLayout::addStylesheet( $this->plugin->getPluginUrl() . "/sys/extensions/jtable/themes/lightcolor/blue/jtable.min.css" );
            PageLayout::addScript(     $this->plugin->getPluginUrl() . "/sys/extensions/jtable/jquery.jtable.min.js" );
            PageLayout::addScript(     $this->plugin->getPluginUrl() . "/sys/extensions/jtable/localization/jquery.jtable.de.js" );

            // setze Variablen (URLs) für die entsprechende Ajax-Anbindung
            $this->listaction   = $this->url_for( "uebung/list",   array("ueid" => Request::quoted("ueid")) );
            $this->updateaction = $this->url_for( "uebung/update", array("ueid" => Request::quoted("ueid")) );
        }


        /** liefert die korrekten Json Daten für den jTable **/
        function list_action()
        {
            // mit nachfolgenden Zeilen wird der View angewiese nur ein Json Objekt zu liefern
            // das set_layout muss "null" als parameter bekommen, damit das Json Objekt korrekt angezeigt wird (ein "false" liefert einen PHP Error)
            $this->set_layout(null);
            $this->response->add_header("Content-Type", "application/json");


            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $this->tabelle = array( "Result"  => "ERROR", "Records" => array() );

            // Daten holen und der View erzeugt dann das Json Objekt
            try {
                
                $lo = Uebung(Request::quoted("cid"), Request::quoted("ueid"));
                if ($lo)
                {
                    foreach( $lo->studentenuebung() as $item )
                    {
                        //array_push( )$this->tabelle["Records"], );
                    }
                    $this->tabelle["Result"] = "OK";
                }

            } catch (Exception $e) { }
        }

        
        function update_action()
        {
            
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
