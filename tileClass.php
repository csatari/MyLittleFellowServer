<?php
ini_set('MAX_EXECUTION_TIME', 900);
ini_set('memory_limit', '1G');
require_once('openstreetmap.php');

class Tile {
	private $db;
	private $centerlat = 0.0;
	private $centerlong = 0.0;
	private $tileSize = 0.005;
	private $hugeWaterSearch = 0.1; //ezzel a koordinátalépésekkel olvassa be a vizeket
	//mysql adatbázisban tárolt tábla és oszlopok nevei
	/*private $table_title = "tile";
	private $table_id = "id";
	private $table_lat = "latitude";
	private $table_long = "longitude";
	private $table_type = "type";
	private $table_resources = "resources";*/
	//típusazonosítok
	private $table_river = 8;
	private $table_water = 6;
    public static $TOWN = 7;
	
	private $nodes;//benne van az összes koordinátapont
	
	public $latitude;
	public $longitude;
	
	//mysqli változó szükséges az adatbázis eléréséhez osztály szinten is
	function __construct ($db) {
		$this->db = $db;
		$this->nodes = array();
		set_time_limit (43200);
        $this->tileSize = $GLOBALS["tilesize"];
	}
	//lekérdezi az összes előforduló tile-t
	/*public function getAllTiles() {
		$stmt = $this->mysqli->prepare('SELECT * FROM '.$this->table_title);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()) {
			$all[] = $row;
		}
		return $all;
		
	}*/
	
	//függvények
	//hozzáad egy új tile-t koordináták alapján. 1.: megvizsgálja, hogy létezik-e. 2.:Ha nem létezik, megnézi, hogy folyó-e. 
	//3.:Ha nem folyó, generál egy új biome-t. 4.: hozzáadja a tile-t az adatbázishoz.
	//Ha már létezett, akkor csak az id-t adja vissza
	//be: latitude, longitude  ki: id
	public function addNewTile($lat,$long) {
		$lat = $this->getCenterLat($lat);
		$long = $this->getCenterLong($long);
		if(!$this->isTileExists($lat,$long)) {
			$isRiver = $this->isRiver($lat,$long);
			if(!$isRiver) {
				$type = $this->generateBiome($lat,$long);
				$row = $this->getTileByLatLong($lat,$long);
				return $row[$GLOBALS["table_tile_id"]];
			}
			else {
				$this->insertNewTile($lat,$long,$this->table_river);
				$lastRow = $this->getLastTile();
				return $lastRow[$GLOBALS["table_tile_id"]];
			}
		}
		else {
			$row = $this->getTileByLatLong($lat,$long);
			return $row[$GLOBALS["table_tile_id"]];
		}
	}
	//Generál nyersanyagokat a megadott típushoz
	private function randomResources($type) {
		$resources = rand(5,30);
		return $resources;
	}
	//Hozzáadja a megadott koordinátát az adatbázishoz a megadott típussal.
	//be: latitude,longitude,típus  ki:a sor
	public function insertNewTile($lat,$long,$type) {
		$resource1 = $this->randomResources($type);
		$resource2 = $this->randomResources($type);
		$resource3 = $this->randomResources($type);
		$sql = "INSERT INTO ".$GLOBALS["table_tile_title"]." (".$GLOBALS["table_tile_lat"].",".$GLOBALS["table_tile_long"].", ".$GLOBALS["table_tile_type"].", ".$GLOBALS["table_tile_resource1"].", ".$GLOBALS["table_tile_resource2"].", ".$GLOBALS["table_tile_resource3"].") VALUES (?, ?, ?, ?, ?, ?)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($lat."",$long."",$type,$resource1,$resource2,$resource3));
		$stmt->fetch(PDO::FETCH_ASSOC);
		$lastRow = $this->getLastTile();
		return $lastRow;
	}
	//Beállítja egy ID-vel megadott tile-nak a típusát
	//type: 6 - víz, 8 - folyó
	//be: id, típus  ki: -
	function setTileTypeByID($id,$type) {
		$sql = "UPDATE ".$GLOBALS["table_tile_title"]." SET ".$GLOBALS["table_tile_type"]." = ? WHERE ".$GLOBALS["table_tile_id"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($type,$id));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}
	//Beállítja egy ID-vel megadott tile-nak a nyersanyagkészletét
	//be: id, nyersanyagszám  ki:-
	function setTileResourceByID($id,$resource1,$resource2,$resource3) {
		$sql = "UPDATE ".$GLOBALS["table_tile_title"]." SET ".$GLOBALS["table_tile_resource1"]."= ?, ".$GLOBALS["table_tile_resource2"]."=?, ".$GLOBALS["table_tile_resource3"]."=? WHERE ".$GLOBALS["table_tile_id"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($resource1,$resource2,$resource3,$id));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}

