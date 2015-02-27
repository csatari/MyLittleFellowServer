<?php
class Exceptions {
    /**
     * Visszaadja hibakód szerint a hibaüzenetet
     * @param $errorid
     * @return string
     */
    public static function getMessage($errorid) {
		if($errorid == 0) return "";
		else if($errorid == 1) return "Your sessionid has expired or is not valid";
		else if($errorid == 2) return "Error in authentication";
		else if($errorid == 3) return "The storage is full";
		else if($errorid == 4) return "The storage would be full";
        else if($errorid == 5) return "No building resource set";
        else if($errorid == 6) return "Not enough resources";
        else if($errorid == 7) return "Building is not developed";
        else if($errorid == 8) return "Not enough intelligence points";
        else if($errorid == 9) return "You have an outdated version of the game. Please update!";
	}
}
?>