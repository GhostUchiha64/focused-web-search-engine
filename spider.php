<?php
/**
 * spider.php
 * Focused crawler + title-term indexer (stopword removal).
 *
 * DB tables (expected):
 *   pages(id, url, title, meta_keywords, meta_description, crawled_at?)
 *   terms(id, term)
 *   page_terms(page_id, term_id)
 *
 * Usage (from indexer.php):
 *   [$count, $log] = crawl_and_index($pdo, $prefix, $max, $mode, [
 *     'politeness_ms' => 120,
 *     'log_limit'     => 25000,
 *     'frontier_cap'  => max($max * 6, 600),
 *   ]);
 */

/**
 * Run a focused crawl restricted to a URL prefix, indexing title terms.
 *
 * @param PDO    $pdo
 * @param string $prefix  URL prefix to keep the crawl focused (must end with '/')
 * @param int    $max     Max pages to index (1..500 per assignment spec)
 * @param string $mode    'bfs' or 'dfs'
 * @param array  $opts    ['politeness_ms'=>int, 'log_limit'=>int, 'frontier_cap'=>int]
 * @return array          [$indexed_count, $log_string]
 */
function crawl_and_index(PDO $pdo, string $prefix, int $max, string $mode = 'bfs', array $opts = []): array {
  // --- options / defaults ---
  $STOPWORDS = [
    'a','an','and','are','as','at','be','by','for','from','has','he','in','is','it','its','of','on','that','the','to','was','were','will','with',
    'this','these','those','you','your','i','we','our','or'
  ];
  $politeness_ms = (int)($opts['politeness_ms'] ?? 100);    // small delay between requests
  $log_limit     = (int)($opts['log_limit'] ?? 20000);      // keep last N bytes of log
  $frontier_cap  = (int)($opts['frontier_cap'] ?? ($max*5));// bound queue/stack growth

  // --- crawl state ---
  $visited  = [];
  $frontier = ($mode === 'dfs') ? new SplStack() : new SplQueue();
  if ($frontier instanceof SplQueue) $frontier->enqueue($prefix); else $frontier->push($prefix);

  // --- SQL prepared statements ---
  $insertPage = $pdo->prepare(
    'INSERT INTO pages(url,title,meta_keywords,meta_description)
     VALUES (?,?,?,?)
     ON DUPLICATE KEY UPDATE
       title=VALUES(title),
       meta_keywords=VALUES(meta_keywords),
       meta_description=VALUES(meta_description)'
  );
  $selTerm = $pdo->prepare('SELECT id FROM terms WHERE term=?');
  $insTerm = $pdo->prepare('INSERT IGNORE INTO terms(term) VALUES (?)');
  $insPT   = $pdo->prepare('INSERT IGNORE INTO page_terms(page_id,term_id) VALUES (?,?)');

  $count = 0; $log = '';

  while (
    $count < $max &&
    (($frontier instanceof SplQueue && $frontier->count()) || ($frontier instanceof SplStack && $frontier->count()))
  ) {
    $url = ($frontier instanceof SplQueue) ? $frontier->dequeue() : $frontier->pop();
    if (isset($visited[$url])) continue;
    $visited[$url] = true;

    // Focus check
    if (stripos($url, $prefix) !== 0) continue;

    // Fetch
    $ctype = $status = null;
    $html = fetch_html($url, $ctype, $status);
    if (!$html) {
      append_log($log, "Skip (status=$status, type=$ctype): $url", $log_limit);
      throttle($politeness_ms);
      continue;
    }

    // Parse + index
    [$title,$mk,$md,$links] = extract_meta($html);

    $insertPage->execute([$url,$title,$mk,$md]);
    $pageId = (int)$pdo->lastInsertId();
    if ($pageId === 0) { // existed; look up id
      $s = $pdo->prepare('SELECT id FROM pages WHERE url=?');
      $s->execute([$url]);
      $pageId = (int)$s->fetchColumn();
    }

    $terms = tokenize_title_terms($title ?? '', $STOPWORDS);
    foreach ($terms as $w) {
      $insTerm->execute([$w]);
      $selTerm->execute([$w]);
      $tid = (int)$selTerm->fetchColumn();
      if ($tid) $insPT->execute([$pageId, $tid]);
    }

    $count++;
    append_log($log, "Indexed: $url (title terms: " . implode(',', $terms) . ")", $log_limit);

    // Expand frontier (bounded)
    foreach ($links as $href) {
      $abs = to_absolute_url($url, $href);
      if (!$abs) continue;

      // Remove schemes we don't fetch + fragments + obvious binaries
      if (!preg_match('~^https?://~i', $abs)) continue;
      if (preg_match('~^(javascript:|mailto:)~i', $abs)) continue;
      if (preg_match('~#~', $abs)) continue;
      if (preg_match('~\.(jpg|jpeg|png|gif|svg|webp|ico|pdf|zip|gz|rar|7z|tar|tgz|mp3|mp4|avi|mov|wmv|mkv)$~i', $abs)) continue;

      if (stripos($abs, $prefix) !== 0) continue; // focused
      if (!isset($visited[$abs])) {
        $qCount = ($frontier instanceof SplQueue) ? $frontier->count() : $frontier->count();
        if ($qCount < max($frontier_cap, $max)) {
          ($frontier instanceof SplQueue) ? $frontier->enqueue($abs) : $frontier->push($abs);
        }
      }
    }

    throttle($politeness_ms);
  }

  return [$count, $log];
}

