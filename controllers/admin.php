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
    require_once(dirname(__DIR__) . "/sys/veranstaltungpermission.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/veranstaltung.class.php");
    require_once(dirname(__DIR__) . "/sys/veranstaltung/uebung.class.php");


    /** Controller für die Administration **/
    class AdminController extends StudipController
    {

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
        function index_action() { }


        /** erzeugt für eine Veranstaltung einen neuen Eintrag mit Defaultwerten **/
        function create_action()
        {
            if (!VeranstaltungPermission::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Punkteverwaltung zu aktivieren") );

            else
                try {
                    Veranstaltung::create();
                    $this->flash["message"] = Tools::createMessage( "success", _("Übungsverwaltung wurde aktiviert") );
                } catch (Exception $e) {
                    $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                }
                
            $this->redirect("admin");
        }


        /** Update Aufruf, um die Einstellungen zu setzen **/
        function updatesettings_action()
        {
            if (!VeranstaltungPermission::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Einstellung der Punkteverwaltung zu ändern") );
            
            elseif (Request::submitted("submitted"))
            {
                $lo = Veranstaltung::get();
                if ($lo)
                    try {
                        $lo->bemerkung( Request::quoted("bemerkung") );
                        $lo->bestandenProzent( Request::float("bestandenprozent"), 100 );
                        $lo->allowNichtBestanden( Request::int("allow_nichtbestanden"), 0 );
                        
                        $this->flash["message"] = Tools::createMessage( "success", _("Einstellung gespeichert") );
                    } catch (Exception $e) {
                        $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                    }

            }

            $this->redirect("admin");
        }


        /** löscht alle Daten zu der Veranstaltung **/
        function delete_action()
        {
            if (!VeranstaltungPermission::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Daten zu löschen") );
            else {
                $lo = Veranstaltung::get();
                if ($lo)
                    try {
                        Veranstaltung::delete( $lo );
                        $this->flash["message"] = Tools::createMessage( "success", _("Daten gelöscht") );
                    } catch (Exception $e) {
                        $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                    }
            }

            $this->redirect("admin");
        }

        


        /** Aufruf um eine neue Übung zu erzeugen **/
        function createuebung_action() { }


        /** führt die Action für das CreateÜbungs Form aus **/
        function createuebungsetting_action()
        {
            if (!VeranstaltungPermission::hasDozentRecht())
                $this->flash["message"] = Tools::createMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die eine Übung anzulegen") );

            elseif (Request::submitted("submitted"))
            {
                $lo = Veranstaltung::get();
                if ($lo)
                    try {
                        Uebung::create( $lo, Request::quoted("uebungname") );
                        $this->flash["message"] = Tools::createMessage( "success", _("neue Übung erstellt") );
                    } catch (Exception $e) {
                        $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() );
                    }

            }

            $this->redirect("admin");
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
