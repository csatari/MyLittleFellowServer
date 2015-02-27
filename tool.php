<?php
require_once("db.php");
require_once('toolClass.php');
require_once('userDetailsClass.php');
require_once('sessionAuthentication.php');


//paraméterek nevei
define("PARAM_ID", "id");
define("PARAM_OPERATION", "operation");
define("PARAM_SESSIONID", "sessionid");

define("PARAM_TOOLTYPE", "tooltype");
//authentikáció sessionid-re
$userid = authenticate($db,$_REQUEST[PARAM_SESSIONID]);
if($userid == null) {
    return;
}
//műveletek
if(isset($_REQUEST[PARAM_OPERATION])) {
    /*
     * Lekérdezi az összes kifejleszthető eszközt
     */
    if($_REQUEST[PARAM_OPERATION] == 0) { //get all developable tools
        $tools = new Tool($db,$userid);
        echo json_encode($tools->getAllDevelopableTools());
    }
    /*
     * Elkezdi kifejleszteni a megadott eszközt
     */
    else if($_REQUEST[PARAM_OPERATION] == 1) { //develop tool, timed
        if(isset($_REQUEST[PARAM_TOOLTYPE])) {
            $tools = new Tool($db,$userid);
            echo json_encode($tools->developTool($_REQUEST[PARAM_TOOLTYPE]));
        }
    }
}
else {
    echo "Error 1";
}


?>