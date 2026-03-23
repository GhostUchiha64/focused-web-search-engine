<?php
/**
 * db.example.php
 *
 * SETUP INSTRUCTIONS:
 * 1. Copy this file and rename it to db.php
 * 2. Fill in your MySQL credentials below
 * 3. Never commit db.php to GitHub (it is in .gitignore)
 */

/* ====== EDIT THESE FIVE LINES ====== */
$DB_HOST = "your-mysql-host.mysql.database.azure.com";
$DB_PORT = 3306;
$DB_NAME = "your_database_name";
$DB_USER = "your_username";
$DB_PASS = "your_password";
/* =================================== */

/* --- Preflight diagnostics --- */
$resolved = @gethostbyname($DB_HOST);
$socket_ok = false;
$errno = 0; $errstr = "";
$fp = @fsockopen($DB_HOST, $DB_PORT, $errno, $errstr, 2.0);
if ($fp) { $socket_ok = true; fclose($fp); }

$diag = [];
$diag[] = "Host: $DB_HOST (resolved: $resolved)";
$diag[] = "Port: $DB_PORT";
$diag[] = "TCP reachability: " . ($socket_ok ? "OPEN" : "CLOSED ($errno: $errstr)");

/* --- SSL PDO options --- */
$ca_candidates = [
  "/etc/pki/tls/certs/ca-bundle.crt",
  "/etc/ssl/certs/ca-certificates.crt",
];
$ssl_ca = null;
foreach ($ca_candidates as $c) { if (is_readable($c)) { $ssl_ca = $c; break; } }

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];
if ($ssl_ca) {
  $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl_ca;
}

try {
  if (!$socket_ok) {
    $msg = implode(" | ", $diag);
    throw new Exception("Preflight failed: cannot reach $DB_HOST:$DB_PORT. $msg");
  }
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
  header("Content-Type: text/plain; charset=utf-8");
  echo "DB connection failed.\n";
  echo "Diagnostics:\n  - " . implode("\n  - ", $diag) . "\n";
  echo "Error: " . $e->getMessage();
  exit;
}
