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
    require_once(dirname(__DIR__) . "/sys/auswertung.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/veranstaltung.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltungpermission.class.php");
    require_once(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . "/lib/classes/exportdocument/ExportPDF.class.php");


    /** Controller für die Auswertungen **/
    class AuswertungController extends StudipController
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

            try {

                // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
                // die aktuellen Daten bekommt
                $this->flash                  = Trails_Flash::instance();
                $this->flash["veranstaltung"] = Veranstaltung::get();

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }
        }


        /** Default Action **/
        function index_action()
        {
            PageLayout::addStyle("tr:nth-child(even) {background: #ccc} tr:nth-child(odd) {background: #eee}");
            PageLayout::addScript($this->plugin->getPluginUrl() . "/sys/extensions/d3.v3/d3.v3.min.js" );
            PageLayout::addScript($this->plugin->getPluginUrl() . "/sys/extensions/d3.v3/box.js" );

            $this->statistikaction  = $this->url_for( "auswertung/jsonstatistik");
        }


        /** erzeugt für die Veranstaltung die statistischen Daten,
         * die dann zur Visualisierung genutzt werden **/
        function jsonstatistik_action()
        {
            // mit nachfolgenden Zeilen wird der View angewiese nur ein Json Objekt zu liefern
            // das set_layout muss "null" als parameter bekommen, damit das Json Objekt korrekt angezeigt wird (ein "false" liefert einen PHP Error)
            $this->set_layout(null);
            $this->response->add_header("Content-Type", "application/json");

            // Result Array mit Daten
            $this->result = array( "uebungsnamen" => array(), "punkteliste" => array());

            try {
                
                if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                    throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


                $loAuswertung = new Auswertung( $this->flash["veranstaltung"] );
                $laListe      = $loAuswertung->studenttabelle();

                foreach ($laListe["uebungen"] as $uebung)
                    array_push($this->result["uebungsnamen"], $uebung["name"]);

                foreach ($laListe["uebungen"] as $uebung)
                {
                    $la = array();
                    foreach ($laListe["studenten"] as $student)
                        array_push($la, $uebung["studenten"][$student["id"]]["punktesumme"]);

                    array_push($this->result["punkteliste"], $la);
                }

            } catch (Exception $e) { }
        }


        /** erzeugt den PDF Export der Veranstaltung
         * @see http://docs.studip.de/develop/Entwickler/PDFExport
         * @see http://hilfe.studip.de/index.php/Basis/VerschiedenesFormat
         **/
        function pdfexport_action()
        {
            try {
                
                if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                    throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


                $loPDF        = new ExportPDF("L");
                $loPDF->setHeaderTitle($this->flash["veranstaltung"]->name() ." "._("im")." ". $this->flash["veranstaltung"]->semester()); // hier fehlt noch das Semester
                $loPDF->addPage();

                $loAuswertung = new Auswertung( $this->flash["veranstaltung"] );
                $laListe      = $loAuswertung->studenttabelle();

                // Sortierung hart nach Matrikelnummern
                uasort( $laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; } );

                // Tabelle mit Punkten erstellen
                $lcTabData = "|&#160;**"._("Name")."** |&#160;**"._("Matrikelnr")."** |&#160;**"._("Studiengang")."** ";
                foreach($laListe["uebungen"] as $uebung)
                    $lcTabData .= "|&#160;**".$uebung["name"]."  ("._("bestanden").")** ";
                $lcTabData .= "|&#160;**"._("bestanden")."** |&#160;**"._("Bonuspunkte")."** |\n";

                foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
                {
                    if ((Request::int("bestandenonly")) && (!$laStudent["veranstaltungenbestanden"]))
                        continue;

                    $lcLine = "|&#160;".$laStudent["name"]." |&#160;".$laStudent["matrikelnummer"]." |&#160;".$laStudent["studiengang"];

                    foreach($laListe["uebungen"] as $laUebung)
                        $lcLine .= " |&#160;".$laUebung["studenten"][$lcStudentKey]["punktesumme"]." (".($laUebung["studenten"][$lcStudentKey]["bestanden"] ? _("ja") : _("nein")).")";

                    $lcTabData .= $lcLine." |&#160;".($laStudent["veranstaltungenbestanden"] ? "ja" : "nein")." |&#160;".$laStudent["bonuspunkte"]." |\n";
                }
                
                $loPDF->addContent( $lcTabData );
                
                // beim PDF senden wir kein Layout
                $this->set_layout(null);
                $loPDF->dispatch("punkteliste");

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }
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
