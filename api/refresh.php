<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

csrf_verify();

$userId = auth_require();

$db = db();
$today = date('Y-m-d');

$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;

if ($projectId) {
    $stmt = $db->prepare('
        SELECT k.id FROM keywords k
        JOIN projects p ON p.id = k.project_id
        WHERE k.project_id = :pid AND p.user_id = :uid
    ');
    $stmt->bindValue(':pid', $projectId, SQLITE3_INTEGER);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
} else {
    $stmt = $db->prepare('
        SELECT k.id FROM keywords k
        JOIN projects p ON p.id = k.project_id
        WHERE p.user_id = :uid
    ');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
}

$keywords = $stmt->execute()->fetchAll(SQLITE3_ASSOC);

$insertPos = $db->prepare('INSERT INTO positions (keyword_id, position, checked_at) VALUES (:kid, :pos, :date)');

$results = [];

foreach ($keywords as $kw) {
    $keywordId = (int) $kw['id'];

    $prev = $db->prepare('SELECT position FROM positions WHERE keyword_id = :kid ORDER BY checked_at DESC LIMIT 1');
    $prev->bindValue(':kid', $keywordId, SQLITE3_INTEGER);
    $prevRow = $prev->execute()->fetchArray(SQLITE3_ASSOC);

    if ($prevRow) {
        $base = (int) $prevRow['position'];
        $drift = rand(-8, 8);
        $position = max(1, min(100, $base + $drift));
    } else {
        $position = rand(1, 100);
    }

    $insertPos->bindValue(':kid', $keywordId, SQLITE3_INTEGER);
    $insertPos->bindValue(':pos', $position, SQLITE3_INTEGER);
    $insertPos->bindValue(':date', $today, SQLITE3_TEXT);
    $insertPos->execute();

    $results[] = ['id' => $keywordId, 'position' => $position];
}

echo json_encode(['ok' => true, 'date' => $today, 'updated' => $results]);
