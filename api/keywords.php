<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../auth.php';

$userId = auth_require();

$db = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $db->prepare('
                SELECT k.id, k.phrase, k.url, k.created_at, k.project_id
                FROM keywords k
                JOIN projects p ON p.id = k.project_id
                WHERE k.id = :id AND p.user_id = :uid
            ');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => 'Keyword not found']);
                exit;
            }
            echo json_encode($row);
        } else {
            if ($projectId) {
                $stmt = $db->prepare('
                    SELECT k.id, k.phrase, k.url, k.created_at, k.project_id
                    FROM keywords k
                    JOIN projects p ON p.id = k.project_id
                    WHERE k.project_id = :pid AND p.user_id = :uid
                    ORDER BY k.id DESC
                ');
                $stmt->bindValue(':pid', $projectId, SQLITE3_INTEGER);
                $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
            } else {
                $stmt = $db->prepare('
                    SELECT k.id, k.phrase, k.url, k.created_at, k.project_id
                    FROM keywords k
                    JOIN projects p ON p.id = k.project_id
                    WHERE p.user_id = :uid
                    ORDER BY k.id DESC
                ');
                $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
            }
            $rows = $stmt->execute()->fetchAll(SQLITE3_ASSOC);
            echo json_encode($rows);
        }
        break;

    case 'POST':
        csrf_verify();

        $input = json_decode(file_get_contents('php://input'), true);
        $phrase = trim($input['phrase'] ?? '');
        $url = trim($input['url'] ?? '');
        $pid = (int) ($input['project_id'] ?? 0);

        if ($phrase === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Phrase is required']);
            exit;
        }

        if ($pid <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'project_id required']);
            exit;
        }

        $check = $db->prepare('SELECT id FROM projects WHERE id = :pid AND user_id = :uid');
        $check->bindValue(':pid', $pid, SQLITE3_INTEGER);
        $check->bindValue(':uid', $userId, SQLITE3_INTEGER);
        if (!$check->execute()->fetchArray()) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
            exit;
        }

        $stmt = $db->prepare('INSERT INTO keywords (project_id, phrase, url) VALUES (:pid, :phrase, :url)');
        $stmt->bindValue(':pid', $pid, SQLITE3_INTEGER);
        $stmt->bindValue(':phrase', $phrase, SQLITE3_TEXT);
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->execute();

        http_response_code(201);
        echo json_encode([
            'id' => $db->lastInsertRowID(),
            'phrase' => $phrase,
            'url' => $url,
            'project_id' => $pid,
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
        $phrase = trim($input['phrase'] ?? '');
        $url = $input['url'] ?? null;

        if ($phrase === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Phrase is required']);
            exit;
        }

        $existing = $db->prepare('
            SELECT k.id FROM keywords k
            JOIN projects p ON p.id = k.project_id
            WHERE k.id = :id AND p.user_id = :uid
        ');
        $existing->bindValue(':id', $id, SQLITE3_INTEGER);
        $existing->bindValue(':uid', $userId, SQLITE3_INTEGER);
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
        csrf_verify();

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        $stmt = $db->prepare('
            DELETE FROM keywords WHERE id = :id AND project_id IN (
                SELECT id FROM projects WHERE user_id = :uid
            )
        ');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
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
