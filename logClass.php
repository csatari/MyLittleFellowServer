<?php
class Log {
    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    /**
     * Elmenti adatbázisba a crash adatait
     * @param $post
     */
    function saveToDatabase($post) {
        $sql = "SET NAMES UTF8";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $sql = "INSERT INTO kozterlog (brand, model, android_version, app_version, user_crash_date,build,stack_trace,display,settings_secure,everything) VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = $this->db->prepare($sql);

        $stmt->execute(array($post["BRAND"],$post["PHONE_MODEL"],$post["ANDROID_VERSION"],$post["APP_VERSION_NAME"],$post["USER_CRASH_DATE"],$post["BUILD"],
            $post["STACK_TRACE"],$post["DISPLAY"],$post["SETTINGS_SECURE"],print_r($post,true)));
        $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>