<?php
// HealthLink — Executive/Leader Dashboard (Dr. Chen persona)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('leader', 'admin');

$user = current_user();

// Timeframe filter (days)
$tf    = in_array((int)($_GET['tf'] ?? 90), [30,60,90,180,365]) ? (int)$_GET['tf'] : 90;
$since = date('Y-m-d', strtotime('-' . $tf . ' days'));

// View section filter
$view = $_GET['view'] ?? 'all';
$allowedViews = ['all','demographics','trends','staffing','ai','approvals'];
if (!in_array($view, $allowedViews)) $view = 'all';

// Active tab (dashboard | approvals)
$tab = $_GET['tab'] ?? 'dashboard';

// ---- Metrics ----
$totalReqs   = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE created_at >= ?');
$totalReqs->execute([$since]);
$totalCount  = (int)$totalReqs->fetchColumn();

$approvedReqs = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status IN ('approved','sent_to_qualtrics','fulfilled') AND created_at >= ?");
$approvedReqs->execute([$since]);
$approvedCount = (int)$approvedReqs->fetchColumn();
$approvalRate  = $totalCount > 0 ? round(($approvedCount / $totalCount) * 100) : 0;

$staffDays = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE request_type = 'inperson_support' AND status IN ('approved','fulfilled') AND created_at >= ?");
$staffDays->execute([$since]);
$staffCount = (int)$staffDays->fetchColumn();

// ---- Pending approvals (requests awaiting leader approval) ----
$pendingStmt = $pdo->prepare(
    "SELECT r.*, u.full_name AS user_full_name
     FROM requests r LEFT JOIN users u ON r.user_id = u.id
     WHERE r.status = 'approved'
     ORDER BY r.ai_priority_score DESC LIMIT 20"
);
$pendingStmt->execute();
$pendingApprovals = $pendingStmt->fetchAll();

