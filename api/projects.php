<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../auth.php';

$userId = auth_require();

$db = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $db->prepare('SELECT id, name, url, created_at FROM projects WHERE id = :id AND user_id = :uid');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => 'Project not found']);
                exit;
            }
            echo json_encode($row);
        } else {
            $stmt = $db->prepare('SELECT id, name, url, created_at FROM projects WHERE user_id = :uid ORDER BY id DESC');
            $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
            $rows = $stmt->execute()->fetchAll(SQLITE3_ASSOC);
            echo json_encode($rows);
        }
        break;

    case 'POST':
        csrf_verify();

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $url = trim($input['url'] ?? '');

        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }

        $stmt = $db->prepare('INSERT INTO projects (user_id, name, url) VALUES (:uid, :name, :url)');
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->execute();

        http_response_code(201);
        echo json_encode([
            'id' => $db->lastInsertRowID(),
            'name' => $name,
            'url' => $url,
        ]);
        break;

    case 'PUT':
        csrf_verify();

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $url = $input['url'] ?? null;

        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }

        $existing = $db->prepare('SELECT id FROM projects WHERE id = :id AND user_id = :uid');
        $existing->bindValue(':id', $id, SQLITE3_INTEGER);
        $existing->bindValue(':uid', $userId, SQLITE3_INTEGER);
        if (!$existing->execute()->fetchArray()) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
            exit;
        }

        if ($url !== null) {
            $stmt = $db->prepare('UPDATE projects SET name = :name, url = :url WHERE id = :id AND user_id = :uid');
            $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        } else {
            $stmt = $db->prepare('UPDATE projects SET name = :name WHERE id = :id AND user_id = :uid');
        }
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['ok' => true]);
        break;

    case 'DELETE':
        csrf_verify();

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        $stmt = $db->prepare('DELETE FROM projects WHERE id = :id AND user_id = :uid');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->execute();

        if ($db->changes() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
            exit;
        }

        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
