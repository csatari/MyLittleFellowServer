<?php
class Building {
    private $userid;
    private $db;

    public static $TOWNCENTER = 1;
    public static $STORAGE = 2;
    public static $TOWER = 3;
    public static $TOOLSTATION = 4;

    public static $STORAGELEVEL = array(20,10,20,30,40,100);

    public static $MAX_LEVEL = 5;

    function __construct($db,$userid) {
        $this->db = $db;
        $this->userid = $userid;
    }

    /**
     * Visszaad egy json listát a user által építhető összes listáról
     */
    public function getBuildableList($tileid,$tileslice) {
        $bui = $this->getBuildingOnSlice($tileid,$tileslice);
        $lista = array();
        if($bui != null) {
            $type = $bui[$GLOBALS["table_buildings_buildingtype"]];
            $level = $bui[$GLOBALS["table_buildings_buildinglevel"]];
            $resources = $this->getBuildingResources($type,$level+1);
            if($resources != null) {
                $res = array();
                $res[$GLOBALS["table_buildings_buildingtype"]] = $type;
                $res[$GLOBALS["table_buildings_buildinglevel"]] = $level+1;
                $res[$GLOBALS["table_buildingresources_resources"]] = $resources;
                $res[$GLOBALS["table_buildingresources_name"]] = $this->getBuildingName($type,$level+1);
                $res[$GLOBALS["table_buildingresources_ipo"]] = $this->getBuildingIpo($type,$level+1);
                array_push($lista,$res);
                return $lista;
            }
            else {
                $result = array();
                $result["hibaid"] = "5";
                $result["hiba"] = Exceptions::getMessage(5);
                return $result;
            }
        }

        $buildings = $this->getAllBuildables();
        foreach($buildings as $building) {
            if($building[$GLOBALS["table_buildingresources_level"]] == 1) {
                $res = array();
                $res[$GLOBALS["table_buildings_buildingtype"]] = $building[$GLOBALS["table_buildingresources_id"]];
                $res[$GLOBALS["table_buildings_buildinglevel"]] = $building[$GLOBALS["table_buildingresources_level"]];
                $res[$GLOBALS["table_buildingresources_resources"]] = json_decode($building[$GLOBALS["table_buildingresources_resources"]]);
                $res[$GLOBALS["table_buildingresources_name"]] = $building[$GLOBALS["table_buildingresources_name"]];
                $res[$GLOBALS["table_buildingresources_ipo"]] = $building[$GLOBALS["table_buildingresources_ipo"]];
                array_push($lista,$res);
            }
        }
        return $lista;

    }

