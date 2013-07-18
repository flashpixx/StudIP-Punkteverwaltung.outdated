<?php

    require_once("../../matrikelnummer/factory.class.php");

    $loMatrikelNr = MatrikelNummerFactory::get();

    echo "test : ".$answer."<br/>";
    echo "user id : ".$currentuser->auth["uid"];
    echo "matrikelnr : ".$loMatrikelNr->get($currentuser->auth["uid"]);
    echo "<pre>".print_r($currentuser, true)."</pre>";

?>