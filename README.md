# MiniRank

A miniature keyword position tracker. Track search phrases, monitor daily rank positions, and visualize trends over time. All ranking data is simulated — no real search engines involved.

## Requirements

- PHP 8.x with SQLite3 extension
- Git

No database server needed — SQLite is embedded.

## Setup (macOS with Homebrew)

```bash
# Install PHP (skip if already installed)
brew install php

# Verify PHP and SQLite are available
php -m | grep sqlite3   # should output "sqlite3"
```

## Quick Start

```bash
# Clone the repository
git clone <repo-url> && cd minirank

# Seed demo data (8 keywords × 30 days of positions)
php seed.php

# Start the development server
php -S 127.0.0.1:8080
```

Open **http://127.0.0.1:8080** in your browser.

## Re-seeding

To reset demo data and start fresh:

```bash
php seed.php
```

This clears all keywords and positions, then re-seeds with fresh data.

## Project Structure

```
minirank/
├── index.php          # Keyword list (add, edit, delete, search, refresh)
├── keyword.php        # Keyword detail page with position history
├── api/
│   ├── keywords.php   # CRUD endpoints (GET, POST, PUT, DELETE)
│   ├── positions.php  # Position history for a keyword
│   └── refresh.php    # Generate today's simulated positions
├── db.php             # SQLite connection and schema
├── seed.php           # Demo data seeder
├── style.css          # Responsive styles
└── README.md
```

## Usage

- **Add a keyword:** Click "+ Add Keyword" on the main page
- **Edit/Delete:** Use the buttons next to each keyword
- **Search:** Type in the search bar to filter keywords instantly
- **Refresh positions:** Click "Refresh Positions" to simulate today's rankings
- **View history:** Click any keyword name to see its full position history
