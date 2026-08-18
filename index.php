<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$userId = auth_require();
$db = db();

$projects = $db->prepare('SELECT id, name, url FROM projects WHERE user_id = :uid ORDER BY id DESC');
$projects->bindValue(':uid', $userId, SQLITE3_INTEGER);
$projectList = $projects->execute()->fetchAll(SQLITE3_ASSOC);

$selectedProjectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

if ($selectedProjectId) {
    $kwStmt = $db->prepare('
        SELECT k.id, k.phrase, k.url, k.created_at, k.project_id
        FROM keywords k
        JOIN projects p ON p.id = k.project_id
        WHERE k.project_id = :pid AND p.user_id = :uid
        ORDER BY k.id DESC
    ');
    $kwStmt->bindValue(':pid', $selectedProjectId, SQLITE3_INTEGER);
    $kwStmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
} else {
    $kwStmt = $db->prepare('
        SELECT k.id, k.phrase, k.url, k.created_at, k.project_id
        FROM keywords k
        JOIN projects p ON p.id = k.project_id
        WHERE p.user_id = :uid
        ORDER BY k.id DESC
    ');
    $kwStmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
}
$keywords = $kwStmt->execute()->fetchAll(SQLITE3_ASSOC);

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniRank — Keyword Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>MiniRank</h1>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
            <a href="logout.php" class="btn btn-sm btn-ghost" style="color:var(--muted);">Logout</a>
        </div>
    </header>

    <div class="toolbar">
        <div class="project-bar">
            <label for="projectSelect" style="font-size:0.85rem; font-weight:500;">Project:</label>
            <select id="projectSelect" onchange="switchProject()">
                <option value="0">All Projects</option>
                <?php foreach ($projectList as $p): ?>
                <option value="<?php echo (int) $p['id']; ?>" <?php echo $selectedProjectId === (int) $p['id'] ? 'selected' : ''; ?>>
                    <?php echo esc($p['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm" onclick="openProjectModal()">+ Project</button>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <button class="btn" onclick="openAddModal()">+ Keyword</button>
            <button class="btn" style="background:#388e3c" onclick="refreshPositions()" id="refreshBtn">Refresh Positions</button>
        </div>
    </div>

    <div class="filter-bar">
        <input type="text" id="search" placeholder="Search keywords..." oninput="filterTable()">
        <select id="filterPosition" onchange="filterTable()">
            <option value="0">All Positions</option>
            <option value="10">Top 10</option>
            <option value="20">Top 20</option>
            <option value="50">Top 50</option>
        </select>
        <select id="filterMovement" onchange="filterTable()">
            <option value="all">All Movement</option>
            <option value="improved">Improved</option>
            <option value="declined">Declined</option>
            <option value="stable">Stable</option>
        </select>
    </div>

    <div class="table-wrap">
        <table id="kwTable">
            <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Project</th>
                    <th>Position</th>
                    <th>7-Day Trend</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keywords as $kw): ?>
                <tr data-id="<?php echo (int) $kw['id']; ?>" data-pid="<?php echo (int) $kw['project_id']; ?>">
                    <td>
                        <a href="keyword.php?id=<?php echo (int) $kw['id']; ?>" style="color:inherit; text-decoration:none; font-weight:500;">
                            <?php echo esc($kw['phrase']); ?>
                        </a>
                    </td>
                    <td class="project-name" data-pid="<?php echo (int) $kw['project_id']; ?>">
                        <?php
                        $projName = '';
                        foreach ($projectList as $p) {
                            if ((int) $p['id'] === (int) $kw['project_id']) {
                                $projName = $p['name'];
                                break;
                            }
                        }
                        echo esc($projName);
                        ?>
                    </td>
                    <td class="pos-cell" data-kid="<?php echo (int) $kw['id']; ?>">--</td>
                    <td class="trend-cell" data-kid="<?php echo (int) $kw['id']; ?>" data-trend="">--</td>
                    <td>
                        <div class="actions">
                            <button class="btn btn-sm btn-ghost" onclick="openEditModal(<?php echo (int) $kw['id']; ?>, '<?php echo esc($kw['phrase']); ?>', '<?php echo esc($kw['url']); ?>')">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteKeyword(<?php echo (int) $kw['id']; ?>)">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Keyword Modal -->
<div class="modal-overlay" id="kwModal">
    <div class="modal">
        <h2 id="kwModalTitle">Add Keyword</h2>
        <input type="hidden" id="editId">
        <div class="form-group">
            <label for="phraseInput">Search phrase</label>
            <input type="text" id="phraseInput" placeholder="e.g. best pizza near me">
        </div>
        <div class="form-group">
            <label for="urlInput">Website URL</label>
            <input type="text" id="urlInput" placeholder="https://example.com">
        </div>
        <div class="form-group" id="projectGroup">
            <label for="kwProjectSelect">Project</label>
            <select id="kwProjectSelect">
                <?php foreach ($projectList as $p): ?>
                <option value="<?php echo (int) $p['id']; ?>"><?php echo esc($p['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="modal-actions">
            <button class="btn btn-cancel" onclick="closeModal('kwModal')">Cancel</button>
            <button class="btn" onclick="saveKeyword()" id="kwSaveBtn">Save</button>
        </div>
    </div>
</div>

<!-- Project Modal -->
<div class="modal-overlay" id="projModal">
    <div class="modal">
        <h2 id="projModalTitle">Add Project</h2>
        <input type="hidden" id="editProjId">
        <div class="form-group">
            <label for="projNameInput">Project name</label>
            <input type="text" id="projNameInput" placeholder="e.g. My Website">
        </div>
        <div class="form-group">
            <label for="projUrlInput">Website URL</label>
            <input type="text" id="projUrlInput" placeholder="https://example.com">
        </div>
        <div class="modal-actions">
            <button class="btn btn-cancel" onclick="closeModal('projModal')">Cancel</button>
            <button class="btn" onclick="saveProject()" id="projSaveBtn">Save</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
var CSRF_TOKEN = "<?php echo esc($csrf); ?>";

function toast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 2500);
}

function switchProject() {
    var pid = document.getElementById('projectSelect').value;
    var url = pid === '0' ? 'index.php' : 'index.php?project_id=' + pid;
    window.location.href = url;
}

function filterTable() {
    var q = document.getElementById('search').value.toLowerCase();
    var maxPos = parseInt(document.getElementById('filterPosition').value) || 0;
    var movement = document.getElementById('filterMovement').value;
    var rows = document.querySelectorAll('#kwTable tbody tr');

    rows.forEach(function(r) {
        var text = r.querySelector('td').textContent.toLowerCase();
        var matchSearch = text.indexOf(q) !== -1;

        var posCell = r.querySelector('.pos-cell');
        var posText = posCell.textContent.replace('#', '');
        var pos = parseInt(posText) || 999;
        var matchPos = maxPos === 0 || pos <= maxPos;

        var trendCell = r.querySelector('.trend-cell');
        var trend = trendCell.getAttribute('data-trend') || '';
        var matchMovement = movement === 'all' || trend === movement;

        r.style.display = (matchSearch && matchPos && matchMovement) ? '' : 'none';
    });
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function openAddModal() {
    document.getElementById('kwModalTitle').textContent = 'Add Keyword';
    document.getElementById('editId').value = '';
    document.getElementById('phraseInput').value = '';
    document.getElementById('urlInput').value = '';
    document.getElementById('kwSaveBtn').textContent = 'Add';
    var sel = document.getElementById('kwProjectSelect');
    var currentPid = document.getElementById('projectSelect').value;
    if (currentPid !== '0') sel.value = currentPid;
    document.getElementById('kwModal').classList.add('active');
}

function openEditModal(id, phrase, url) {
    document.getElementById('kwModalTitle').textContent = 'Edit Keyword';
    document.getElementById('editId').value = id;
    document.getElementById('phraseInput').value = phrase;
    document.getElementById('urlInput').value = url;
    document.getElementById('kwSaveBtn').textContent = 'Save';
    document.getElementById('kwModal').classList.add('active');
}

function saveKeyword() {
    var id = document.getElementById('editId').value;
    var phrase = document.getElementById('phraseInput').value.trim();
    var url = document.getElementById('urlInput').value.trim();
    var pid = document.getElementById('kwProjectSelect').value;

    if (!phrase) { toast('Phrase is required'); return; }
    if (!pid || pid === '0') { toast('Select a project'); return; }

    var isEdit = id !== '';
    var method = isEdit ? 'PUT' : 'POST';
    var body = JSON.stringify({phrase: phrase, url: url, project_id: parseInt(pid)});
    var endpoint = 'api/keywords.php' + (isEdit ? '?id=' + id : '');

    fetch(endpoint, {
        method: method,
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN},
        body: body
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) { toast(data.error); return; }
        closeModal('kwModal');
        toast(isEdit ? 'Keyword updated' : 'Keyword added');
        setTimeout(function() { location.reload(); }, 400);
    })
    .catch(function() { toast('Request failed'); });
}

function deleteKeyword(id) {
    if (!confirm('Delete this keyword and all its history?')) return;

    fetch('api/keywords.php?id=' + id, {
        method: 'DELETE',
        headers: {'X-CSRF-Token': CSRF_TOKEN}
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) { toast(data.error); return; }
        var row = document.querySelector('tr[data-id="' + id + '"]');
        if (row) row.remove();
        toast('Keyword deleted');
    })
    .catch(function() { toast('Request failed'); });
}

function refreshPositions() {
    var btn = document.getElementById('refreshBtn');
    btn.disabled = true;
    btn.textContent = 'Refreshing...';

    var pid = document.getElementById('projectSelect').value;
    var url = 'api/refresh.php' + (pid !== '0' ? '?project_id=' + pid : '');

    fetch(url, {
        method: 'POST',
        headers: {'X-CSRF-Token': CSRF_TOKEN}
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.textContent = 'Refresh Positions';
        if (data.error) { toast(data.error); return; }
        if (data.updated) {
            data.updated.forEach(function(item) {
                var posCell = document.querySelector('.pos-cell[data-kid="' + item.id + '"]');
                if (posCell) posCell.textContent = '#' + item.position;
            });
        }
        toast('Positions updated for ' + data.date);
        loadTrends();
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = 'Refresh Positions';
        toast('Refresh failed');
    });
}

function openProjectModal() {
    document.getElementById('projModalTitle').textContent = 'Add Project';
    document.getElementById('editProjId').value = '';
    document.getElementById('projNameInput').value = '';
    document.getElementById('projUrlInput').value = '';
    document.getElementById('projSaveBtn').textContent = 'Add';
    document.getElementById('projModal').classList.add('active');
}

function openEditProjectModal(id, name, url) {
    document.getElementById('projModalTitle').textContent = 'Edit Project';
    document.getElementById('editProjId').value = id;
    document.getElementById('projNameInput').value = name;
    document.getElementById('projUrlInput').value = url;
    document.getElementById('projSaveBtn').textContent = 'Save';
    document.getElementById('projModal').classList.add('active');
}

function saveProject() {
    var id = document.getElementById('editProjId').value;
    var name = document.getElementById('projNameInput').value.trim();
    var url = document.getElementById('projUrlInput').value.trim();

    if (!name) { toast('Name is required'); return; }

    var isEdit = id !== '';
    var method = isEdit ? 'PUT' : 'POST';
    var body = JSON.stringify({name: name, url: url});
    var endpoint = 'api/projects.php' + (isEdit ? '?id=' + id : '');

    fetch(endpoint, {
        method: method,
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN},
        body: body
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) { toast(data.error); return; }
        closeModal('projModal');
        toast(isEdit ? 'Project updated' : 'Project added');
        setTimeout(function() { location.reload(); }, 400);
    })
    .catch(function() { toast('Request failed'); });
}

