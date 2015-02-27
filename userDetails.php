<?php
require_once("db.php");
require_once("userDetailsClass.php");
require_once("sessionAuthentication.php");

define("PARAM_OPERATION", "operation");
define("PARAM_SESSIONID", "sessionid");
define("PARAM_LATITUDE", "latitude");
define("PARAM_LONGITUDE", "longitude");
define("PARAM_PLACEID", "placeid");
define("PARAM_HOMEID", "homeid");
define("PARAM_STORAGELIMIT", "storagelimit");
define("PARAM_TYPE", "type");
define("PARAM_AMOUNT", "amount");
define("PARAM_TIME","time");
define("PARAM_TILEID","tileid");
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
     * Felfedez egy új területet
     */
	if($_REQUEST[PARAM_OPERATION] == 0) { //add, itt discover
		if(isset($_REQUEST[PARAM_LATITUDE]) && isset($_REQUEST[PARAM_LONGITUDE])) {
			$userdetails = new UserDetails($db,$userid);
			echo json_encode($userdetails->discoverTile($_REQUEST[PARAM_LATITUDE],$_REQUEST[PARAM_LONGITUDE]));
		}
	}
    /*
     * Lekérdezi a felhasználó által felfedezett összes területet
     */
	else if($_REQUEST[PARAM_OPERATION] == 1) { //get all tiles
		//get all
		$userdetails = new UserDetails($db,$userid);
		echo json_encode($userdetails->getAllDiscoveredTiles());
	}
    /*
     * Lekérdezi, hogy melyik területen áll a karakter
     */
	else if($_REQUEST[PARAM_OPERATION] == 2) { //get placeid
		$userdetails = new UserDetails($db,$userid);
		echo json_encode($userdetails->getUserPlaceId());
	}

    /*else if($_REQUEST[PARAM_OPERATION] == 3) { //teszt
		$userdetails = new UserDetails($db,$userid);
		echo $userdetails->getAllStorage($userid);
		//print_r( $userdetails->getAllDiscoveredTiles()); 
		//echo $userdetails->setStorageResource(2,4);
	}*/

	/*else if($_REQUEST[PARAM_OPERATION] == 4) { //set placeid, elutazás
		if(isset($_REQUEST[PARAM_PLACEID])) {
			$userdetails = new UserDetails($db,$userid);
			$userdetails->setUserPlaceId($_REQUEST[PARAM_PLACEID]);
		}
	}*/
    /*
     * Lekérdezi a karakter lakhelyét
     */
    else if($_REQUEST[PARAM_OPERATION] == 3) { //get homeid
        $userdetails = new UserDetails($db,$userid);
        $home = $userdetails->getHomeId();
        $result = array();
        $result[$GLOBALS["table_userdetails_homeid"]] = $home;
        echo json_encode($result);
    }
    /*else if($_REQUEST[PARAM_OPERATION] == 6) { //set homeid
        if(isset($_REQUEST[PARAM_HOMEID])) {
            $userdetails = new UserDetails($db,$userid);
            $userdetails->setHomeId($_REQUEST[PARAM_HOMEID]);
        }
    }*/

    /*
     * Lekérdezi a karakter raktárának méretét
     */
    else if($_REQUEST[PARAM_OPERATION] == 4) { //get storagelimit
        $userdetails = new UserDetails($db,$userid);
        $res = $userdetails->getStorageLimit();
        $result = array();
        $result[$GLOBALS["table_userdetails_storagelimit"]] = $res;
        echo json_encode($result);
    }
    /*else if($_REQUEST[PARAM_OPERATION] == 8) { //set storagelimit
        if(isset($_REQUEST[PARAM_STORAGELIMIT])) {
            $userdetails = new UserDetails($db,$userid);
            $userdetails->setStorageLimit($_REQUEST[PARAM_STORAGELIMIT]);
        }
    }*/
    /*
     * Lekérdezi a karakter raktárának tartalmát
     */
    else if($_REQUEST[PARAM_OPERATION] == 5) { //get all storage
        $userdetails = new UserDetails($db,$userid);
        echo $userdetails->getAllStorage($userid);
    }
    else if($_REQUEST[PARAM_OPERATION] == 6) { //set resource
        if(isset($_REQUEST[PARAM_TYPE]) && isset($_REQUEST[PARAM_AMOUNT])) {
            $userdetails = new UserDetails($db,$userid);
            $userdetails->setStorageResource($userid,$_REQUEST[PARAM_TYPE],$_REQUEST[PARAM_AMOUNT]);
        }
    }
    /*else if($_REQUEST[PARAM_OPERATION] == 11) { //get resource
        if(isset($_REQUEST[PARAM_TYPE])) {
            $userdetails = new UserDetails($db,$userid);
            $res =  $userdetails->getStorageResource($userid,$_REQUEST[PARAM_TYPE]);
            $result = array();
            $result[$GLOBALS["table_userdetails_type"]] = $res;
            echo json_encode($result);
        }
    }*/
    /*
     * Beállítja, hogy melyik területen legyen a karakter (elutazás)
     */
    else if($_REQUEST[PARAM_OPERATION] == 7) { //set placeid, elutazás
        if(isset($_REQUEST[PARAM_PLACEID])) {
            $userdetails = new UserDetails($db,$userid);
            $result = $userdetails->travelToPlace($_REQUEST[PARAM_PLACEID]);
            echo json_encode($result);
        }
    }
    /*
     * Elindítja a területre letelepedést
     */
    else if($_REQUEST[PARAM_OPERATION] == 8) { //set homeid, timed
        if(isset($_REQUEST[PARAM_HOMEID])) {
            $userdetails = new UserDetails($db,$userid);
            $result = $userdetails->settleDown($_REQUEST[PARAM_HOMEID]);
            echo json_encode($result);
        }
    }
    /*
     * Lekérdezi, hogy tele van-e a raktár
     */
    else if($_REQUEST[PARAM_OPERATION] == 9) { //is storage full
        $userdetails = new UserDetails($db,$userid);
        $res = $userdetails->isStorageFull($userid);
        $result = array();
        $full = 0;
        if($res) {
            $full = 1;
        }
        $result[$GLOBALS["table_userdetails_storagelimit"]] = $full;
        echo json_encode($result);
    }
    /*
     * Az első helyen lévő nyersanyag kitermelése
     */
    else if($_REQUEST[PARAM_OPERATION] == 10) { //set resource 1, timed
        if(isset($_REQUEST[PARAM_TYPE]) && isset($_REQUEST[PARAM_PLACEID])) {
            $userdetails = new UserDetails($db,$userid);
            $result = $userdetails->mineResource1($_REQUEST[PARAM_TYPE],$_REQUEST[PARAM_PLACEID]);
            echo json_encode($result);
        }
    }
    /*
     * A második helyen lévő nyersanyag kitermelése
     */
    else if($_REQUEST[PARAM_OPERATION] == 11) { //set resource 2, timed
        if(isset($_REQUEST[PARAM_TYPE]) && isset($_REQUEST[PARAM_PLACEID])) {
            $userdetails = new UserDetails($db,$userid);
            $result = $userdetails->mineResource2($_REQUEST[PARAM_TYPE],$_REQUEST[PARAM_PLACEID]);
            echo json_encode($result);
        }
    }
    /*
     * A harmadik helyen lévő nyersanyag kitermelése
     */
    else if($_REQUEST[PARAM_OPERATION] == 12) { //set resource 3, timed
        if(isset($_REQUEST[PARAM_TYPE]) && isset($_REQUEST[PARAM_PLACEID])) {
            $userdetails = new UserDetails($db,$userid);
            $result = $userdetails->mineResource3($_REQUEST[PARAM_TYPE],$_REQUEST[PARAM_PLACEID]);
            echo json_encode($result);
        }
    }
    /*
     * Lekérdezi a karakter által beállított adókat
     */
    else if($_REQUEST[PARAM_OPERATION] == 13) { //get tax
        $userdetails = new UserDetails($db,$userid);
        $result = array();
        $result[$GLOBALS["table_userdetails_tax"]] = $userdetails->getTaxResources($userid);
        echo json_encode($result);
    }
    /*
     * Beállítja az adóban a paraméterben megadott nyersanyagot
     */
    else if($_REQUEST[PARAM_OPERATION] == 14) { //set tax
        if(isset($_REQUEST[PARAM_TYPE]) && isset($_REQUEST[PARAM_AMOUNT])) {
            $userdetails = new UserDetails($db,$userid);
            $userdetails->setTaxResource($_REQUEST[PARAM_TYPE],$_REQUEST[PARAM_AMOUNT]);
        }
    }
    /*else if($_REQUEST[PARAM_OPERATION] == 20) { //reset tax
        $userdetails = new UserDetails($db,$userid);
        $result = $userdetails->resetTaxResources();
    }*/
    /*
     * Kifizeti a karakter az adót, ha az adott területre lépett.
     */
    else if($_REQUEST[PARAM_OPERATION] == 15) { //pay tax
        if(isset($_REQUEST[PARAM_TILEID])) {
            $userdetails = new UserDetails($db,$userid);
            $userdetails->payTax($userid,$_REQUEST[PARAM_TILEID]);
        }
    }
    /*
     * Lekéri a karakter intelligenciapontját
     */
    else if($_REQUEST[PARAM_OPERATION] == 16) { //get ipo
        $userdetails = new UserDetails($db,$userid);
        $res = $userdetails->getIpo();
        $result = array();
        $result[$GLOBALS["table_userdetails_ipo"]] = $res;
        echo json_encode($result);
    }

    /*else if($_REQUEST[PARAM_OPERATION] == 100) { //set resource 3, timed
        $userdetails = new UserDetails($db,$userid);
        echo $userdetails->isBuildingDeveloped(2,1)."<br>";
        echo $userdetails->isBuildingDeveloped(2,2)."<br>";
        echo $userdetails->isBuildingDeveloped(2,3)."<br>";
        echo $userdetails->isBuildingDeveloped(3,1)."<br>";
        //echo json_encode($result);
    }*/



}
?>