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
    require_once(dirname(__DIR__) . "/sys/student.class.php");


    /** Controller f¸r die Sicht eines Studenten **/
    class ShowController extends StudipController
    {

        /** Before-Aufruf zum setzen von Defaultvariablen
         * @warn da der StudIPController keine Session initialisiert, muss die
         * Eigenschaft "flash" h‰ndisch initialisiert werden, damit persistent die Werte
         * ¸bergeben werden kˆnnen
         **/
        function before_filter( &$action, &$args )
        {
            PageLayout::setTitle(_($_SESSION["SessSemName"]["header_line"]. " - Punkteverwaltung - Anzeige"));
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            // Initialisierung der Session & setzen der Veranstaltung, damit jeder View
            // die aktuellen Daten bekommt
            $this->flash                  = Trails_Flash::instance();
            $this->flash["veranstaltung"] = Veranstaltung::get();
        }


        /** Default Action **/
        function index_action()
        {
            PageLayout::addStyle("tr:nth-child(even) {background: #ccc} tr:nth-child(odd) {background: #eee}");
        }

        /** setzt den Studiengang des Users **/
        function studiengang_action()
        {

            try {
                $laData    = explode("#", Request::quoted("studiengang"));

                if (count($laData) == 2)
                {
                    $loStudent = new Student( $GLOBALS["user"]->id );
                    $loStudent->studiengang($this->flash["veranstaltung"], trim($laData[0]), trim($laData[1]));

                    $this->flash["message"] = Tools::createMessage( "success", _("Anerkennung f¸r den Studiengang f¸r diese Veranstaltung ge‰ndert") );
                }
                

            } catch (Exception $e) { $this->flash["message"] = Tools::createMessage( "error", $e->getMessage() ); }

            $this->redirect("show");
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
