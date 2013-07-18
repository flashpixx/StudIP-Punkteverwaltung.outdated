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


    require_once("bootstrap.php");
    //require_once("matrikelnummer/factory.class.php");



    // http://docs.studip.de/develop/Entwickler/HowToFormulars
    // http://docs.studip.de/develop/Entwickler/HowToHTML
    // http://studip.tleilax.de/plugins/generator/
    // http://docs.studip.de/api


    /** Basisklasse für das Plugin **/
    class punkteverwaltung extends StudIPPlugin implements StandardPlugin
    {

        function __construct() {
            parent::__construct();

            /* Trails Menü Definition wird nicht verwendet
            $navigation = new AutoNavigation(_("Punkteverwaltung"));
            $navigation->setURL(PluginEngine::GetURL($this, array(), "show"));
            $navigation->setImage(Assets::image_path("blank.gif"));
            Navigation::addItem("/punkteverwaltung", $navigation);
            */

            /** Menü wird unter die Veranstalung als Tab eingehangen **/
            $loNav = Navigation::getItem("/course");
            $loNav->addSubNavigation("punkteverwaltung", new Navigation(_("Punkteverwaltung"), PluginEngine::GetURL($this, array(), "show/punkteverwaltung")));
        }

        function initialize () { }

        function getIconNavigation($course_id, $last_visit) { }

        function getInfoTemplate($course_id) { }

        function perform($unconsumed_path)
        {
            $this->setupAutoload();
            $dispatcher = new Trails_Dispatcher(
                                                $this->getPluginPath(),
                                                rtrim(PluginEngine::getLink($this, array(), null), "/"),
                                                "show"
                                                );
            $dispatcher->plugin = $this;
            $dispatcher->dispatch($unconsumed_path);
        }

        private function setupAutoload()
        {
            if (class_exists("StudipAutoloader"))
                StudipAutoloader::addAutoloadPath(__DIR__ . "/models");
            else
                spl_autoload_register(function ($class) { include_once __DIR__ . $class . ".php"; });
        }
    }
