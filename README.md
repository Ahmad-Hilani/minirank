# MiniRank

A miniature keyword position tracker. Track search phrases, monitor daily rank positions, and visualize trends over time. Built as a full-stack PHP application for the rankingCoach AI Assessment.

All ranking data is simulated — no real search engines involved.

## Quick Start

### Option 1: Local PHP

```bash
git clone <repo-url> && cd minirank
php seed.php
php -S 127.0.0.1:8080
```

### Option 2: Docker

```bash
git clone <repo-url> && cd minirank
docker compose up
```

Open **http://localhost:8080** and log in with:

```
Email:    demo@minirank.dev
Password: demo1234
```

## Requirements

| Requirement | Notes |
|-------------|-------|
| PHP 8.x | With SQLite3 extension |
| SQLite | Embedded — no database server needed |
| Git | For version control |
| Docker | Optional — for containerized setup |

### macOS Setup (Homebrew)

```bash
brew install php
php -m | grep sqlite3   # verify "sqlite3" is listed
```

## Features

### Must-Haves (All Complete)

| # | Requirement | Status |
|---|-------------|--------|
| M1 | **Keywords CRUD** — add, edit, delete keywords for a website | Done |
| M2 | **Seeded history** — 8 keywords × 30 days of daily positions | Done |
| M3 | **Refresh simulation** — AJAX button generates today's positions | Done |
| M4 | **Keyword list** — position, 7-day trend, text search | Done |
| M5 | **Keyword detail** — full position history table | Done |
| M6 | **Security basics** — parameterized queries, escaped output, no secrets | Done |
| M7 | **Runs in 5 minutes** — README, seed command, one-command start | Done |
| M8 | **Responsive** — usable at phone width | Done |

### Stretch Goals (All Complete)

| # | Feature | Status |
|---|---------|--------|
| S1 | **Line chart** — 30-day position history on detail page (Chart.js) | Done |
| S2 | **Multiple projects** — organize keywords by website | Done |
| S3 | **User accounts** — register, login, logout, hashed passwords, CSRF | Done |
| S4 | **Advanced filtering** — position range (Top 10/20/50) and movement filters | Done |
| S5 | **CSV export** — download keyword position history | Done |
| S6 | **PHPUnit tests** — 18 tests covering core logic | Done |
| S7 | **Docker setup** — `docker compose up` starts app + database | Done |
| S8 | **AGENTS.md** — project conventions file | Present |

## Project Structure

```
minirank/
├── index.php              # Keyword list with filters and project selector
├── keyword.php            # Keyword detail with chart and CSV export
├── login.php              # Login page
├── register.php           # Registration page
├── logout.php             # Session destroy
├── api/
│   ├── keywords.php       # Keyword CRUD (GET/POST/PUT/DELETE)
│   ├── projects.php       # Project CRUD (GET/POST/PUT/DELETE)
│   ├── positions.php      # Position history endpoint
│   └── refresh.php        # Generate today's simulated positions
├── db.php                 # SQLite connection and schema
├── auth.php               # Authentication and CSRF helpers
├── helpers.php            # Testable business logic functions
├── seed.php               # Demo data seeder
├── style.css              # Responsive styles
├── tests/
│   ├── TrendCalculationTest.php
│   ├── PositionGenerationTest.php
│   ├── AuthTest.php
│   └── bootstrap.php
├── Dockerfile             # Docker image definition
├── docker-compose.yml     # Docker Compose config
├── phpunit.xml            # PHPUnit configuration
├── process.html           # AI process document
├── AGENTS.md              # Project conventions
└── README.md
```

## Testing

Run the PHPUnit test suite:

```bash
php phpunit.phar tests/
```

Tests cover:
- **Trend calculation** — improved/declined/stable/unknown detection
- **Position drift** — boundary validation (1–100 range)
- **Position generation** — date sequencing, count validation
- **CSRF tokens** — generation, consistency, hex format, length
- **Password hashing** — hash/verify, wrong password rejection

## API Endpoints

All endpoints require authentication. State-changing endpoints require CSRF tokens.

| Method | Endpoint | CSRF | Description |
|--------|----------|------|-------------|
| GET | `/api/keywords.php` | No | List keywords (filterable by project) |
| POST | `/api/keywords.php` | Yes | Create keyword |
| PUT | `/api/keywords.php?id=N` | Yes | Update keyword |
| DELETE | `/api/keywords.php?id=N` | Yes | Delete keyword (cascades) |
| GET | `/api/projects.php` | No | List projects |
| POST | `/api/projects.php` | Yes | Create project |
| PUT | `/api/projects.php?id=N` | Yes | Update project |
| DELETE | `/api/projects.php?id=N` | Yes | Delete project (cascades) |
| GET | `/api/positions.php?keyword_id=N` | No | Get position history |
| POST | `/api/refresh.php` | Yes | Generate today's positions |

### CSRF Token

For POST/PUT/DELETE requests, include the CSRF token in the `X-CSRF-Token` header or as `csrf_token` in the request body.

## Security

- **Parameterized queries** — all SQL uses `bindValue()`, zero string concatenation
- **Output escaping** — `htmlspecialchars()` via `esc()` on all rendered output
- **Password hashing** — `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- **CSRF protection** — tokens via `random_bytes(32)`, timing-safe comparison with `hash_equals()`
- **Session security** — `session_regenerate_id(true)` on login, full destroy on logout
- **Authorization** — every query scopes to the authenticated user via JOINs
- **No secrets** — database path is relative, no credentials in code

## Technology Stack

| Layer | Choice | Rationale |
|-------|--------|-----------|
| Backend | Plain PHP | Minimal context usage for free models |
| Database | SQLite | Zero setup, single file, recommended by brief |
| Frontend | HTML/CSS/JS | No build step, Chart.js via CDN for charts |
| Auth | Session-based | Simple, no dependencies |
| Testing | PHPUnit 11 | PHAR download, no Composer required |
| Container | Docker | `php:8.3-cli` base image |
