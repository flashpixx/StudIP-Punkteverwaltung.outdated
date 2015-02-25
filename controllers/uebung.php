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
    require_once(dirname(__DIR__) . "/sys/veranstaltung/veranstaltung.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/uebung.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/studentuebung.class.php");
    require_once(dirname(__DIR__) . "/sys/authentification.class.php");


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
            $this->plugin   = $poDispatch->plugin;
        }


        /** Before-Aufruf zum setzen von Defaultvariablen
         * @warn da der StudIPController keine Session initialisiert, muss die
         * Eigenschaft "flash" händisch initialisiert werden, damit persistent die Werte
         * übergeben werden können
         **/
        function before_filter( &$action, &$args )
        {
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
            // die aktuellen Daten bekommt
            $this->flash                  = Trails_Flash::instance();
            $this->flash["veranstaltung"] = Veranstaltung::get();
        
            try {

                // falls keine ÜbungsID gesetzt ist, versuchen die letzte Übung (zu letzt hinzugefügt) zu finden, wenn nicht mit Exception abbrechen
                if (Request::quoted("ueid"))
                {
                    $this->flash["uebung"] = new Uebung($this->flash["veranstaltung"], Request::quoted("ueid"));
                    PageLayout::setTitle( sprintf("%s - Punkteverwaltung - Übung [%s]", $_SESSION["SessSemName"]["header_line"], $this->flash["uebung"]->name()) );
                    return;
                } else {
                    $laUebungen = $this->flash["veranstaltung"]->uebungen();
                    if ( (is_array($laUebungen)) && (!empty($laUebungen)) )
                    {
                        $this->flash["uebung"] = end($laUebungen);
                        PageLayout::setTitle( sprintf("%s - Punkteverwaltung - Übung [%s]", $_SESSION["SessSemName"]["header_line"], $this->flash["uebung"]->name()) );
                        return;
                    }
                }
                
                throw new Exception(  _("Es wurden bisher keine Daten hinterlegt.").( Authentification::hasDozentRecht($this->flash["veranstaltung"]) ? null : " "._("Bei Fragen wenden Sie sich bitte an den/die Dozenten der Veranstaltung") )  );

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

        }
    
    
        /** Default Action **/
        function index_action()
        {
            Tools::addHTMLHeaderElements( $this->plugin );
        
            // setze Variablen (URLs) für die entsprechende Ajax-Anbindung
            if (!is_object($this->flash["uebung"]))
                return;
        
            $this->listaction       = $this->url_for( "uebung/jsonlist",   array("ueid" => $this->flash["uebung"]->id()) );
            $this->updateaction     = $this->url_for( "uebung/jsonupdate", array("ueid" => $this->flash["uebung"]->id()) );
            $this->childlistaction  = $this->url_for( "uebung/jsonchildlist", array("ueid" => $this->flash["uebung"]->id()) );
            $this->childiconpath    = $this->plugin->getPluginUrl() . "/assets/img/log.png";
        }


        /** setzt die Einstellungen für die Übung **/
        function updatesetting_action()
        {
            try {
                if (!Authentification::hasDozentRecht($this->flash["uebung"]->veranstaltung()))
                    $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Übung zu verändern") );
                elseif (Request::submitted("submitted"))
                {
                    $this->flash["uebung"]->name( Request::quoted("uebungname") );
                    $this->flash["uebung"]->bestandenProzent( Request::float("bestandenprozent") );
                    $this->flash["uebung"]->maxPunkte( Request::float("maxpunkte") );
                    $this->flash["uebung"]->bemerkung( Request::quoted("bemerkung") );
                    $this->flash["uebung"]->abgabeDatum( Request::quoted("abgabedatum") );

                    $this->flash["message"] = Tools::createMessage( "success", _("Einstellung der Übung geändert") );
                }

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

            $this->redirect($this->url_for("uebung", array("ueid" => $this->flash["uebung"]->id())));
        }


        /** löscht eine Übung **/
        function delete_action()
        {
            try {
                if (Request::int("dialogyes"))
                {
                    if (!Authentification::hasDozentRecht($this->flash["uebung"]->veranstaltung()))
                        $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Übung zu löschen") );
                    else
                        Uebung::delete( $this->flash["uebung"] );

                    $this->flash["message"] = Tools::createMessage( "success", _("Übung gelöscht") );
                    
                    if ($this->flash["veranstaltung"]->hasUebungen())
                        $this->redirect("uebung");
                    else
                        $this->redirect("admin");
                    return;
                }
                elseif (Request::int("dialogno")) { }

                else
                    $this->flash["message"] = Tools::createMessage( "question", _("Soll die Übung inkl aller Punkte gelöscht werden?"), array(), $this->url_for("uebung/delete", array("ueid" => $this->flash["uebung"]->id())) );


            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

            $this->redirect($this->url_for("uebung", array("ueid" => $this->flash["uebung"]->id())));
        }


        /** liefert die Daten zu einem Eintrag (Log Auswertung) **/
        function jsonchildlist_action()
        {
            // Daten für das Json Objekt holen und ein Default Objekt setzen
            $laResult = array( "Result"  => "ERROR", "Records" => array() );

            try {
                if (!Authentification::hasDozentRecht( $this->flash["uebung"]->veranstaltung() ))
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));

                // hole Student und Logdaten
                $loStudentUebung = new StudentUebung($this->flash["uebung"], Request::quoted("aid"));

                // wir brauchen einen Pseudoindex, da sonst die Childtabelle die Daten nicht anzeigt
                // hier einfach eine inkrementelle Nummer verwenden
                $n = 0;
                foreach( $loStudentUebung->log() as $item )
                {
                    array_push( $laResult["Records"], array(
                                "ID"              => $n,
                                "ErreichtePunkte" => $item["erreichtepunkte"],
                                "ZusatzPunkte"    => $item["zusatzpunkte"],
                                "Bemerkung"       => studip_utf8encode( $item["bemerkung"] ),
                                "Korrektor"       => studip_utf8encode( $item["korrektor"] )
                    ));
                    $n++;
                }


                // alles fehlerfrei durchlaufen, setze Result
                $laResult["Result"] = "OK";

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
                if ( (!Authentification::hasTutorRecht( $this->flash["uebung"]->veranstaltung() )) && (!Authentification::hasDozentRecht( $this->flash["uebung"]->veranstaltung() )) )
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));

                $laData = $this->flash["uebung"]->studentenuebung();
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
                    {
                        // siehe Arraykeys unter views/uebung/jsonlist.php & alle String müssen UTF-8 codiert werden, da Json UTF-8 ist
                        $lxGruppen = array();
                        foreach( $item->student()->gruppen($this->flash["veranstaltung"]) as $groupitem )
                            array_push($lxGruppen, $groupitem["name"]);
                        $lxGruppen = empty($lxGruppen) ? null : implode(", ", $lxGruppen);
                        
                        
                        $laItem = array(
                                    "Auth"            => studip_utf8encode( $item->student()->id() ),
                                    "Matrikelnummer"  => $item->student()->matrikelnummer(),
                                    "Name"            => studip_utf8encode( $item->student()->name() ),
                                    "EmailAdresse"    => studip_utf8encode( $item->student()->email() ),
                                    "Gruppen"         => studip_utf8encode( $lxGruppen ),
                                    "ErreichtePunkte" => $item->erreichtePunkte(),
                                    "ZusatzPunkte"    => $item->zusatzPunkte(),
                                    "Bemerkung"       => studip_utf8encode( $item->bemerkung() )
                        );

                        if (Authentification::hasDozentRecht( $this->flash["uebung"]->veranstaltung() ))
                            $laItem["Korrektor"] = studip_utf8encode( $item->korrektor() );


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
                if ( (!Authentification::hasTutorRecht( $this->flash["uebung"]->veranstaltung() )) && (!Authentification::hasDozentRecht( $this->flash["uebung"]->veranstaltung() )) )
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));

                // hole die Zuordnung von Übung und Student und setze die Daten, wobei bei den Textdaten auf korrektes UTF-8 Decoding geachtet werden muss
                $lo = new StudentUebung( $this->flash["uebung"], studip_utf8decode(Request::quoted("Auth")) );
                $lo->update( Request::float("ErreichtePunkte"), Request::float("ZusatzPunkte"), studip_utf8decode(Request::quoted("Bemerkung")) );
               

                // alles fehlerfrei durchlaufen, setze Result (lese die geänderten Daten aus der Datenbank)
                $laResult["Result"] = "OK";


            // fange Exception und liefer Exceptiontext passend codiert in das Json-Result
            } catch (Exception $e) { $laResult["Message"] = studip_utf8encode( $e->getMessage() ); }
        
            Tools::sendJson( $this, $laResult );
        }


        /** schreibt die Daten aus dem Massenedit Feld in die Datenbank **/
        function massedit_action()
        {
            // Daten holen und der View erzeugt dann das Json Objekt, wobei auf korrekte UTF8 Encoding geachtet werden muss
            try {

                // hole die Übung und prüfe die Berechtigung (in Abhängigkeit des gesetzen Parameter die Übung initialisieren)
                if ( (!Authentification::hasTutorRecht( $this->flash["uebung"]->veranstaltung() )) && (!Authentification::hasDozentRecht( $this->flash["uebung"]->veranstaltung() )) )
                    throw new Exception(_("Sie haben nicht die notwendige Berechtigung"));

                $i = 0;
                $laError = array();
                $this->flash["massinput"] =  Request::quoted("massinput");
                foreach(explode("\n", $this->flash["massinput"]) as $lcLine)
                {
                    $i++;
                    if (empty($lcLine))
                        continue;

                    $laItems = preg_split("/[\s]+/", trim($lcLine), -1, PREG_SPLIT_NO_EMPTY);
                    $laData  = array("matrikelnummer" => null, "punkte" => 0, "bonuspunkte" => 0, "bemerkung" => null);

                    if ( (!is_array($laItems)) || (empty($laItems)) )
                    {
                        array_push($laError, sprintf(_("Zeile %d hat ein ungültiges Format"), $i));
                        continue;
                    }
                
                
                    // der erste Eintrag des gesplitten Array ist die Matrikelnummer
                    $laData["matrikelnummer"] = array_shift($laItems);
                    if (!is_numeric($laData["matrikelnummer"]))
                    {
                        array_push($laError, sprintf(_("Matrikelnummer in Zeile %d ist nicht numerisch"), $i));
                        continue;
                    }
                    $laData["matrikelnummer"] = intval($laData["matrikelnummer"]);
                
                    // der zweite Eintrag müssen die Punkte sein, wobei als Trenner auch ein Komma erlaubt sein muss
                    $laData["punkte"] = str_replace(",", ".", array_shift($laItems));
                    if (!is_numeric($laData["punkte"]))
                    {
                        array_push($laError, sprintf(_("Punkte in Zeile %d sind nicht numerisch"), $i));
                        continue;
                    }
                    $laData["punkte"] = abs(floatval($laData["punkte"]));
                
                    // der dritte Eintrag müssen die Bonuspunkte sein, wobei als Trenner ebenso ein Komma erlaubt sein muss
                    if (!empty($laItems))
                    {
                        $laData["bonuspunkte"] = str_replace(",", ".", array_shift($laItems));
                        if (!is_numeric($laData["bonuspunkte"]))
                        {
                            array_push($laError, sprintf(_("Bonuspunkte in Zeile %d sind nicht numerisch"), $i));
                            continue;
                        }
                        $laData["bonuspunkte"] = abs(floatval($laData["bonuspunkte"]));
                    }
                
                    // alle weiteren Einträge sind die Bemerkung
                    if (!empty($laItems))
                        $laData["bemerkung"] = implode(" ", $laItems);
                
                
                    try {
                        
                        $lo = new StudentUebung( $this->flash["uebung"], $laData["matrikelnummer"] );
                        $lo->update( $laData["punkte"], $laData["bonuspunkte"], $laData["bemerkung"] );
                    
                    } catch (UserNotFound $e) {
                        array_push($laError, "Zeile ".$i.": ".$e->getMessage());
                    } catch (UserDataIncomplete $e) {
                        array_push($laError, "Zeile ".$i.": ".$e->getMessage());
                    } catch (UserNotSeminarMember $e) {
                        array_push($laError, "Zeile ".$i.": ".$e->getMessage());
                    }
                }

                if (empty($laError))
                    $this->flash["massinput"] = null;
                else
                    $this->flash["message"] = Tools::createMessage( "error", implode("<br/>", $laError) );

                
            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

            $this->redirect($this->url_for("uebung", array("ueid" => $this->flash["uebung"]->id())));
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
