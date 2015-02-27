<?php
require_once("tileClass.php");
require_once("knownTilesClass.php");
require_once("userDetailsClass.php");
require_once("buildingClass.php");
require_once("toolClass.php");
class TimedAction {
    private $userid;
    private $db;

    public static $NONE = 0;
    public static $TRAVEL = 1;
    public static $EXAMINE = 2;
    public static $SETTLEDOWN = 3;
    public static $RESOURCE1 = 4;
    public static $RESOURCE2 = 5;
    public static $RESOURCE3 = 6;
    public static $BUILD = 7;
    public static $DEVELOPBUILDING = 8;
    public static $DEVELOPTOOL = 9;

    public static $TOWNCENTER = 1;
    public static $STORAGE = 2;

    function __construct($db,$userid) {
        $this->db = $db;
        $this->userid = $userid;
        $this->addToTableIfNotExists();
    }

    /*hozzáadja a timedaction-t a táblához, üresen, ha még nem volt benne*/
    function addToTableIfNotExists() {
        $sql = "INSERT INTO ".$GLOBALS["table_timedaction_title"]." (".$GLOBALS["table_timedaction_userid"].") VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Frissíti a táblát a megadott interakció adataival
     * @param $oldtime
     * @param $newtime
     * @param $actionid
     * @param $goal
     * @param $goal2
     */
    private function updateTable($oldtime,$newtime,$actionid,$goal,$goal2) {
        $sql = "UPDATE ".$GLOBALS["table_timedaction_title"]." SET ".$GLOBALS["table_timedaction_oldtime"]."= ?, ".$GLOBALS["table_timedaction_newtime"]."= ?, ".$GLOBALS["table_timedaction_actionid"]."= ?, ".$GLOBALS["table_timedaction_goal"]."= ?, ".$GLOBALS["table_timedaction_goal2"]."= ? WHERE ".$GLOBALS["table_timedaction_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($oldtime,$newtime,$actionid,$goal,$goal2,$this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekérdezi a legutóbb futott action id-jét
     * @return mixed
     */
    public function getActionId() {
        $sql = "SELECT ".$GLOBALS["table_timedaction_actionid"]." FROM ".$GLOBALS["table_timedaction_title"]." WHERE ".$GLOBALS["table_timedaction_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_timedaction_actionid"]];
    }

    /**
     * Lekérdezi az első segédváltozót
     * @return mixed
     */
    private function getGoal() {
        $sql = "SELECT ".$GLOBALS["table_timedaction_goal"]." FROM ".$GLOBALS["table_timedaction_title"]." WHERE ".$GLOBALS["table_timedaction_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_timedaction_goal"]];
    }

    /**
     * Lekérdezi a második segédváltozót
     * @return mixed
     */
    private function getGoal2() {
        $sql = "SELECT ".$GLOBALS["table_timedaction_goal2"]." FROM ".$GLOBALS["table_timedaction_title"]." WHERE ".$GLOBALS["table_timedaction_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_timedaction_goal2"]];
    }

