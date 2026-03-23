<?php
// --- Settings ---
session_start();
$PASSWORD = "data525"; // <- change to your chosen password

// Whitelisted files to show (add/remove as you like)
$FILES = [
  'index.php'   => 'Index.php',
  'clear.php'   => 'Clear.php',
  'indexer.php' => 'Indexer.php',
  'spider.php'  => 'Spider.php',
  'search.php'  => 'Search.php',
  // If you do NOT want to show db.php, comment it out:
  // 'db.php'      => 'db.php',
];

// Helpers
function is_authed(): bool { return !empty($_SESSION['show_src_ok']); }
function authorize($pw) { if ($pw !== null && hash_equals($pw, $GLOBALS['PASSWORD'])) $_SESSION['show_src_ok'] = true; }

// Handle auth
if (!is_authed() && $_SERVER['REQUEST_METHOD'] === 'POST') {
  authorize($_POST['pw'] ?? null);
}

$selected = $_GET['f'] ?? '';                 // requested file key
$selected = array_key_exists($selected, $FILES) ? $selected : ''; // enforce whitelist

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Display Source</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .pillbar{display:flex;flex-wrap:wrap;gap:10px;margin:12px 0 20px}
    .pill{background:#1b1e25;border:1px solid #23262d;border-radius:999px;padding:10px 14px;text-decoration:none;color:#e8eef9}
    .pill.active{border-color:rgba(122,162,255,.5);box-shadow:0 4px 12px rgba(122,162,255,.2)}
    pre.code{max-height:70vh;overflow:auto;background:#0f1116;padding:14px;border-radius:10px;border:1px solid #23262d;white-space:pre-wrap}
    .muted{color:#aab3c5;font-size:13px}
    .right{float:right}
  </style>
</head>
<body>
  <div class="container">
    <h1>Display Source</h1>

    <?php if (!is_authed()): ?>
      <form method="post" class="form-stack" style="max-width:380px">
        <label>Password</label>
        <input type="password" name="pw" placeholder="Enter password" required>
        <button type="submit">View Code</button>
      </form>
      <p class="muted" style="margin-top:12px"><a class="link" href="index.php">← Back to home</a></p>

    <?php else: ?>
      <!-- Pills -->
      <div class="pillbar">
        <?php foreach ($FILES as $key => $label): ?>
          <a class="pill <?= $selected === $key ? 'active' : '' ?>" href="?f=<?= urlencode($key) ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
        <a class="pill right" href="?logout=1" onclick="return confirm('Sign out of Display Source?')">Sign out</a>
      </div>

      <?php
        if (isset($_GET['logout'])) { session_destroy(); header("Location: check.php"); exit; }
        if ($selected === '') {
          echo '<p class="muted">Select a file above to view its source.</p>';
        } else {
          $path = __DIR__ . '/' . $selected; // safe due to whitelist check
          if (!is_readable($path)) {
            echo '<p class="muted">File not readable.</p>';
          } else {
            $code = htmlspecialchars(file_get_contents($path));
            echo '<h2 style="margin-top:6px">'.htmlspecialchars($FILES[$selected]).'</h2>';
            echo '<pre class="code">'.$code.'</pre>';
          }
        }
      ?>

      <p class="muted" style="margin-top:12px"><a class="link" href="index.php">← Back to home</a></p>
    <?php endif; ?>
  </div>
</body>
</html>