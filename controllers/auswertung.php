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
    require_once(dirname(__DIR__) . "/sys/student.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltungpermission.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/veranstaltung.class.php");
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
                
                $this->bonuspunkte   = new Bonuspunkt( $this->flash["veranstaltung"] );
                $this->auswertung    = new Auswertung( $this->flash["veranstaltung"] );

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }
        }


        /** Default Action **/
        function index_action()
        {
            Tools::addHTMLHeaderElements( $this->plugin );
        
            $this->statistikaction  = $this->url_for( "auswertung/jsonstatistik");
            $this->listaction       = $this->url_for( "auswertung/jsonlist");
            $this->auswertungaction = $this->url_for( "auswertung/jsonauswertung");
        }


        /** erzeugt für die Veranstaltung die statistischen Daten,
         * die dann zur Visualisierung genutzt werden **/
        function jsonstatistik_action()
        {
            // Result Array mit Daten
            $laResult = array( "uebungsnamen" => array(), "punkteliste" => array());

            try {
                
                if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                    throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));

                $laListe      = $this->auswertung->studententabelle();

                foreach ($laListe["uebungen"] as $uebung)
                    array_push($laResult["uebungsnamen"], $uebung["name"]);

                foreach ($laListe["uebungen"] as $uebung)
                {
                    $la = array();
                    foreach ($laListe["studenten"] as $student)
                        array_push($la, $uebung["studenten"][$student["id"]]["punktesumme"]);

                    array_push($laResult["punkteliste"], $la);
                }

            } catch (Exception $e) { }
        
            Tools::sendJson( $this, $laResult );
        }
        
        
        /** Json Struktur, die allgemeine Informationen zu der Veranstaltung liefert **/
        function jsonauswertung_action()
        {
            
            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );
            
            
            try {
                
                $laBonuspunkte = $this->bonuspunkte->liste();
                $laListe       = $this->auswertung->studententabelle();
                $laData        = array();
                
                
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("Teilnehmeranzahl") ),
                    "Data1"   => $laListe["statistik"]["teilnehmergesamt"],
                    "Data2"   => null
                ));
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("Anzahl bestandenen Studenten") ),
                    "Data1"   => $laListe["statistik"]["teilnehmerbestanden"],
                    "Data2"   => $laListe["statistik"]["teilnehmergesamt"] == 0 ? 0 : round($laListe["statistik"]["teilnehmerbestanden"] / $laListe["statistik"]["teilnehmergesamt"] * 100, 2)."%"
                ));
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("Anzahl Studenten mit Bonuspunkten / Prozent der bestandenen") ),
                    "Data1"   => $laListe["statistik"]["teilnehmerbonus"],
                    "Data2"   => $laListe["statistik"]["teilnehmerbestanden"] == 0 ? 0 : round($laListe["statistik"]["teilnehmerbonus"] / $laListe["statistik"]["teilnehmerbestanden"] * 100,2)."%"
                ));
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("Anzahl Studenten mit mehr als null Punkten") ),
                    "Data1"   => $laListe["statistik"]["teilnehmerpunktenotzero"],
                    "Data2"   => $laListe["statistik"]["teilnehmergesamt"] == 0 ? 0 : round($laListe["statistik"]["teilnehmerpunktenotzero"] / $laListe["statistik"]["teilnehmergesamt"] * 100, 2)."%"
                ));
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("Gesamtpunktanzahl") ),
                    "Data1"   => $laListe["gesamtpunkte"],
                    "Data2"   => null
                ));
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("Punkte zur Zulassung") ),
                    "Data1"   => $laListe["gesamtpunktebestanden"],
                    "Data2"   => null
                ));
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("max. erreichte Punkte") ),
                    "Data1"   => $laListe["statistik"]["maxpunkte"],
                    "Data2"   => null
                ));
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("min. erreichte Punkte") ),
                    "Data1"   => $laListe["statistik"]["minpunkte"],
                    "Data2"   => null
                ));
                
                array_push($laData, array(
                    "Titel"   => studip_utf8encode( _("min. erreichte Punkte > 0") ),
                    "Data1"   => $laListe["statistik"]["minpunktegreaterzero"],
                    "Data2"   => null
                ));
                
                // @todo beste Studenten sollen gelistet werden
                
                
                // alles fehlerfrei durchlaufen, setze Result
                $laResult["Records"] = $laData;
                $laResult["Result"]  = "OK";
                
                // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
            
            Tools::sendJson( $this, $laResult );
        }
        
        
        /** liefert die korrekten Json Daten für den jTable **/
        function jsonlist_action()
        {
            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );
            
            
            
            // Daten holen und der View erzeugt dann das Json Objekt, wobei auf korrekte UTF8 Encoding geachtet werden muss
            try {
                
                // hole die Übung und prüfe die Berechtigung (in Abhängigkeit des gesetzen Parameter die Übung initialisieren)
                if (!VeranstaltungPermission::hasDozentRecht( $this->flash["veranstaltung"] ))
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));
                
                $laData = $this->auswertung->studententabelle();
                if (is_array($laData))
                {
                    // setze Defaultwerte für jTable
                    $laResult["TotalRecordCount"] = count($laData["studenten"]);
                    
                    // sortiere Daten anhand des Kriteriums
                    /*
                    usort($laData["studenten"], function($a, $b) {
                          $ln = 0;
                          
                          if ($a == $b)
                            return 0;
                          
                          elseif (stripos(Request::quoted("jtSorting"), "matrikelnummer") !== false)
                            $ln = $a->student()->matrikelnummer() - $b->student()->matrikelnummer();
                          
                          elseif (stripos(Request::quoted("jtSorting"), "name") !== false)
                            $ln = strcasecmp(studip_utf8encode($a->student()->name()), studip_utf8encode($b->student()->name()));
                          
                          elseif (stripos(Request::quoted("jtSorting"), "email") !== false)
                            $ln = strcasecmp(studip_utf8encode($a->student()->email()), studip_utf8encode($b->student()->email()));

                          
                          if (stripos(Request::quoted("jtSorting"), "asc") === false)
                            $ln = -1 * $ln;
                          
                          return $ln;
                    });
                    */
                    
                    // hole Query Parameter, um die Datenmenge passend auszuwählen
                    $laData["studenten"] = array_slice($laData["studenten"], Request::int("jtStartIndex"), Request::int("jtPageSize"));
                    
                    
                    foreach ($laData["studenten"] as $lcStudentKey => $laStudent)
                    {
                        // erzeuge Basis-Datensatz
                        $laItem = array(
                            "Auth"                => studip_utf8encode( $lcStudentKey ),
                            "Hinweis"             => null,
                            "Matrikelnummer"      => $laStudent["matrikelnummer"],
                            "Name"                => studip_utf8encode( $laStudent["name"] ),
                            "EmailAdresse"        => studip_utf8encode( $laStudent["email"] ),
                            "Studiengang"         => $laStudent["studiengang"],
                            "Gesamtpunkte"        => $laStudent["uebungenpunkte"],
                            "GesamtpunkteProzent" => $laData["gesamtpunkte"] ? round($laStudent["uebungenpunkte"] / $laData["gesamtpunkte"] * 100, 2) : 0,
                            "Bonuspunkte"         => $laStudent["bonuspunkte"],
                            "gesamtbestanden"     => $laStudent["veranstaltungenbestanden"]
                        );

                        
                        
                        // erzeuge Übungseinträge
                        foreach($laData["uebungen"] as $laUebung)
                        {
                            $lcHash                           = md5($laUebung["name"]);
                            $lnPunkte                         = $laUebung["studenten"][$lcStudentKey]["punktesumme"];
                            
                            $laItem["ueb_punkte_".$lcHash]    = $lnPunkte;
                            $laItem["ueb_prozent_".$lcHash]   = $laUebung["maxPunkte"] ? round( $lnPunkte / $laUebung["maxPunkte"] * 100, 2) : 0;
                            $laItem["ueb_bestanden_".$lcHash] = $laUebung["studenten"][$lcStudentKey]["bestanden"];
                        }
                        
                        
                        
                        // Daten überprüfen und Hinweis setzen
                        $loStudent = new Student($lcStudentKey);
                        if ($loStudent->checkStudiengangAbschlussFehler())
                            $laItem["Hinweis"] = studip_utf8encode( _("Fehler bei Studiengang und/oder Abschluss") );
                        
                        
                        array_push( $laResult["Records"], $laItem );
                    }
                }
                
                
                // alles fehlerfrei durchlaufen, setze Result
                $laResult["Result"] = "OK";
                
                // fange Exception und liefer Exceptiontext passend codiert in das Json-Result 
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
            
            Tools::sendJson( $this, $laResult );
        }


        /** Export Controller um Datenstruktur zu erzeugen **/
        function export_action()
        {
            try {
                
                if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                    throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


                // erzeuge Datenarray mit harter Sortierung nach Matrikelnummer,
                // Items die leer (empty) sind, erscheinen nicht in der Ausgabe
                $laListe      = $this->auswertung->studententabelle();
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
                                "bonuspunkte"    => floatval($laStudent["bonuspunkte"]),
                                "uebung"         => array()
                            );
                            foreach($laListe["uebungen"] as $laUebung)
                                $laItem["uebung"][$laUebung["name"]] = array(
                                    "punktesumme" => floatval($laUebung["studenten"][$lcStudentKey]["punktesumme"]),
                                    "bestanden"   => $laUebung["studenten"][$lcStudentKey]["bestanden"]
                                );
                            array_push( $laOutput,  $laItem );
                        }
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
                                    "bonuspunkte"    => floatval($laStudent["bonuspunkte"]),
                                    "uebung"         => array()
                                );
                                foreach($laListe["uebungen"] as $laUebung)
                                    $laItem["uebung"][$laUebung["name"]] = array(
                                        "punktesumme" => floatval($laUebung["studenten"][$lcStudentKey]["punktesumme"])
                                    );
                                array_push( $laOutput,  $laItem );
                            }
                        break;


                    default :
                        throw new Exception(_("Exportart unbekannt"));
                }


                // erzeuge Ausgabeformat, das Senden inkl. Headerinformationen geschieht durch den View
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
            $loExcel->getProperties()->setKeywords("Stud.IP '".utf8_encode($pcTite)."' Studium");
        
         
            // erzeuge Sheet und setze Layout-Strukturen
            $loExcel->setActiveSheetIndex(0);
            $loExcel->getDefaultStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
            $loSheet = $loExcel->getActiveSheet();
        
            $loSheet->setTitle("Punkteliste");
            $loSheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
            $loSheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        
        
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
                        $loSheet->setCellValue( chr(65+$i)."1", utf8_encode($laHeader[$i]));
                
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
                                array_push($laItem, utf8_encode($lxUebungData["punktesumme"]));
                            if (array_key_exists("bestanden", $lxUebungData))
                                array_push($laItem, $lxUebungData["bestanden"] ? _("ja") : _("nein") );
                        }
                    else
                        array_push($laItem, utf8_encode($lxData));
            
                $laLine = $laItem;
            
            }
        
            // setze Daten in das Sheet und setze Autosizing für die Zellen
            $loSheet->fromArray($paOutput, NULL, "A3");
        
            $loCellIterator = $loSheet->getRowIterator()->current()->getCellIterator();
            $loCellIterator->setIterateOnlyExistingCells( true );
            foreach( $loCellIterator as $loCell )
                $loSheet->getColumnDimension( $loCell->getColumn() )->setAutoSize( true );
        
        
            // erzeuge Download / Ausgabe (ohne den View zu rendern)
            $this->set_layout(null);
            $this->render_nothing();
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
            // Querformat verwendet
            $loPDF = new ExportPDF("L");
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
            $this->set_layout(null);
            $this->render_nothing();
            $loPDF->addContent( $lcData );
            $loPDF->dispatch($lcTitle);
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