    /**
     * Lekérdezi a táblából a timedaction adatait
     * @return mixed
     */
    private function getTimedAction() {
        $sql = "SELECT ".$GLOBALS["table_timedaction_goal2"].",".$GLOBALS["table_timedaction_goal"].",".$GLOBALS["table_timedaction_oldtime"].",".$GLOBALS["table_timedaction_newtime"].",".$GLOBALS["table_timedaction_actionid"]." FROM ".$GLOBALS["table_timedaction_title"]." WHERE ".$GLOBALS["table_timedaction_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray;
    }

    /**
     * Összegyűjti a timedaction-nel kapcsolatos infókat (a végállapotot, meg az időt) és azt adja vissza egy tömbben
     */
    public function getData() {
        $timedAction = $this->getTimedAction();
        $actionid = $timedAction[$GLOBALS["table_timedaction_actionid"]];
        if($actionid == TimedAction::$TRAVEL) {
            $userDetails = new UserDetails($this->db,$this->userid);
            $place = $userDetails->getUserNewPlaceId();
            $timedAction[$GLOBALS["return_userdetails_goal"]] = $place[$GLOBALS["table_userdetails_newplaceid"]];
        }
        else if($actionid == TimedAction::$EXAMINE) {
            $timedAction[$GLOBALS["return_userdetails_goal"]] = $this->getGoal();
        }
        else if($actionid == TimedAction::$SETTLEDOWN) {
            $timedAction[$GLOBALS["return_userdetails_goal"]] = $this->getGoal();
        }
        else if($actionid == TimedAction::$RESOURCE1 || $actionid == TimedAction::$RESOURCE2 || $actionid == TimedAction::$RESOURCE3) {
            $timedAction[$GLOBALS["return_userdetails_goal"]] = $this->getGoal();
            $timedAction[$GLOBALS["return_userdetails_goal2"]] = $this->getGoal2();
        }
        else if($actionid == TimedAction::$BUILD) {
            $tileid = $this->getGoal();
            $tileslice = $this->getGoal2();
            $building = new Building($this->db,$this->userid);
            return $building->getBuildingOnSlice($tileid,$tileslice);
        }
        else if($actionid == TimedAction::$DEVELOPBUILDING) {
            $timedAction[$GLOBALS["return_userdetails_goal"]] = $this->getGoal();
            $timedAction[$GLOBALS["return_userdetails_goal2"]] = $this->getGoal2();
        }
        else if($actionid == TimedAction::$DEVELOPTOOL) {
            $timedAction[$GLOBALS["return_userdetails_goal"]] = $this->getGoal();
        }
        else if($actionid = TimedAction::$NONE) {
            $timedAction[$GLOBALS["return_userdetails_goal"]] = 0;
        }
        return $timedAction;
    }
    public function stopAction() {
        $this->updateTable(0,0,TimedAction::$NONE,0,0);
    }

    /**
     * Akkor hívja meg a kliens, amikor lejárt a timed action ideje, ez a függvény pedig átírja a "_new" oszlopokat 0-ra, és beírja az eredetibe
     * emellett lefutnak a szerveren szükséges változtatások
     */
    public function refreshData() {
        $timedAction = $this->getTimedAction();
        $actionid = $timedAction[$GLOBALS["table_timedaction_actionid"]];
        //$oldtime = $timedAction[$GLOBALS["table_timedaction_oldtime"]];
        $newtime = $timedAction[$GLOBALS["table_timedaction_newtime"]];
        $goal =  $timedAction[$GLOBALS["table_timedaction_goal"]];
        $goal2 =  $timedAction[$GLOBALS["table_timedaction_goal2"]];
        $date = new DateTime("now");
        $array = array();
        if($newtime <= $date->getTimestamp()) {
            if($actionid == TimedAction::$TRAVEL) {
                $userDetails = new UserDetails($this->db,$this->userid);
                $place = $userDetails->getUserNewPlaceId();
                $userDetails->setUserPlaceId($place[$GLOBALS["table_userdetails_newplaceid"]]);
                $userDetails->setUserNewPlaceId(0);
                $this->updateTable(0,0,TimedAction::$NONE,0,0);
                $array[$GLOBALS["return_timedaction_result"]] = 1;
                return $array;
            }
            else if($actionid == TimedAction::$EXAMINE) {
                $knownTiles = new KnownTiles($this->db,$this->userid);
                $knownTiles->setTileExamined($goal);
                $this->updateTable(0,0,TimedAction::$NONE,0,0);
                $array[$GLOBALS["return_timedaction_result"]] = 1;
                return $array;
            }
            else if($actionid == TimedAction::$SETTLEDOWN) {
                $userDetails = new UserDetails($this->db,$this->userid);
                $userDetails->setHomeId($goal);
                $userDetails->addBuildingToDeveloped(1,2); //lvl 2 városháza
                $userDetails->addBuildingToDeveloped(2,2); //lvl 2 raktár
                $userDetails->addBuildingToDeveloped(3,1); //lvl 1 tudástornya
                $userDetails->addBuildingToDeveloped(4,1); //lvl 1 toolstation

                $storagelimit = $userDetails->getStorageLimit();
                $storagelimit += Building::$STORAGELEVEL[0];
                $userDetails->setStorageLimit($storagelimit);
                $building = new Building($this->db,$this->userid);
                $building->addBuildingToUser($goal,5,1,1);
                $this->updateTable(0,0,TimedAction::$NONE,0,0);
                $array[$GLOBALS["return_timedaction_result"]] = 1;
                return $array;
            }
            else if($actionid == TimedAction::$RESOURCE1 || $actionid == TimedAction::$RESOURCE2 || $actionid == TimedAction::$RESOURCE3) {
                $userDetails = new UserDetails($this->db,$this->userid);
                $res = $userDetails->getStorageResource($this->userid,$goal);
                $userDetails->setStorageResource($this->userid,$goal,$res+1);
                $tile = new Tile($this->db,$this->userid);
                $t = $tile->getTileById($goal2);
                if($actionid == TimedAction::$RESOURCE1) {
                    $res = $t[$GLOBALS["table_tile_resource1"]];
                    $tile->setTileResource1ByID($goal2,$res-1);
                }
                else if($actionid == TimedAction::$RESOURCE2) {
                    $res = $t[$GLOBALS["table_tile_resource2"]];
                    $tile->setTileResource2ByID($goal2,$res-1);
                }
                else if($actionid == TimedAction::$RESOURCE3) {
                    $res = $t[$GLOBALS["table_tile_resource3"]];
                    $tile->setTileResource3ByID($goal2,$res-1);
                }

                $this->updateTable(0,0,TimedAction::$NONE,0,0);
                $array[$GLOBALS["return_timedaction_result"]] = 1;
                return $array;
            }
            else if($actionid == TimedAction::$BUILD) {
                $building = new Building($this->db,$this->userid);
                $building->setBuildingToDone($this->getGoal(),$this->getGoal2());
                $buildingArray = $building->getBuildingOnSlice($this->getGoal(),$this->getGoal2());
                if($buildingArray[$GLOBALS["table_buildings_buildingtype"]] == TimedAction::$STORAGE) {
                    $userDetails = new UserDetails($this->db,$this->userid);
                    $storagelimit = $userDetails->getStorageLimit();
                    $storagelimit += Building::$STORAGELEVEL[$buildingArray[$GLOBALS["table_buildings_buildinglevel"]]];
                    $userDetails->setStorageLimit($storagelimit);
                }
                else if($buildingArray[$GLOBALS["table_buildings_buildingtype"]] == TimedAction::$TOWNCENTER) {
                    $tile = new Tile($this->db,$this->userid);
                    $level = $buildingArray[$GLOBALS["table_buildings_buildinglevel"]];
                    if($level == Building::$MAX_LEVEL) {
                        if($tile->getOwner($this->getGoal()) == 0) {
                            $tile->setOwner($this->getGoal(),$this->userid);
                            $tile->setTileTypeByID($this->getGoal(),Tile::$TOWN);
                        }
                    }
                }
                $this->updateTable(0,0,TimedAction::$NONE,0,0);
                $array[$GLOBALS["return_timedaction_result"]] = 1;
                return $array;
            }
            else if($actionid == TimedAction::$DEVELOPBUILDING) {
                $userDetails = new UserDetails($this->db,$this->userid);
                $userDetails->addBuildingToDeveloped($this->getGoal(),(int)$this->getGoal2());
                $this->updateTable(0,0,TimedAction::$NONE,0,0);
                $array[$GLOBALS["return_timedaction_result"]] = 1;
                return $array;
            }
            else if($actionid == TimedAction::$DEVELOPTOOL) {
                $userDetails = new UserDetails($this->db,$this->userid);
                $userDetails->addToolToDeveloped($this->getGoal());
                $this->updateTable(0,0,TimedAction::$NONE,0,0);
                $array[$GLOBALS["return_timedaction_result"]] = 1;
                return $array;
            }
            else {
                $array[$GLOBALS["return_timedaction_result"]] = 0;
                return $array;
            }
        }
        else {
            $array[$GLOBALS["return_timedaction_result"]] = 0;
            return $array;
        }

    }

    /**
     * Kiszámolja a két időpont közötti különbséget
     * @param $oldtime
     * @param $newtime
     * @param $difference
     */
    private function getOldAndNewTime(&$oldtime,&$newtime,$difference) {
        $kezdo = new DateTime("now");
        $oldtime = $kezdo->getTimestamp();
        $difference = floor($difference);
        $sec = new DateInterval('PT'.$difference.'S');
        $veg = new DateTime();
        $veg->setTimestamp($oldtime);
        $veg->add($sec);
        $newtime = $veg->getTimestamp();
    }

    /**
     * Beállít egy utazást a megadott helyre, ami time másodpercig fog tartani, oldtime-ban kezdődik
     * @param UserDetails $userDetails
     * @param $placeid
     * @return array
     */
    public function setTravelToPlace(UserDetails $userDetails,$placeid) {
        $kezdo = new DateTime("now");
        $oldtime = $kezdo->getTimestamp();
        $tile = new Tile($this->db);
        $userPlaceIdArray = $userDetails->getUserPlaceId();
        $userPlaceId = $userPlaceIdArray[$GLOBALS["table_userdetails_placeid"]];
        $timeArray = array();

        $oldPlaceArray = $tile->getTileById($userPlaceId);
        $newPlaceArray = $tile->getTileById($placeid);

        $lat1 = $oldPlaceArray[$GLOBALS["table_tile_lat"]];
        $lon1 = $oldPlaceArray[$GLOBALS["table_tile_long"]];
        $lat2 = $newPlaceArray[$GLOBALS["table_tile_lat"]];
        $lon2 = $newPlaceArray[$GLOBALS["table_tile_long"]];

        $dif1 = abs($lat1-$lat2);
        $dif2 = abs($lon1-$lon2);
        $tav = sqrt(pow($dif1,2)+pow($dif2,2));

        //$seconds = $tav/$GLOBALS["tilesize"]*60;
        $seconds = 10;
        $sec = new DateInterval('PT'.round($seconds).'S');
        $veg = new DateTime();
        $veg->setTimestamp($oldtime);
        $veg->add($sec);
        //echo $oldtime."<br>";
        $timeArray[$GLOBALS["return_userdetails_time"]] = $veg->getTimestamp();
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        //Ha már ott van vagy még sehol se járt, akkor csak átállítjuk a helyszínt
        if($userPlaceId == 0 || $userPlaceId == $placeid) {
            $timeArray[$GLOBALS["return_userdetails_time"]] = $oldtime+1;
        }

        $userDetails->setUserNewPlaceId($placeid);
        $this->updateTable($oldtime,$veg->getTimestamp(),TimedAction::$TRAVEL,$placeid,0);
        return $timeArray;
    }

    /**
     * ELkezdi megvizsgálni a területet
     * @param $tileid
     * @return array
     */
    public function setExamineTile($tileid) {
        $kezdo = new DateTime("now");
        $oldtime = $kezdo->getTimestamp();

        $seconds = 5;

        $sec = new DateInterval('PT'.$seconds.'S');
        $veg = new DateTime();
        $veg->setTimestamp($oldtime);
        $veg->add($sec);

        $timeArray = array();
        $timeArray[$GLOBALS["return_userdetails_time"]] = $veg->getTimestamp();
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        $this->updateTable($oldtime,$veg->getTimestamp(),TimedAction::$EXAMINE,$tileid,0);
        return $timeArray;
    }

    /**
     * Elkezdi a letelepedést
     * @param $tileid
     * @return array
     */
    public function setSettleDown($tileid) {
        $kezdo = new DateTime("now");
        $oldtime = $kezdo->getTimestamp();

        $seconds = 30;

        $sec = new DateInterval('PT'.$seconds.'S');
        $veg = new DateTime();
        $veg->setTimestamp($oldtime);
        $veg->add($sec);

        $timeArray = array();
        $timeArray[$GLOBALS["return_userdetails_time"]] = $veg->getTimestamp();
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        $this->updateTable($oldtime,$veg->getTimestamp(),TimedAction::$SETTLEDOWN,$tileid,0);
        return $timeArray;
    }

    /**
     * Elkezdi kitermelni az első nyersanyagot
     * @param $type
     * @param $tileid
     * @return array
     */
    public function setMineResource1($type,$tileid) {
        $tool = new Tool($this->db,$this->userid);
        $time = $tool->getMiningTime($type);
        $this->getOldAndNewTime($oldtime,$newtime,$time);

        $timeArray = array();
        $timeArray[$GLOBALS["return_userdetails_time"]] = $newtime;
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        $this->updateTable($oldtime,$newtime,TimedAction::$RESOURCE1,$type,$tileid);
        return $timeArray;
    }

    /**
     * Elkezdi kitermelni a 2. nyersanyagot
     * @param $type
     * @param $tileid
     * @return array
     */
    public function setMineResource2($type,$tileid) {
        $this->getOldAndNewTime($oldtime,$newtime,5);

        $timeArray = array();
        $timeArray[$GLOBALS["return_userdetails_time"]] = $newtime;
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        $this->updateTable($oldtime,$newtime,TimedAction::$RESOURCE2,$type,$tileid);
        return $timeArray;
    }

    /**
     * Elkezdi kitermelni a 3. nyersanyagot
     * @param $type
     * @param $tileid
     * @return array
     */
    public function setMineResource3($type,$tileid) {
        $this->getOldAndNewTime($oldtime,$newtime,30);

        $timeArray = array();
        $timeArray[$GLOBALS["return_userdetails_time"]] = $newtime;
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        $this->updateTable($oldtime,$newtime,TimedAction::$RESOURCE3,$type,$tileid);
        return $timeArray;
    }

    /**
     * Elkezd építeni egy épületet
     * @param $tileid
     * @param $tileslice
     * @param $buildingtype
     * @return array
     */
    public function setBuild($tileid,$tileslice,$buildingtype) {
        $this->getOldAndNewTime($oldtime,$newtime,10);
        $timeArray = array();
        $timeArray[$GLOBALS["return_userdetails_time"]] = $newtime;
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        $building = new Building($this->db,$this->userid);
        $building->addBuildingToUser($tileid,$tileslice,$buildingtype,0);
        $this->updateTable($oldtime,$newtime,TimedAction::$BUILD,$tileid,$tileslice);
        return $timeArray;

    }

    /**
     * Elkezd kifejleszteni egy épületet
     * @param $buildingtype
     * @param $buildinglevel
     * @return array
     */
    public function setDevelop($buildingtype,$buildinglevel) {
        $this->getOldAndNewTime($oldtime,$newtime,15);
        $timeArray = array();
        $timeArray[$GLOBALS["return_userdetails_time"]] = $newtime;
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        $this->updateTable($oldtime,$newtime,TimedAction::$DEVELOPBUILDING,$buildingtype,$buildinglevel);
        return $timeArray;

    }

    /**
     * Elkezd kifejleszteni egy eszközt
     * @param $tooltype
     * @return array
     */
    public function setDevelopTool($tooltype) {
        $this->getOldAndNewTime($oldtime,$newtime,15);
        $timeArray = array();
        $timeArray[$GLOBALS["return_userdetails_time"]] = $newtime;
        $timeArray[$GLOBALS["return_userdetails_oldtime"]] = $oldtime;

        $this->updateTable($oldtime,$newtime,TimedAction::$DEVELOPTOOL,$tooltype,0);
        return $timeArray;

    }
}
?>