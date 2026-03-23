<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Help</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .k { background:#0f1116; border:1px solid #23262d; border-radius:10px; padding:12px; }
    .list li{ margin:.4rem 0; }
    code { background:#11141a; padding:.1rem .35rem; border-radius:6px; }
    .step{ counter-increment: s; }
    .step::before{ content: counter(s) ". "; color:#aab3c5; font-weight:600; margin-right:6px; }
    .callout{ border-left:4px solid #7aa2ff; padding:10px 12px; background:#0f1116; border-radius:8px; }
    .ok{ color:#57e39c; } .bad{ color:#ff8a8a; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Help</h1>

    <section class="card">
      <h2>What is this?</h2>
      <p class="small">
        A focused web search engine for DATA 525. It crawls pages under a seed URL prefix,
        indexes <em>title terms</em> (minus stopwords), and ranks search results by how many query
        keywords appear in the title.
      </p>
      <p class="small">
        Quick links:
        <a class="link" href="index.php">Home</a> ·
        <a class="link" href="indexer.php">Indexer</a> ·
        <a class="link" href="search.php">Search</a> ·
        <a class="link" href="check.php">Display Source</a>
      </p>
    </section>

    <section class="card">
      <h2>How to use</h2>
      <ol class="list" style="counter-reset: s;">
        <li class="step"><strong>Open the Indexer</strong> and enter a <em>seed URL</em>. Use a folder-style prefix ending with <code>/</code>, e.g. <code>https://www.w3schools.com/js/</code>.</li>
        <li class="step">Set <strong>Max pages</strong> (1–500). Start small (20–50) to test, then increase.</li>
        <li class="step">Choose <strong>Traversal</strong> (BFS recommended) and click <em>Start crawl</em>. Watch the log.</li>
        <li class="step"><strong>Search</strong> with space-separated keywords (case-insensitive). Results are ranked by the number of matched keywords.</li>
        <li class="step">Use <strong>Display Source</strong> to view code (password required).</li>
        <li class="step">Use <strong>Clear System</strong> on the home page to truncate all tables if you want to start fresh.</li>
      </ol>
    </section>

    <section class="card">
      <h2>Input rules & tips</h2>
      <ul class="list small">
        <li><span class="ok">✓</span> Good seed for a section: <code>https://example.com/docs/</code></li>
        <li><span class="bad">✗</span> Avoid adding a slash to a file URL: <code>.../default.asp/</code> (404)</li>
        <li>The crawler only visits URLs that begin with your seed prefix (“focused crawl”).</li>
        <li>Non-HTML and large media (pdf, images, video, zips) are skipped.</li>
        <li>Title tokenization is lowercase, non-letter/digit stripped, and common stopwords removed.</li>
        <li>Runtime is capped in PHP to keep within grading limits.</li>
      </ul>
    </section>

    <section class="card">
      <h2>What’s indexed?</h2>
      <div class="k small">
        <strong>pages</strong>(id, url, title, meta_keywords, meta_description, crawled_at)<br>
        <strong>terms</strong>(id, term)<br>
        <strong>page_terms</strong>(page_id, term_id)
      </div>
      <p class="small" style="margin-top:8px">
        Search queries join these tables to count how many of your keywords appear in a page’s title.
      </p>
    </section>

    <section class="card">
      <h2>Troubleshooting</h2>
      <div class="callout small">
        <p><strong>404 in the log?</strong> Make sure the seed exists and is typed exactly; for a folder seed, keep the trailing <code>/</code>. For a single file, do <em>not</em> add <code>/</code>.</p>
        <p><strong>No results?</strong> Try broader keywords, or crawl more pages first. Also check that your seed points to the section you care about.</p>
        <p><strong>Database errors?</strong> Confirm your connection in <code>db.php</code> and ensure the tables exist:
          <code>pages</code>, <code>terms</code>, <code>page_terms</code>.</p>
      </div>
    </section>

    <p class="small" style="margin-top:14px">
      ← <a class="link" href="index.php">Back to home</a>
    </p>
  </div>
</body>
</html>