<?php
require_once("db.php");
require_once("ipoClass.php");
require_once("sessionAuthentication.php");

define("PARAM_OPERATION", "operation");
define("PARAM_SESSIONID", "sessionid");

define("PARAM_LATFROM", "latfrom");
define("PARAM_LONFROM", "lonfrom");
define("PARAM_LATTO", "latto");
define("PARAM_LONTO", "lonto");
define("PARAM_IPOID", "ipoid");
define("PARAM_RADIUS", "radius");

error_reporting(E_ALL);
ini_set("display_errors", 1);

/*
 * Legenerálja a megadott paraméterek által definiált látványosságokat
 */
if($_REQUEST[PARAM_OPERATION] == 0) {
    if(isset($_REQUEST[PARAM_LATFROM]) && isset($_REQUEST[PARAM_LONFROM]) && isset($_REQUEST[PARAM_LATTO]) && isset($_REQUEST[PARAM_LONTO]) && isset($_REQUEST[PARAM_RADIUS])) {
        header('Content-Type: text/html; charset=utf-8');
        $ipo = new IPO($db,0);
        $ipo->generateIPOs($_REQUEST[PARAM_LATFROM],$_REQUEST[PARAM_LONFROM],$_REQUEST[PARAM_LATTO],$_REQUEST[PARAM_LONTO],$_REQUEST[PARAM_RADIUS]);
    }
}
//authentikáció sessionid-re
if(isset($_REQUEST[PARAM_SESSIONID])) {
    $userid = authenticate($db,$_REQUEST[PARAM_SESSIONID]);
    if($userid == null) {
        return;
    }
}
else return;
if(isset($_REQUEST[PARAM_OPERATION])) {
    /*
     * Lekérdezi a felhasználó által látott látványosságokat a megadott keretben
     */
    if($_REQUEST[PARAM_OPERATION] == 1) {
        if(isset($_REQUEST[PARAM_LATFROM]) && isset($_REQUEST[PARAM_LONFROM]) && isset($_REQUEST[PARAM_LATTO]) && isset($_REQUEST[PARAM_LONTO])) {
            $ipo = new IPO($db,$userid);
            echo json_encode($ipo->getUserIPOs($_REQUEST[PARAM_LATFROM],$_REQUEST[PARAM_LONFROM],$_REQUEST[PARAM_LATTO],$_REQUEST[PARAM_LONTO]));
        }
    }
    /*
     * Felfedezi a látványosságot a felhasználó
     */
    else if($_REQUEST[PARAM_OPERATION] == 2) {
        if(isset($_REQUEST[PARAM_IPOID])) {
            $ipo = new IPO($db,$userid);
            $ipo->userExamineIPO($_REQUEST[PARAM_IPOID]);
        }
    }
}
?>