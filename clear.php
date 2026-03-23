<?php
require __DIR__ . '/db.php';

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('TRUNCATE TABLE page_terms');
$pdo->exec('TRUNCATE TABLE terms');
$pdo->exec('TRUNCATE TABLE pages');
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

echo "<h2>System cleared </h2>";
echo '<a href="index.php">Back to home</a>';