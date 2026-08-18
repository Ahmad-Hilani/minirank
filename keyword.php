<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$userId = auth_require();

$keywordId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($keywordId <= 0) {
    header('Location: index.php');
    exit;
}

$db = db();

$kw = $db->prepare('
    SELECT k.id, k.phrase, k.url, k.project_id
    FROM keywords k
    JOIN projects p ON p.id = k.project_id
    WHERE k.id = :id AND p.user_id = :uid
');
$kw->bindValue(':id', $keywordId, SQLITE3_INTEGER);
$kw->bindValue(':uid', $userId, SQLITE3_INTEGER);
$kwRow = $kw->execute()->fetchArray(SQLITE3_ASSOC);

if (!$kwRow) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare('SELECT position, checked_at FROM positions WHERE keyword_id = :kid ORDER BY checked_at ASC');
$stmt->bindValue(':kid', $keywordId, SQLITE3_INTEGER);
$positions = $stmt->execute()->fetchAll(SQLITE3_ASSOC);

$proj = $db->prepare('SELECT name FROM projects WHERE id = :pid');
$proj->bindValue(':pid', (int) $kwRow['project_id'], SQLITE3_INTEGER);
$projRow = $proj->execute()->fetchArray(SQLITE3_ASSOC);
$projectName = $projRow ? $projRow['name'] : '';

$chartLabels = array_map(fn($p) => $p['checked_at'], $positions);
$chartData = array_map(fn($p) => (int) $p['position'], $positions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($kwRow['phrase']); ?> — MiniRank</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="container">
    <div class="detail-header">
        <a href="index.php" class="back-link">&larr; Back</a>
        <h1><?php echo esc($kwRow['phrase']); ?></h1>
    </div>

    <p style="margin-bottom:0.5rem; font-size:0.85rem; color:var(--muted);">
        Project: <strong><?php echo esc($projectName); ?></strong>
    </p>

    <?php if ($kwRow['url']): ?>
    <p style="margin-bottom:1rem; color:var(--muted); font-size:0.9rem;">
        Tracking for: <a href="<?php echo esc($kwRow['url']); ?>" target="_blank" rel="noopener" style="color:var(--primary);"><?php echo esc($kwRow['url']); ?></a>
    </p>
    <?php endif; ?>

    <?php if (count($positions) > 0): ?>
    <p style="margin-bottom:0.75rem; font-size:0.9rem; color:var(--muted);">
        <?php echo count($positions); ?> days recorded
        &middot;
        Latest: <strong style="color:var(--text);">#<?php echo (int) end($positions)['position']; ?></strong>
        &middot;
        Best: <strong style="color:var(--green);">#<?php echo (int) min(array_column($positions, 'position')); ?></strong>
    </p>
    <?php endif; ?>

    <?php if (count($positions) > 0): ?>
    <div class="chart-wrap">
        <canvas id="posChart"></canvas>
    </div>

    <div style="margin: 1rem 0; text-align:right;">
        <button class="btn btn-sm" onclick="downloadCsv()">Download CSV</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Position</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($positions) as $pos): ?>
                <tr>
                    <td><?php echo esc($pos['checked_at']); ?></td>
                    <td>
                        <strong>#<?php echo (int) $pos['position']; ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="loading">No position data yet. Click <strong>Refresh Positions</strong> on the main page to generate data.</div>
    <?php endif; ?>
</div>

<script>
var csvLabels = <?php echo json_encode($chartLabels); ?>;
var csvData = <?php echo json_encode($chartData); ?>;
var keywordPhrase = <?php echo json_encode($kwRow['phrase']); ?>;

document.addEventListener('DOMContentLoaded', function() {
    if (csvLabels.length === 0) return;

    var ctx = document.getElementById('posChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: csvLabels,
            datasets: [{
                label: 'Position',
                data: csvData,
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25, 118, 210, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    reverse: true,
                    min: 1,
                    max: 100,
                    title: { display: true, text: 'Position (lower = better)' },
                    ticks: { stepSize: 10 }
                },
                x: {
                    title: { display: true, text: 'Date' },
                    ticks: { maxRotation: 45, maxTicksLimit: 15 }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return '#' + ctx.parsed.y; }
                    }
                }
            }
        }
    });
});

function downloadCsv() {
    var rows = [['Date', 'Position']];
    for (var i = 0; i < csvLabels.length; i++) {
        rows.push([csvLabels[i], csvData[i]]);
    }
    var csv = rows.map(function(r) { return r.join(','); }).join('\n');
    var blob = new Blob([csv], {type: 'text/csv'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = keywordPhrase.replace(/[^a-z0-9]/gi, '_') + '_positions.csv';
    a.click();
}
</script>
</body>
</html>
