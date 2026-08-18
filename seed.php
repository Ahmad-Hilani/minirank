<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$db = db();

$db->exec('DELETE FROM positions');
$db->exec('DELETE FROM keywords');
$db->exec('DELETE FROM projects');
$db->exec('DELETE FROM users');
$db->exec("DELETE FROM sqlite_sequence WHERE name IN ('users', 'projects', 'keywords', 'positions')");

$demoEmail = 'demo@minirank.dev';
$demoPassword = 'demo1234';
$hash = password_hash($demoPassword, PASSWORD_DEFAULT);

$insUser = $db->prepare('INSERT INTO users (email, password_hash) VALUES (:email, :hash)');
$insUser->bindValue(':email', $demoEmail, SQLITE3_TEXT);
$insUser->bindValue(':hash', $hash, SQLITE3_TEXT);
$insUser->execute();
$userId = (int) $db->lastInsertRowID();

$projects = [
    ['name' => 'Pizza Palace',    'url' => 'https://pizzapalace.example.com', 'keywords' => ['best pizza near me', 'pizza delivery', 'cheap pizza']],
    ['name' => 'Travel Deals',    'url' => 'https://traveldeals.example.com', 'keywords' => ['cheap flights to london', 'budget holidays', 'flight comparison']],
    ['name' => 'Tech Blog',       'url' => 'https://techblog.example.com',    'keywords' => ['how to learn php', 'wordpress themes free', 'best laptops 2026']],
];

$insProj = $db->prepare('INSERT INTO projects (user_id, name, url) VALUES (:uid, :name, :url)');
$insKw   = $db->prepare('INSERT INTO keywords (project_id, phrase, url) VALUES (:pid, :phrase, :url)');
$insPos  = $db->prepare('INSERT INTO positions (keyword_id, position, checked_at) VALUES (:kid, :pos, :date)');

$totalKeywords = 0;

foreach ($projects as $proj) {
    $insProj->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $insProj->bindValue(':name', $proj['name'], SQLITE3_TEXT);
    $insProj->bindValue(':url', $proj['url'], SQLITE3_TEXT);
    $insProj->execute();
    $projectId = (int) $db->lastInsertRowID();

    foreach ($proj['keywords'] as $phrase) {
        $insKw->bindValue(':pid', $projectId, SQLITE3_INTEGER);
        $insKw->bindValue(':phrase', $phrase, SQLITE3_TEXT);
        $insKw->bindValue(':url', $proj['url'], SQLITE3_TEXT);
        $insKw->execute();
        $keywordId = (int) $db->lastInsertRowID();
        $totalKeywords++;

        $basePosition = rand(5, 60);
        $date = new DateTime('-29 days');

        for ($day = 0; $day < 30; $day++) {
            $drift = rand(-5, 5);
            $position = max(1, min(100, $basePosition + $drift));
            $basePosition = $position;

            $insPos->bindValue(':kid', $keywordId, SQLITE3_INTEGER);
            $insPos->bindValue(':pos', $position, SQLITE3_INTEGER);
            $insPos->bindValue(':date', $date->format('Y-m-d'), SQLITE3_TEXT);
            $insPos->execute();

            $date->modify('+1 day');
        }
    }
}

echo "Seeded demo user: $demoEmail / $demoPassword\n";
echo "Created " . count($projects) . " projects with $totalKeywords keywords (30 days each).\n";