// Handle leader approve/return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leader_action'])) {
    $rid    = (int)$_POST['request_id'];
    $action = $_POST['leader_action'];
    if ($rid && in_array($action, ['approve','return'])) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE requests SET status = 'sent_to_qualtrics', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$rid]);
            $stmt2 = $pdo->prepare('INSERT INTO status_history (request_id, status, changed_by, notes) VALUES (?, ?, ?, ?)');
            $stmt2->execute([$rid, 'sent_to_qualtrics', $user['name'], 'Approved by leadership']);
        } else {
            $stmt = $pdo->prepare("UPDATE requests SET status = 'in_review', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$rid]);
            $stmt2 = $pdo->prepare('INSERT INTO status_history (request_id, status, changed_by, notes) VALUES (?, ?, ?, ?)');
            $stmt2->execute([$rid, 'in_review', $user['name'], 'Returned to admin by leadership']);
        }
        header('Location: /pages/leader_dashboard.php?tab=approvals&tf=' . $tf);
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
$typeLabel = [
    'mailing'          => 'Mailing',
    'presentation'     => 'Presentation',
    'inperson_support' => 'In-person support',
];

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="page-header">
    <div class="container">
        <div class="d-flex align-center justify-between" style="flex-wrap:wrap; gap:var(--space-md);">
            <div>
                <h1>Executive Dashboard</h1>
                <p>Strategic overview of HealthLink community health requests</p>
            </div>
            <div class="d-flex gap-sm" style="flex-wrap:wrap; align-items:center;">
                <form method="GET" style="display:flex; gap:var(--space-sm); flex-wrap:wrap;">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    <select name="tf" onchange="this.form.submit()">
                        <option value="30" <?= $tf===30?'selected':'' ?>>Last 30 days</option>
                        <option value="60" <?= $tf===60?'selected':'' ?>>Last 60 days</option>
                        <option value="90" <?= $tf===90?'selected':'' ?>>Last 90 days</option>
                        <option value="180" <?= $tf===180?'selected':'' ?>>Last 6 months</option>
                        <option value="365" <?= $tf===365?'selected':'' ?>>Last 12 months</option>
                    </select>
                    <select name="view" onchange="this.form.submit()">
                        <option value="all"          <?= $view==='all'?'selected':'' ?>>All sections</option>
                        <option value="demographics" <?= $view==='demographics'?'selected':'' ?>>Demographics</option>
                        <option value="trends"       <?= $view==='trends'?'selected':'' ?>>Predictive trends</option>
                        <option value="staffing"     <?= $view==='staffing'?'selected':'' ?>>Staff &amp; demand</option>
                        <option value="ai"           <?= $view==='ai'?'selected':'' ?>>AI summary</option>
                        <option value="approvals"    <?= $view==='approvals'?'selected':'' ?>>Approvals</option>
                    </select>
                </form>
                <button onclick="window.print()" class="btn btn-success">Print / PDF summary</button>
            </div>
        </div>
    </div>
</div>

<main class="main-content" style="padding-top:0;">
    <div class="container" style="padding-top:var(--space-xl);">

        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=dashboard&tf=<?= $tf ?>&view=<?= htmlspecialchars($view) ?>" class="tab-btn <?= $tab==='dashboard'?'active':'' ?>">Overview</a>
            <a href="?tab=approvals&tf=<?= $tf ?>" class="tab-btn <?= $tab==='approvals'?'active':'' ?>">Pending approvals <span class="badge badge-in-review" style="margin-left:4px;"><?= count($pendingApprovals) ?></span></a>
        </div>

        <!-- DASHBOARD TAB -->
        <?php if ($tab === 'dashboard'): ?>

        <!-- Metric cards — always visible -->
        <div class="metric-grid">
            <div class="metric-card">
                <p class="metric-label">Total requests</p>
                <p class="metric-value"><?= $totalCount ?></p>
                <p class="metric-sub">Last <?= $tf ?> days</p>
            </div>
            <div class="metric-card metric-success">
                <p class="metric-label">Approval rate</p>
                <p class="metric-value"><?= $approvalRate ?>%</p>
                <p class="metric-sub">Approved or fulfilled</p>
            </div>
            <div class="metric-card">
                <p class="metric-label">Avg. fulfillment</p>
                <p class="metric-value">3.2d</p>
                <p class="metric-sub">Days to fulfill</p>
            </div>
            <div class="metric-card metric-cobalt">
                <p class="metric-label">Staff deployments</p>
                <p class="metric-value"><?= $staffCount ?></p>
                <p class="metric-sub">Approved in-person</p>
            </div>
        </div>

        <!-- SECTION: Demographics -->
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
            <div style="max-width:500px; margin:0 auto;">
                <canvas id="pieChart" height="260"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- SECTION: Predictive trends -->
        <?php if ($view === 'all' || $view === 'trends'): ?>
        <div class="card">
            <div class="card-header"><h3>Predictive trends</h3></div>
            <p class="text-muted text-small">Projected inventory, geographic demand, and staff deployment days over 6 months</p>
            <canvas id="lineChart" height="120"></canvas>
        </div>
        <?php endif; ?>

        <!-- SECTION: Staff & demand -->
        <?php if ($view === 'all' || $view === 'staffing'): ?>
        <div class="card">
            <div class="card-header"><h3>Staff capacity vs demand</h3></div>
            <p class="text-muted text-small">Monthly comparison of available staff days versus event demand</p>
            <canvas id="barChart" height="100"></canvas>
        </div>
        <?php endif; ?>

        <!-- SECTION: AI Summary -->
        <?php if ($view === 'all' || $view === 'ai'): ?>
        <div class="card">
            <div class="card-header">
                <h3>AI executive summary</h3>
                <small class="text-muted">Based on approved requests &mdash; last <?= $tf ?> days</small>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:var(--space-md); margin-bottom:var(--space-md);">
                <div class="alert alert-success">
                    <strong>Staffing</strong><br>
                    Current staff levels are sufficient for the next 30 days. <?= $staffCount ?> deployment<?= $staffCount !== 1 ? 's' : '' ?> approved. No shortfall detected.
                </div>
                <div class="alert alert-warning">
                    <strong>Inventory</strong><br>
                    Educational material stock is projected to run low based on current request volume. Recommend reorder of 400+ units within 2 weeks.
                </div>
                <div class="alert alert-danger">
                    <strong>Geographic gap</strong><br>
                    Requests from outside the service area are increasing. Mailing fulfillment is handling demand &mdash; consider expanding the service boundary.
                </div>
                <div class="alert alert-success">
                    <strong>Fulfillment channel</strong><br>
                    Mailing is 2.4&times; more cost-effective than in-person support for events under 75 participants. 61% of requests qualify.
                </div>
                <div class="alert alert-warning">
                    <strong>Language access</strong><br>
                    31% of requests indicate Spanish preference, but only 18% of fulfillments include bilingual materials. A gap exists.
                </div>
                <div class="alert alert-success">
                    <strong>Demand trend</strong><br>
                    Request volume is up this quarter. Schools and youth programs are the fastest-growing segment at 38% of new submissions.
                </div>
            </div>

            <div class="alert alert-ai">
                <div class="ai-indicator"></div>
                <div>
                    <strong>Summary (last <?= $tf ?> days):</strong>
                    HealthLink processed <?= $totalCount ?> community health requests with a <?= $approvalRate ?>% approval rate.
                    Mailing fulfillment is the most efficient channel and should be prioritized for events under 75 attendees.
                    Staff capacity is stable, but inventory replenishment for educational materials is time-sensitive.
                    The most actionable item is addressing the bilingual materials gap &mdash; 31% of requestors indicate Spanish preference but are not receiving bilingual kits.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; /* end dashboard tab */ ?>

        <!-- APPROVALS TAB -->
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
                    <thead>
                        <tr>
                            <th>Event / requestor</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Participants</th>
                            <th>Status</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pendingApprovals as $req): ?>
                        <?php
                        $tl = $typeLabel[$req['request_type']] ?? $req['request_type'];
                        $sb = $statusBadge[$req['status']] ?? ['label' => ucfirst($req['status']), 'class' => 'badge-submitted'];
                        ?>
                        <tr>
                            <td>
                                <div class="td-name"><?= htmlspecialchars($req['event_name']) ?></div>
                                <div class="td-muted"><?= htmlspecialchars($req['requestor_name']) ?> &middot; <?= htmlspecialchars($req['organization']) ?></div>
                            </td>
                            <td class="td-muted"><?= htmlspecialchars(date('M j, Y', strtotime($req['event_date']))) ?></td>
                            <td><span class="badge badge-<?= $req['request_type'] === 'mailing' ? 'mailing' : ($req['request_type'] === 'presentation' ? 'presentation' : 'inperson') ?>"><?= htmlspecialchars($tl) ?></span></td>
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
    .card { break-inside: avoid; border: 1px solid #ccc; }
}
</style>

