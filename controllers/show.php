<?php
    
    class ShowController extends StudipController
    {

        function before_filter( &$action, &$args )
        {
            $this->set_layout($GLOBALS["template_factory"]->open("layouts/base_without_infobox"));
            $this->userseminar = $GLOBALS["user"];
            // PageLayout::setTitle("");
        }

        
        function index_action()
        {
            $this->answer = "Yes";
        }

        
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