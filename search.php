<?php
require_once "db.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$terms = [];

if ($q !== '') {
  $q = mb_strtolower($q, 'UTF-8');
  $q = preg_replace('/[^\p{L}\p{Nd}]+/u', ' ', $q);
  $terms = array_filter(array_unique(explode(' ', $q)));
}

if (empty($terms)) {
  // Empty query: return all pages
  $stmt = $pdo->query("SELECT id, url, title FROM pages ORDER BY id");
  $results = $stmt->fetchAll();
} else {
  // Keyword search
  $placeholders = implode(',', array_fill(0, count($terms), '?'));
  $term_ids = $pdo->prepare("SELECT id FROM terms WHERE term IN ($placeholders)");
  $term_ids->execute($terms);
  $ids = $term_ids->fetchAll(PDO::FETCH_COLUMN);

  if (empty($ids)) {
    $results = [];
  } else {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $sql = "
      SELECT p.id, p.url, p.title, COUNT(*) AS hits
      FROM page_terms pt
      JOIN pages p ON pt.page_id = p.id
      WHERE pt.term_id IN ($in)
      GROUP BY pt.page_id
      ORDER BY hits DESC, p.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $results = $stmt->fetchAll();
  }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Search</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .result { margin-bottom: 1rem; }
    .result h3 { margin: 0.25rem 0; }
    .small { font-size: 0.9rem; color: #ccc; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Search</h1>

    <form action="search.php" method="get">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Enter keywords" style="width: 100%; padding: 10px;">
      <button type="submit">Search</button>
    </form>

    <p class="small" style="margin-top:10px">
      <?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?> found <?= ($q !== '') ? "for <code>" . htmlspecialchars($q) . "</code>" : "(all pages)" ?>.
    </p>

    <div>
      <?php if (empty($results)): ?>
        <p>No results found.</p>
      <?php else: ?>
        <ol>
          <?php foreach ($results as $r): ?>
            <li class="result">
              <h3><a href="<?= htmlspecialchars($r['url']) ?>" target="_blank"><?= htmlspecialchars($r['title'] ?: '[No title]') ?></a></h3>
              <p class="small"><code><?= htmlspecialchars($r['url']) ?></code>
              <?php if (isset($r['hits'])): ?> — <?= $r['hits'] ?> keyword hit<?= $r['hits'] > 1 ? 's' : '' ?><?php endif; ?>
              </p>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>

    <p class="small" style="margin-top: 2rem;">
      ← <a href="index.php" class="link">Back to home</a>
    </p>
  </div>
</body>
</html>
