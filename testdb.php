<?php
require __DIR__ . "/db.php";
header("Content-Type: text/html; charset=utf-8");
echo "<h3>Connected!</h3><pre>";
print_r($pdo->query("SHOW TABLES")->fetchAll());
echo "</pre>";