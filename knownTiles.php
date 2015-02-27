<?php
require_once("db.php");
require_once("knownTilesClass.php");
require_once("timedActionClass.php");
require_once("sessionAuthentication.php");


define("PARAM_OPERATION", "operation");
define("PARAM_SESSIONID", "sessionid");
define("PARAM_TILEID", "tileid");
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
     * Megvizsgál egy területet
     */
	if($_REQUEST[PARAM_OPERATION] == 0) { //examine tile
		if(isset($_REQUEST[PARAM_TILEID])) {
			$knownTiles = new KnownTiles($db,$userid);
            $result = $knownTiles->examineTile($_REQUEST[PARAM_TILEID]);
            echo json_encode($result);
		}
	}
    /*
     * Lekérdezi a területről, hogy a felhasználó megvizsgálta-e már
     */
	else if($_REQUEST[PARAM_OPERATION] == 1) { //is tile examined
		if(isset($_REQUEST[PARAM_TILEID])) {
			$knownTiles = new KnownTiles($db,$userid);
			$examined = $knownTiles->isExamined($_REQUEST[PARAM_TILEID]);
            $result = array();
            $result[$GLOBALS["table_knowntiles_examined"]] = $examined;
            echo json_encode($result);
		}
	}
}
?>