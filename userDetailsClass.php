<?php
require_once("tileClass.php");
require_once("knownTilesClass.php");
require_once("timedActionClass.php");
class UserDetails {
	private $userid;
	private $db;
	private $knownTiles;

    public static $WOOD = 1;
    public static $TWIG = 2;
    public static $MEAT = 3;
    public static $BERRY = 4;
    public static $STONE = 5;
    public static $COAL = 6;
    public static $IRON = 7;
    public static $GOLD = 8;
    public static $FISH = 9;

    public static $MAX_TYPE = 9;

    public static $PAYMENT_TIME = 604800; //1 hét
	
	/*private $table_title = "userdetails";
	private $table_userid = "userid";
	private $table_knowntiles = "knowntiles";*/
	
	function __construct($db,$userid) {
		$this->db = $db;
		$this->userid = $userid;
		$this->addToTableIfNotExists();
		$this->knownTiles = new KnownTiles($db,$userid);
	}
	//Visszaadja egy tömbben az összes felhasználó által felfedezett tile elemet
	function getAllDiscoveredTiles() {
		$knownTileArray = $this->getAllDiscoveredTilesRaw();
		$tile = new Tile($this->db);
		$result = null;
		foreach($knownTileArray as $oneTile) {
			$temp = $tile->getTileById($oneTile);
			if($temp != null) {
                $temp[$GLOBALS["table_tile_owner"]] = $this->getUserName($temp[$GLOBALS["table_tile_owner"]]);
				$temp["examined"] = $this->knownTiles->isExamined($oneTile);
				$result[]=$temp;
			}
		}
		return $result;
	}
	//Visszaadja egy tömbben az összes felhasználó által felfedezett tile elemek ID-jét
	function getAllDiscoveredTilesRaw() {
		$knownTileArray = $this->knownTiles->getAll();
		return $knownTileArray;
	}
	/*amikor felfedez egy koordinátát, akkor kell meghívni. Hozzáadja a tile táblához a tile-t, ha még nem létezett, és a knowntiles-ba berakja*/
	function discoverTile($lat,$long) {
		$tile = new Tile($this->db);
		$id = $tile->addNewTile($lat,$long);
		$this->addTileToKnownTile($id);
		$this->setUserPlaceId($id);
		return $tile->getTileById($id);
	}
	/*hozzáadja a megadott tile id-jét a knowntiles-hoz (a knowntiles végére beírja)*/
	function addTileToKnownTile($tileid) {
		$this->knownTiles->add($tileid);
	}
	/*hozzáadja a userid-t a táblához, üresen, ha még nem volt benne*/
	function addToTableIfNotExists() {
		if(!$this->isUserExists()) {
			$sql = "INSERT INTO ".$GLOBALS["table_userdetails_title"]." (".$GLOBALS["table_userdetails_userid"].") VALUES (?)";
			$stmt = $this->db->prepare($sql);
			$stmt->execute(array($this->userid));
			$stmt->fetch(PDO::FETCH_ASSOC);
		}
	}
	/*Megnézi, hogy az adott userid szerepel-e már a táblában*/
	function isUserExists() {
		$sql = "SELECT * FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->userid));
		$stmt->fetch(PDO::FETCH_ASSOC);
		$count = $stmt->rowCount();
		return ($count == 0 ? false : true);
	}
    /*Lekérdezi egy user nevét id által*/
    function getUserName($userid) {
        $sql = "SELECT ".$GLOBALS["table_user_username"]." FROM ".$GLOBALS["table_user_title"]." WHERE ".$GLOBALS["table_user_id"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        if($resultArray == null) {
            return "";
        }
        return $resultArray[$GLOBALS["table_user_username"]];
    }
	/*Beállítja a usernek a tartózkodási helyét*/
	function setUserPlaceId($id) {
		$sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_placeid"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($id,$this->userid));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}
	/*Lekéri a usernek a tartózkodási helyét*/
	function getUserPlaceId() {
		$sql = "SELECT ".$GLOBALS["table_userdetails_placeid"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->userid));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray;
	}
    /*Beállítja a usernek azt a helyet, ahova utazik*/
    function setUserNewPlaceId($id) {
        $sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_newplaceid"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($id,$this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /*Lekéri a usernek azt a helyét, ahova tart*/
    function getUserNewPlaceId() {
        $sql = "SELECT ".$GLOBALS["table_userdetails_newplaceid"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray;
    }
	/*Visszaadja a már json kódolt adatokat a raktárról*/
	public function getAllStorage($userid) {
		$sql = "SELECT ".$GLOBALS["table_userdetails_storage"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($userid));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray[$GLOBALS["table_userdetails_storage"]];
	}

    /**
     * Lekérdezi, hogy tele van-e a raktár
     * @param $userid
     * @return bool
     */
    public function isStorageFull($userid) {
        $limit = $this->getStorageLimit();
        $storageArray = json_decode($this->getAllStorage($userid));
        $count = 0;
        if($storageArray != null) {
            foreach($storageArray as $anyag) {
                $count += $anyag;
            }
        }
        if($limit <= $count) {
            return true;
        }
        else {
            return false;
        }

    }
	/*Beállítja a raktárban az adott típus nyersanyagainak számát. 3-as vagy 4-es hibát dob, ha tele van a raktár*/
	public function setStorageResource($userid,$type,$resource) {
		$storageArray = json_decode($this->getAllStorage($userid));
		$count = 0;
		if($storageArray != null) {
			foreach($storageArray as $anyag) {
				$count += $anyag;
			}
		}
		else {
			$storageArray = new stdClass();
		}
		/*if($count >= $this->getStorageLimit()) {
			$result = array();
			$result["hibaid"] = "3";
			$result["hiba"] = Exceptions::getMessage(3);
			echo json_encode($result);
			return;
		}*/
		
		$count -= $this->getStorageResource($userid,$type);
		
		if($count + $resource > $this->getStorageLimit()) {
			$result = array();
			$result["hibaid"] = "4";
			$result["hiba"] = Exceptions::getMessage(4);
			echo json_encode($result);
			return;
		}
		$storageArray->$type = (int)$resource;
		$sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_storage"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(json_encode($storageArray),$userid));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}
	/*Lekérdezi, hogy a raktárban hány megadott típusú nyersanyag található*/
	public function getStorageResource($userid,$type) {
		$storageArray = json_decode($this->getAllStorage($userid));
		if($storageArray == null) {
			return 0;
		}
		if(!property_exists($storageArray, $type)) {
			return 0;
		}
		else {
			return $storageArray->$type;
		}
	}
	/*Lekérdezi a raktár kapacitását*/
	public function getStorageLimit() {
		$sql = "SELECT ".$GLOBALS["table_userdetails_storagelimit"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->userid));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray[$GLOBALS["table_userdetails_storagelimit"]];
	}
	/*Beállítja a raktár kapacitását*/
	public function setStorageLimit($storageLimit) {
		$sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_storagelimit"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(json_encode($storageLimit),$this->userid));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}
	/*Lekérdezi a lakhelyet*/
	public function getHomeId() {
		$sql = "SELECT ".$GLOBALS["table_userdetails_homeid"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->userid));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray[$GLOBALS["table_userdetails_homeid"]];
	}
	/*Beállítja a lakhelyet*/
	public function setHomeId($homeid) {
		$sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_homeid"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($homeid,$this->userid));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}

    /**
     * Hozzáad egy intelligenciapontot
     */
    public function addOneIpo() {
        $ipocount = $this->getIpo();
        $sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_ipo"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($ipocount+1,$this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekérdezi hány intelligenciapontja van a karakternek
     * @return mixed
     */
    public function getIpo() {
        $sql = "SELECT ".$GLOBALS["table_userdetails_ipo"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_userdetails_ipo"]];
    }

    /**
     * Beállítja, hogy hány intelligenciapontja legyen a karakternek
     * @param $ipo
     */
    public function setIpo($ipo) {
        $sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_ipo"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($ipo,$this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekérdezi a karakter által összes kifejlesztett épületet
     * @return mixed
     */
    public function getAllDevelopedBuildings() {
        $sql = "SELECT ".$GLOBALS["table_userdetails_buildingdeveloped"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_userdetails_buildingdeveloped"]];
    }

    /**
     * Lekérdezi a karakter által összes kifejlesztett eszközt
     * @return mixed
     */
    public function getAllDevelopedTools() {
        $sql = "SELECT ".$GLOBALS["table_userdetails_tooldeveloped"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_userdetails_tooldeveloped"]];
    }

    /**
     * Hozzáadja az épületet a kifejlesztett épületekhez
     * @param $buildingid
     * @param $buildinglevel
     */
    public function addBuildingToDeveloped($buildingid,$buildinglevel) {
        $bdArray = json_decode($this->getAllDevelopedBuildings());
        if($bdArray == null) {
            $bdArray = new stdClass();
        }
        $bdArray->$buildingid = $buildinglevel;
        $sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_buildingdeveloped"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(json_encode($bdArray),$this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekérdezi, hogy az épület ki van-e fejlesztve
     * @param $buildingid
     * @param $buildinglevel
     * @return int
     */
    public function isBuildingDeveloped($buildingid,$buildinglevel) {
        $bdArray = json_decode($this->getAllDevelopedBuildings());
        if($bdArray == null) {
            return 0;
        }
        if(!isset($bdArray->$buildingid)) {
            return 0;
        }
        if($bdArray->$buildingid >= $buildinglevel) {
            return 1;
        }
        else {
            return 0;
        }
    }

    /**
     * Hozzáaadja az eszközt a kifejlesztett eszközökhöz
     * @param $tooltype
     */
    public function addToolToDeveloped($tooltype) {
        $bdArray = json_decode($this->getAllDevelopedTools());
        if($bdArray == null) {
            $bdArray = new stdClass();
        }
        $bdArray->$tooltype = 1;
        $sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_tooldeveloped"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(json_encode($bdArray),$this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekérdezi, hogy az eszköz ki van-e fejlesztve
     * @param $toolid
     * @return int
     */
    public function isToolDeveloped($toolid) {
        $bdArray = json_decode($this->getAllDevelopedTools());
        if($bdArray == null) {
            return 0;
        }
        if(!isset($bdArray->$toolid)) {
            return 0;
        }
        if($bdArray->$toolid == 1) {
            return 1;
        }
        else {
            return 0;
        }
    }

    /**
     * Elmenti az adó egyik nyersanyagát
     * @param $type
     * @param $amount
     */
    function setTaxResource($type,$amount) {
        $taxArray = $this->getTaxResources($this->userid);
        if($taxArray == null) {
            $taxArray = new stdClass();
        }
        $taxArray->$type = (int)$amount;
        $sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_tax"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(json_encode($taxArray),$this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekéri a beállított adót
     * @param $userid
     * @return mixed|stdClass
     */
    function getTaxResources($userid) {
        $sql = "SELECT ".$GLOBALS["table_userdetails_tax"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        $array = json_decode($resultArray[$GLOBALS["table_userdetails_tax"]]);
        if($array == null) {
            $array = new stdClass();
        }
        for($i = 1; $i<=UserDetails::$MAX_TYPE; $i++) {
            if($this->isTaxType($i)) {
                if(!isset($array->$i)) {
                    $array->$i = 0;
                }
            }
        }
        return $array;
    }

    /**
     * Kitörli a beállított adót
     */
    function resetTaxResources() {
        $sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_tax"]."= '' WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekéri az adott karakter adóját
     * @param $userid - az adott karakter
     * @param $tileid
     * @return mixed|stdClass|string
     */
    function getTaxForUser($userid,$tileid) {
        $tile = new Tile($this->db);
        $result=$tile->getTileById($tileid);
        $ownerid = $result[$GLOBALS["table_tile_owner"]];
        if($ownerid == $userid) { //ha ő a földesúr, akkor nem vonja le
            return "";
        }
        $date = new DateTime();
        if(abs($this->getTaxPaymentTime($userid)-$date->getTimestamp()) < UserDetails::$PAYMENT_TIME) {
            return "";
        }
        $taxArray = $this->getTaxResources($ownerid);
        $count = 0;
        foreach($taxArray as $tax) {
            $count += $tax;
        }
        if($count == 0) {
            return "";
        }
        return $taxArray;
    }

    /**
     * Befizeti az adott karakternek az adót
     * @param $userid
     * @param $tileid
     * @return int
     */
    function payTax($userid,$tileid) {
        $taxArray = $this->getTaxForUser($userid,$tileid);
        print_r($taxArray);
        $date = new DateTime();
        if(abs($this->getTaxPaymentTime($userid)-$date->getTimestamp()) < UserDetails::$PAYMENT_TIME) {
            return 0;
        }
        $tile = new Tile($this->db);
        $ownerid = $tile->getOwner($tileid);
        //emberkétől levonás, földesúrhoz hozzáadás
        foreach($taxArray as $type=>$amount) {
            $inStorageUser = $this->getStorageResource($userid,$type);
            $inStorageOwner = $this->getStorageResource($ownerid,$type);
            if($amount > 0) {
                if($inStorageUser <= $amount) {
                    $this->setStorageResource($userid,$type,0);
                    $this->setStorageResource($ownerid,$type,$inStorageOwner+$inStorageUser);
                }
                else {
                    $this->setStorageResource($userid,$type,$inStorageUser-$amount);
                    $this->setStorageResource($ownerid,$type,$inStorageOwner+$amount);
                }
            }
        }
        //idő átállítása
        $this->setTaxPaymentTime($userid,$date->getTimestamp());
    }

    /**
     * Lekérdezi, hogy mikor fizetett utoljára adót
     * @param $userid
     * @return mixed
     */
    function getTaxPaymentTime($userid) {
        $sql = "SELECT ".$GLOBALS["table_userdetails_taxtime"]." FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($userid));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_userdetails_taxtime"]];
    }

    /**
     * Beállítja, hogy mikor fizetett adót
     * @param $userid
     * @param $time
     */
    function setTaxPaymentTime($userid,$time) {
        $sql = "UPDATE ".$GLOBALS["table_userdetails_title"]." SET ".$GLOBALS["table_userdetails_taxtime"]."= ? WHERE ".$GLOBALS["table_userdetails_userid"]." = ?";
        $stmt = $this->db->prepare($sql);

        $stmt->execute(array($time,$userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /*Lekéri, hogy egy tile hány embernek a lakhelye*/
    function getTilePopulation($tileid) {
        $sql = "SELECT COUNT(*) FROM ".$GLOBALS["table_userdetails_title"]." WHERE ".$GLOBALS["table_userdetails_homeid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($tileid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        //print_r($resultArray);
        return $resultArray["COUNT(*)"];
    }

    /**
     * Kiszámolja két koordináta között a távolságot.
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @param $unit K - kilométer, M - mérföld
     * @return float
     */
    function distance($lat1, $lon1, $lat2, $lon2, $unit) {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

    /**
     * Elutazik a megadott helyszínre
     * @param $placeid
     * @return array
     */
    public function travelToPlace($placeid) {
        $timedAction = new TimedAction($this->db,$this->userid);
        $timeArray = $timedAction->setTravelToPlace($this,$placeid);
        return $timeArray;
    }

    /**
     * Elkezdi a letelepedést
     * @param $placeid
     * @return array
     */
    public function settleDown($placeid) {
        $timedAction = new TimedAction($this->db,$this->userid);
        $timeArray = $timedAction->setSettleDown($placeid);
        return $timeArray;
    }

    /**
     * Elkezdi kitermelni az első nyersanyagot
     * @param $type
     * @param $placeid
     * @return array
     */
    public function mineResource1($type,$placeid) {
        $timedAction = new TimedAction($this->db,$this->userid);
        $timeArray = $timedAction->setMineResource1($type,$placeid);
        return $timeArray;
    }

    /**
     * Elkezdi kitermelni a második nyersanyagot
     * @param $type
     * @param $placeid
     * @return array
     */
    public function mineResource2($type,$placeid) {
        $timedAction = new TimedAction($this->db,$this->userid);
        $timeArray = $timedAction->setMineResource2($type,$placeid);
        return $timeArray;
    }

    /**
     * Elkezdi kitermelni a harmadik nyersanyagot
     * @param $type
     * @param $placeid
     * @return array
     */
    public function mineResource3($type,$placeid) {
        $timedAction = new TimedAction($this->db,$this->userid);
        $timeArray = $timedAction->setMineResource3($type,$placeid);
        return $timeArray;
    }

    /**
     * Lekérdezi, hogy milyen nyersanyag lehet adó
     * @param $type
     * @return bool
     */
    public function isTaxType($type) {
        if($type == UserDetails::$WOOD ||
            $type == UserDetails::$TWIG ||
            $type == UserDetails::$MEAT ||
            $type == UserDetails::$BERRY ||
            $type == UserDetails::$STONE ||
            $type == UserDetails::$FISH) {
            return true;
        }
        else {
            return false;
        }
    }
}
?>