    /**
     * Lekérdezi hogy az adott tile tilesliceon milyen épület van
     */
    function getBuildingOnSlice($tileid,$tileslice) {
        $sql = "SELECT * FROM ".$GLOBALS["table_buildings_title"]." WHERE ".$GLOBALS["table_buildings_userid"]." = ? AND ".
        $GLOBALS["table_buildings_tileid"]." = ? AND ".$GLOBALS["table_buildings_tilesliceid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid,$tileid,$tileslice));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray;
    }

    /**
     * Hozzáad egy épületet a userhez, ha már volt ilyen épület, akkor a levelt növeli eggyel
     * @param $tileid
     * @param $tileslice
     * @param $buildingtype
     * @param $finished
     * @return mixed
     */
    function addBuildingToUser($tileid,$tileslice,$buildingtype,$finished) {
        $resultArray = $this->getBuildingOnSlice($tileid,$tileslice);
        if($resultArray != null) {
            $level = $resultArray[$GLOBALS["table_buildings_buildinglevel"]]+1;
            $sql = "UPDATE ".$GLOBALS["table_buildings_title"]." SET ".$GLOBALS["table_buildings_finished"]."= ?, ".$GLOBALS["table_buildings_buildinglevel"]."= ? WHERE ".$GLOBALS["table_buildings_userid"]." = ? AND ".$GLOBALS["table_buildings_tileid"]." = ? AND ".$GLOBALS["table_buildings_tilesliceid"]." = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($finished,$level,$this->userid,$tileid,$tileslice));
            $stmt->fetch(PDO::FETCH_ASSOC);
        }
        else {
            $level = 1;
            $sql = "INSERT INTO ".$GLOBALS["table_buildings_title"]." (".$GLOBALS["table_buildings_userid"].",".$GLOBALS["table_buildings_tileid"].", ".$GLOBALS["table_buildings_tilesliceid"].", ".$GLOBALS["table_buildings_buildingtype"].", ".$GLOBALS["table_buildings_buildinglevel"].", ".$GLOBALS["table_buildings_finished"].") VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);


            $stmt->execute(array($this->userid,$tileid,$tileslice,$buildingtype,$level,$finished));
            $stmt->fetch(PDO::FETCH_ASSOC);
        }

    }


    /**
     * Beállítja a megadott épületet, hogy kész van
     * @param $tileid
     * @param $tileslice
     */
    function setBuildingToDone($tileid,$tileslice) {
        $sql = "UPDATE ".$GLOBALS["table_buildings_title"]." SET ".$GLOBALS["table_buildings_finished"]."= 1 WHERE ".$GLOBALS["table_buildings_userid"]." = ? AND ".$GLOBALS["table_buildings_tileid"]." = ? AND ".$GLOBALS["table_buildings_tilesliceid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid,$tileid,$tileslice));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekéri az összes építhető épület receptjét
     * @return mixed
     */
    function getAllBuildables() {
        $sql = "SELECT * FROM ".$GLOBALS["table_buildingresources_title"];
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array());
        $resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultArray;
    }

    /**
     * Lekéri egy tömbben az adott típusú és szintű épülethez szükséges nyersanyagokat
     * @param $type
     * @param $level
     * @return mixed
     */
    function getBuildingResources($type,$level) {
        $sql = "SELECT ".$GLOBALS["table_buildingresources_resources"]." FROM ".$GLOBALS["table_buildingresources_title"]." WHERE ".$GLOBALS["table_buildingresources_id"]." = ? AND ". $GLOBALS["table_buildingresources_level"]." = ? ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($type,$level));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        $result = json_decode($resultArray[$GLOBALS["table_buildingresources_resources"]]);
        return $result;
    }

    /**
     * Lekéri, hogy mennyi intelligenciapont szükséges a megadott épülethez
     * @param $type az épület típusa
     * @param $level az épület szintje
     * @return mixed
     */
    function getBuildingIpo($type,$level) {
        $sql = "SELECT ".$GLOBALS["table_buildingresources_ipo"]." FROM ".$GLOBALS["table_buildingresources_title"]." WHERE ".$GLOBALS["table_buildingresources_id"]." = ? AND ". $GLOBALS["table_buildingresources_level"]." = ? ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($type,$level));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        $result = $resultArray[$GLOBALS["table_buildingresources_ipo"]];
        return $result;
    }

    /**
     * Lekérdezi az épület nevét
     * @param $type
     * @param $level
     * @return mixed
     */
    function getBuildingName($type,$level) {
        $sql = "SELECT ".$GLOBALS["table_buildingresources_name"]." FROM ".$GLOBALS["table_buildingresources_title"]." WHERE ".$GLOBALS["table_buildingresources_id"]." = ? AND ". $GLOBALS["table_buildingresources_level"]." = ? ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($type,$level));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        $result = $resultArray[$GLOBALS["table_buildingresources_name"]];
        return $result;
    }

    /**
     * Lekérdezi az épület szintjét típus szerint
     * @param $type
     * @return mixed
     */
    function getBuildingLevel($type) {
        $sql = "SELECT ".$GLOBALS["table_buildings_buildinglevel"]." FROM ".$GLOBALS["table_buildings_title"]." WHERE ".$GLOBALS["table_buildings_userid"]." = ? AND ".$GLOBALS["table_buildings_buildingtype"]." = ? AND ".$GLOBALS["table_buildings_finished"]." = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid,$type));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        $result = $resultArray[$GLOBALS["table_buildings_buildinglevel"]];
        return $result;
    }

    /**
     * Lekérdezi a felhasználó által épített épületeket
     * @return mixed
     */
    function getAllBuildingsByUser() {
        $sql = "SELECT * FROM ".$GLOBALS["table_buildings_title"]." WHERE ".$GLOBALS["table_buildings_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultArray;
    }

    /**
     * Timed, elindít egy építkezést
     * @param $tileid
     * @param $tileslice
     * @param $buildingtype
     * @return array
     */
    function buildBuilding($tileid,$tileslice,$buildingtype) {
        $resultArray = $this->getBuildingOnSlice($tileid,$tileslice);
        if($resultArray != null) {
            $level = $resultArray[$GLOBALS["table_buildings_buildinglevel"]]+1;
        }
        else {
            $level = 1;
        }
        $neededResources = $this->getBuildingResources($buildingtype,$level);
        if($neededResources == null) {
            $result["hibaid"] = "5";
            $result["hiba"] = Exceptions::getMessage(5);
            return $result;
        }
        $userdetails = new UserDetails($this->db,$this->userid);
        foreach($neededResources as $restype=>$resamount) {
            $stored = $userdetails->getStorageResource($this->userid,$restype);
            if($stored < $resamount) {
                $result["hibaid"] = "6";
                $result["hiba"] = Exceptions::getMessage(6);
                return $result;
            }
        }
        $engedelyvan = $userdetails->isBuildingDeveloped($buildingtype,$level);
        if($engedelyvan == 0) {
            $result["hibaid"] = "7";
            $result["hiba"] = Exceptions::getMessage(7);
            return $result;
        }
        $ipoNeeded = $this->getBuildingIpo($buildingtype,$level);
        $myIpo = $userdetails->getIpo();
        if($myIpo < $ipoNeeded) {
            $result["hibaid"] = "8";
            $result["hiba"] = Exceptions::getMessage(8);
            return $result;
        }
        foreach($neededResources as $restype=>$resamount) {
            $stored = $userdetails->getStorageResource($this->userid,$restype);
            $userdetails->setStorageResource($this->userid,$restype,$stored-$resamount);
        }
        $userdetails->setIpo($myIpo-$ipoNeeded);

        $timedAction = new TimedAction($this->db,$this->userid);
        $timeArray = $timedAction->setBuild($tileid,$tileslice,$buildingtype);
        return $timeArray;
    }

    /**
     * Lekérdezi a tudás tornya szintjét
     * @return mixed
     */
    public function getTowerLevel() {
        return $this->getBuildingLevel(Building::$TOWER);
    }

    /**
     * Lekérdezi az eszközkészítő szintjét
     * @return mixed
     */
    public function getToolStationLevel() {
        return $this->getBuildingLevel(Building::$TOOLSTATION);
    }

    /**
     * Lekérdezi, hogy a karakter meddig fejlesztette ki az épületet
     * @param $buildingid
     * @return int
     */
    private function getUserBuildingDevelopingLevel($buildingid) {
        $userdetails = new UserDetails($this->db,$this->userid);
        $developedBuildings = json_decode($userdetails->getAllDevelopedBuildings());
        if(isset($developedBuildings->$buildingid)) {
            return $developedBuildings->$buildingid;
        }
        else {
            return 0;
        }
    }

    /**
     * Lekérdezi az összes kifejleszthető épületet
     * @return array
     *
     */
    public function getAllDevelopableBuildings() {
        $towerLevel = $this->getTowerLevel();
        $sql = "SELECT * FROM ".$GLOBALS["table_buildingresources_title"]." WHERE ".$GLOBALS["table_buildingresources_minlevel"]." <= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($towerLevel));
        $resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $userdetails = new UserDetails($this->db,$this->userid);
        $bResources = array();
        foreach($resultArray as $buildingResource) {
            if(!$userdetails->isBuildingDeveloped($buildingResource[$GLOBALS["table_buildingresources_id"]],$buildingResource[$GLOBALS["table_buildingresources_level"]])) {
                if($this->getUserBuildingDevelopingLevel($buildingResource[$GLOBALS["table_buildingresources_id"]])+1 == $buildingResource[$GLOBALS["table_buildingresources_level"]]) {
                    $buildingResource[$GLOBALS["table_buildingresources_resources"]] = json_decode($buildingResource[$GLOBALS["table_buildingresources_resources"]]);
                    array_push($bResources,$buildingResource);
                }
            }
        }
        return $bResources;
    }

    /**
     * Elkezd kifejleszteni egy épületet
     * @param $buildingid
     * @param $buildinglevel
     * @return array
     */
    public function developBuilding($buildingid,$buildinglevel) {
        $neededResources = $this->getBuildingResources($buildingid,$buildinglevel);
        if($neededResources == null) {
            $result["hibaid"] = "5";
            $result["hiba"] = Exceptions::getMessage(5);
            return $result;
        }
        $userdetails = new UserDetails($this->db,$this->userid);
        foreach($neededResources as $restype=>$resamount) {
            $stored = $userdetails->getStorageResource($this->userid,$restype);
            if($stored < $resamount) {
                $result["hibaid"] = "6";
                $result["hiba"] = Exceptions::getMessage(6);
                return $result;
            }
        }
        $ipoNeeded = $this->getBuildingIpo($buildingid,$buildinglevel);
        $myIpo = $userdetails->getIpo();
        if($myIpo < $ipoNeeded) {
            $result["hibaid"] = "8";
            $result["hiba"] = Exceptions::getMessage(8);
            return $result;
        }
        foreach($neededResources as $restype=>$resamount) {
            $stored = $userdetails->getStorageResource($this->userid,$restype);
            $userdetails->setStorageResource($this->userid,$restype,$stored-$resamount);
        }
        $userdetails->setIpo($myIpo-$ipoNeeded);

        $timedAction = new TimedAction($this->db,$this->userid);
        $timeArray = $timedAction->setDevelop($buildingid,$buildinglevel);
        return $timeArray;
    }

}
?>