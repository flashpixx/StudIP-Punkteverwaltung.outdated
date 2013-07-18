<?php

    require_once( dirname(dirname(__DIR__)) . "/matrikelnummer/factory.class.php");

    $loMatrikelNr = MatrikelNummerFactory::get();

    echo "test : ".$answer."<br/>";
    echo "user id : ".$userseminar->id;
    echo "matrikelnr : ".$loMatrikelNr->get($userseminar->id);
    echo "<pre>".print_r($userseminar, true)."</pre>";

?>