<?php
class KnownTiles {
	private $userid;
	private $db;
	
	/*private $table_title = "knowntiles";
	private $table_userid = "userid";
	private $table_tileid = "tileid";*/
	
	function __construct($db,$userid) {
		$this->db = $db;
		$this->userid = $userid;
	}
	//Hozzáad a táblához egy tile-t, ha még nem volt bent
	function add($tile) {
		$sql = "INSERT INTO ".$GLOBALS["table_knowntiles_title"]." (".$GLOBALS["table_knowntiles_userid"].",".$GLOBALS["table_knowntiles_tileid"].") VALUES (?,?)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->userid,$tile));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}
	//Lekérdezi, hogy létezik-e a megadott tile
	//Nem is nagyon kell, elintézi az adatbázis
	function isExists($tile) {
		$sql = "SELECT * FROM ".$GLOBALS["table_knowntiles_title"]." WHERE ".$GLOBALS["table_knowntiles_userid"]." = ? AND ".$GLOBALS["table_knowntiles_tileid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->userid,$tile));
		$stmt->fetch(PDO::FETCH_ASSOC);
		$count = $stmt->rowCount();
		return ($count == 0 ? false : true);
	}
	//Lekérdezi a felhasználó által összes felfedezett tile-t egy tömbben
	function getAll() {
		$sql = "SELECT ".$GLOBALS["table_knowntiles_tileid"]." FROM ".$GLOBALS["table_knowntiles_title"]." WHERE ".$GLOBALS["table_knowntiles_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->userid));
		$resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$allId = array();
		foreach($resultArray as $tile) {
			$allId[] = $tile[$GLOBALS["table_knowntiles_tileid"]];
		}
		return $allId;
	}

    /**
     * Beállítja egy területre, hogy meg lett vizsgálva a karakter által
     * @param $tile
     */
    public function setTileExamined($tile) {
        $sql = "UPDATE ".$GLOBALS["table_knowntiles_title"]." SET ".$GLOBALS["table_knowntiles_examined"]."= '1' WHERE ".$GLOBALS["table_knowntiles_tileid"]." = ? AND ".$GLOBALS["table_knowntiles_userid"]." = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($tile,$this->userid));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }

	/*Beállítja, hogy a tile-t már felmérte a karakter*/
	public function examineTile($tile) {
        $timedAction = new TimedAction($this->db,$this->userid);
        $timeArray = $timedAction->setExamineTile($tile);
        return $timeArray;
	}
	/*Lekérdezi, hogy a tile-t már felfedezte-e a karakter*/
	public function isExamined($tile) {
		$sql = "SELECT ".$GLOBALS["table_knowntiles_examined"]." FROM ".$GLOBALS["table_knowntiles_title"]." WHERE ".$GLOBALS["table_knowntiles_tileid"]." = ? AND ".$GLOBALS["table_knowntiles_userid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($tile,$this->userid));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray[$GLOBALS["table_knowntiles_examined"]];
	}
}
?>