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
    require_once("sys/tools.class.php");
    require_once("sys/veranstaltungpermission.class.php");
    require_once("sys/veranstaltung/veranstaltung.class.php");


    
    //ini_set("display_errors", TRUE);
    //error_reporting(E_ALL);
    //error_reporting(E_ALL ^ E_NOTICE);

    // http://docs.studip.de/develop/Entwickler/HowToFormulars
    // http://docs.studip.de/develop/Entwickler/HowToHTML
    // http://studip.tleilax.de/plugins/generator/
    // http://docs.studip.de/api
    // http://docs.studip.de/develop/Entwickler/Migrations
    // http://docs.studip.de/develop/Entwickler/HowToGettext / find . -type f -name "*.php" | xargs xgettext -o translation.mo -L php --keyword=_ --from-code=iso-8859-1
    


    /** Basisklasse für das Plugin nach dem Trails-Framework
     * @see http://docs.studip.de/develop/Entwickler/Trails
     * @see http://docs.studip.de/develop/Entwickler/Navigation
     **/
    class punkteverwaltung extends StudIPPlugin implements StandardPlugin, SystemPlugin
    {

        /** Ctor des Plugins zur Erzeugung der Navigation
         * @todo bindtextdomain einfügen, siehe http://php.net/manual/en/function.gettext.php
         **/
        function __construct()
        {
            parent::__construct();
            
            // setzt die Plugin-Instance in das zentrale Storage, um in Exceptions auf das Objekt zugreifen zu können
            Tools::setStorage("plugin", $this);

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
        
            if (VeranstaltungPermission::hasRootRecht())
                $this->setRootNavigation();

        }


        /** Navigation für Autoren, sie sehen nur die Navigation, wenn 
         * für die Veranstaltung Übungen vorhanden sind
         **/
        private function setAutorNavigation()
        {
            if (!Veranstaltung::get())
                return;

            Navigation::addItem( "/course/punkteverwaltung",              new Navigation(_("Punkte"),             PluginEngine::GetURL($this, array(), "show")) );
            Navigation::addItem( "/course/punkteverwaltung/show",         new AutoNavigation(_("Bewertungen"),    PluginEngine::GetURL($this, array(), "show")) );
            Navigation::addItem( "/course/punkteverwaltung/studiengang",  new AutoNavigation(_("Studiengang"),    PluginEngine::GetURL($this, array(), "show/studiengang")) );
            Navigation::addItem( "/course/punkteverwaltung/hilfe",        new AutoNavigation(_("Hilfe"),          PluginEngine::GetURL($this, array(), "hilfe")) );
        }


        /** Administratoren (Dozenten) sehen die Verwaltung generell **/
        private function setAdminNavigation()
        {
            Navigation::addItem( "/course/punkteverwaltung",                        new Navigation(_("Punkteverwaltung"),                               PluginEngine::GetURL($this, array(), "admin")) );

            $loVeranstaltung = Veranstaltung::get();
            if (!is_object($loVeranstaltung))
                return;

            Navigation::addItem( "/course/punkteverwaltung/editsettings",           new AutoNavigation(_("globale Einstellungen"),                      PluginEngine::GetURL($this, array(), "admin")) );
            Navigation::addItem( "/course/punkteverwaltung/bonuspunkte",            new AutoNavigation(_("Bonuspunkte"),                                PluginEngine::GetURL($this, array(), "bonuspunkte")) );
            Navigation::addItem( "/course/punkteverwaltung/statistik",              new AutoNavigation(_("Auswertungen"),                               PluginEngine::GetURL($this, array(), "auswertung")) );
            Navigation::addItem( "/course/punkteverwaltung/zulassung",              new AutoNavigation(_("manuelle Zulassung"),                         PluginEngine::GetURL($this, array(), "zulassung")) );
            Navigation::addItem( "/course/punkteverwaltung/teilnehmer",             new AutoNavigation(_("Teilnehmer verwalten"),                       PluginEngine::GetURL($this, array(), "admin/teilnehmer")) );
            Navigation::addItem( "/course/punkteverwaltung/createuebung",           new AutoNavigation(_("neue Übung erzeugen"),                        PluginEngine::GetURL($this, array(), "admin/createuebung")) );
            Navigation::addItem( "/course/punkteverwaltung/uebung",                 new AutoNavigation(_("Übungen"),                                    PluginEngine::GetURL($this, array(), "uebung")) );
            Navigation::addItem( "/course/punkteverwaltung/hilfe",                  new AutoNavigation(_("Hilfe"),                                      PluginEngine::GetURL($this, array(), "hilfe")) );
        }


        /** Tutoren sehen nur die einzelnen Übungen **/
        private function setTutorNavigation()
        {
            $loVeranstaltung = Veranstaltung::get();
            if (!is_object($loVeranstaltung))
                return;
        
            Navigation::addItem( "/course/punkteverwaltung",        new Navigation(_("Punkteverwaltung"),   PluginEngine::GetURL($this, array(), "uebung")) );
            Navigation::addItem( "/course/punkteverwaltung/uebung", new AutoNavigation(_("Übungen"),        PluginEngine::GetURL($this, array(), "uebung")) );
            Navigation::addItem( "/course/punkteverwaltung/hilfe",  new AutoNavigation(_("Hilfe"),          PluginEngine::GetURL($this, array(), "hilfe")) );
        }
    
    
        /** Root Navigation
         * @warn addItem muss direkt vom Root-Element sein, da sonst das Menü nicht angezeigt wird
         **/
        private function setRootNavigation()
        {
            $loNavigation = new AutoNavigation(_("Punkteverwaltung"));
            $loNavigation->setURL(PluginEngine::GetURL($this, array(), "root"));
            $loNavigation->setImage(Assets::image_path("blank.gif"));
            Navigation::addItem("/punkteverwaltung", $loNavigation);
            //Navigation::activateItem("/punkteverwaltung");
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
