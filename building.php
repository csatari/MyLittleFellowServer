<?php
require_once("db.php");
require_once("buildingClass.php");
require_once("timedActionClass.php");
require_once("sessionAuthentication.php");


define("PARAM_OPERATION", "operation");
define("PARAM_SESSIONID", "sessionid");
define("PARAM_TILEID", "tileid");
define("PARAM_TILESLICE", "tileslice");
define("PARAM_BUILDINGTYPE", "buildingtype");
define("PARAM_BUILDINGLEVEL", "buildinglevel");
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
     * Elindít egy építkezést
     */
    if($_REQUEST[PARAM_OPERATION] == 0) { //build
        if(isset($_REQUEST[PARAM_TILEID]) && isset($_REQUEST[PARAM_TILESLICE]) && isset($_REQUEST[PARAM_BUILDINGTYPE])) {
            $building = new Building($db,$userid);
            $result = $building->buildBuilding($_REQUEST[PARAM_TILEID],$_REQUEST[PARAM_TILESLICE],$_REQUEST[PARAM_BUILDINGTYPE]);
            echo json_encode($result);
        }
    }
    /*
     * Lekérdezi a megadott területen építhető épületek listáját
     */
    if($_REQUEST[PARAM_OPERATION] == 1) { //get buildables
        if(isset($_REQUEST[PARAM_TILEID]) && isset($_REQUEST[PARAM_TILESLICE])) {
            $building = new Building($db,$userid);
            echo json_encode($building->getBuildableList($_REQUEST[PARAM_TILEID],$_REQUEST[PARAM_TILESLICE]));
        }
    }
    /*
     * Lekérdezi a felhasznáó által épített összes épületet.
     */
    if($_REQUEST[PARAM_OPERATION] == 2) { //get all user building
        $building = new Building($db,$userid);
        echo json_encode($building->getAllBuildingsByUser());
    }
    /*
     * Lekérdezi az összes kifejleszthető épülethez szükséges nyersanyagokat
     */
    if($_REQUEST[PARAM_OPERATION] == 3) { //get all developable building receipts
        $building = new Building($db,$userid);
        echo json_encode($building->getAllDevelopableBuildings());
    }
    /*
     * kifejleszt egy épületet
     */
    if($_REQUEST[PARAM_OPERATION] == 4) { //develop building, timed
        if(isset($_REQUEST[PARAM_BUILDINGTYPE]) && isset($_REQUEST[PARAM_BUILDINGLEVEL])) {
            $building = new Building($db,$userid);
            echo json_encode($building->developBuilding($_REQUEST[PARAM_BUILDINGTYPE],$_REQUEST[PARAM_BUILDINGLEVEL]));
        }
    }

}
?>