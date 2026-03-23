<?php
// indexer.php — Focused crawler UI + runner
require __DIR__ . '/db.php'; // your working DB connection

$run = false;
$count = 0;
$log = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $seed = trim($_POST['seed'] ?? '');
  $max  = (int)($_POST['max_pages'] ?? 50);
  $mode = ($_POST['mode'] ?? 'bfs') === 'dfs' ? 'dfs' : 'bfs';

  // Basic validation
  if ($seed === '' || !preg_match('~^https?://~i', $seed)) {
    $err = 'Please enter a valid http(s) seed URL (folder seeds should end with /).';
  } elseif ($max < 1 || $max > 500) {
    $err = 'Max pages must be between 1 and 500.';
  } else {
    // Harden runtime for larger crawls
    @ini_set('memory_limit', '256M');
    @ini_set('max_execution_time', '0'); // we’ll control with set_time_limit
    set_time_limit(480);                 // ~8 minutes

    $run = true;
    require __DIR__ . '/spider.php';

    // Tuning options passed to spider
    $options = [
      'politeness_ms' => 120,        // tiny delay between requests
      'log_limit'     => 25000,      // keep last ~25KB of log to avoid memory bloat
      'frontier_cap'  => max($max * 6, 600), // prevent queue explosion
    ];

    // Normalize seed to prefix-lock (folder seeds recommended)
    $prefix = rtrim($seed, '/') . '/';

    // Start crawl
    [$count, $log] = crawl_and_index($pdo, $prefix, $max, $mode, $options);
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Indexer</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    select{
      width:100%; padding:10px 12px; border-radius:10px; border:1px solid #23262d;
      background:#0f1116; color:#e8eef9; outline:none;
    }
    pre.log{
      white-space:pre-wrap; background:#0f1116; padding:12px; border-radius:10px;
      border:1px solid #23262d; max-height:60vh; overflow:auto;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Indexer</h1>

    <div class="grid">
      <!-- Form -->
      <section class="card">
        <h2>Focused Crawl</h2>
        <form method="post" class="form-stack" autocomplete="off">
          <label>Seed URL (prefix-locked)</label>
          <input type="url" name="seed" placeholder="https://www.w3schools.com/js/" value="<?= htmlspecialchars($_POST['seed'] ?? '') ?>" required>

          <label>Max pages (≤ 500)</label>
          <input type="number" name="max_pages" min="1" max="500" value="<?= htmlspecialchars($_POST['max_pages'] ?? '100') ?>">

          <label>Traversal</label>
          <select name="mode">
            <option value="bfs" <?= (($_POST['mode'] ?? 'bfs') === 'bfs') ? 'selected' : '' ?>>Breadth-first (recommended)</option>
            <option value="dfs" <?= (($_POST['mode'] ?? '') === 'dfs') ? 'selected' : '' ?>>Depth-first</option>
          </select>

          <button type="submit">Start crawl</button>
        </form>
        <?php if ($err): ?>
          <p class="small" style="color:#ff8a8a;margin-top:8px"><?= htmlspecialchars($err) ?></p>
        <?php endif; ?>
        <p class="small" style="margin-top:8px">
          Tip: For a folder seed, keep the trailing <code>/</code>. For a single file (e.g., <code>default.asp</code>) <em>do not</em> add <code>/</code>.
        </p>
      </section>

      <!-- Status -->
      <section class="card">
        <h2>Status</h2>
        <?php if ($run): ?>
          <p class="small">Indexed pages: <strong><?= (int)$count ?></strong></p>
          <details open>
            <summary>Log</summary>
            <pre class="log"><?= htmlspecialchars($log) ?></pre>
          </details>
        <?php else: ?>
          <p class="small">Submit the form to begin crawling and indexing.</p>
        <?php endif; ?>
      </section>
    </div>

    <p class="small" style="margin-top:18px">
      ← <a class="link" href="index.php">Back to home</a> ·
      <a class="link" href="help.php">Help</a> ·
      <a class="link" href="check.php">Display Source</a>
    </p>
  </div>
</body>
</html>