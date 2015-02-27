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
    require_once(dirname(__DIR__) . "/sys/authentification.class.php");


    /** Controller für die Bonuspunkte eines Studenten **/
    class BonuspunkteController extends StudipController
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
         * übergeben werden können
         **/
        function before_filter( &$action, &$args )
        {
            PageLayout::setTitle( sprintf(_("%s - Punkteverwaltung - Bonuspunkte"), $_SESSION["SessSemName"]["header_line"]) );
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
            
            $this->listaction       = $this->url_for( "bonuspunkte/jsonlist");
            $this->deleteaction     = $this->url_for( "bonuspunkte/jsondelete");
            $this->updateaction     = $this->url_for( "bonuspunkte/jsonupdate");
        }
        
        
        function jsonlist_action()
        {
            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );
            
            
            try {
                
                // hole die Übung und prüfe die Berechtigung (in Abhängigkeit des gesetzen Parameter die Übung initialisieren)
                if (!Authentification::hasDozentRecht( $this->flash["veranstaltung"] ))
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));
                
                $la = array();
                foreach($this->flash["veranstaltung"]->bonuspunkte()->liste() as $key => $val)
                    array_push( $la, array("Prozent" => floatval($key), "Punkte" => floatval($val) ) );
                
                // alles fehlerfrei durchlaufen, setze Result
                $laResult["TotalRecordCount"] = count($la);
                $laResult["Records"]          = $la;
                $laResult["Result"]           = "OK";
                
                // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
            
            Tools::sendJson( $this, $laResult );
        }
        


        /** Update Action
        function update_action()
        {
            try {

                if (!Authentification::hasDozentRecht($this->flash["veranstaltung"]))
                    $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Bonuspunkte der Veranstaltung zu verändern") );

                $loBonusPunkte = $this->flash["veranstaltung"]->bonuspunkte();

                $newitem = array("prozent" => Request::float("prozentnew"), "punkte" => Request::float("punktenew"));
                if ( (!empty($newitem["prozent"])) && (!empty($newitem["punkte"])) )
                    $loBonusPunkte->set($newitem["prozent"], $newitem["punkte"]);

                for($i=0; $i < Request::int("count"); $i++)
                {
                    if (Request::int("del".$i))
                        $loBonusPunkte->remove( Request::float("prozent".$i) );
                    else
                        $loBonusPunkte->set( Request::float("prozent".$i), Request::float("punkte".$i) );
                }


            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }
        
            $this->redirect("bonuspunkte");
        }
        */


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
