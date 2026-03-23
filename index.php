<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Focused Web Search Engine</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container">
    <h1>Focused Web Search Engine</h1>

    <div class="grid">
      <!-- Clear -->
      <section class="card">
        <h2>Clear System</h2>
        <p class="small">Clear all database tables (<code>pages</code>, <code>terms</code>, <code>page_terms</code>).</p>
        <form action="clear.php" method="post">
          <button type="submit">Clear</button>
        </form>
      </section>

      <!-- Index -->
      <section class="card">
        <h2>Index</h2>
        <p class="small">Seed URL + max pages (≤ 500). Focused crawl stays under the same prefix.</p>
        <a class="btn" href="indexer.php">Open Indexer</a>
      </section>

      <!-- Search -->
      <section class="card">
        <h2>Search</h2>
        <form action="search.php" method="get" class="form-stack">
          <input type="text" name="q" placeholder="e.g., api auth pagination">
          <button type="submit">Search</button>
        </form>
        <p class="small">Results ranked by number of matched title keywords.</p>
      </section>

      <!-- Display source -->
      <section class="card">
        <h2>Display Source</h2>
        <p class="small">Password-protected view of Clear, Index/Spider, and Search code.</p>
        <a class="btn" href="check.php">Open</a>
      </section>

      <!-- Help -->
      <section class="card">
        <h2>Help</h2>
        <p class="small">Step-by-step instructions, tips, and troubleshooting for this project.</p>
        <a class="btn" href="help.php">Open Help</a>
      </section>
    </div>

    <p class="small" style="margin-top:18px">
      Keep this page at <code>…/525/1/index.php</code> exactly for grading.
    </p>
  </div>
</body>
</html>