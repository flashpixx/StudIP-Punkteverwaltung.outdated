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
    require_once(dirname(__DIR__) . "/sys/veranstaltung/studentuebung.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltungpermission.class.php");


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
            $this->listaction   = $this->url_for( "uebung/jsonlist",   array("ueid" => Request::quoted("ueid")) );
            $this->updateaction = $this->url_for( "uebung/jsonupdate", array("ueid" => Request::quoted("ueid")) );

            // setze Metainfos
            $lo               = new Uebung(Request::quoted("cid"), Request::quoted("ueid"));
            $this->uebungname = $lo->name();
        }


        /** liefert die korrekten Json Daten für den jTable **/
        function jsonlist_action()
        {
            // mit nachfolgenden Zeilen wird der View angewiese nur ein Json Objekt zu liefern
            // das set_layout muss "null" als parameter bekommen, damit das Json Objekt korrekt angezeigt wird (ein "false" liefert einen PHP Error)
            $this->set_layout(null);
            $this->response->add_header("Content-Type", "application/json");

            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $this->result = array( "Result"  => "ERROR", "Records" => array() );



            // Daten holen und der View erzeugt dann das Json Objekt, wobei auf korrekte UTF8 Encoding geachtet werden muss
            try {

                // hole die Übung und prüfe die Berechtigung
                $lo = new Uebung(Request::quoted("cid"), Request::quoted("ueid"));
                if ( (!VeranstaltungPermission::hasTutorRecht( $lo->veranstaltung() )) && (!VeranstaltungPermission::hasDozentRecht( $lo->veranstaltung() )) )
                    throw new Exception("Sie haben nicht die notwendige Berechtigung");

                if ($lo)
                {
                    $laData = $lo->studentenuebung();
                    if ($laData)
                    {
                        // setze Defaultwerte für jTable
                        $this->result["TotalRecordCount"] = count($laData);

                        // sortiere Daten anhand des Kriteriums
                        usort($laData, function($a, $b) {
                              $ln = 0;

                              if ($a == $b)
                                return 0;

                              elseif (stripos(Request::quoted("jtSorting"), "matrikelnummer") !== false)
                                $ln = $a->student()->matrikelnummer() - $b->student()->matrikelnummer();

                              elseif (stripos(Request::quoted("jtSorting"), "name") !== false)
                                $ln = strcasecmp(studip_utf8encode($a->student()->name()), studip_utf8encode($b->student()->name()));

                              elseif (stripos(Request::quoted("jtSorting"), "email") !== false)
                                $ln = strcasecmp(studip_utf8encode($a->student()->email()), studip_utf8encode($b->student()->email()));

                              elseif (stripos(Request::quoted("jtSorting"), "bemerkung") !== false)
                                $ln = strcasecmp(studip_utf8encode($a->bemerkung()), studip_utf8encode($b->bemerkung()));

                              elseif (stripos(Request::quoted("jtSorting"), "erreichtepunkte") !== false)
                                $ln = strcasecmp($a->erreichtePunkte(), $b->erreichtePunkte());

                              elseif (stripos(Request::quoted("jtSorting"), "zusatzpunkte") !== false)
                                $ln = strcasecmp($a->zusatzPunkte(), $b->zusatzPunkte());

                              
                              if (stripos(Request::quoted("jtSorting"), "asc") === false)
                                $ln = -1 * $ln;

                              return $ln;
                        });

                        // hole Query Parameter, um die Datenmenge passend auszuwählen
                        $laData = array_slice($laData, Request::int("jtStartIndex"), Request::int("jtPageSize"));
                        

                        foreach( $laData as $item )
                            // siehe Arraykeys unter views/uebung/list.php & alle String müssen UTF-8 codiert werden, da Json UTF-8 ist
                            array_push( $this->result["Records"],
                                array(
                                      "Auth"            => studip_utf8encode( $item->student()->id() ),
                                      "Matrikelnummer"  => $item->student()->matrikelnummer(),
                                      "Name"            => studip_utf8encode( $item->student()->name() ),
                                      "EmailAdresse"    => studip_utf8encode( $item->student()->email() ),
                                      "ErreichtePunkte" => $item->erreichtePunkte(),
                                      "ZusatzPunkte"    => $item->zusatzPunkte(),
                                      "Bemerkung"       => studip_utf8encode( $item->bemerkung() )
                                )
                            );
                    }
                }

                // alles fehlerfrei durchlaufen, setze Result
                $this->result["Result"] = "OK";

            // fange Exception und liefer Exceptiontext passend codiert in das Json-Result 
            } catch (Exception $e) { $this->result["Message"] = studip_utf8encode( $e->getMessage() ); }
        }


        /** erzeugt das Update, für den jTable **/
        function jsonupdate_action()
        {
            // mit nachfolgenden Zeilen wird der View angewiese nur ein Json Objekt zu liefern
            // das set_layout muss "null" als parameter bekommen, damit das Json Objekt korrekt angezeigt wird (ein "false" liefert einen PHP Error)
            $this->set_layout(null);
            $this->response->add_header("Content-Type", "application/json");

            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $this->result = array( "Result"  => "ERROR", "Records" => array() );

            try {

                // hole die Übung und prüfe die Rechte
                $loUebung = new Uebung(Request::quoted("cid"), Request::quoted("ueid"));
                if ( (!VeranstaltungPermission::hasTutorRecht( $loUebung->veranstaltung() )) && (!VeranstaltungPermission::hasDozentRecht( $loUebung->veranstaltung() )) )
                    throw new Exception("Sie haben nicht die notwendige Berechtigung");

                // hole die Zuordnung von Übung und Student und setze die Daten
                $lo = new StudentUebung( $loUebung, Request::quoted("Auth") );
                $lo->update( Request::float("ErreichtePunkte"), Request::float("ZusatzPunkte"), Request::quoted("Bemerkung") );
               

                // alles fehlerfrei durchlaufen, setze Result (lese die geänderten Daten aus der Datenbank)
                $this->result["Result"] = "OK";


            // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
            } catch (Exception $e) { $this->result["Message"] = studip_utf8encode( $e->getMessage() ); }
            
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
