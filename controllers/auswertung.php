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
    require_once(dirname(__DIR__) . "/sys/extensions/xlsxwriter.class.php");
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
            // CSS Styles für den Boxplot & Datentabelle
            PageLayout::addStyle("tr:nth-child(even) {background: #ccc} tr:nth-child(odd) {background: #eee}");

            PageLayout::addStyle(".box { font: 10px sans-serif; }");
            PageLayout::addStyle(".box line, .box rect, .box circle { fill: #fff; stroke: #000; stroke-width: 1.5px; }");
            PageLayout::addStyle(".box .center { stroke-dasharray: 3,3; }");
            PageLayout::addStyle(".box .outlier { fill: none; stroke: #ccc; }");

            
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
                $laListe      = $loAuswertung->studententabelle();

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
         * @see https://github.com/mk-j/PHP_XLSXWriter
         * @bug nicht mehr aktuell - Export muss um Excel ergänz werden -
         **/
        function export_action()
        {
            try {
                
                if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                    throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


                // erzeuge Datenarray mit harter Sortierung nach Matrikelnummer,
                // Items die leer (empty) sind, erscheinen nicht in der Ausgabe
                $loAuswertung = new Auswertung( $this->flash["veranstaltung"] );
                $laListe      = $loAuswertung->studententabelle();
                uasort( $laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; } );

                $laOutput       = array();
                switch (strtolower(Request::quoted("target")))
                {

                    // Vollexport aller Daten
                    case "full" :
                        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
                        {
                            $laItem = array(
                                "name"           => $laStudent["name"],
                                "matrikelnummer" => $laStudent["matrikelnummer"],
                                "studiengang"    => $laStudent["studiengang"],
                                "bestanden"      => $laStudent["veranstaltungenbestanden"],
                                "bonuspunkte"    => $laStudent["bonuspunkte"],
                                "uebung"         => array()
                            );
                            foreach($laListe["uebungen"] as $laUebung)
                                $laItem["uebung"][$uebung["name"]] = array(
                                    "punktesumme" => $laUebung["studenten"][$lcStudentKey]["punktesumme"],
                                    "bestanden"   => $laUebung["studenten"][$lcStudentKey]["bestanden"]
                                );
                            array_push( $laOutput,  $laItem );
                        }
                        break;


                    // reduzierter Export für den Aushang, d.h. nur Matrikelnummer, Bonuspunkte & bestanden / nicht bestanden
                    case "aushang" :
                        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
                            array_push($laOutput, array(
                                "matrikelnummer" => $laStudent["matrikelnummer"],
                                "bestanden"      => $laStudent["veranstaltungenbestanden"],
                                "bonuspunkte"    => $laStudent["bonuspunkte"],
                            ));

                        break;


                    // kurze Liste aller Studenten (Matrikelnummer, Name und Studiengang), die die Veranstaltung bestanden
                    // haben für den Import in HIS
                    case "bestandenshort" :
                        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
                            if ($laStudent["veranstaltungenbestanden"])
                                array_push($laOutput, array(
                                    "name"           => $laStudent["name"],
                                    "matrikelnummer" => $laStudent["matrikelnummer"],
                                    "studiengang"    => $laStudent["studiengang"],
                                ));
                        break;


                    // volle Liste der Studenten, die die Veranstaltung bestanden haben
                    case "bestanden" :
                        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
                            if ($laStudent["veranstaltungenbestanden"])
                            {
                                $laItem = array(
                                    "name"           => $laStudent["name"],
                                    "matrikelnummer" => $laStudent["matrikelnummer"],
                                    "studiengang"    => $laStudent["studiengang"],
                                    "bonuspunkte"    => $laStudent["bonuspunkte"],
                                    "uebung"         => array()
                                );
                                foreach($laListe["uebungen"] as $laUebung)
                                    $laItem["uebung"][$uebung["name"]] = array(
                                        "punktesumme" => $laUebung["studenten"][$lcStudentKey]["punktesumme"]
                                    );
                                array_push( $laOutput,  $laItem );
                            }
                        break;


                    default :
                        throw new Exception(_("Exportart unbekannt"));
                }


                // erzeuge Ausgabeformat, das Senden inkl. Headerinformationen geschieht durch den View
                $this->set_layout(null);
                $lcTitle = $this->flash["veranstaltung"]->name() ." "._("im")." ". $this->flash["veranstaltung"]->semester();
                switch (strtolower(Request::quoted("type")))
                {
                    case "pdf" : $this->exportPDF( $laOutput, $lcTitle ); break;

                    //case "xlsx"

                    //case "csv"

                    default :
                        throw new Exception(_("Exportparameter unbekannt"));
                }


            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

/*

                $loPDF        = (Request::int("extern")) ? new ExportPDF() : new ExportPDF("L");
                $loPDF->setHeaderTitle($this->flash["veranstaltung"]->name() ." "._("im")." ". $this->flash["veranstaltung"]->semester());
                $loPDF->addPage();

                $loAuswertung = new Auswertung( $this->flash["veranstaltung"] );
                $laListe      = $loAuswertung->studententabelle();

                // Sortierung hart nach Matrikelnummern
                uasort( $laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; } );

                // Tabelle mit Punkten erstellen (entweder für Aushang, dann nur mit Matrikelnummer, bestanden, Bonuspunkte oder intern, dann mit Name etc.
                if (Request::int("extern"))
                {
                    $lcTabData = "|&#160;**"._("Matrikelnr")."** |&#160;**"._("bestanden")."** |&#160;**"._("Bonuspunkte")."** |\n";
                    foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
                        $lcTabData .= "|&#160;".$laStudent["matrikelnummer"]." |&#160;".($laStudent["veranstaltungenbestanden"] ? _("ja") : _("nein"))." |&#160;".$laStudent["bonuspunkte"]." |\n";

                } else {
                    
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
                }

                // Tabelle erzeugen
                $loPDF->addContent( $lcTabData );
                
                // beim PDF senden wir kein Layout
                $this->set_layout(null);
                $loPDF->dispatch("punkteliste");

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }
 */
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


        /** Exportfunktion für PDF
         * @param $paOutput Datenarray
         * @param $pcTitle String mit Titel der Veranstaltung
         **/
        private function exportPDF( $paOutput, $pcTitle )
        {
            // für den Ausgang wird Hochformat, sonst Querformat verwendet
            $loPDF = strtolower(Request::quoted("target")) == "aushang" ? new ExportPDF() : new ExportPDF("L");
            $loPDF->setHeaderTitle($pcTitle);
            $loPDF->addPage();

            $lcData= "";
            foreach( $paOutput as $laLine )
            {
                // für den ersten Eintrag den Header erzeugen
                if (empty($lcData))
                {
                    if (isset($laLine["matrikelnummer"]))
                        $lcData .= "|&#160;**"._("Matrikelnummer")."** ";
                    if (isset($laLine["name"]))
                        $lcData .= "|&#160;**"._("Name")."** ";
                    if (isset($laLine["studiengang"]))
                        $lcData .= "|&#160;**"._("Studiengang")."** ";
                    if (isset($laLine["bestanden"]))
                        $lcData .= "|&#160;**"._("bestanden")."** ";
                    if (isset($laLine["Bonuspunkte"]))
                        $lcData .= "|&#160;**"._("Bonuspunkte")."** ";
                    if (isset($laLine["uebung"]))
                        foreach( $laLine["uebung"] as $lcName => $laData )
                        {
                            $lcData .= "|&#160;**".$lcName;
                            if (isset($laData["bestanden"]))
                                $lcData .= " ("._("bestanden").")";
                            $lcData .= "** ";
                        }
                    $lcData .= "|\n";
                }

                // Daten hinzufügen
                if (isset($laLine["matrikelnummer"]))
                    $lcData .= "|&#160; ".$laLine["matrikelnummer"];
                if (isset($laLine["name"]))
                    $lcData .= "|&#160; ".$laLine["name"];
                if (isset($laLine["studiengang"]))
                    $lcData .= "|&#160; ".$laLine["studiengang"];
                if (isset($laLine["bestanden"]))
                    $lcData .= "|&#160; ".($laLine["bestanden"] ? _("ja") : _("nein"));
                if (isset($laLine["bonuspunkte"]))
                    $lcData .= "|&#160; ".$laLine["bonuspunkte"];
                if (isset($laLine["uebung"]))
                    foreach( $laLine["uebung"] as $lcName => $laData )
                    {
                        $lcData .= "|&#160; ".$laLine["punktesumme"];
                        if (isset($laData["bestanden"]))
                            $lcData .= " (".($laData["bestanden"] ? _("ja") : _("nein")).")";
                    }
                $lcData .= "|\n";
            }

            // Daten dem PDF hinzufügen und senden
            $loPDF->addContent( $lcData );
            $loPDF->dispatch("punkteliste");
        }

    }
