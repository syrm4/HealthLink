<?php
// HealthLink — Executive/Leader Dashboard (Dr. Chen persona)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_role('leader', 'admin');

$user = current_user();
$db   = getDB();

$tf    = in_array((int)($_GET['tf'] ?? 90), [30,60,90,180,365]) ? (int)$_GET['tf'] : 90;
$since = date('Y-m-d', strtotime('-' . $tf . ' days'));
$view  = in_array($_GET['view'] ?? 'all', ['all','demographics','trends','staffing','ai','approvals']) ? ($_GET['view'] ?? 'all') : 'all';
$tab   = $_GET['tab'] ?? 'dashboard';

$s = $db->prepare('SELECT COUNT(*) FROM requests WHERE created_at >= ?');
$s->execute([$since]); $totalCount = (int)$s->fetchColumn();

$s = $db->prepare("SELECT COUNT(*) FROM requests WHERE status IN ('approved','sent_to_qualtrics','fulfilled') AND created_at >= ?");
$s->execute([$since]); $approvedCount = (int)$s->fetchColumn();
$approvalRate = $totalCount > 0 ? round(($approvedCount / $totalCount) * 100) : 0;

$s = $db->prepare("SELECT COUNT(*) FROM requests WHERE request_type = 'inperson_support' AND status IN ('approved','fulfilled') AND created_at >= ?");
$s->execute([$since]); $staffCount = (int)$s->fetchColumn();

$s = $db->prepare("SELECT r.*, u.full_name AS user_full_name FROM requests r LEFT JOIN users u ON r.user_id = u.id WHERE r.status = 'approved' ORDER BY r.ai_priority_score DESC LIMIT 20");
$s->execute(); $pendingApprovals = $s->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leader_action'])) {
    $rid    = (int)$_POST['request_id'];
    $action = $_POST['leader_action'];
    if ($rid && in_array($action, ['approve','return'], true)) {
        if ($action === 'approve') {
            $db->prepare("UPDATE requests SET status = 'sent_to_qualtrics', updated_at = NOW() WHERE id = ?")->execute([$rid]);
            $db->prepare('INSERT INTO status_history (request_id, status, changed_by, notes) VALUES (?,?,?,?)')->execute([$rid, 'sent_to_qualtrics', $user['name'], 'Approved by leadership']);
        } else {
            $db->prepare("UPDATE requests SET status = 'in_review', updated_at = NOW() WHERE id = ?")->execute([$rid]);
            $db->prepare('INSERT INTO status_history (request_id, status, changed_by, notes) VALUES (?,?,?,?)')->execute([$rid, 'in_review', $user['name'], 'Returned to admin by leadership']);
        }
        header('Location: ' . BASE_PATH . '/pages/leader_dashboard.php?tab=approvals&tf=' . $tf);
        exit;
    }
}

$statusBadge = [
    'submitted'         => ['label' => 'Submitted',    'class' => 'badge-submitted'],
    'in_review'         => ['label' => 'In review',    'class' => 'badge-in-review'],
    'approved'          => ['label' => 'Approved',     'class' => 'badge-approved'],
    'sent_to_qualtrics' => ['label' => 'To Qualtrics', 'class' => 'badge-qualtrics'],
    'fulfilled'         => ['label' => 'Fulfilled',    'class' => 'badge-fulfilled'],
];
$typeLabel = ['mailing'=>'Mailing','presentation'=>'Presentation','inperson_support'=>'In-person support'];

