<?php
require_once("db.php");
require_once("userClass.php");
define("PARAM_ID", "id");
define("PARAM_OPERATION", "operation");
define("PARAM_EMAIL", "email");
define("PARAM_PASSWORD", "password");
define("PARAM_USERNAME", "username");
define("PARAM_SESSIONID", "sessionid");
define("PARAM_APPVERSION", "version");
if(isset($_REQUEST[PARAM_OPERATION])) {
    /*
     * Hozzáad egy felhasználót az adatbázishoz (regisztrálás), ha még nem létezett, egyébként bejelentkezteti
     * Kimenet: sessionid
     */
	if($_REQUEST[PARAM_OPERATION] == 0) { //add
        /**
         * megvizsgálja, hogy van-e paraméter
         */
        if(isset($_REQUEST[PARAM_EMAIL]) && isset($_REQUEST[PARAM_PASSWORD]) && isset($_REQUEST[PARAM_USERNAME])) { // PARAM_OPERATION = 1 && PARAM_EMAIL == email
			if(!isset($_REQUEST[PARAM_APPVERSION])) { //ha nincs még benne, hogy verziószámot kér
                $result["hibaid"] = "9";
                $result["hiba"] = Exceptions::getMessage(9);
                echo json_encode($result);
                return;
            }
            if($_REQUEST[PARAM_APPVERSION] < $GLOBALS["app_version"]) { //ha nem elég jó a verziószám
                $result["hibaid"] = "9";
                $result["hiba"] = Exceptions::getMessage(9);
                echo json_encode($result);
                return;
            }
            $user = new User($db,$_REQUEST[PARAM_EMAIL]);
			if(!$user->isEmailExists()) {
				$result;
				$result["id"] = $user->addUser($_REQUEST[PARAM_USERNAME],$_REQUEST[PARAM_PASSWORD]);
				$user->authenticateUser($_REQUEST[PARAM_USERNAME],$_REQUEST[PARAM_PASSWORD]);
				$result["sessionid"] = $user->getSessionId();
				$result["hibaid"] = "0";
				$result["hiba"] = Exceptions::getMessage(0);
				echo json_encode($result);
				return;
			}
			else {
				$authenticated = $user->authenticateUser($_REQUEST[PARAM_USERNAME],$_REQUEST[PARAM_PASSWORD]);
				if($authenticated) {
					$result["id"] = $user->getUserId($_REQUEST[PARAM_USERNAME],$_REQUEST[PARAM_PASSWORD]);
					$sessionid = $user->getSessionId();
					if($sessionid == null) {
						$result["hibaid"] = "1";
						$hiba["hiba"] = Exceptions::getMessage(1);
						echo json_encode($hiba);
					}
					else {
						$result["sessionid"] = $sessionid;
						$result["hibaid"] = "0";
						$result["hiba"] = Exceptions::getMessage(0);;
						echo json_encode($result);
					}
				}
				else {
					$result["hibaid"] = "2";
					$result["hiba"] = Exceptions::getMessage(2);
					echo json_encode($result);
					return null;
				}
			}
		}
	}
    /*
     * Lekéri a felhasználó e-mail címe alapján a sessionid-t
     */
	/*else if($_REQUEST[PARAM_OPERATION] == 1) { //get
		if(isset($_REQUEST[PARAM_EMAIL])) { // PARAM_OPERATION = 1 && PARAM_EMAIL == email
			$user = new User($db,$_REQUEST[PARAM_EMAIL]);
			$result[]=$user->getSessionId();
			echo json_encode($result);
		}
	}*/
	/*else if($_REQUEST[PARAM_OPERATION] == 2) { //test
		$user = new User($db,"");
		$sessionjo = $user->authenticateSessionId($_REQUEST[PARAM_SESSIONID]);
		if($sessionjo) {
			echo "jo";
		}
		else {
			echo "rossz";
		}
	}*/
}
else {
	echo "Error 1 - No operation";
}
?>