/* ----------------- helpers ----------------- */

/**
 * Short sleep to be polite and reduce timeouts.
 */
function throttle(int $ms): void {
  if ($ms > 0) usleep($ms * 1000);
}

/**
 * Append a line to the log; keep only the tail to limit memory.
 */
function append_log(string &$log, string $line, int $limit): void {
  $log .= $line . "\n";
  if (strlen($log) > $limit) {
    $log = substr($log, -$limit); // keep last N bytes
  }
}

/**
 * Fetch HTML with cURL; returns null on non-200 or non-HTML.
 */
function fetch_html(string $url, ?string &$contentType = null, ?int &$status = null): ?string {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_USERAGENT      => 'UND-525-Spider/1.0 (+coursework)',
    CURLOPT_NOSIGNAL       => true,
    CURLOPT_ENCODING       => '',      // accept gzip/deflate
    CURLOPT_TCP_KEEPALIVE  => 1,
  ]);
  $html = curl_exec($ch);
  $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  $errno = curl_errno($ch);
  curl_close($ch);

  if ($errno !== 0) return null;
  if ($status !== 200 || !is_string($html)) return null;
  if ($contentType && stripos($contentType, 'text/html') === false) return null;
  return $html;
}

/**
 * Extract <title>, meta keywords/description, and page <a> links.
 */
function extract_meta(string $html): array {
  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  if (!@$dom->loadHTML($html)) return [null,null,null,[]];
  $xp = new DOMXPath($dom);

  $titleNode = $xp->query('//title')->item(0);
  $title = $titleNode ? normalize_ws($titleNode->textContent) : null;

  $mkNode = $xp->query('//meta[translate(@name,"KEYWORDS","keywords")="keywords"]/@content')->item(0);
  $mdNode = $xp->query('//meta[translate(@name,"DESCRIPTION","description")="description"]/@content')->item(0);
  $mk = $mkNode ? normalize_ws($mkNode->textContent) : null;
  $md = $mdNode ? normalize_ws($mdNode->textContent) : null;

  $links = [];
  foreach ($xp->query('//a[@href]') as $a) $links[] = $a->getAttribute('href');

  return [$title,$mk,$md,$links];
}

/**
 * Tokenize title to unique, lowercase terms without stopwords.
 */
function tokenize_title_terms(string $title, array $STOPWORDS): array {
  $t = mb_strtolower($title, 'UTF-8');
  $t = preg_replace('/[^\p{L}\p{Nd}]+/u', ' ', $t);
  $parts = array_filter(array_map('trim', explode(' ', $t)));
  $uniq = [];
  foreach ($parts as $w) {
    if ($w !== '' && !in_array($w, $STOPWORDS, true)) $uniq[$w] = 1;
  }
  return array_keys($uniq);
}

/**
 * Normalize whitespace.
 */
function normalize_ws(string $s): string {
  return trim(preg_replace('/\s+/u',' ', $s));
}

/**
 * Make an absolute URL from base + relative (basic, robust).
 */
function to_absolute_url(string $base, string $rel): ?string {
  if ($rel === '' || $rel === '#' || preg_match('~^(mailto:|javascript:)~i', $rel)) return null;
  if (preg_match('~^https?://~i', $rel)) return $rel;

  // drop fragment
  $rel = preg_replace('~#.*$~','',$rel);

  $abs = parse_url($base);
  if (!$abs || empty($abs['scheme']) || empty($abs['host'])) return null;
  $scheme = $abs['scheme'];
  $host   = $abs['host'];
  $port   = isset($abs['port']) ? ':' . $abs['port'] : '';
  $path   = $abs['path'] ?? '/';

  if (strpos($rel,'/') === 0) {
    $path = $rel;
  } else {
    $dir  = preg_replace('~/[^/]*$~','/',$path);
    $path = $dir . $rel;
  }

  // Resolve ./ and ../
  $parts = [];
  foreach (explode('/', $path) as $seg) {
    if ($seg === '' || $seg === '.') continue;
    if ($seg === '..') { array_pop($parts); continue; }
    $parts[] = $seg;
  }
  return $scheme . '://' . $host . $port . '/' . implode('/', $parts);
}