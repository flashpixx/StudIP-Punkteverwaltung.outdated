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
    require_once("sys/veranstaltungpermission.class.php");
    require_once("sys/veranstaltung/veranstaltung.class.php");


    //ini_set("display_errors", TRUE);
    //error_reporting(E_ALL);
    //error_reporting(E_ALL ^ E_NOTICE);

    // http://docs.studip.de/develop/Entwickler/HowToFormulars
    // http://docs.studip.de/develop/Entwickler/HowToHTML
    // http://studip.tleilax.de/plugins/generator/
    // http://docs.studip.de/api


    /** Basisklasse für das Plugin nach dem Trails-Framework
     * @see http://docs.studip.de/develop/Entwickler/Trails
     * @see http://docs.studip.de/develop/Entwickler/Navigation
     **/
    class punkteverwaltung extends StudIPPlugin implements StandardPlugin
    {

        /** Ctor des Plugins zur Erzeugung der Navigation **/
        function __construct()
        {
            parent::__construct();

            // Navigation wird in Abhängigkeit der Berechtigungen und des Kontextes gesetzt,
            // nur wenn Plugin aktiviert ist und es es sich um eine Veranstaltung handelt wird
            // es aktiviert
            if ( ($this->isActivated()) && (Navigation::hasItem("/course")) )
                if (VeranstaltungPermission::hasDozentRecht())
                    $this->setAdminNavigation();
                elseif (VeranstaltungPermission::hasTutorRecht())
                    $this->setTutorNavigation();
                elseif (VeranstaltungPermission::hasAutorRecht())
                    $this->setAutorNavigation();

        }


        /** Navigation für Autoren, sie sehen nur die Navigation, wenn 
         * für die Veranstaltung Übungen vorhanden sind
         **/
        private function setAutorNavigation()
        {
            if (!Veranstaltung::get())
                return;

            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkte"), PluginEngine::GetURL($this, array(), "show")) );
        }


        /** Administratoren (Dozenten) sehen die Verwaltung generell **/
        private function setAdminNavigation()
        {
            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkteverwaltung"), PluginEngine::GetURL($this, array(), "admin")) );

            if (!Veranstaltung::get())
                return;

            Navigation::addItem( "/course/punkteverwaltung/editsettings", new AutoNavigation(_("globale Einstellungen"), PluginEngine::GetURL($this, array(), "admin")) );
            Navigation::addItem( "/course/punkteverwaltung/bonuspunkte", new AutoNavigation(_("Bonuspunkte"), PluginEngine::GetURL($this, array(), "bonuspunkte")) );
            Navigation::addItem( "/course/punkteverwaltung/statistik", new AutoNavigation(_("Auswertungen"), PluginEngine::GetURL($this, array(), "auswertung")) );
            Navigation::addItem( "/course/punkteverwaltung/zulassung", new AutoNavigation(_("manuelle Zulassung"), PluginEngine::GetURL($this, array(), "zulassung")) );

            $loVeranstaltung = Veranstaltung::get();
            if ($loVeranstaltung)
            {
                if (!$loVeranstaltung->isClosed())
                    Navigation::addItem( "/course/punkteverwaltung/createuebung", new AutoNavigation(_("neue Übung erzeugen"), PluginEngine::GetURL($this, array(), "admin/createuebung")) );

                // ggf einmal Übung als Navigation + eine Subnavigation für jede einzelne Übung (Tab Struktur)
                $laUebung = $loVeranstaltung->uebungen();
                if ($laUebung)
                {
                    Navigation::addItem( "/course/punkteverwaltung/updateteilnehmer", new AutoNavigation(_("Teilnehmer in Übung(en) aktualisieren"), PluginEngine::GetURL($this, array(), "admin/updateteilnehmer")) );
                    foreach($laUebung as $ueb)
                        Navigation::addItem( "/course/punkteverwaltung/edituebung".$ueb->id(), new AutoNavigation($ueb->name(), PluginEngine::GetURL($this, array("ueid" => $ueb->id()), "uebung")) );
                }
            }
        }


        /** Tutoren sehen nur die einzelnen Übungen **/
        private function setTutorNavigation()
        {
            $loVeranstaltung = Veranstaltung::get();

            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkteverwaltung"), PluginEngine::GetURL($this, array(), "uebung")) );
            if ( (!$loVeranstaltung) || (!$loVeranstaltung->uebungen()) )
                return;

            foreach($laUebungen as $ueb)
                Navigation::addItem( "/course/punkteverwaltung/edituebung".$ueb->id(), new AutoNavigation($ueb->name(), PluginEngine::GetURL($this, array("ueid" => $ueb->id()), "uebung")) );

        }



        function initialize () { }


        /** @note dritten Parameter durch StudIP 2.5 eingefügt, aber mit Defaultvalue versehen,
         * damit Abwärtskompatibilität erhalten bleibt
        **/
        function getIconNavigation($course_id, $last_visit, $user_id = null) { }

        /** @note Methode wurde in StudIP 2.5 eingefügt **/
        function getTabNavigation ($course_id) {}

        /** @note Methode wurde in StudIP 2.5 eingefügt **/
        function getNotificationObjects ($course_id, $since, $user_id) {}

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
                spl_autoload_register(function ($class) { @include_once __DIR__ . $class . ".php"; });
        }
    }
