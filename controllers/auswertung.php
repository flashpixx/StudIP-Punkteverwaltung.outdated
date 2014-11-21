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
    require_once(dirname(__DIR__) . "/sys/extensions/excel/PHPExcel.php");
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
            PageLayout::setTitle(_($_SESSION["SessSemName"]["header_line"]. " - Punkteverwaltung - Auswertung"));
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


        /** Export Controller um Datenstruktur zu erzeugen **/
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
                                "matrikelnummer" => $laStudent["matrikelnummer"],
                                "name"           => $laStudent["name"],
                                "studiengang"    => $laStudent["studiengang"],
                                "bestanden"      => $laStudent["veranstaltungenbestanden"],
                                "bonuspunkte"    => $laStudent["bonuspunkte"],
                                "uebung"         => array()
                            );
                            foreach($laListe["uebungen"] as $laUebung)
                                $laItem["uebung"][$laUebung["name"]] = array(
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
                                    "matrikelnummer" => $laStudent["matrikelnummer"],
                                    "name"           => $laStudent["name"],
                                    "studiengang"    => $laStudent["studiengang"],
                                ));
                        break;


                    // volle Liste der Studenten, die die Veranstaltung bestanden haben
                    case "bestanden" :
                        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
                            if ($laStudent["veranstaltungenbestanden"])
                            {
                                $laItem = array(
                                    "matrikelnummer" => $laStudent["matrikelnummer"],
                                    "name"           => $laStudent["name"],
                                    "studiengang"    => $laStudent["studiengang"],
                                    "bonuspunkte"    => $laStudent["bonuspunkte"],
                                    "uebung"         => array()
                                );
                                foreach($laListe["uebungen"] as $laUebung)
                                    $laItem["uebung"][$laUebung["name"]] = array(
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
                    case "pdf"  : $this->exportPDF( $laOutput, $lcTitle );   break;
                    case "xlsx" : $this->exportExcel( $laOutput, $lcTitle ); break;

                    default :
                        throw new Exception(_("Exportparameter unbekannt"));
                }


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
    
    
    
        /** Exportfunktion für Excel
         * @see https://github.com/PHPOffice/PHPExcel
         * @param $paOutput Datenarray
         * @param $pcTitle String mit Titel der Veranstaltung
         **/
        private function exportExcel( $paOutput, $pcTitle )
        {
            $loExcel = new PHPExcel();
        
            // Dokument Properties setzen
            $loExcel->getProperties()->setCreator("Stud.IP Punkteplugin");
			$loExcel->getProperties()->setTitle(utf8_encode($pcTitle));
            $loExcel->getProperties()->setDescription(utf8_encode("Liste mit den Übungsleistungen"));
            $loExcel->getProperties()->setKeywords("Stud.IP '".$pcTite."' Studium");
        
         
            // erzeuge Sheet und setze Layout-Strukturen
            $loExcel->setActiveSheetIndex(0);
            $loExcel->getActiveSheet()->setTitle("Punkteliste");
            $loExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
            $loExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        
        
            // erzeuge Array mit Ausgabedaten
            $laHeader = array();
            foreach( $paOutput as &$laLine )
            {
            
                // erzeuge Header
                if (empty($laHeader))
                {
                    if (array_key_exists("matrikelnummer", $laLine))
                        array_push($laHeader, _("Matrikelnummer"));
                    if (array_key_exists("name", $laLine))
                        array_push($laHeader, _("Name"));
                    if (array_key_exists("studiengang", $laLine))
                        array_push($laHeader, _("Studiengang"));
                    if (array_key_exists("bestanden", $laLine))
                        array_push($laHeader, _("bestanden"));
                    if (array_key_exists("bonuspunkte", $laLine))
                        array_push($laHeader, _("Bonuspunkte"));
                    if (array_key_exists("uebung", $laLine))
                        foreach( $laLine["uebung"] as $lcName => $laData )
                        {
                            if (array_key_exists("punktesumme", $laData))
                               array_push($laHeader, $lcName);
                            if (array_key_exists("bestanden", $laData))
                                array_push($laHeader, $lcName." "._("bestanden"));
                        }

                    for($i=0; $i < count($laHeader); $i++)
                        $loExcel->getActiveSheet()->setCellValue( chr(65+$i)."1", utf8_encode($laHeader[$i]));
                
                    $loHeader = $loExcel->getActiveSheet()->getStyle("A1:".(chr(65+count($laHeader)))."1");
                    $loHeader->getFont()->setBold(true);
                    $loHeader->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
            
            
                // modifziere Datensatz, so dass die Daten so enthalten sind,
                // wie sie vom Header verlangt werden
            
                $laItem = array();
                foreach( $laLine as $lcKey => $lxData)
                    if ($lcKey == "bestanden")
                        array_push( $laItem, $lxData ? _("ja") : _("nein") );
                    elseif ($lcKey == "uebung")
                        foreach($lxData as $lcName => $lxUebungData)
                        {
                            if (array_key_exists("punktesumme", $lxUebungData))
                                array_push($laItem, $lxUebungData["punktesumme"]);
                            if (array_key_exists("bestanden", $lxUebungData))
                                array_push($laItem, $lxUebungData["bestanden"] ? _("ja") : _("nein") );
                        }
                    else
                        array_push($laItem, utf8_encode($lxData));
            
                $laLine = $laItem;
            
            }
        
            // setze Daten in das Sheet
            $loExcel->getActiveSheet()->fromArray($paOutput, NULL, "A3");
        
        

        
            // erzeuge Download / Ausgabe
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment;filename=\"".$pcTitle.".xlsx\"");
            header("Cache-Control: max-age=1");
        
            $loOutput = PHPExcel_IOFactory::createWriter($loExcel, "Excel2007");
            $loOutput->save("php://output");
        }
    


        /** Exportfunktion für PDF
         * @see http://docs.studip.de/develop/Entwickler/PDFExport
         * @see http://hilfe.studip.de/index.php/Basis/VerschiedenesFormat
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
                    if (array_key_exists("matrikelnummer", $laLine))
                        $lcData .= "|&#160;**"._("Matrikelnummer")."** ";
                    if (array_key_exists("name", $laLine))
                        $lcData .= "|&#160;**"._("Name")."** ";
                    if (array_key_exists("studiengang", $laLine))
                        $lcData .= "|&#160;**"._("Studiengang")."** ";
                    if (array_key_exists("bestanden", $laLine))
                        $lcData .= "|&#160;**"._("bestanden")."** ";
                    if (array_key_exists("bonuspunkte", $laLine))
                        $lcData .= "|&#160;**"._("Bonuspunkte")."** ";
                    if (array_key_exists("uebung", $laLine))
                        foreach( $laLine["uebung"] as $lcName => $laData )
                        {
                            $lcData .= "|&#160;**".$lcName;
                            if (array_key_exists("bestanden", $laData))
                                $lcData .= " ("._("bestanden").")";
                            $lcData .= "** ";
                        }
                    $lcData .= "|\n";
                }

                // Daten hinzufügen
                if (array_key_exists("matrikelnummer", $laLine))
                    $lcData .= "|&#160; ".$laLine["matrikelnummer"];
                if (array_key_exists("name", $laLine))
                    $lcData .= "|&#160; ".$laLine["name"];
                if (array_key_exists("studiengang", $laLine))
                    $lcData .= "|&#160; ".$laLine["studiengang"];
                if (array_key_exists("bestanden", $laLine))
                    $lcData .= "|&#160; ".($laLine["bestanden"] ? _("ja") : _("nein"));
                if (array_key_exists("bonuspunkte", $laLine))
                    $lcData .= "|&#160; ".$laLine["bonuspunkte"];
                if (array_key_exists("uebung", $laLine))
                    foreach( $laLine["uebung"] as $lcName => $laData )
                    {
                        $lcData .= "|&#160; ";
                        if (array_key_exists("punktesumme", $laData))
                            $lcData .= $laData["punktesumme"];
                        if (array_key_exists("bestanden", $laData))
                            $lcData .= " (".($laData["bestanden"] ? _("ja") : _("nein")).")";
                    }
                $lcData .= "|\n";
            }

            // Daten dem PDF hinzufügen und senden
            $loPDF->addContent( $lcData );
            $loPDF->dispatch($lcTitle);
        }

    }
