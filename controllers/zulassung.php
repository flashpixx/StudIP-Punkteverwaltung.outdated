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

    
    /** Controller für die manuelle Zulassung eines Studenten **/
    class ZulassungController extends StudipController
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
            PageLayout::setTitle( sprintf("%s - Punkteverwaltung - Zulassung", $_SESSION["SessSemName"]["header_line"]) );
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

            // setze Variablen (URLs) für die entsprechende Ajax-Anbindung
            $this->listaction       = $this->url_for( "zulassung/jsonlist" );
            $this->updateaction     = $this->url_for( "zulassung/jsonupdate" );
        }


        /** Update Action **/
        function update_action()
        {
            try {

                if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                    $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Bonuspunkte der Veranstaltung zu verändern") );


            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

            $this->redirect("bonuspunkte");
        }


        /** liefert die korrekten Json Daten für den jTable **/
        function jsonlist_action()
        {
            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );



            // Daten holen und der View erzeugt dann das Json Objekt, wobei auf korrekte UTF8 Encoding geachtet werden muss
            try {

                // hole die Übung und prüfe die Berechtigung (in Abhängigkeit des gesetzen Parameter die Übung initialisieren)
                if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                    throw new Exception("Sie haben nicht die notwendige Berechtigung");

                $laData = $this->flash["veranstaltung"]->teilnehmer();
                if ($laData)
                {
                    // setze Defaultwerte für jTable
                    $laResult["TotalRecordCount"] = count($laData);

                    // sortiere Daten anhand des Kriteriums
                    usort($laData, function($a, $b) {
                          $ln = 0;

                          if ($a == $b)
                            return 0;

                          elseif (stripos(Request::quoted("jtSorting"), "matrikelnummer") !== false)
                            $ln = $a->matrikelnummer() - $b->matrikelnummer();

                          elseif (stripos(Request::quoted("jtSorting"), "name") !== false)
                            $ln = strcasecmp(studip_utf8encode($a->name()), studip_utf8encode($b->name()));

                          elseif (stripos(Request::quoted("jtSorting"), "email") !== false)
                            $ln = strcasecmp(studip_utf8encode($a->email()), studip_utf8encode($b->email()));

                          elseif (stripos(Request::quoted("jtSorting"), "bemerkung") !== false)
                            $ln = strcasecmp(studip_utf8encode($a->manuelleZulassung($this->flash["veranstaltung"])), studip_utf8encode($b->manuelleZulassung($this->flash["veranstaltung"])));


                          if (stripos(Request::quoted("jtSorting"), "asc") === false)
                            $ln = -1 * $ln;

                          return $ln;
                          });

                    // hole Query Parameter, um die Datenmenge passend auszuwählen
                    $laData = array_slice($laData, Request::int("jtStartIndex"), Request::int("jtPageSize"));


                    foreach( $laData as $item )
                    {
                        // siehe Arraykeys unter views/zulassung/list.php & alle String müssen UTF-8 codiert werden, da Json UTF-8 ist
                        $laItem = array(
                                        "Auth"            => studip_utf8encode( $item->id() ),
                                        "Matrikelnummer"  => $item->matrikelnummer(),
                                        "Name"            => studip_utf8encode( $item->name() ),
                                        "EmailAdresse"    => studip_utf8encode( $item->email() ),
                                        "Bemerkung"       => studip_utf8encode( $item->manuelleZulassung($this->flash["veranstaltung"]) )
                                        );


                        array_push( $laResult["Records"], $laItem );
                    }
                }

                // alles fehlerfrei durchlaufen, setze Result
                $laResult["Result"] = "OK";

                // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
        
            Tools::sendJson( $this, $laResult );
        }


        /** erzeugt das Update, für den jTable **/
        function jsonupdate_action()
        {
            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );

            try {

                // hole die Übung und prüfe die Rechte
                if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                    throw new Exception("Sie haben nicht die notwendige Berechtigung");

                // setze die Bemerkung und decodiert vorher die das von Json geforderte UTF-8
                $lo = new Student( studip_utf8decode(Request::quoted("Auth")) );
                $lo->manuelleZulassung( $this->flash["veranstaltung"], studip_utf8decode(Request::quoted("Bemerkung")) );
                
                
                // alles fehlerfrei durchlaufen, setze Result (lese die geänderten Daten aus der Datenbank)
                $laResult["Result"] = "OK";
                
                
                // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
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