// Header outputs <!DOCTYPE html> — Chart.js must load AFTER this
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Chart.js loaded inside <body>, after the HTML document starts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="page-header">
    <div class="container">
        <div class="d-flex align-center justify-between" style="flex-wrap:wrap; gap:var(--space-md);">
            <div><h1>Executive Dashboard</h1><p>Strategic overview of HealthLink community health requests</p></div>
            <div class="d-flex gap-sm" style="flex-wrap:wrap; align-items:center;">
                <form method="GET" style="display:flex; gap:var(--space-sm); flex-wrap:wrap;">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    <select name="tf" onchange="this.form.submit()">
                        <?php foreach ([30=>'Last 30 days',60=>'Last 60 days',90=>'Last 90 days',180=>'Last 6 months',365=>'Last 12 months'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $tf===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="view" onchange="this.form.submit()">
                        <?php foreach (['all'=>'All sections','demographics'=>'Demographics','trends'=>'Predictive trends','staffing'=>'Staff &amp; demand','ai'=>'AI summary','approvals'=>'Approvals'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $view===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <button onclick="window.print()" class="btn btn-success">Print / PDF summary</button>
            </div>
        </div>
    </div>
</div>

<main class="main-content" style="padding-top:0;">
    <div class="container" style="padding-top:var(--space-xl);">

        <div class="tabs">
            <a href="?tab=dashboard&tf=<?= $tf ?>&view=<?= htmlspecialchars($view) ?>" class="tab-btn <?= $tab==='dashboard'?'active':'' ?>">Overview</a>
            <a href="?tab=approvals&tf=<?= $tf ?>" class="tab-btn <?= $tab==='approvals'?'active':'' ?>">Pending approvals <span class="badge badge-in-review" style="margin-left:4px;"><?= count($pendingApprovals) ?></span></a>
        </div>

        <?php if ($tab === 'dashboard'): ?>

        <div class="metric-grid">
            <div class="metric-card"><p class="metric-label">Total requests</p><p class="metric-value"><?= $totalCount ?></p><p class="metric-sub">Last <?= $tf ?> days</p></div>
            <div class="metric-card metric-success"><p class="metric-label">Approval rate</p><p class="metric-value"><?= $approvalRate ?>%</p><p class="metric-sub">Approved or fulfilled</p></div>
            <div class="metric-card"><p class="metric-label">Avg. fulfillment</p><p class="metric-value">3.2d</p><p class="metric-sub">Days to fulfill</p></div>
            <div class="metric-card metric-cobalt"><p class="metric-label">Staff deployments</p><p class="metric-value"><?= $staffCount ?></p><p class="metric-sub">Approved in-person</p></div>
        </div>

        <?php if ($view === 'all' || $view === 'demographics'): ?>
        <div class="card">
            <div class="card-header">
                <h3>Demographic breakdown</h3>
                <div class="d-flex gap-sm">
                    <button class="btn btn-sm btn-secondary" onclick="setPie('language')">Language</button>
                    <button class="btn btn-sm btn-secondary" onclick="setPie('age')">Age group</button>
                    <button class="btn btn-sm btn-secondary" onclick="setPie('gender')">Gender</button>
                </div>
            </div>
            <p class="text-muted text-small">Who is being served by HealthLink requests</p>
            <div style="max-width:480px; margin:0 auto;"><canvas id="pieChart" height="240"></canvas></div>
        </div>
        <?php endif; ?>

        <?php if ($view === 'all' || $view === 'trends'): ?>
        <div class="card">
            <div class="card-header"><h3>Predictive trends</h3></div>
            <p class="text-muted text-small">Projected inventory, geographic demand, and staff deployment days over 6 months</p>
            <canvas id="lineChart" height="120"></canvas>
        </div>
        <?php endif; ?>

        <?php if ($view === 'all' || $view === 'staffing'): ?>
        <div class="card">
            <div class="card-header"><h3>Staff capacity vs demand</h3></div>
            <p class="text-muted text-small">Monthly comparison of available staff days versus event demand</p>
            <canvas id="barChart" height="100"></canvas>
        </div>
        <?php endif; ?>

        <?php if ($view === 'all' || $view === 'ai'): ?>
        <div class="card">
            <div class="card-header">
                <h3>AI executive summary</h3>
                <small class="text-muted">Based on approved requests &mdash; last <?= $tf ?> days</small>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:var(--space-md); margin-bottom:var(--space-md);">
                <div class="alert alert-success"><strong>Staffing</strong><br>Current staff levels are sufficient. <?= $staffCount ?> deployment<?= $staffCount !== 1 ? 's' : '' ?> approved. No shortfall detected.</div>
                <div class="alert alert-warning"><strong>Inventory</strong><br>Educational material stock projected to run low. Recommend reorder of 400+ units within 2 weeks.</div>
                <div class="alert alert-danger"><strong>Geographic gap</strong><br>Requests from outside the service area are increasing. Mailing fulfillment is handling demand &mdash; consider expanding the service boundary.</div>
                <div class="alert alert-success"><strong>Fulfillment channel</strong><br>Mailing is 2.4&times; more cost-effective than in-person for events under 75 participants. 61% of requests qualify.</div>
                <div class="alert alert-warning"><strong>Language access</strong><br>31% of requests indicate Spanish preference, but only 18% of fulfillments include bilingual materials.</div>
                <div class="alert alert-success"><strong>Demand trend</strong><br>Request volume up this quarter. Schools and youth programs are the fastest-growing segment at 38% of new submissions.</div>
            </div>
            <div class="alert alert-ai">
                <div class="ai-indicator"></div>
                <div><strong>Summary (last <?= $tf ?> days):</strong> HealthLink processed <?= $totalCount ?> community health requests with a <?= $approvalRate ?>% approval rate. Mailing fulfillment is the most efficient channel for events under 75 attendees. Staff capacity is stable, but inventory replenishment for educational materials is time-sensitive. Most actionable item: address the bilingual materials gap.</div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; /* end dashboard tab */ ?>

        <?php if ($tab === 'approvals' || $view === 'approvals'): ?>
        <div class="card">
            <div class="card-header">
                <h3>Pending approvals &mdash; from admin queue</h3>
                <small class="text-muted">Review and approve or return to admin</small>
            </div>
            <?php if (empty($pendingApprovals)): ?>
            <p class="text-muted text-center" style="padding:2rem 0;">No requests pending leadership approval.</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Event / requestor</th><th>Date</th><th>Type</th><th>Participants</th><th>Status</th><th class="no-print">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($pendingApprovals as $req):
                        $tl = $typeLabel[$req['request_type']] ?? $req['request_type'];
                        $sb = $statusBadge[$req['status']] ?? ['label' => ucfirst($req['status']), 'class' => 'badge-submitted'];
                        $tbMap = ['mailing'=>'badge-mailing','presentation'=>'badge-presentation','inperson_support'=>'badge-inperson'];
                        $tb = $tbMap[$req['request_type']] ?? 'badge-submitted';
                    ?>
                    <tr>
                        <td><div class="td-name"><?= htmlspecialchars($req['event_name']) ?></div><div class="td-muted"><?= htmlspecialchars($req['requestor_name']) ?> &middot; <?= htmlspecialchars($req['organization']) ?></div></td>
                        <td class="td-muted"><?= htmlspecialchars(date('M j, Y', strtotime($req['event_date']))) ?></td>
                        <td><span class="badge <?= $tb ?>"><?= htmlspecialchars($tl) ?></span></td>
                        <td class="td-muted"><?= (int)$req['estimated_attendees'] ?></td>
                        <td><span class="badge <?= $sb['class'] ?>"><?= $sb['label'] ?></span></td>
                        <td class="no-print">
                            <form method="POST" style="display:inline; margin-right:var(--space-xs);">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button type="submit" name="leader_action" value="approve" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button type="submit" name="leader_action" value="return" class="btn btn-danger btn-sm">Return to admin</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</main>

<style>
@media print {
    .no-print, .navbar, footer, .tabs, form select, .btn-success { display: none !important; }
    .card { break-inside: avoid; }
}
</style>

<script>
var pieChart, lineChart, barChart;
var pieData = {
    language: { labels: ['English','Spanish','Portuguese','Chinese','Other'], data: [52,31,7,5,5],    colors: ['#4A00E2','#1D9E75','#FF5D55','#7CAFD0','#FFAA45'] },
    age:      { labels: ['Under 18','18-34','35-54','55-64','65+'],           data: [38,22,18,12,10], colors: ['#4A00E2','#1D9E75','#FF5D55','#FFAA45','#7CAFD0'] },
    gender:   { labels: ['Female','Male','Non-binary','Not specified'],        data: [54,33,6,7],     colors: ['#FF5D55','#4A00E2','#7CAFD0','#888780'] }
};
var months = ['Jan','Feb','Mar','Apr','May','Jun'];

window.addEventListener('load', function() {
    <?php if ($view === 'all' || $view === 'demographics'): ?>
    var pieCtx = document.getElementById('pieChart');
    if (pieCtx) {
        pieChart = new Chart(pieCtx.getContext('2d'), {
            type: 'doughnut',
            data: { labels: pieData.language.labels, datasets: [{ data: pieData.language.data, backgroundColor: pieData.language.colors, borderWidth: 2, borderColor: '#fff' }] },
            options: { responsive: true, plugins: { legend: { position: 'right' } } }
        });
    }
    <?php endif; ?>
    <?php if ($view === 'all' || $view === 'trends'): ?>
    var lineCtx = document.getElementById('lineChart');
    if (lineCtx) {
        lineChart = new Chart(lineCtx.getContext('2d'), {
            type: 'line',
            data: { labels: months, datasets: [
                { label: 'Inventory (units)',      data: [420,390,350,280,240,190], borderColor: '#4A00E2', backgroundColor: 'rgba(74,0,226,0.06)',   tension: 0.4, fill: true,  pointRadius: 4 },
                { label: 'Geographic reach (zips)',data: [8,10,11,13,15,18],        borderColor: '#00857C', backgroundColor: 'rgba(0,133,124,0.06)',   tension: 0.4, fill: true,  pointRadius: 4 },
                { label: 'Staff days',             data: [4,6,5,8,9,11],            borderColor: '#FFAA45',                                            tension: 0.4, fill: false, borderDash: [4,3], pointRadius: 4 }
            ]},
            options: { responsive: true, plugins: { legend: { labels: { font: { size: 11 } } } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#f0f0f0' } } } }
        });
    }
    <?php endif; ?>
    <?php if ($view === 'all' || $view === 'staffing'): ?>
    var barCtx = document.getElementById('barChart');
    if (barCtx) {
        barChart = new Chart(barCtx.getContext('2d'), {
            type: 'bar',
            data: { labels: months, datasets: [
                { label: 'Staff available (days)', data: [12,14,12,15,13,16], backgroundColor: 'rgba(0,133,124,0.7)',   borderRadius: 4 },
                { label: 'Staff demand (days)',     data: [4,6,5,8,9,11],     backgroundColor: 'rgba(255,170,69,0.7)', borderRadius: 4 }
            ]},
            options: { responsive: true, plugins: { legend: { labels: { font: { size: 11 } } } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#f0f0f0' } } } }
        });
    }
    <?php endif; ?>
});

function setPie(type) {
    if (!pieChart) return;
    pieChart.data.labels                    = pieData[type].labels;
    pieChart.data.datasets[0].data          = pieData[type].data;
    pieChart.data.datasets[0].backgroundColor = pieData[type].colors;
    pieChart.update();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
