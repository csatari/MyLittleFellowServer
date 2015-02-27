<?php
class User {
	/*Authentikálás folyamata:
	0. Először hozzá kell adni a felhasználót a táblához addUser függvénnyel - regisztrálás
	1. authenticateUser függvénnyel megvizsgáljuk, hogy helyes-e a jelszó
	2. getSessionId függvénnyel lekérdezzük a sessionid-t
	3. csak akkor futtatunk függvényt, ha a megadott sessionid az authenticateSessionId függvénnyel meg lett vizsgálva
	*/
	private $db;
	private $mysqli;
	public $email;
	
	/*private $table_title = "user";
	private $table_id = "id";
	private $table_username = "username";
	private $table_password = "password";
	private $table_email = "email";
	private $table_sessionid = "sessionid";
	private $table_sessionidExpiration = "sessionidExpiration";*/
	
	function __construct($db,$email) {
		$this->db = $db;
		$this->email = $email;
	}
	//lekéri a megadott e-mail cím alapján a sessionid-t
	//be: -
	//ki: sessionid
	public function getSessionId() {
		//$stmt = $this->mysqli->prepare("SELECT * FROM ".$this->table_title." WHERE ".$this->table_email." = ?");
		$sql = "SELECT ".$GLOBALS["table_user_sessionid"]." FROM ".$GLOBALS["table_user_title"]." WHERE ".$GLOBALS["table_user_email"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->email));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray[$GLOBALS["table_user_sessionid"]];
		
		//$stmt->bind_param('s', $this->email);
		//$stmt->execute();
		/*$result = $stmt->get_result();
		while($row = $result->fetch_assoc()) {
			return $row[$this->table_sessionid];
		}
		return null;*/
	}

    /**
     * Lekérdezi a user azonosítóját sessionid szerint
     * @param $sessionid
     * @return mixed
     */
    public function getUserIdBySessionId($sessionid) {
		$sql = "SELECT ".$GLOBALS["table_user_id"]." FROM ".$GLOBALS["table_user_title"]." WHERE ".$GLOBALS["table_user_sessionid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($sessionid));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray[$GLOBALS["table_user_id"]];
		/*$result = $stmt->get_result();
		while($row = $result->fetch_assoc()) {
			return $row[$this->table_id];
		}
		return null;*/
	}
	//beállítja a sessionid-t és a lejárati dátumát az email címre
	//be: -
	//ki: -
	public function setSessionId() {
		$date = new DateTime();
		$timestamp = $date->getTimestamp();
		$timestamp += 48*60*60; //+2 nap
		$sessionid = hash ( "sha256", $this->email."session".$timestamp);
		$sql = "UPDATE ".$GLOBALS["table_user_title"]." SET ".$GLOBALS["table_user_sessionid"]."= ?, ".$GLOBALS["table_user_sessionidExpiration"]."=? WHERE ".$GLOBALS["table_user_email"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($sessionid,$timestamp,$this->email));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}
	//meghosszabbítja a sessiont 2 órával
	//be: sessionid
	//ki: -
	public function expandSessionId($sessionid) {
		$date = new DateTime();
		$timestamp = $date->getTimestamp();
		$timestamp += 48*60*60; //+48 óra
		$sql = "UPDATE ".$GLOBALS["table_user_title"]." SET ".$GLOBALS["table_user_sessionidExpiration"]."=? WHERE ".$GLOBALS["table_user_sessionid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($timestamp,$sessionid));
		$stmt->fetch(PDO::FETCH_ASSOC);
	}
	//megnézi, hogy a megadott sessionid valid-e. Egyúttal meghosszabbítja a sessiont
	//be: sessionid
	//ki: igaz, ha valid
	public function authenticateSessionId($sessionid) {
		$sql = "SELECT ".$GLOBALS["table_user_sessionidExpiration"]." FROM ".$GLOBALS["table_user_title"]." WHERE ".$GLOBALS["table_user_sessionid"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($sessionid));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		$count = $stmt->rowCount();
		if($count > 0) {
			$date = new DateTime();
			if($date->getTimestamp() < $resultArray[$GLOBALS["table_user_sessionidExpiration"]]) {
				$this->expandSessionId($sessionid);
				return true;
			}
			return false;
		}
		return false;
	}
	//hozzáad egy felhasználót a táblához, kell az e-mail cím, felhasználónév, jelszó
	//be: felhasználónév, jelszó
	//ki: id
	public function addUser($username,$password) {
        header('Content-Type: text/html; charset=utf-8');
        $sql = "SET NAMES UTF8";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
		$sql = "INSERT INTO ".$GLOBALS["table_user_title"]." (".$GLOBALS["table_user_username"].",".$GLOBALS["table_user_password"].", ".$GLOBALS["table_user_email"].") VALUES (?, ?, ?)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($username,$password,$this->email));
		$stmt->fetch(PDO::FETCH_ASSOC);
		$lastRow = $this->getLastUser();
		return $lastRow["id"];
		/*$stmt->bind_param('sss', $username,$password,$this->email);
		$stmt->execute();
		$lastRow = $this->getLastUser();
		return $lastRow["id"];*/
	}
	//a megadott e-mailt és jelszót ellenőrzi, hogy stimmel-e. Ha stimmel, akkor beállítja a sessionid-t is
	//be: jelszó
	//ki: igaz, ha helyes
	public function authenticateUser($username,$password) {
        header('Content-Type: text/html; charset=utf-8');
        $sql = "SET NAMES UTF8";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
		$sql = "SELECT * FROM ".$GLOBALS["table_user_title"]." WHERE ".$GLOBALS["table_user_email"]." = ? AND ".$GLOBALS["table_user_password"]." = ? AND ".$GLOBALS["table_user_username"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->email,$password,$username));
		$stmt->fetch(PDO::FETCH_ASSOC);
		$count = $stmt->rowCount();
		if($count > 0) {
			$this->setSessionId();
		}
		return ($count == 0 ? false : true);
	}
	//lekérdezi a legutoljára beillesztett user-t
	//be: -
	//ki: sor
	private function getLastUser() {
		$sql = "SELECT * FROM ".$GLOBALS["table_user_title"]." ORDER BY ".$GLOBALS["table_user_id"]." DESC LIMIT 0,1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row;
	}
	//Visszatér egy felhasználónév, jelszó id-jével
	//be: felhasználónév,jelszó
	//ki: id
	public function getUserId($username,$password) { 
		$sql = "SELECT ".$GLOBALS["table_user_id"]." FROM ".$GLOBALS["table_user_title"]." WHERE ".$GLOBALS["table_user_email"]." = ? AND ".$GLOBALS["table_user_username"]." = ? AND ".$GLOBALS["table_user_password"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->email,$username,$password));
		$resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
		return $resultArray[$GLOBALS["table_user_id"]];
	}
	//true, ha létezik a megadott e-maillel a sor, egyébként false
	//be: -  ki: true vagy false
	public function isEmailExists() {
		$sql = "SELECT ".$GLOBALS["table_user_id"]." FROM ".$GLOBALS["table_user_title"]." WHERE ".$GLOBALS["table_user_email"]." = ?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->email));
		$stmt->fetch(PDO::FETCH_ASSOC);
		$count = $stmt->rowCount();
		return ($count == 0 ? false : true);
	}

    /**
     * Lekérdezi a user nevét id alapján
     * @param $userid
     * @return string
     */
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
}
?>