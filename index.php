<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$db = db();

$keywords = $db->query('SELECT id, phrase, url, created_at FROM keywords ORDER BY id DESC')
    ->fetchAll(SQLITE3_ASSOC);

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
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
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <button class="btn" onclick="openAddModal()">+ Add Keyword</button>
            <button class="btn" style="background:#388e3c" onclick="refreshPositions()" id="refreshBtn">Refresh Positions</button>
        </div>
    </header>

    <div class="search-bar">
        <input type="text" id="search" placeholder="Search keywords..." oninput="filterTable()">
    </div>

    <div class="table-wrap">
        <table id="kwTable">
            <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Position</th>
                    <th>7-Day Trend</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keywords as $kw): ?>
                <tr data-id="<?php echo (int) $kw['id']; ?>">
                    <td>
                        <a href="keyword.php?id=<?php echo (int) $kw['id']; ?>" style="color:inherit; text-decoration:none; font-weight:500;">
                            <?php echo esc($kw['phrase']); ?>
                        </a>
                    </td>
                    <td class="pos-cell" data-kid="<?php echo (int) $kw['id']; ?>">--</td>
                    <td class="trend-cell" data-kid="<?php echo (int) $kw['id']; ?>">--</td>
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

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <h2 id="modalTitle">Add Keyword</h2>
        <input type="hidden" id="editId">
        <div class="form-group">
            <label for="phraseInput">Search phrase</label>
            <input type="text" id="phraseInput" placeholder="e.g. best pizza near me">
        </div>
        <div class="form-group">
            <label for="urlInput">Website URL</label>
            <input type="text" id="urlInput" placeholder="https://example.com">
        </div>
        <div class="modal-actions">
            <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn" onclick="saveKeyword()" id="saveBtn">Save</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
function escHtml(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
}

function toast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 2500);
}

function filterTable() {
    var q = document.getElementById('search').value.toLowerCase();
    var rows = document.querySelectorAll('#kwTable tbody tr');
    rows.forEach(function(r) {
        var text = r.querySelector('td').textContent.toLowerCase();
        r.style.display = text.indexOf(q) === -1 ? 'none' : '';
    });
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Keyword';
    document.getElementById('editId').value = '';
    document.getElementById('phraseInput').value = '';
    document.getElementById('urlInput').value = '';
    document.getElementById('saveBtn').textContent = 'Add';
    document.getElementById('modal').classList.add('active');
}

function openEditModal(id, phrase, url) {
    document.getElementById('modalTitle').textContent = 'Edit Keyword';
    document.getElementById('editId').value = id;
    document.getElementById('phraseInput').value = phrase;
    document.getElementById('urlInput').value = url;
    document.getElementById('saveBtn').textContent = 'Save';
    document.getElementById('modal').classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

function saveKeyword() {
    var id = document.getElementById('editId').value;
    var phrase = document.getElementById('phraseInput').value.trim();
    var url = document.getElementById('urlInput').value.trim();

    if (!phrase) {
        toast('Phrase is required');
        return;
    }

    var isEdit = id !== '';
    var method = isEdit ? 'PUT' : 'POST';
    var body = JSON.stringify({phrase: phrase, url: url});
    var endpoint = 'api/keywords.php' + (isEdit ? '?id=' + id : '');

    fetch(endpoint, {
        method: method,
        headers: {'Content-Type': 'application/json'},
        body: body
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) {
            toast(data.error);
            return;
        }
        closeModal();
        toast(isEdit ? 'Keyword updated' : 'Keyword added');
        setTimeout(function() { location.reload(); }, 400);
    })
    .catch(function() { toast('Request failed'); });
}

function deleteKeyword(id) {
    if (!confirm('Delete this keyword and all its history?')) return;

    fetch('api/keywords.php?id=' + id, { method: 'DELETE' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) {
            toast(data.error);
            return;
        }
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

    fetch('api/refresh.php', { method: 'POST' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.textContent = 'Refresh Positions';

        if (data.error) {
            toast(data.error);
            return;
        }

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
                    trendCell.innerHTML = '<span class="trend trend-up">▲ Improved</span>';
                } else if (diff > 2) {
                    trendCell.innerHTML = '<span class="trend trend-down">▼ Declined</span>';
                } else {
                    trendCell.innerHTML = '<span class="trend trend-stable">● Stable</span>';
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
