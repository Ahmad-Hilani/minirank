<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$db = db();

$db->exec('DELETE FROM positions');
$db->exec('DELETE FROM keywords');
$db->exec("DELETE FROM sqlite_sequence WHERE name IN ('keywords', 'positions')");

$keywords = [
    'best pizza near me',
    'cheap flights to london',
    'how to learn php',
    'wordpress themes free',
    'running shoes review',
    'coffee shops downtown',
    'used cars for sale',
    'best laptops 2026',
];

$insertKw = $db->prepare('INSERT INTO keywords (phrase, url) VALUES (:phrase, :url)');
$insertPos = $db->prepare('INSERT INTO positions (keyword_id, position, checked_at) VALUES (:kid, :pos, :date)');

foreach ($keywords as $phrase) {
    $insertKw->bindValue(':phrase', $phrase, SQLITE3_TEXT);
    $insertKw->bindValue(':url', 'https://example.com', SQLITE3_TEXT);
    $insertKw->execute();
    $keywordId = $db->lastInsertRowID();

    $basePosition = rand(5, 60);
    $date = new DateTime('-29 days');

    for ($day = 0; $day < 30; $day++) {
        $drift = rand(-5, 5);
        $position = max(1, min(100, $basePosition + $drift));
        $basePosition = $position;

        $insertPos->bindValue(':kid', $keywordId, SQLITE3_INTEGER);
        $insertPos->bindValue(':pos', $position, SQLITE3_INTEGER);
        $insertPos->bindValue(':date', $date->format('Y-m-d'), SQLITE3_TEXT);
        $insertPos->execute();

        $date->modify('+1 day');
    }
}

echo "Seeded " . count($keywords) . " keywords with 30 days of positions each.\n";
