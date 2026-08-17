<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$db = db();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $db->prepare('SELECT id, phrase, url, created_at FROM keywords WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => 'Keyword not found']);
                exit;
            }
            echo json_encode($row);
        } else {
            $rows = $db->query('SELECT id, phrase, url, created_at FROM keywords ORDER BY id DESC')
                ->fetchAll(SQLITE3_ASSOC);
            echo json_encode($rows);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $phrase = trim($input['phrase'] ?? '');
        $url = trim($input['url'] ?? '');

        if ($phrase === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Phrase is required']);
            exit;
        }

        $stmt = $db->prepare('INSERT INTO keywords (phrase, url) VALUES (:phrase, :url)');
        $stmt->bindValue(':phrase', $phrase, SQLITE3_TEXT);
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->execute();

        http_response_code(201);
        echo json_encode([
            'id' => $db->lastInsertRowID(),
            'phrase' => $phrase,
            'url' => $url,
        ]);
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $phrase = trim($input['phrase'] ?? '');
        $url = $input['url'] ?? null;

        if ($phrase === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Phrase is required']);
            exit;
        }

        $existing = $db->prepare('SELECT id FROM keywords WHERE id = :id');
        $existing->bindValue(':id', $id, SQLITE3_INTEGER);
        if (!$existing->execute()->fetchArray()) {
            http_response_code(404);
            echo json_encode(['error' => 'Keyword not found']);
            exit;
        }

        if ($url !== null) {
            $stmt = $db->prepare('UPDATE keywords SET phrase = :phrase, url = :url WHERE id = :id');
            $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        } else {
            $stmt = $db->prepare('UPDATE keywords SET phrase = :phrase WHERE id = :id');
        }
        $stmt->bindValue(':phrase', $phrase, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['ok' => true]);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        $stmt = $db->prepare('DELETE FROM keywords WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        if ($db->changes() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Keyword not found']);
            exit;
        }

        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
