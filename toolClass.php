<?php
require_once("buildingClass.php");
require_once("userDetailsClass.php");
class Tool {
    private $userid;
    private $db;

    public static $FISH_AXE = 10;
    public static $WOODEN_AXE = 11;
    public static $STONE_AXE = 12;
    public static $FISH_PICKAXE = 13;
    public static $WOODEN_PICKAXE = 14;
    public static $STONE_PICKAXE = 15;

    function __construct($db,$userid) {
        $this->db = $db;
        $this->userid = $userid;
    }

    /**
     * Lekérdezi az eszközhöz szükséges nyersanyagokat
     * @param $type
     * @return mixed
     */
    function getToolResources($type) {
        $sql = "SELECT ".$GLOBALS["table_toolresources_resources"]." FROM ".$GLOBALS["table_toolresources_title"]." WHERE ".$GLOBALS["table_toolresources_toolid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($type));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        $result = json_decode($resultArray[$GLOBALS["table_toolresources_resources"]]);
        return $result;
    }

    /**
     * Lekérdezi az eszközhöz szükséges intelligenciapontokat
     * @param $type
     * @return mixed
     */
    function getToolIpo($type) {
        $sql = "SELECT ".$GLOBALS["table_toolresources_ipo"]." FROM ".$GLOBALS["table_toolresources_title"]." WHERE ".$GLOBALS["table_toolresources_toolid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($type));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        $result = $resultArray[$GLOBALS["table_toolresources_ipo"]];
        return $result;
    }

    /**
     * Lekérdezi az összes kifejleszthető eszközt
     * @return array
     */
    public function getAllDevelopableTools() {
        $building = new Building($this->db,$this->userid);
        $toolStationLevel = $building->getToolStationLevel();
        $sql = "SELECT * FROM ".$GLOBALS["table_toolresources_title"]." WHERE ".$GLOBALS["table_toolresources_minlevel"]." <= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($toolStationLevel));
        $resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $userdetails = new UserDetails($this->db,$this->userid);
        $tResources = array();
        foreach($resultArray as $buildingResource) {
            if(!$userdetails->isToolDeveloped($buildingResource[$GLOBALS["table_toolresources_toolid"]])) {
                $buildingResource[$GLOBALS["table_toolresources_resources"]] = json_decode($buildingResource[$GLOBALS["table_toolresources_resources"]]);
                array_push($tResources,$buildingResource);
            }
        }
        return $tResources;
    }

    /**
     * Elkezdi kifejleszteni az adott eszközt
     * @param $toolid
     * @return array
     */
    public function developTool($toolid) {
        $neededResources = $this->getToolResources($toolid);
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
        $ipoNeeded = $this->getToolIpo($toolid);
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
        $timeArray = $timedAction->setDevelopTool($toolid);
        return $timeArray;
    }

    /**
     * Lekérdezi, hogy menyni ideig tart kitermelni egy nyersanyagot
     * @param $type
     * @return float|int
     */
    public function getMiningTime($type) {
        $userdetails = new UserDetails($this->db,$this->userid);
        $time = 10;
        if($type == UserDetails::$WOOD) {
            $time = 10;
            if($userdetails->isToolDeveloped(Tool::$STONE_AXE)) {
                $time /= 4;
            }
            else if($userdetails->isToolDeveloped(Tool::$WOODEN_AXE)) {
                $time /= 2;
            }
            else if($userdetails->isToolDeveloped(Tool::$FISH_AXE)) {
                $time /= 1.2;
            }
        }
        else if($type == UserDetails::$TWIG) {
            $time = 10;
            if($userdetails->isToolDeveloped(Tool::$STONE_AXE)) {
                $time /= 4;
            }
            else if($userdetails->isToolDeveloped(Tool::$WOODEN_AXE)) {
                $time /= 2;
            }
            else if($userdetails->isToolDeveloped(Tool::$FISH_AXE)) {
                $time /= 1.2;
            }
        }
        else if($type == UserDetails::$MEAT) {
            $time = 10;
        }
        else if($type == UserDetails::$BERRY) {
            $time = 10;
        }
        else if($type == UserDetails::$STONE) {
            $time = 10;
            if($userdetails->isToolDeveloped(Tool::$STONE_PICKAXE)) {
                $time /= 4;
            }
            else if($userdetails->isToolDeveloped(Tool::$WOODEN_PICKAXE)) {
                $time /= 2;
            }
            else if($userdetails->isToolDeveloped(Tool::$FISH_PICKAXE)) {
                $time /= 1.2;
            }
        }
        else if($type == UserDetails::$FISH) {
            $time = 10;
        }
        return $time;
    }
}
?>