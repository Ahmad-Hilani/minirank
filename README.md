# MiniRank

A miniature keyword position tracker. Track search phrases, monitor daily rank positions, and visualize trends over time. All ranking data is simulated — no real search engines involved.

## Requirements

- PHP 8.x with SQLite3 extension
- Git
- Docker (optional, for containerized setup)

No database server needed — SQLite is embedded.

## Quick Start

### Option 1: Local PHP

```bash
# Clone the repository
git clone <repo-url> && cd minirank

# Seed demo data
php seed.php

# Start the development server
php -S 127.0.0.1:8080
```

### Option 2: Docker

```bash
git clone <repo-url> && cd minirank
docker compose up
```

Open **http://localhost:8080** in your browser.

### Demo Credentials

```
Email:    demo@minirank.dev
Password: demo1234
```

## Features

- **Keywords CRUD:** Add, edit, delete search phrases
- **Multiple Projects:** Organize keywords by website/project
- **Position Tracking:** 30-day simulated rank history per keyword
- **Trend Analysis:** 7-day trend indicator (improved/declined/stable)
- **Line Chart:** Visual position history on keyword detail page
- **Advanced Filtering:** Filter by position range and movement
- **CSV Export:** Download keyword position history
- **User Accounts:** Registration, login, session management
- **CSRF Protection:** Secure forms with token verification
- **Responsive:** Works on mobile and desktop

## Project Structure

```
minirank/
├── index.php          # Keyword list with filters and project selector
├── keyword.php        # Keyword detail with chart and CSV export
├── login.php          # Login page
├── register.php       # Registration page
├── logout.php         # Session destroy
├── api/
│   ├── keywords.php   # Keyword CRUD endpoints
│   ├── projects.php   # Project CRUD endpoints
│   ├── positions.php  # Position history endpoint
│   └── refresh.php    # Generate today's positions
├── db.php             # SQLite connection and schema
├── auth.php           # Authentication and CSRF helpers
├── helpers.php        # Testable business logic
├── seed.php           # Demo data seeder
├── style.css          # Responsive styles
├── tests/             # PHPUnit test suite
├── Dockerfile         # Docker image definition
├── docker-compose.yml # Docker Compose config
└── README.md
```

## Testing

Run the PHPUnit test suite:

```bash
php phpunit.phar tests/
```

Tests cover:
- Trend calculation logic (improved/declined/stable)
- Position drift and boundary validation
- Position history generation
- CSRF token generation
- Password hashing

## Re-seeding

To reset demo data and start fresh:

```bash
php seed.php
```

This clears all users, projects, keywords, and positions, then re-seeds with fresh data.

## API Endpoints

| Method | Endpoint | Auth | CSRF | Description |
|--------|----------|------|------|-------------|
| GET | `/api/keywords.php` | Yes | No | List keywords |
| POST | `/api/keywords.php` | Yes | Yes | Create keyword |
| PUT | `/api/keywords.php?id=N` | Yes | Yes | Update keyword |
| DELETE | `/api/keywords.php?id=N` | Yes | Yes | Delete keyword |
| GET | `/api/projects.php` | Yes | No | List projects |
| POST | `/api/projects.php` | Yes | Yes | Create project |
| PUT | `/api/projects.php?id=N` | Yes | Yes | Update project |
| DELETE | `/api/projects.php?id=N` | Yes | Yes | Delete project |
| GET | `/api/positions.php?keyword_id=N` | Yes | No | Get position history |
| POST | `/api/refresh.php` | Yes | Yes | Generate today's positions |