function loadTrends() {
    var rows = document.querySelectorAll('#kwTable tbody tr');
    rows.forEach(function(row) {
        var id = row.getAttribute('data-id');
        fetch('api/positions.php?keyword_id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var positions = data.positions || [];
            var posCell = document.querySelector('.pos-cell[data-kid="' + id + '"]');
            var trendCell = document.querySelector('.trend-cell[data-kid="' + id + '"]');

            if (positions.length > 0) {
                var latest = positions[positions.length - 1];
                posCell.textContent = '#' + latest.position;
            }

            if (positions.length >= 7) {
                var now = positions[positions.length - 1].position;
                var weekAgo = positions[positions.length - 7].position;
                var diff = now - weekAgo;

                if (diff < -2) {
                    trendCell.innerHTML = '<span class="trend trend-up">Improved</span>';
                    trendCell.setAttribute('data-trend', 'improved');
                } else if (diff > 2) {
                    trendCell.innerHTML = '<span class="trend trend-down">Declined</span>';
                    trendCell.setAttribute('data-trend', 'declined');
                } else {
                    trendCell.innerHTML = '<span class="trend trend-stable">Stable</span>';
                    trendCell.setAttribute('data-trend', 'stable');
                }
            } else {
                trendCell.textContent = '--';
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadTrends();
});
</script>
</body>
</html>
