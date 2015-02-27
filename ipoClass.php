<?php
ini_set('MAX_EXECUTION_TIME', -1);
require_once("openstreetmap.php");
require_once("userDetailsClass.php");
class IPO {
    private $userid;
    private $db;

    //ebben található az összes node-id és a hozzá rendelt lat és long
    private $nodes;

    private $ipoSearch = 0.1;

    function __construct($db,$userid) {
        $this->db = $db;
        $this->userid = $userid;
        set_time_limit (43200);
    }
    //47.599826,19.35417, $this->ipoSearch

    /**
     * Lekérdezi a koordináta alapján a méretben beállított távolságra lévő látványosságokat
     * @param $latitude a szélességi fok
     * @param $longitude a hosszúsági fok
     * @param $size a méret
     * @return bool
     */
    function getAllIPOs($latitude, $longitude, $size) {
        $osm = new OpenStreetMap($latitude,$longitude,$size);

        $xmlString = $osm->getIntelligencePointsXML();

        $timedout = strpos($xmlString,"timed out");
        if($xmlString == "" || $timedout > 0) {
            return false;
        }
        $xmlObject = simplexml_load_string($xmlString);
        $this->xmlParser($xmlObject,$ipoNodes,$ipoWays);
        echo "Nodecoordinates: <br>";
        //print_r($this->nodes);
        //echo "<br><br><br><br>";
        //echo "Nodes: <br>";
        print_r($ipoNodes);
        foreach($ipoNodes as $node) {
            $lat = $this->nodes[$node["id"]]["lat"];
            $lon = $this->nodes[$node["id"]]["lon"];
            //echo $node["name"]."<br>";
            $this->saveToDatabaseNode($lat,$lon,$node["name"],$node["url"]);
            //echo $name." latlon: ".$lat.", ".$lon." farlatlon: ".$farlat.", ".$farlon."<br>";
        }
        echo "<br><br><br>Ways: <br>";
        print_r($ipoWays);


        foreach($ipoWays as $way) {
            $this->getCenterAndFurthest($way,$name,$lat,$lon,$farlat,$farlon,$url);
            $this->saveToDatabaseWay($lat,$lon,$farlat,$farlon,$name,$url);
            //echo $name." latlon: ".$lat.", ".$lon." farlatlon: ".$farlat.", ".$farlon."<br>";
        }
        return true;
    }

