<?php
// HealthLink — Staff Portal (James persona)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('staff', 'admin', 'leader');

$user = current_user();

// Fetch all requests (staff see all)
$filter    = $_GET['filter'] ?? 'all';
$search    = trim($_GET['q'] ?? '');
$tab       = $_GET['tab'] ?? 'queue';

// Build query with optional filters
$where  = [];
$params = [];

if ($filter !== 'all' && $filter === 'flagged') {
    $where[]  = "ai_flags IS NOT NULL AND ai_flags != ''";
} elseif (in_array($filter, ['submitted','in_review','approved','fulfilled'])) {
    $where[]  = 'status = ?';
    $params[] = $filter;
}

if ($search !== '') {
    $where[]  = '(event_name LIKE ? OR requestor_name LIKE ? OR organization LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}

$sql = 'SELECT r.*, u.full_name AS user_full_name
        FROM requests r
        LEFT JOIN users u ON r.user_id = u.id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ai_priority_score DESC, created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Notification counts
$newCount = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'submitted'")->fetchColumn();
$flagCount = $pdo->query("SELECT COUNT(*) FROM requests WHERE ai_flags IS NOT NULL AND ai_flags != ''")->fetchColumn();

// Priority helper
function priorityClass(int $score): string {
    if ($score >= 9) return 'priority-urgent';
    if ($score >= 7) return 'priority-high';
    if ($score >= 5) return 'priority-medium';
    return 'priority-low';
}

$statusBadge = [
    'submitted'         => ['label' => 'Submitted',   'class' => 'badge-submitted'],
    'in_review'         => ['label' => 'In review',   'class' => 'badge-in-review'],
    'approved'          => ['label' => 'Approved',    'class' => 'badge-approved'],
    'sent_to_qualtrics' => ['label' => 'To Qualtrics','class' => 'badge-qualtrics'],
    'fulfilled'         => ['label' => 'Fulfilled',   'class' => 'badge-fulfilled'],
];

$typeLabel = [
    'mailing'          => 'Mailing',
    'presentation'     => 'Presentation',
    'inperson_support' => 'In-person',
];

$typeBadge = [
    'mailing'          => 'badge-mailing',
    'presentation'     => 'badge-presentation',
    'inperson_support' => 'badge-inperson',
];

// Selected request for detail panel
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected   = null;
if ($selectedId) {
    $stmt = $pdo->prepare('SELECT * FROM requests WHERE id = ?');
    $stmt->execute([$selectedId]);
    $selected = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="d-flex align-center justify-between">
            <div>
                <h1>Staff Request Queue</h1>
                <p>Review, triage, and forward community health requests</p>
            </div>
            <a href="?tab=new" class="btn btn-primary">+ New request</a>
        </div>
    </div>
</div>

<main class="main-content" style="padding-top:0;">
    <div class="container" style="display:grid; grid-template-columns:220px 1fr; gap:var(--space-lg); padding-top:var(--space-xl);">

        <!-- Sidebar -->
        <aside>
            <div class="metric-card metric-cobalt mb-md">
                <p class="metric-label">New requests</p>
                <p class="metric-value"><?= (int)$newCount ?></p>
                <p class="metric-sub">Awaiting review</p>
            </div>
            <div class="metric-card metric-danger mb-md">
                <p class="metric-label">Flagged</p>
                <p class="metric-value"><?= (int)$flagCount ?></p>
                <p class="metric-sub">Require attention</p>
            </div>

            <hr class="divider">
            <nav>
                <a href="?filter=all" class="btn btn-block btn-<?= $filter === 'all' ? 'dark' : 'secondary' ?> btn-sm mb-sm">All requests</a>
                <a href="?filter=flagged" class="btn btn-block btn-<?= $filter === 'flagged' ? 'dark' : 'secondary' ?> btn-sm mb-sm">Flagged</a>
                <a href="?filter=submitted" class="btn btn-block btn-<?= $filter === 'submitted' ? 'dark' : 'secondary' ?> btn-sm mb-sm">Submitted</a>
                <a href="?filter=in_review" class="btn btn-block btn-<?= $filter === 'in_review' ? 'dark' : 'secondary' ?> btn-sm mb-sm">In review</a>
                <a href="?filter=approved" class="btn btn-block btn-<?= $filter === 'approved' ? 'dark' : 'secondary' ?> btn-sm mb-sm">Approved</a>
                <a href="?filter=fulfilled" class="btn btn-block btn-<?= $filter === 'fulfilled' ? 'dark' : 'secondary' ?> btn-sm">Fulfilled</a>
            </nav>
        </aside>

        <!-- Main content -->
        <div>
            <!-- Filter bar + search -->
            <div class="filter-bar">
                <span class="text-muted text-small"><?= count($requests) ?> request<?= count($requests) !== 1 ? 's' : '' ?></span>
                <div class="search-box">
                    <form method="GET" action="">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search events, contacts...">
                    </form>
                </div>
            </div>

            <!-- Queue table -->
            <div class="card" style="padding:0;">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Event / contact</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>AI flag</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">No requests found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                            <?php
                            $sb  = $statusBadge[$req['status']] ?? ['label' => ucfirst($req['status']), 'class' => 'badge-submitted'];
                            $tl  = $typeLabel[$req['request_type']] ?? $req['request_type'];
                            $tb  = $typeBadge[$req['request_type']] ?? 'badge-submitted';
                            $pc  = priorityClass((int)($req['ai_priority_score'] ?? 0));
                            $pct = min(100, (int)($req['ai_priority_score'] ?? 0) * 10);
                            $isSelected = ($selectedId === (int)$req['id']);
                            ?>
                            <tr class="<?= $isSelected ? 'selected' : '' ?>" onclick="location.href='?filter=<?= urlencode($filter) ?>&id=<?= $req['id'] ?>'">
                                <td>
                                    <div class="td-name"><?= htmlspecialchars($req['event_name']) ?></div>
                                    <div class="td-muted"><?= htmlspecialchars($req['requestor_name']) ?> &middot; <?= htmlspecialchars($req['organization']) ?></div>
                                </td>
                                <td><span class="badge <?= $tb ?>"><?= htmlspecialchars($tl) ?></span></td>
                                <td><span class="badge <?= $sb['class'] ?>"><?= $sb['label'] ?></span></td>
                                <td>
                                    <div class="priority-bar <?= $pc ?>">
                                        <div class="priority-track"><div class="priority-fill" style="width:<?= $pct ?>%;"></div></div>
                                        <small><?= (int)($req['ai_priority_score'] ?? 0) ?></small>
                                    </div>
                                </td>
                                <td class="td-muted"><?= htmlspecialchars(date('M j', strtotime($req['event_date']))) ?></td>
                                <td class="td-muted"><?= htmlspecialchars(substr($req['material_category'] ?? '', 0, 18)) ?></td>
                                <td>
                                    <?php if (!empty($req['ai_flags'])): ?>
                                    <span class="ai-flag"><?= htmlspecialchars(substr($req['ai_flags'], 0, 22)) ?></span>
                                    <?php else: ?>
                                    <span class="td-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/pages/admin_dashboard.php?id=<?= $req['id'] ?>" class="btn btn-secondary btn-sm" onclick="event.stopPropagation();">Send to admin</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detail / AI summary panel -->
            <?php if ($selected): ?>
            <div class="card mt-md" id="detail-panel">
                <div class="card-header">
                    <h3><?= htmlspecialchars($selected['event_name']) ?></h3>
                    <a href="?filter=<?= urlencode($filter) ?>" class="btn btn-secondary btn-sm">Close</a>
                </div>

                <!-- AI flag explanation -->
                <?php if (!empty($selected['ai_flags'])): ?>
                <div class="alert alert-danger">
                    <strong>AI flag:</strong> <?= htmlspecialchars($selected['ai_flags']) ?>
                </div>
                <?php endif; ?>

                <!-- AI Summary Panel -->
                <div class="ai-panel mb-md">
                    <p class="ai-panel-title">AI summary</p>
                    <?php
                    $att   = (int)($selected['estimated_attendees'] ?? 0);
                    $stype = $selected['request_type'] ?? '';
                    $staff = $att >= 200 ? '4–5 staff recommended' : ($att >= 100 ? '2–3 staff recommended' : ($stype === 'mailing' ? 'No staff — mailing pathway' : '1–2 staff recommended'));
                    $mats  = $att > 0 ? '~' . $att . ' material packets' : 'Quantity TBD';
                    if (!empty($selected['material_category'])) {
                        $mats .= ' — ' . $selected['material_category'];
                    }
                    $room  = $stype === 'mailing' ? 'N/A — mailing only' : ($att >= 200 ? 'Large venue / multi-room setup likely' : 'Standard event space');
                    $setup = $stype === 'mailing' ? 'Packing + shipping est. 1–2 business days' : ($att >= 200 ? '45+ min setup' : '20–30 min setup');
                    ?>
                    <div class="ai-row"><span class="ai-label">Staff prediction</span><span class="ai-value"><?= htmlspecialchars($staff) ?></span></div>
                    <div class="ai-row"><span class="ai-label">Materials required</span><span class="ai-value"><?= htmlspecialchars($mats) ?></span></div>
                    <div class="ai-row"><span class="ai-label">Room / space needed</span><span class="ai-value"><?= htmlspecialchars($room) ?></span></div>
                    <div class="ai-row"><span class="ai-label">Estimated setup time</span><span class="ai-value"><?= htmlspecialchars($setup) ?></span></div>
                    <div class="ai-row"><span class="ai-label">Routing recommendation</span><span class="ai-value"><?= htmlspecialchars($selected['ai_routing_recommendation'] ?? 'Pending classification') ?></span></div>
                </div>

                <!-- Request details grid -->
                <div class="form-grid">
                    <div>
                        <label>Contact</label>
                        <p><?= htmlspecialchars($selected['requestor_name']) ?></p>
                    </div>
                    <div>
                        <label>Organization</label>
                        <p><?= htmlspecialchars($selected['organization']) ?></p>
                    </div>
                    <div>
                        <label>Event date</label>
                        <p><?= htmlspecialchars(date('F j, Y', strtotime($selected['event_date']))) ?></p>
                    </div>
                    <div>
                        <label>Location</label>
                        <p><?= htmlspecialchars($selected['city'] . ', ' . $selected['zip_code']) ?></p>
                    </div>
                    <div>
                        <label>Participants</label>
                        <p><?= (int)$selected['estimated_attendees'] ?></p>
                    </div>
                    <div>
                        <label>Items requested</label>
                        <p><?= htmlspecialchars($selected['material_category'] ?? '&mdash;') ?></p>
                    </div>
                    <?php if (!empty($selected['notes'])): ?>
                    <div class="form-full">
                        <label>Notes</label>
                        <p><?= htmlspecialchars($selected['notes']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-sm" style="margin-top:var(--space-md);">
                    <a href="/pages/admin_dashboard.php?id=<?= $selected['id'] ?>" class="btn btn-primary">Send to admin (Sarah)</a>
                    <a href="/api/update_status.php?id=<?= $selected['id'] ?>&status=in_review&redirect=<?= urlencode('/pages/staff_portal.php?filter=' . $filter) ?>" class="btn btn-secondary">Mark in review</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