    /**
     * Beállítja az 1. nyersanyagot
     * @param $id
     * @param $resource1
     */
    function setTileResource1ByID($id,$resource1) {
        $sql = "UPDATE ".$GLOBALS["table_tile_title"]." SET ".$GLOBALS["table_tile_resource1"]."= ? WHERE ".$GLOBALS["table_tile_id"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($resource1,$id));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Beállítja az 2. nyersanyagot
     * @param $id
     * @param $resource2
     */
    function setTileResource2ByID($id,$resource2) {
        $sql = "UPDATE ".$GLOBALS["table_tile_title"]." SET ".$GLOBALS["table_tile_resource2"]."= ? WHERE ".$GLOBALS["table_tile_id"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($resource2,$id));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * Beállítja az 3. nyersanyagot
     * @param $id
     * @param $resource3
     */
    function setTileResource3ByID($id,$resource3) {
        $sql = "UPDATE ".$GLOBALS["table_tile_title"]." SET ".$GLOBALS["table_tile_resource3"]."= ? WHERE ".$GLOBALS["table_tile_id"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($resource3,$id));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }
	//Visszaadja egy ID szerint megadott tile adatait
	//be: id  ki: teljes tile
	function getTileById($id) {
		$sql = "SELECT * FROM ".$GLOBALS["table_tile_title"]." WHERE ".$GLOBALS["table_tile_id"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($id));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray;
	}
	//Visszaadja egy koordináta szerint megadott tile adatait
	//be: lat, long  ki: teljes tile
	function getTileByLatLong($lat,$long) {
		$sql = "SELECT * FROM ".$GLOBALS["table_tile_title"]." WHERE ".$GLOBALS["table_tile_lat"]." = ? AND ".$GLOBALS["table_tile_long"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($lat."",$long.""));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray;
	}
	//lekérdezi az id szerinti legutolsó sort (a legnagyobb id-jűt)
	//be: nincs  ki: utolsó ID szerinti sor
	function getLastTile() {
		$sql = "SELECT * FROM ".$GLOBALS["table_tile_title"]." ORDER BY ".$GLOBALS["table_tile_id"]." DESC LIMIT 0,1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray;
	}
	//true, ha létezik a megadott latlong-gal a sor, egyébként false
	//be: latitude, longitude  ki: true vagy false
	function isTileExists($lat,$long) {
		$sql = "SELECT * FROM ".$GLOBALS["table_tile_title"]." WHERE ".$GLOBALS["table_tile_lat"]." = ? AND ".$GLOBALS["table_tile_long"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($lat."",$long.""));
		$stmt->fetch(PDO::FETCH_ASSOC);
		$count = $stmt->rowCount();
		return ($count == 0 ? false : true);
	}

    /**
     * Lekérdezi a terület földesurát
     * @param $tileid
     * @return mixed
     */
    function getOwner($tileid) {
        $sql = "SELECT ".$GLOBALS["table_tile_owner"]." FROM ".$GLOBALS["table_tile_title"]." WHERE ".$GLOBALS["table_tile_id"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($tileid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_tile_owner"]];
    }

    /**
     * Beállítja a terület földesurát
     * @param $tileid
     * @param $userid
     */
    function setOwner($tileid,$userid) {
        $sql = "UPDATE ".$GLOBALS["table_tile_title"]." SET ".$GLOBALS["table_tile_owner"]."= ? WHERE ".$GLOBALS["table_tile_id"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($userid,$tileid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lekéri, hogy mikor volt utoljára növekedve a terület
     * @param $tileid
     * @return mixed
     */
    function getLastGrown($tileid) {
        $sql = "SELECT ".$GLOBALS["table_tile_lastgrown"]." FROM ".$GLOBALS["table_tile_title"]." WHERE ".$GLOBALS["table_tile_id"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($tileid));
        $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultArray[$GLOBALS["table_tile_lastgrown"]];
    }

    /**
     * Beállítja a terület növekedését
     * @param $tileid
     * @param $time - az új idő
     */
    function setLastGrown($tileid,$time) {
        $sql = "UPDATE ".$GLOBALS["table_tile_title"]." SET ".$GLOBALS["table_tile_lastgrown"]."= ? WHERE ".$GLOBALS["table_tile_id"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($time,$tileid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /*Lekérdezi a megadott felhasználóról, hogy földesúr-e*/
    function amIOwner($tileid,$userid) {
        $res = array();
        if($this->getOwner($tileid) == $userid) {
            $res[$GLOBALS["table_tile_owner"]] = 1;
        }
        else {
            $res[$GLOBALS["table_tile_owner"]] = 0;
        }
        return $res;
    }

    /**
     * Növeli a terület nyersanyagait
     * @param $tileid
     */
    function growTile($tileid) {
        $lastgrown = $this->getLastGrown($tileid);
        $growing = 0;
        $now = new DateTime("now");
        if($lastgrown == 0) {
            $growing = 1;
        }
        else {
            $lastGrownDate = new DateTime();
            $lastGrownDate->setTimestamp($lastgrown);

            $diff = $now->diff($lastGrownDate);
            $growing = round(sqrt($diff->days));
        }
        if($growing != 0) {
            $this->setLastGrown($tileid,$now->getTimestamp());
        }
        $tile = $this->getTileById($tileid);
        $this->setTileResourceByID($tileid,$tile[$GLOBALS["table_tile_resource1"]]+$growing,$tile[$GLOBALS["table_tile_resource2"]]+$growing,$tile[$GLOBALS["table_tile_resource3"]]+$growing);

    }

	//megadja a tile-on belül a legkeletibb longitude koordinátát
	function getEast() {
		return $this->longitude+($this->tileSize/2);
	}
	//megadja a tile-on belül a legészakibb latitude koordinátát
	function getNorth() {
		return $this->latitude+($this->tileSize/2);
	}
	//megadja a tile-on belül a legnyugatibb longitude koordinátát
	function getWest() {
		return $this->longitude-($this->tileSize/2);
	}
	//megadja a tile-on belül a legdélibb latitude koordinátát
	function getSouth() {
		return $this->latitude-($this->tileSize/2);
	}
	//beállítja osztály szinten a koordinátákat és középre rendezi
	function setCenterLat($lat) { 
		$this->latitude = ((round(($lat-$this->centerlat)/($this->tileSize)))*$this->tileSize)+$this->centerlat;
	}
	//a megadott koordinátának visszaadja a középre rendezését
	function getCenterLat($lat) { 
		return ((round(($lat-$this->centerlat)/($this->tileSize)))*$this->tileSize)+$this->centerlat;
	}
	//beállítja osztály szinten a koordinátákat és középre rendezi
	function setCenterLong($long) { 
		$this->longitude = ((round(($long-$this->centerlong)/($this->tileSize)))*$this->tileSize)+$this->centerlong;
	}
	//a megadott koordinátának visszaadja a középre rendezését
	function getCenterLong($long) { 
		return ((round(($long-$this->centerlong)/($this->tileSize)))*$this->tileSize)+$this->centerlong;
	}
	//lekérdezi a koordináta körüli folyókat az openstreetmap szerverről és visszaadja, hogy van-e folyó
	//be: lat, long  ki: true vagy false
	function isRiver($lat,$long) {
		$osm = new OpenStreetMap($lat,$long, $this->tileSize);
        $success = false;
        while(!$success) {
            $xmlString = $osm->getRiverXML();
            $timedout = strpos($xmlString,"timed out");
            if($xmlString == "" || $timedout > 0) {
                $success = true;
            }
            else {
                $success = true;
            }
        }

		$xmlObject = simplexml_load_string($xmlString);
		return $this->riverXmlParser($xmlObject);
	}
	//A megadott koordinátát véletlenszerűen eltolja (irány és méret szerint), 
	//majd egy véletlenszerű mérettel rombusz alakban tile-okat generál véletlenszerű típussal
	//Ha már egy tile foglalt, akkor azt kihagyja
	//be: lat, long  ki: -
	function generateBiome($lat,$long) {
		//random type - can be: 0-4
		$type = rand(0,4);
		//random size
		$size = 0;
		if($type == 4) {
			$size = rand(1,3);
		}
		else {
			$size = rand(1,6);
		}
		//echo $type.",".$size."<br>";
		//random center, direction (n,e,w,s)
		$shift = rand(0,$size-1);
		$direction = rand(1,4);
		$centerLat = $lat;
		$centerLong = $long;
		if($direction == 1) $centerLat = $lat-($this->tileSize*$shift);
		else if($direction == 2) $centerLat = $lat+($this->tileSize*$shift);
		else if($direction == 3) $centerLong = $long+($this->tileSize*$shift);
		else if($direction == 4) $centerLong = $long+($this->tileSize*$shift);
		//echo "shift:".$shift." dir:".$direction." ".$lat."->".$centerLat." ".$long."->".$centerLong."<br>";
		//végigmegyek az összes érintett koordinátán
		$futoLat = $centerLat;
		$futoLong = $centerLong- (($size-1)*$this->tileSize);
		$result = array();
		for($i = 0;$i < (2*$size)-1; $i++) {
			$index = 0;
			if($i<$size) $index = $i;
			else $index = (2*$size)-2-$i;
			
			$atloIndex = ($size-1)-$index;
			$jMeret = ((($size-1)-$atloIndex)*2)+1;
			
			$futoLat = $centerLat;
			$futoLat -= $index*$this->tileSize;
			for($j = 0; $j < $jMeret; $j++) {
				//echo $i.",".$j.": ".$futoLat." ".$futoLong."<br>";
				if(!$this->isTileExists($futoLat,$futoLong)) {
                    $isRiver = $this->isRiver($futoLat,$futoLong);
                    if(!$isRiver) {
                        $result[] = $this->insertNewTile($futoLat,$futoLong,$type);
                    }
                    else {
                        $result[] = $this->insertNewTile($futoLat,$futoLong,$this->table_river);
                    }
				}
				/*else {
					echo "már volt...<br>";
				}*/
				$futoLat += $this->tileSize;
			}
			$futoLong += $this->tileSize;
		}
		//echo json_encode($result);
	}
	//a megadott koordináták közötti részben hugeWaterSearch-ben megadott koordináták alapján négyzetekben lekérdezi a vizeket az openstreetmap szerverről
	//és belerakja a tile-ok közé
	//be: koordináta from, koordináta to
	//ki: -
	function generateWater($latfrom,$longfrom,$latto,$longto,$radius) {
        $this->hugeWaterSearch = $radius;
        echo "Kirajzolás megkezdése...";
		$latShift = 0;
		$longShift = 0;
		if($latfrom > $latto || $longfrom > $longto) {
			echo "Error in latitude or longitude";
			return false;
		}
		do {
			$longShift = 0;
			do {
				$success = $this->generateWaterAtLatLong($latfrom+$latShift,$longfrom+$longShift);
                if($success) {
                    $longShift += ($this->hugeWaterSearch*2);
                }
			}
			while($longfrom+$longShift < $longto);
			$latShift += ($this->hugeWaterSearch*2);
		}
		while($latfrom+$latShift < $latto);
        echo "<br><br>KÉSZ!";
	}
	//ténylegesen lekérdezi egy megadott koordinátán a vizeket és belerakja a tile-ok közé
	//be: koordináta
	//ki: -
	function generateWaterAtLatLong($latfrom,$longfrom) {
		$osm = new OpenStreetMap($latfrom,$longfrom, $this->hugeWaterSearch);
        $xmlString = $osm->getWaterXML();
        $timedout = strpos($xmlString,"timed out");
        if($xmlString == "" || $timedout > 0) {
            return false;
        }
		$xmlObject = simplexml_load_string($xmlString);
		$allWays = $this->xmlParser($xmlObject);
        print_r($allWays);
		foreach($allWays as $way) {
			$this->getNorthWestSouthEastPoint($way,$north,$west,$south,$east);
			$southCentered = $this->getCenterLat($south);
			$northCentered = $this->getCenterLat($north);
			$westCentered = $this->getCenterLong($west);
			$eastCentered = $this->getCenterLong($east);
			$latIndex = $southCentered;
			while($latIndex <= $northCentered) {
				$longIndex = $westCentered;
				while($longIndex <= $eastCentered) {
					//erre kell ellenőrizni, hogy tó-e
					//echo "futó: ".$latIndex." ".$longIndex." ";
					$benneVan = false;
					$futo = 0;
					$benneVan = $this->isPointInPolygon($way,$latIndex,$longIndex);
					while(!$benneVan && $futo <= 20) {
						if(!$benneVan) {
							$benneVan = $this->isPointInPolygon($way,$latIndex+($futo/10000),$longIndex);
						}
						if(!$benneVan) {
							$benneVan = $this->isPointInPolygon($way,$latIndex+($futo/10000),$longIndex+($futo/10000));
						}
						if(!$benneVan) {
							$benneVan = $this->isPointInPolygon($way,$latIndex,$longIndex+($futo/10000));
						}
						if(!$benneVan) {
							$benneVan = $this->isPointInPolygon($way,$latIndex-($futo/10000),$longIndex);
						}
						if(!$benneVan) {
							$benneVan = $this->isPointInPolygon($way,$latIndex-($futo/10000),$longIndex-($futo/10000));
						}
						if(!$benneVan) {
							$benneVan = $this->isPointInPolygon($way,$latIndex,$longIndex-($futo/10000));
						}
						if(!$benneVan) {
							$benneVan = $this->isPointInPolygon($way,$latIndex+($futo/10000),$longIndex-($futo/10000));
						}
						if(!$benneVan) {
							$benneVan = $this->isPointInPolygon($way,$latIndex-($futo/10000),$longIndex+($futo/10000));
						}
						$futo++;
					}
					if($benneVan) {
						//echo ' benneVan:'.$latIndex.' '.$longIndex.'<br>';
						if($this->isTileExists($latIndex,$longIndex)) {
                            $newid = 0;
							$this->setTileTypeByID($newid,$this->table_water);
                            $resource1 = $this->randomResources($this->table_water);
                            $resource2 = $this->randomResources($this->table_water);
                            $resource3 = $this->randomResources($this->table_water);
							$this->setTileResourceByID($newid,$resource1,$resource2,$resource3);
						}
						else {
							$this->insertNewTile($latIndex,$longIndex,$this->table_water);
						}
					}
					//echo ' benneVan:'.$benneVan.'<br>';
					$longIndex += $this->tileSize;
					
				}
				//echo "long nő<br>";
				$latIndex += $this->tileSize;
			}
			//echo "vége<br>";
		}
        return true;
	}
	//igazzal tér vissza, ha van a megadott xml válaszban folyó
	//be: xml válasz az openstreetmap szervertől
	//ki: igaz, ha van folyó, egyébként hamis
	function riverXmlParser($xmlObject) {
		$isWater = false;
		foreach($xmlObject as $obj) {
			if($obj->getName() == 'way') {
				foreach ($obj->children() as $child) {
					if($child->getName() == 'tag' && $child['k'] == 'waterway' && $child['v'] == 'river') {
						$isWater = true;
						return true;
					}
				}
			}
		}
		return false;
	}
	//Az xml választ feldolgozza és visszaadja az összes way-ben és relation-ben található node id-ket egy tömbben. 
	//Emellett beállítja a $node-ot, ami tartalmazza az összes id-hez tartozó koordinátát tömbben
	//be: xml válasz
	//ki: way és relation-ök id-i egy tömbben
	function xmlParser($xmlObject) {
		$ways = array(); //csak azok a way-ek, amik vizek
		$allWays = array(); //minden way benne van -> [id] => adatok
		$relations = array();
		$waterForSure = false;
		$waterWayAndRelationCoordinateIDs = array(); //benne van minden waterway és relationhöz szükséges node-id
		//relation-ökhöz
		$relationWays = array();
		//kiválogatja a way-eket és a relation-öket, ami vizet jelent közvetlen
		foreach($xmlObject as $obj) {
			if($obj->getName() == 'way') {
				$isWater = false;
				foreach ($obj->children() as $child) {
					if($child->getName() == 'tag' && $child['k'] == 'natural' && $child['v'] == 'water') {
						$isWater = true;
					}
					/*if($child->getName() == 'tag' && $child['k'] == 'waterway' && $child['v'] == 'river') {
						if($riverOkay) {
							//echo 'Van folyó<br>';
							$waterForSure = true;
						}
					}*/
				}
				if($isWater == true) {
					array_push($ways,$obj);
				}
				$allWays[(string)$obj['id']] = $obj;
			}
			else if($obj->getName() == 'relation') {
				$isWater = false;
				foreach ($obj->children() as $child) {
					if($child->getName() == 'tag' && $child['k'] == 'natural' && $child['v'] == 'water') {
						$isWater = true;
					}
				}
				if($isWater == true) {
					array_push($relations,$obj);
				}
			}
			else if($obj->getName() == 'node') {
				$this->nodes[(string)$obj['id']] = array('lat' => (double)$obj['lat'], 'lon' => (double)$obj['lon']);
			}
		}
		//betölti a waterway-ekben található node-id-ket a waterWayAndRelationCoordinateIDs-be
		foreach($ways as $way) {
			$waterWayND = array();
			foreach ($way->children() as $nd) {
				if($nd->getName() == 'nd') {
					array_push($waterWayND,(string)$nd['ref']);
				}
			}
			array_push($waterWayAndRelationCoordinateIDs,$waterWayND);
		}
		//relations - beállítja az összes relation id-jét egy tömbbe
		foreach($relations as $relation) {
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
		}
		return $waterWayAndRelationCoordinateIDs;
	}
	//kiválasztja egy node id-ket tartalmazó tömbből a legészakibb, legdélibb, legnyugatibb és legkeletibb koordinátákat
	//be: node-id-ket tartalmazó tömb
	//ki: 4 referencia
	function getNorthWestSouthEastPoint($way,&$north,&$west,&$south,&$east) {
		$north = $this->nodes[$way[0]]['lat'];
		$south = $this->nodes[$way[0]]['lat'];
		$east = $this->nodes[$way[0]]['lon'];
		$west = $this->nodes[$way[0]]['lon'];
		foreach($way as $point) {
			//lat nagyobb, long kisebb
			if($north < $this->nodes[$point]['lat']) {
				$north = $this->nodes[$point]['lat'];
			}
			if($south > $this->nodes[$point]['lat']) {
				$south = $this->nodes[$point]['lat'];
			}
			if($east < $this->nodes[$point]['lon']) {
				$east = $this->nodes[$point]['lon'];
			}
			if($west > $this->nodes[$point]['lon']) {
				$west = $this->nodes[$point]['lon'];
			}
		}
	}
	//megmondja egy koordinátáról, hogy benne van-e a node id-kkel megadott tömbben
	//be: node id-ket tartalmazó tömb, ami egy sokszöget ír le, koordináta
	//ki: igaz, ha benne van a sokszögben a koordináta, egyébként hamis
	function isPointInPolygon($polygonCoordinates,$lat,$long) {
		$i=0;
		$j=0;
		$c=0;
		for($i=0,$j=count($polygonCoordinates)-1; $i < count($polygonCoordinates); $j = $i++) {
			if( (($this->nodes[$polygonCoordinates[$i]]['lon'] > $long) != ($this->nodes[$polygonCoordinates[$j]]['lon'] > $long) ) &&
				($lat < ($this->nodes[$polygonCoordinates[$j]]['lat']-$this->nodes[$polygonCoordinates[$i]]['lat'])*($long-$this->nodes[$polygonCoordinates[$i]]['lon']) /
				($this->nodes[$polygonCoordinates[$j]]['lon']-$this->nodes[$polygonCoordinates[$i]]['lon']) + $this->nodes[$polygonCoordinates[$i]]['lat']) ) {
					if($c == 0) {
						$c = 1;
					}
					else if($c == 1){
						$c = 0;
					}
				}
		}
		return $c;
	}
	
	/*function isCoordinateVisible($lat,$long) {
		if($lat > $this->getSouth() && $lat < $this->getNorth() && $long > $this->getWest() && $long < $this->getEast()) {
			return true;
		}
		else {
			return false;
		}
	}*/
}
?>