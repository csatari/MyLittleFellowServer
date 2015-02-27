<?php
require_once("db.php");
require_once('openstreetmap.php');
require_once("userClass.php");
require_once("userDetailsClass.php");
require_once('tileClass.php');
require_once("knownTilesClass.php");
require_once('sessionAuthentication.php');

/*
paraméterek: sessionid, operation (add:0,get:1,set:2,[delete:3]), id, latitude, longitude, neighbour, resources, type
	lehetőségek:
	sessionid, add, latitude, longitude (új tile hozzáadása) X
	sessionid, get, id (id szerinti lekérdezés) X
	sessionid, get, latitude, longitude (koordináta szerinti lekérdezés) X
	sessionid, set, id, resources
	
típus legenerálása új tile létrehozásakor
sessionid alapján authentikálás
*/

//paraméterek nevei
define("PARAM_ID", "id");
define("PARAM_OPERATION", "operation");
define("PARAM_LAT", "latitude");
define("PARAM_LONG", "longitude");
define("PARAM_LAT_TO", "latitudeTo");
define("PARAM_LONG_TO", "longitudeTo");
define("PARAM_RADIUS", "radius");
define("PARAM_SESSIONID", "sessionid");
define("PARAM_RES1", "resource1");
define("PARAM_RES2", "resource2");
define("PARAM_RES3", "resource3");

/*
 * Víz generálása
 */
if($_REQUEST[PARAM_OPERATION] == 2) { //generate water
    if(!(isset($_REQUEST[PARAM_LAT]) && isset($_REQUEST[PARAM_LONG]) && isset($_REQUEST[PARAM_LAT_TO]) && isset($_REQUEST[PARAM_LONG_TO]) && isset($_REQUEST[PARAM_RADIUS]))) {
        echo "Error 2";
    }
    //a from koordinátáktól végigmegy a to koordinátákig és megvizsgálja a tavakat. Ha talál, akkor létrehoz egy tile sort, tó típussal
    else { // PARAM_OPERATION = 2 && PARAM_LAT == latitudeFrom && PARAM_LONG == longitudeFrom && PARAM_LAT_TO == latitudeTo && PARAM_LONG_TO == longitudeTo
        echo "A vizezés megkezdése...";
        $tile = new Tile($db);
        $latfrom = $tile->getCenterLat($_REQUEST[PARAM_LAT]);
        $longfrom = $tile->getCenterLong($_REQUEST[PARAM_LONG]);
        //$tile->generateBiome($latfrom,$longfrom);
        $latto = $tile->getCenterLat($_REQUEST[PARAM_LAT_TO]);
        $longto = $tile->getCenterLong($_REQUEST[PARAM_LONG_TO]);
        $tile->generateWater($latfrom,$longfrom,$latto,$longto,$_REQUEST[PARAM_RADIUS]);
        return;
    }
}


//authentikáció sessionid-re
$userid = authenticate($db,$_REQUEST[PARAM_SESSIONID]);
if($userid == null) {
	return;
}
//műveletek
if(isset($_REQUEST[PARAM_OPERATION])) {
    /*
     * Frissíti a megadott területet a paraméterekben megadott nyersanyagokkal
     */
	if($_REQUEST[PARAM_OPERATION] == 0) { //update
        if(isset($_REQUEST[PARAM_ID]) && isset($_REQUEST[PARAM_RES1]) && isset($_REQUEST[PARAM_RES2]) && isset($_REQUEST[PARAM_RES3])) {
            $tile = new Tile($db);
            $tile->setTileResourceByID($_REQUEST[PARAM_ID],$_REQUEST[PARAM_RES1],$_REQUEST[PARAM_RES2],$_REQUEST[PARAM_RES3]);
        }
	}
    /*
     * Lekérdezi a paraméterekben definiált terület információit
     */
	else if($_REQUEST[PARAM_OPERATION] == 1) { //get
		if(isset($_REQUEST[PARAM_ID])) { // PARAM_OPERATION = 1 && PARAM_ID == tileid
			$id = $_REQUEST[PARAM_ID];
			$tile = new Tile($db);
            $tile->growTile($id);
			$result=$tile->getTileById($id);
            $knownTiles = new KnownTiles($db,$userid);
            $examined = $knownTiles->isExamined($_REQUEST[PARAM_ID]);
            $user = new User($db,$userid);
            $userdetails = new UserDetails($db,$userid);
            $payingTax = $userdetails->getTaxForUser($userid,$id);
            $result[$GLOBALS["other_tile_population"]] = $userdetails->getTilePopulation($id);
            $result[$GLOBALS["table_tile_tax"]] = $payingTax;
            $result[$GLOBALS["table_tile_owner"]] = $user->getUserName($result[$GLOBALS["table_tile_owner"]]);
            $result[$GLOBALS["table_knowntiles_examined"]] = $examined;
			echo json_encode($result);
		}
		/*else if(isset($_REQUEST[PARAM_LAT]) && isset($_REQUEST[PARAM_LONG])) { // PARAM_OPERATION = 1 && PARAM_LAT == latitude && PARAM_LONG == longitude
			$tile = new Tile($db);
			//beállítja a középpont koordinátákat
			$tile->setCenterLat($_REQUEST[PARAM_LAT]);
			$tile->setCenterLong($_REQUEST[PARAM_LONG]);
			//lekérdezi, hogy van-e már ilyen
			$id = $tile->addNewTile($tile->latitude,$tile->longitude);
			$result[]=$tile->getTileById($id);
			echo json_encode($result);
		}*/
		//lekérdez minden tile-t
		/*else {
			$tile = new Tile($db);
			$allTiles = $tile->getAllTiles();
			echo json_encode($allTiles);
		}*/
	}
    /*
     * Lekérdezi egy területről, hogy a felhasználó-e a földesúr
     */
    else if($_REQUEST[PARAM_OPERATION] == 3) { //am i owner
        if(isset($_REQUEST[PARAM_ID])) {
            $tile = new Tile($db);
            $amiowner = $tile->amIOwner($_REQUEST[PARAM_ID],$userid);
            echo json_encode($amiowner);
        }
    }
}
else {
	echo "Error 1";
}


?>