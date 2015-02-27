<?php
require_once("globals.php");
require_once("exceptions.php");
$db;
try {
    /**
     * Csatlakozik az adatbázishoz
     */
    $db = new PDO('mysql:host=localhost;dbname=mylittlefellow;charset=utf8', 'csatari', '', array(
        PDO::ATTR_PERSISTENT => true
    ));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
