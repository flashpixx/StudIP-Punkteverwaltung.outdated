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
        }


        /** erzeugt den PDF Export der Veranstaltung
         * @see http://docs.studip.de/develop/Entwickler/PDFExport
         * @see http://hilfe.studip.de/index.php/Basis/VerschiedenesFormat
         **/
        function pdfexport_action()
        {
            if (!VeranstaltungPermission::hasDozentRecht($this->flash["veranstaltung"]))
                throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


            $loPDF        = new ExportPDF();
            $loPDF->addPage("L");

            $loAuswertung = new Auswertung( $this->flash["veranstaltung"] );
            $laListe      = $loAuswertung->studenttabelle();
            
            // Sortierung hart nach Matrikelnummern
            uasort($laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; });

            // erzeuge Array für die Namen der Übungen
            $laUebungen      = array();
            foreach($this->flash["veranstaltung"]->uebungen() as $uebung)
                array_push($laUebungen, $uebung->name());


            

            // Tabelle mit Punkten erstellen
            $loPDF->addContent("|**"._("Name (EMail)")."**|**"._("Matrikelnummer"))."**";
            foreach($laUebungen as $name)
                $loPDF->addContent("|**".$name."  ("._("bestanden").")**");
            $loPDF->addContent("|**"._("bestanden")."**|**"._("Bonuspunkte")."**|\n");

            /*
            foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
            {
                echo "<td>".$laStudent["name"]." (".$laStudent["email"].")</td>";
                echo "<td>".$laStudent["matrikelnummer"]."</td>";

                foreach($laUebungen as $lcUebung)
                {
                    echo "<td>";
                    echo $laListe["uebungen"][$lcUebung]["studenten"][$lcStudentKey]["punktesumme"]." (".($laListe["uebungen"][$lcUebung]["studenten"][$lcStudentKey]["bestanden"] ? _("ja") : _("nein")).")";
                    echo "</td>";
                }
                echo "<td>".($laStudent["veranstaltungenbestanden"] ? "ja" : "nein")."</td>";
                echo "<td>&nbsp;</td>";
                echo "</tr>";
            }

             */



            $loPDF->dispatch("test_pdf");
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
