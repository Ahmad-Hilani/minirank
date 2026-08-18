<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$db = db();

$keywordId = isset($_GET['keyword_id']) ? (int) $_GET['keyword_id'] : 0;

if ($keywordId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'keyword_id required']);
    exit;
}

$kw = $db->prepare('SELECT id, phrase, url FROM keywords WHERE id = :id');
$kw->bindValue(':id', $keywordId, SQLITE3_INTEGER);
$kwRow = $kw->execute()->fetchArray(SQLITE3_ASSOC);

if (!$kwRow) {
    http_response_code(404);
    echo json_encode(['error' => 'Keyword not found']);
    exit;
}

$stmt = $db->prepare('SELECT position, checked_at FROM positions WHERE keyword_id = :kid ORDER BY checked_at ASC');
$stmt->bindValue(':kid', $keywordId, SQLITE3_INTEGER);
$positions = $stmt->execute()->fetchAll(SQLITE3_ASSOC);

echo json_encode([
    'keyword' => $kwRow,
    'positions' => $positions,
]);
