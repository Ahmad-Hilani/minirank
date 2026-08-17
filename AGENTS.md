# AGENTS.md

## Project

MiniRank — keyword rank tracker. Vanilla PHP + SQLite, no framework, no composer.

## Structure

- `hello.php` — throwaway timestamp test
- `minirank/db.php` — DB connection + schema auto-creation (SQLite3, WAL mode, foreign keys on)
- `minirank/seed.php` — clears and reseeds keywords + 30 days of positions
- `minirank/api/keywords.php` — REST API (GET/POST/PUT/DELETE) served as PHP entry point
- `minirank/minirank.db` — SQLite database (gitignored)

## Run

Serve from repo root with PHP built-in server:

```
php -S localhost:8000 -t .
```

API: `GET/POST http://localhost:8000/minirank/api/keywords.php`
`GET/PUT/DELETE` with `?id=N`.

Seed test data: `php minirank/seed.php` (destructive — wipes all data).

## Conventions

- `declare(strict_types=1)` in every PHP file.
- Schema lives in `db.php:initSchema()` — table changes go there, not in migrations.
- DB is created on first `db()` call; no manual setup needed.
- Positions table uses `CHECK(position BETWEEN 1 AND 100)`.
