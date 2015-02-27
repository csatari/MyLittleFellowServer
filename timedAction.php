<?php
require_once("db.php");
require_once("timedActionClass.php");
require_once("sessionAuthentication.php");

define("PARAM_OPERATION", "operation");
define("PARAM_SESSIONID", "sessionid");
define("PARAM_TIME", "time");
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
     * Lekérdezi az időigényes interakció adatait
     */
    if($_REQUEST[PARAM_OPERATION] == 0) { //get data
        $timedAction = new TimedAction($db,$userid);
        $res = $timedAction->getData();
        echo json_encode($res);
    }
    /*
     * Az időigényes interakció végén kell meghívni
     */
    else if($_REQUEST[PARAM_OPERATION] == 1) { //karbantartás - az idő végén kell meghívni
        $timedAction = new TimedAction($db,$userid);
        $res = $timedAction->refreshData();
        echo json_encode($res);
    }
    /*
     * Leállítja az interakciót
     */
    else if($_REQUEST[PARAM_OPERATION] == 2) { //timed action törlése
        $timedAction = new TimedAction($db,$userid);
        $timedAction->stopAction();
    }

}
?>