    /**
     * Elment egy nagyobb kiterjedésű látványosságot az adatbázisba - wayt
     * @param $latitude
     * @param $longitude
     * @param $farlatitude
     * @param $farlongitude
     * @param $name
     * @param $url
     */
    function saveToDatabaseWay($latitude,$longitude,$farlatitude,$farlongitude,$name,$url) {
        if(!$this->isExist($latitude,$longitude)) {
            $sql = "SET NAMES UTF8";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $sql = "INSERT INTO ".$GLOBALS["table_ipo_title"]." (".$GLOBALS["table_ipo_lat"].",".$GLOBALS["table_ipo_lon"].", ".$GLOBALS["table_ipo_farlat"].", ".$GLOBALS["table_ipo_farlon"].", ".$GLOBALS["table_ipo_name"].", ".$GLOBALS["table_ipo_url"].") VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($latitude."",$longitude."",$farlatitude."",$farlongitude."",$name,$url));
            $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Elment egy pontszerű látványosságot az adatbázisba
     * @param $latitude
     * @param $longitude
     * @param $name
     * @param $url
     */
    function saveToDatabaseNode($latitude,$longitude,$name,$url) {
        if(!$this->isExist($latitude,$longitude)) {
            $sql = "SET NAMES UTF8";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $sql = "INSERT INTO ".$GLOBALS["table_ipo_title"]." (".$GLOBALS["table_ipo_lat"].",".$GLOBALS["table_ipo_lon"].", ".$GLOBALS["table_ipo_name"].", ".$GLOBALS["table_ipo_url"].") VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($latitude."",$longitude."",$name,$url));
            $stmt->fetch(PDO::FETCH_ASSOC);
        }

    }

    /**
     * Visszaadja, hogy létezik-e már a koordinátán látványosság
     * @param $latitude
     * @param $longitude
     * @return bool
     */
    function isExist($latitude,$longitude) {
        $sql = "SELECT * FROM ".$GLOBALS["table_ipo_title"]." WHERE ".$GLOBALS["table_ipo_lat"]." = ? AND ".$GLOBALS["table_ipo_lon"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($latitude."",$longitude.""));
        $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $stmt->rowCount();
        return ($count == 0 ? false : true);
    }

    /**
     * A karakter felfedez egy látványosságot
     * @param $ipoid
     */
    function userExamineIPO($ipoid) {
        if(!$this->isKnownIPOExists($ipoid)) {
            $sql = "INSERT INTO ".$GLOBALS["table_knownipo_title"]." (".$GLOBALS["table_knownipo_userid"].",".$GLOBALS["table_knownipo_ipoid"].") VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($this->userid,$ipoid));
            $stmt->fetch(PDO::FETCH_ASSOC);

            $userdetails = new UserDetails($this->db,$this->userid);
            $userdetails->addOneIpo();
        }
    }

    /**
     * Lekérdezi, hogy létezik-e már az adatbázisban az intelligenciapont
     * @param $ipoid
     * @return bool
     */
    function isKnownIPOExists($ipoid) {
        $sql = "SELECT * FROM ".$GLOBALS["table_knownipo_title"]." WHERE ".$GLOBALS["table_knownipo_userid"]." = ? AND ".$GLOBALS["table_knownipo_ipoid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->userid,$ipoid));
        $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $stmt->rowCount();
        return ($count == 0 ? false : true);
    }

    /**
     * Lekérdezi a megadott koordináták közötti összes látványosságot
     * @param $fromlat
     * @param $fromlon
     * @param $tolat
     * @param $tolon
     * @return array
     */
    function getUserIPOs($fromlat,$fromlon,$tolat,$tolon) {
        $sql = "SET NAMES UTF8";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $sql = "SELECT * FROM ".$GLOBALS["table_ipo_title"]." WHERE ".$GLOBALS["table_ipo_lat"]." > ? AND ".$GLOBALS["table_ipo_lat"]." < ? AND ".$GLOBALS["table_ipo_lon"]." > ? AND ".$GLOBALS["table_ipo_lon"]." < ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($fromlat."",$tolat."",$fromlon."",$tolon.""));
        $resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ret = array();
        foreach($resultArray as $result) {
            $res = $result;
            if($this->isKnownIPOExists($result[$GLOBALS["table_ipo_id"]])) {
                $res[$GLOBALS["other_ipo_known"]] = 1;
            }
            else {
                $res[$GLOBALS["other_ipo_known"]] = 0;
            }
            array_push($ret,$res);
        }
        return $ret;
    }

    /**
     * Végigmegy a megadott mérettel, négyzetekben, és lekéri az összes IPO-t FROM: DK TO ÉNY
     */
    function generateIPOs($fromlatitude,$fromlongitude,$tolatitude,$tolongitude,$size) {
        $latShift = 0;
        $longShift = 0;
        if($fromlatitude > $tolatitude || $fromlongitude > $tolongitude) {
            echo "Error in latitude or longitude";
            return false;
        }
        do {
            $longShift = 0;
            do {
                echo "latlon: ".($fromlatitude+$latShift).", ".($fromlongitude+$longShift)."<br>";
                $success = $this->getAllIPOs($fromlatitude+$latShift,$fromlongitude+$longShift,$size);
                if($success) {
                    $longShift += ($size*2);
                }
            }
            while($fromlongitude+$longShift < $tolongitude);
            $latShift += ($size*2);
        }
        while($fromlatitude+$latShift < $tolatitude);
    }

    /**
     * Az xml fájlt beparse-olja, majd visszaadja referenciában a betöltött információkat
     * @param $xmlObject az xml adat
     * @param $ipoNodes a pontszerű látványosságok
     * @param $ipoWays a nagy kiterjedésű látványosságok
     */
    function xmlParser($xmlObject,&$ipoNodes,&$ipoWays) {
        //az ipoNode-ban van az összes olyan érdekesség, ami csak egy node
        //szerkezete: nodeid => 1234, név => 'abc', website => 'http://asd.com'
        $ipoNodes = array();

        $ipoWays = array();

        $ways = array(); //csak azok a way-ek, amik vizek

        foreach($xmlObject as $obj) {
            if($obj->getName() == 'way') {
                array_push($ways,$obj);
            }
            /*else if($obj->getName() == 'relation') {
                array_push($relations,$obj);
            }*/
            else if($obj->getName() == 'node') {
                $this->nodes[(string)$obj['id']] = array('lat' => (double)$obj['lat'], 'lon' => (double)$obj['lon']);
                $ipoNode = array();
                $ipoNode['id'] = (string)$obj['id'];
                $hozzaad = false;
                //print_r($obj->children());
                $url = "";
                $urlRank = 0;
                foreach ($obj->children() as $child) {
                    //echo $child['k']."<br>";
                    if($child->getName() == 'tag' && $child['k'] == 'name') {
                        $ipoNode['name'] = (string)$child['v'];
                        //$hozzaad = true;
                    }
                    if(!isset($ipoNode['name'])) {
                        if($child->getName() == 'tag' && $child['k'] == 'building')  $ipoNode['name'] = (string)$child['v'];
                        if($child->getName() == 'tag' && $child['k'] == 'historic')  $ipoNode['name'] = (string)$child['v'];
                        if($child->getName() == 'tag' && $child['k'] == 'tourism')  $ipoNode['name'] = (string)$child['v'];
                    }
                    if($urlRank < 10 && $child->getName() == 'tag' && $child['k'] == 'website') {
                        $urlRank = 10;
                        $url = (string)$child['v'];
                    }
                    if($urlRank < 9 && $child->getName() == 'tag' && $child['k'] == 'source') {
                        if(strpos((string)$child['v'], 'http') !== FALSE) {
                            $urlRank = 9;
                            $url = (string)$child['v'];
                        }
                    }
                    if($urlRank < 8 && $child->getName() == 'tag' && $child['k'] == 'wikipedia:en') {
                        if(strpos((string)$child['v'], 'http') !== FALSE) {
                            $urlRank = 8;
                            $url = (string)$child['v'];
                        }
                        else {
                            $urlRank = 8;
                            $url = "http://en.wikipedia.org/wiki/".(string)$child['v'];
                        }
                        $url = str_replace(" ", "_", $url);
                    }
                    if($urlRank < 7 && $child->getName() == 'tag' && $child['k'] == 'wikipedia:hu') {
                        if(strpos((string)$child['v'], 'http') !== FALSE) {
                            $urlRank = 7;
                            $url = (string)$child['v'];
                        }
                        else {
                            $urlRank = 7;
                            $url = "http://hu.wikipedia.org/wiki/".(string)$child['v'];
                        }
                        $url = str_replace(" ", "_", $url);
                    }
                    if($urlRank < 6 && $child->getName() == 'tag' && $child['k'] == 'wikipedia') {
                        if(strpos((string)$child['v'], 'http') !== FALSE) {
                            $urlRank = 6;
                            $url = (string)$child['v'];
                        }
                        else {
                            $urlRank = 6;
                            $url = "http://wikipedia.org/wiki/".(string)$child['v'];
                        }
                        $url = str_replace(" ", "_", $url);
                    }
                }
                if(isset($ipoNode['name'])) {
                    if($ipoNode['name'] == 'church') $ipoNode['name'] = 'Church';
                    else if($ipoNode['name'] == 'cathedral') $ipoNode['name'] = 'Cathedral';
                    else if($ipoNode['name'] == 'archaeological_site') $ipoNode['name'] = 'Archaeological site';
                    else if($ipoNode['name'] == 'aircraft') $ipoNode['name'] = 'Aircraft';
                    else if($ipoNode['name'] == 'castle') $ipoNode['name'] = 'Castle';
                    else if($ipoNode['name'] == 'city_gate') $ipoNode['name'] = 'City gate';
                    else if($ipoNode['name'] == 'memorial') $ipoNode['name'] = 'Memorial';
                    else if($ipoNode['name'] == 'monument') $ipoNode['name'] = 'Monument';
                    else if($ipoNode['name'] == 'ruins') $ipoNode['name'] = 'Ruins';
                    else if($ipoNode['name'] == 'rune_stone') $ipoNode['name'] = 'Rune Stone';
                    else if($ipoNode['name'] == 'tree_shrine') $ipoNode['name'] = 'Tree shrine';
                    else if($ipoNode['name'] == 'wayside_cross') $ipoNode['name'] = 'Wayside cross';
                    else if($ipoNode['name'] == 'wayside_shrine') $ipoNode['name'] = 'Wayside shrine';
                    else if($ipoNode['name'] == 'attraction') $ipoNode['name'] = 'Attraction';
                    else if($ipoNode['name'] == 'artwork') $ipoNode['name'] = 'Artwork';
                    else if($ipoNode['name'] == 'museum') $ipoNode['name'] = 'Museum';
                    else if($ipoNode['name'] == 'viewpoint') $ipoNode['name'] = 'Viewpoint';
                }
                //ha még nincs url, akkor google keresés
                if($urlRank < 5 && isset($ipoNode['name'])) {
                    $urlRank = 5;
                    $url = "http://googl.com/#q=".$ipoNode['name'];
                }
                $ipoNode['url'] = $url;
                if(isset($ipoNode['name'])) {
                    array_push($ipoNodes,$ipoNode);
                }
                //if($hozzaad) {

                //}
            }
        }
        print_r($ipoNodes);


        //végigmegyünk a way-eken, a node-id-ket összegyűjtjük és elmentjük a neveket az ipoWays-be
        foreach($ways as $way) {
            $ipoWay = array();
            $url = "";
            $urlRank = 0;
            foreach ($way->children() as $nd) {
                if($nd->getName() == 'nd') {
                    array_push($ipoWay,(string)$nd['ref']);
                }
                else if($nd->getName() == 'tag' && $nd['k'] == 'name') {
                    $ipoWay['name'] = (string)$nd['v'];
                }
                if(!isset($ipoWay['name'])) {
                    if($nd->getName() == 'tag' && $nd['k'] == 'building')  $ipoWay['name'] = (string)$nd['v'];
                    if($nd->getName() == 'tag' && $nd['k'] == 'historic')  $ipoWay['name'] = (string)$nd['v'];
                    if($nd->getName() == 'tag' && $nd['k'] == 'tourism')  $ipoWay['name'] = (string)$nd['v'];
                }

                if($urlRank < 10 && $nd->getName() == 'tag' && $nd['k'] == 'website') {
                    $urlRank = 10;
                    $url = (string)$nd['v'];
                }
                if($urlRank < 9 && $nd->getName() == 'tag' && $nd['k'] == 'source') {
                    if(strpos((string)$nd['v'], 'http') !== FALSE) {
                        $urlRank = 9;
                        $url = (string)$nd['v'];
                    }
                }
                if($urlRank < 8 && $nd->getName() == 'tag' && $nd['k'] == 'wikipedia:en') {
                    if(strpos((string)$nd['v'], 'http') !== FALSE) {
                        $urlRank = 8;
                        $url = (string)$nd['v'];
                    }
                    else {
                        $urlRank = 8;
                        $url = "http://en.wikipedia.org/wiki/".(string)$nd['v'];
                    }
                    $url = str_replace(" ", "_", $url);
                }
                if($urlRank < 7 && $nd->getName() == 'tag' && $nd['k'] == 'wikipedia:hu') {
                    if(strpos((string)$nd['v'], 'http') !== FALSE) {
                        $urlRank = 7;
                        $url = (string)$nd['v'];
                    }
                    else {
                        $urlRank = 7;
                        $url = "http://hu.wikipedia.org/wiki/".(string)$nd['v'];
                    }
                    $url = str_replace(" ", "_", $url);
                }
                if($urlRank < 6 && $nd->getName() == 'tag' && $nd['k'] == 'wikipedia') {
                    if(strpos((string)$nd['v'], 'http') !== FALSE) {
                        $urlRank = 6;
                        $url = (string)$nd['v'];
                    }
                    else {
                        $urlRank = 6;
                        $url = "http://wikipedia.org/wiki/".(string)$nd['v'];
                    }
                    $url = str_replace(" ", "_", $url);
                }
            }
            if(isset($ipoWay['name'])) {
                if($ipoWay['name'] == 'church') $ipoWay['name'] = 'Church';
                else if($ipoWay['name'] == 'cathedral') $ipoWay['name'] = 'Cathedral';
                else if($ipoWay['name'] == 'archaeological_site') $ipoWay['name'] = 'Archaeological site';
                else if($ipoWay['name'] == 'aircraft') $ipoWay['name'] = 'Aircraft';
                else if($ipoWay['name'] == 'castle') $ipoWay['name'] = 'Castle';
                else if($ipoWay['name'] == 'city_gate') $ipoWay['name'] = 'City gate';
                else if($ipoWay['name'] == 'memorial') $ipoWay['name'] = 'Memorial';
                else if($ipoWay['name'] == 'monument') $ipoWay['name'] = 'Monument';
                else if($ipoWay['name'] == 'ruins') $ipoWay['name'] = 'Ruins';
                else if($ipoWay['name'] == 'rune_stone') $ipoWay['name'] = 'Rune Stone';
                else if($ipoWay['name'] == 'tree_shrine') $ipoWay['name'] = 'Tree shrine';
                else if($ipoWay['name'] == 'wayside_cross') $ipoWay['name'] = 'Wayside cross';
                else if($ipoWay['name'] == 'wayside_shrine') $ipoWay['name'] = 'Wayside shrine';
                else if($ipoWay['name'] == 'attraction') $ipoWay['name'] = 'Attraction';
                else if($ipoWay['name'] == 'artwork') $ipoWay['name'] = 'Artwork';
                else if($ipoWay['name'] == 'museum') $ipoWay['name'] = 'Museum';
                else if($ipoWay['name'] == 'viewpoint') $ipoWay['name'] = 'Viewpoint';
            }
            //ha még nincs url, akkor google keresés
            if($urlRank < 5 && isset($ipoWay['name'])) {
                $urlRank = 5;
                $url = "http://googl.com/#q=".$ipoWay['name'];
            }
            $ipoWay['url'] = $url;
            array_push($ipoWays,$ipoWay);
        }

        //relations - beállítja az összes relation id-jét egy tömbbe - nincs relation
        /*foreach($relations as $relation) {
            $relationWay = array();
            foreach ($relation->children() as $rel) {
                if($rel->getName() == 'member') {
                    if($rel['role'] == 'outer') {
                        array_push($relationWay,(string)$rel['ref']);
                    }
                }
            }
            array_push($relationWays,$relationWay);
        }
        foreach($relationWays as $relationWay) { //betölti a realtion-ökben található node-id-ket a waterWayAndRelationCoordinateIDs-be
            $oneRelationCoordinates = array();
            foreach($relationWay as $oneRelationWay) {
                foreach($allWays[$oneRelationWay]->children() as $relationChild) {
                    if($relationChild->getName() == 'nd') {
                        $coord = (string)$relationChild['ref'];
                        array_push($oneRelationCoordinates,$coord);
                    }
                }
                array_push($waterWayAndRelationCoordinateIDs,$oneRelationCoordinates);
            }
        }*/
        echo "<br><br><br><br><br><br>";
    }

    /**
     * Visszaadja egy nagy kiterjedésű látványosság nevét, középpontját és a legtávolabbi pontját
     * @param $way
     * @param $name
     * @param $centerlat
     * @param $centerlon
     * @param $farlat
     * @param $farlon
     * @param $url
     */
    function getCenterAndFurthest($way,&$name,&$centerlat,&$centerlon,&$farlat,&$farlon,&$url) {
        $averageLat = 0.0;
        $averageLatSum = 0;
        $averageLon = 0.0;
        $averageLonSum = 0;
        $name = "Unknown";
        $url = "";
        foreach($way as $key=>$value) {
            if((string)$key != "name" && (string)$key != "url") {
                $averageLat += $this->nodes[$value]["lat"];
                $averageLon += $this->nodes[$value]["lon"];
                $averageLatSum++;
                $averageLonSum++;
            }
            else if((string)$key == "url") {
                $url = $value;
            }
            else {
                $name = $value;
            }
        }
        //a középpont
        $lat = $averageLat/$averageLatSum;
        $lon = $averageLon/$averageLonSum;

        $centerlat = $lat;
        $centerlon = $lon;
        //a legtávolabbi pont megtalálása

        $farlat = $lat;
        $farlon = $lon;
        foreach($way as $key=>$value) {
            if((string)$key != "name" && (string)$key != "url") {
                $latdif = abs($this->nodes[$value]["lat"] - $lat);
                $londif = abs($this->nodes[$value]["lon"] - $lon);
                $oldlatdif = abs($farlat - $lat);
                $oldlondif = abs($farlon - $lon);
                if($latdif+$londif > $oldlatdif+$oldlondif) {
                    $farlat = $this->nodes[$value]["lat"];
                    $farlon = $this->nodes[$value]["lon"];
                }
            }
        }
    }
}
?>