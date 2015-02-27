<?php
require_once("userClass.php");
/**
 * Ellenőrzi, hogy a felhasználó be van-e jelentkezve és visszaadja a userid-t sessionid-ből
 * @param $db
 * @param $sessionid
 * @return null
 */
function authenticate($db,$sessionid) {
	if(isset($sessionid)) {
		$user = new User($db,"");
		if(!$user->authenticateSessionId($sessionid)) {
			$result;
			$result["hibaid"] = "2";
			$result["hiba"] = "Error in authentication";
			echo json_encode($result);
			return null;
		}
		$userid = $user->getUserIdBySessionId($sessionid);
	}
	else {
		$result;
		$result["hibaid"] = "2";
		$result["hiba"] = "Error in authentication";
		echo json_encode($result);
		return null;
	}
	return $userid;
}
?>