<script>
var pieChart, lineChart, barChart;
var pieDatasets = {
    language: { labels: ['English','Spanish','Portuguese','Chinese','Other'], data: [52,31,7,5,5], colors: ['#4A00E2','#1D9E75','#FF5D55','#7CAFD0','#FFAA45'] },
    age:      { labels: ['Under 18','18-34','35-54','55-64','65+'],             data: [38,22,18,12,10], colors: ['#4A00E2','#1D9E75','#FF5D55','#FFAA45','#7CAFD0'] },
    gender:   { labels: ['Female','Male','Non-binary','Not specified'],          data: [54,33,6,7],    colors: ['#FF5D55','#4A00E2','#7CAFD0','#888780'] }
};

window.addEventListener('load', function() {
    var months = ['Jan','Feb','Mar','Apr','May','Jun'];

    <?php if ($view === 'all' || $view === 'demographics'): ?>
    var pieCtx = document.getElementById('pieChart');
    if (pieCtx) {
        pieChart = new Chart(pieCtx.getContext('2d'), {
            type: 'doughnut',
            data: { labels: pieDatasets.language.labels, datasets: [{ data: pieDatasets.language.data, backgroundColor: pieDatasets.language.colors, borderWidth: 2, borderColor: '#fff' }] },
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
                { label: 'Inventory (units)', data: [420,390,350,280,240,190], borderColor: '#4A00E2', backgroundColor: 'rgba(74,0,226,0.06)', tension: 0.4, fill: true, pointRadius: 4 },
                { label: 'Geographic reach (zips)', data: [8,10,11,13,15,18], borderColor: '#00857C', backgroundColor: 'rgba(0,133,124,0.06)', tension: 0.4, fill: true, pointRadius: 4 },
                { label: 'Staff days', data: [4,6,5,8,9,11], borderColor: '#FFAA45', tension: 0.4, fill: false, borderDash: [4,3], pointRadius: 4 }
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
                { label: 'Staff available (days)', data: [12,14,12,15,13,16], backgroundColor: 'rgba(0,133,124,0.7)', borderRadius: 4 },
                { label: 'Staff demand (days)',     data: [4,6,5,8,9,11],     backgroundColor: 'rgba(255,170,69,0.7)', borderRadius: 4 }
            ]},
            options: { responsive: true, plugins: { legend: { labels: { font: { size: 11 } } } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#f0f0f0' } } } }
        });
    }
    <?php endif; ?>
});

function setPie(type) {
    if (!pieChart) return;
    var d = pieDatasets[type];
    pieChart.data.labels = d.labels;
    pieChart.data.datasets[0].data = d.data;
    pieChart.data.datasets[0].backgroundColor = d.colors;
    pieChart.update();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
