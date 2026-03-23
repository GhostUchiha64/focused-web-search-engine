# Focused Web Search Engine
### DATA 525 – Exercise I | University of North Dakota

A PHP-based focused web crawler and keyword search engine backed by a MySQL database. Given a seed URL, the spider crawls pages within the same URL prefix (BFS or DFS), indexes title terms with stopword removal, and ranks search results by keyword hit count.

---

## Live Demo

**Web App:** http://undcemcs02.und.edu/~siddartha.bandi/525/1/

**GitHub Pages:** https://ghostuchiha64.github.io/focused-web-search-engine/

---

## Project Overview

This project implements a complete search engine pipeline:

1. **Crawl** — Spider fetches pages using cURL, restricted to a given URL prefix (focused crawl)
2. **Index** — Extracts page titles, meta tags, and links; tokenizes title terms with stopword removal
3. **Store** — Saves pages and terms to a relational MySQL database (Azure)
4. **Search** — Keyword search ranks results by number of matched title terms

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8, cURL, DOMDocument/XPath |
| Database | MySQL (Azure), PDO with SSL |
| Crawl Strategy | BFS (SplQueue) or DFS (SplStack) |
| Server | University Linux Server (Apache) |

---

## Features

- **Focused crawl** — stays within a URL prefix, ignores binaries and fragments
- **BFS / DFS** — switch between breadth-first and depth-first traversal
- **Stopword removal** — filters common words before indexing
- **Politeness delay** — configurable ms delay between requests
- **Ranked search** — results ordered by keyword hit count descending
- **Clear system** — wipe all database tables for a fresh crawl
- **Display source** — password-protected source code viewer
- **Help page** — step-by-step usage instructions

---

## Database Schema

```sql
CREATE TABLE pages (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  url              VARCHAR(2048) NOT NULL UNIQUE,
  title            VARCHAR(512),
  meta_keywords    TEXT,
  meta_description TEXT,
  crawled_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE terms (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  term VARCHAR(128) NOT NULL UNIQUE
);

CREATE TABLE page_terms (
  page_id INT NOT NULL,
  term_id INT NOT NULL,
  PRIMARY KEY (page_id, term_id),
  FOREIGN KEY (page_id) REFERENCES pages(id),
  FOREIGN KEY (term_id) REFERENCES terms(id)
);
```

---

## Project Structure

```
focused-web-search-engine/
│
├── assets/
│   └── style.css           # Dark-themed UI styles
│
├── index.php               # Home page — links to all features
├── indexer.php             # Crawl interface (seed URL + max pages)
├── spider.php              # Core crawler + indexer logic
├── search.php              # Keyword search with ranked results
├── clear.php               # Clear all database tables
├── check.php               # Password-protected source viewer
├── help.php                # Step-by-step usage guide
├── testdb.php              # Database connection test
│
├── db.php                  # ← NOT committed (see db.example.php)
├── db.example.php          # Template for db.php — fill in credentials
│
├── docs/
│   └── index.html          # GitHub Pages showcase
│
├── .gitignore
└── README.md
```

---

## Setup Instructions

1. Clone the repo and copy `db.example.php` to `db.php`
2. Fill in your MySQL host, port, database name, username, and password in `db.php`
3. Create the three database tables using the schema above
4. Upload all files to a PHP-enabled web server
5. Visit `index.php` in your browser
6. Enter a seed URL and max page count in the **Indexer**, then click **Crawl**
7. Use the **Search** page to query indexed pages by keyword

> **Note:** `db.php` is excluded from this repo via `.gitignore` — never commit real credentials.

---

## Security Notes

- `db.php` is excluded via `.gitignore` — use `db.example.php` as the template
- The source viewer (`check.php`) is password-protected
- The database password is not documented publicly and should be shared privately

---

## Author

**Siddartha Bandi**
GitHub: [@GhostUchiha64](https://github.com/GhostUchiha64)

---

## License

This project was built for academic purposes as part of DATA 525 coursework at the University of North Dakota.
