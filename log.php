<?php
require_once("db.php");
require_once("logClass.php");
$log = new Log($db);
$log->saveToDatabase($_POST);

?>