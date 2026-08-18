<?php
declare(strict_types=1);

define('DB_PATH', __DIR__ . '/minirank.db');

function db(): SQLite3
{
    static $db = null;
    if ($db === null) {
        $db = new SQLite3(DB_PATH);
        $db->enableExceptions(true);
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA journal_mode = WAL');
        initSchema($db);
    }
    return $db;
}

function initSchema(SQLite3 $db): void
{
    $db->exec('
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            email         TEXT    NOT NULL UNIQUE,
            password_hash TEXT    NOT NULL,
            created_at    TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    $db->exec('
        CREATE TABLE IF NOT EXISTS projects (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            name       TEXT    NOT NULL,
            url        TEXT    NOT NULL DEFAULT "",
            created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $db->exec('
        CREATE INDEX IF NOT EXISTS idx_projects_user_id
            ON projects(user_id)
    ');

    $db->exec('
        CREATE TABLE IF NOT EXISTS keywords (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
            phrase     TEXT    NOT NULL,
            url        TEXT    NOT NULL DEFAULT "",
            created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');
    $db->exec('
        CREATE INDEX IF NOT EXISTS idx_keywords_project_id
            ON keywords(project_id)
    ');

    $db->exec('
        CREATE TABLE IF NOT EXISTS positions (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            keyword_id INTEGER NOT NULL REFERENCES keywords(id) ON DELETE CASCADE,
            position   INTEGER NOT NULL CHECK(position BETWEEN 1 AND 100),
            checked_at TEXT    NOT NULL
        )
    ');
    $db->exec('
        CREATE INDEX IF NOT EXISTS idx_positions_keyword_id
            ON positions(keyword_id)
    ');
    $db->exec('
        CREATE INDEX IF NOT EXISTS idx_positions_checked_at
            ON positions(checked_at)
    ');
}
