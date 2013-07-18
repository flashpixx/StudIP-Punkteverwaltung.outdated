<?php

    require_once( dirname(dirname(__DIR__)) . "/matrikelnummer/factory.class.php");

    $loMatrikelNr = MatrikelNummerFactory::get();

    echo "test : ".$answer."<br/>";
    echo "user id : ".$userseminar->id."<br/>";
    echo "matrikelnr : ".$loMatrikelNr->get($userseminar->id)."<br/>";
    echo "<pre>".print_r($userseminar, true)."</pre>";

?>