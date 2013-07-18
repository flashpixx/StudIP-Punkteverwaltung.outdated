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
    //require_once("view/factory.class.php");



    // http://docs.studip.de/develop/Entwickler/HowToFormulars
    // http://docs.studip.de/develop/Entwickler/HowToHTML


    /** Basisklasse für das Plugin **/
    class punkteverwaltung extends StudIPPlugin implements StandardPlugin {

        public function __construct() {
            parent::__construct();

            $navigation = new AutoNavigation(_("Punkteverwaltung"));
            $navigation->setURL(PluginEngine::GetURL($this, array(), "show"));
            $navigation->setImage(Assets::image_path("blank.gif"));
            Navigation::addItem("/punkteverwaltung", $navigation);
        }

        public function initialize () {

        }

        public function getTabNavigation($course_id) {
            return array();
        }

        public function getNotificationObjects($course_id, $since, $user_id) {
            return array();
        }

        public function getIconNavigation($course_id, $last_visit, $user_id) {
            // ...
        }

        public function getInfoTemplate($course_id) {
            // ...
        }

        public function perform($unconsumed_path) {
            $this->setupAutoload();
            $dispatcher = new Trails_Dispatcher(
                                                $this->getPluginPath(),
                                                rtrim(PluginEngine::getLink($this, array(), null), "/"),
                                                "show"
                                                );
            $dispatcher->plugin = $this;
            $dispatcher->dispatch($unconsumed_path);
        }
        
        private function setupAutoload() {
            if (class_exists("StudipAutoloader"))
                StudipAutoloader::addAutoloadPath(__DIR__ . "/models");
            else
                spl_autoload_register(function ($class) { include_once(__DIR__ . $class . ".php"); });

        }
    }



    /**
    class Punkteverwaltung extends AbstractStudIPStandardPlugin implements StandardPlugin
    {
        private $moView = null;


        
        ** Ctor der Klasse für Initialisierung **
        function __construct()
        {
            parent::AbstractStudIPStandardPlugin();

            // setzt die Klasseneigenschaften
            $this->moView = ViewFactory::get( $this->getUser() );


            // erzeuge Navigation in der Veranstaltung und setzt sie in der Übersicht
            $loNav = new PluginNavigation();
            $loNav->setDisplayname( $this->moView->getMenuName() );
            $this->setNavigation($loNav);
        }

    }
    **/

?>
