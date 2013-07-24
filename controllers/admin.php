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


    /** Controller für die Administration **/
    class AdminController extends StudipController
    {

        /** Before-Aufruf zum setzen von Defaultvariablen **/
        function before_filter( &$action, &$args )
        {
            // PageLayout::setTitle("");
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));

            $this->message       = null;
            $this->veranstaltung = Veranstaltung::get();
        }


        /** Default Action **/
        function index_action() { }


        /** erzeugt für eine Veranstaltung einen neuen Eintrag mit Defaultwerten **/
        function create_action()
        {
            if (VeranstaltungPermission::hasDozentRecht())
                try {
                    Veranstaltung::create();
                    $this->message = Tools::CreateMessage( "success", _("Übungsverwaltung wurde aktiviert") );
                } catch (Exception $e) {
                    $this->message = Tools::CreateMessage( "error", $e->getMessage() );
                }
            else
                $this->message = Tools::CreateMessage( "error", _("Sie haben nicht die erforderlichen Rechte um die Übungen anzulegen") );

            
            $this->redirect("admin/index");
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
