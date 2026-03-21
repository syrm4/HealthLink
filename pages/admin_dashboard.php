<?php
// HealthLink — Admin Dashboard (Sarah persona)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('admin', 'leader');

$user   = current_user();
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

// Build query
$where  = [];
$params = [];

if ($filter === 'flagged') {
    $where[] = "ai_flags IS NOT NULL AND ai_flags != ''";
} elseif (in_array($filter, ['submitted','in_review','approved','sent_to_qualtrics','fulfilled'])) {
    $where[]  = 'status = ?';
    $params[] = $filter;
}

if ($search !== '') {
    $where[]  = '(event_name LIKE ? OR requestor_name LIKE ? OR organization LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}

$sql = 'SELECT r.*, u.full_name AS user_full_name
        FROM requests r LEFT JOIN users u ON r.user_id = u.id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY ai_priority_score DESC, created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Metrics
$totalCount    = $pdo->query('SELECT COUNT(*) FROM requests')->fetchColumn();
$pendingCount  = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'submitted' OR status = 'in_review'")->fetchColumn();
$flaggedCount  = $pdo->query("SELECT COUNT(*) FROM requests WHERE ai_flags IS NOT NULL AND ai_flags != ''")->fetchColumn();
$outsideCount  = $pdo->query('SELECT COUNT(*) FROM requests WHERE in_service_area = 0')->fetchColumn();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $rid    = (int)$_POST['request_id'];
    $status = $_POST['new_status'] ?? '';
    $note   = trim($_POST['admin_note'] ?? '');
    $allowed = ['in_review','approved','sent_to_qualtrics','fulfilled'];
    if ($rid && in_array($status, $allowed)) {
        $stmt = $pdo->prepare('UPDATE requests SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $note, $rid]);
        $stmt2 = $pdo->prepare('INSERT INTO status_history (request_id, status, changed_by, notes) VALUES (?, ?, ?, ?)');
        $stmt2->execute([$rid, $status, $user['name'], $note]);
        header('Location: /pages/admin_dashboard.php?filter=' . urlencode($filter) . '&id=' . $rid);
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
$typeBadge = [
    'mailing'          => 'badge-mailing',
    'presentation'     => 'badge-presentation',
    'inperson_support' => 'badge-inperson',
];

function priorityClass(int $score): string {
    if ($score >= 9) return 'priority-urgent';
    if ($score >= 7) return 'priority-high';
    if ($score >= 5) return 'priority-medium';
    return 'priority-low';
}

// Selected request
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected   = null;
$history    = [];
if ($selectedId) {
    $s = $pdo->prepare('SELECT * FROM requests WHERE id = ?');
    $s->execute([$selectedId]);
    $selected = $s->fetch();
    $h = $pdo->prepare('SELECT * FROM status_history WHERE request_id = ? ORDER BY changed_at ASC');
    $h->execute([$selectedId]);
    $history = $h->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Manage and approve all incoming community health requests</p>
    </div>
</div>

<main class="main-content" style="padding-top:0;">
    <div class="container" style="padding-top:var(--space-xl);">

        <!-- Metric cards -->
        <div class="metric-grid">
            <div class="metric-card">
                <p class="metric-label">Total requests</p>
                <p class="metric-value"><?= (int)$totalCount ?></p>
                <p class="metric-sub">All time</p>
            </div>
            <div class="metric-card metric-warning">
                <p class="metric-label">Pending action</p>
                <p class="metric-value"><?= (int)$pendingCount ?></p>
                <p class="metric-sub">Submitted or in review</p>
            </div>
            <div class="metric-card metric-danger">
                <p class="metric-label">AI flags</p>
                <p class="metric-value"><?= (int)$flaggedCount ?></p>
                <p class="metric-sub">Require attention</p>
            </div>
            <div class="metric-card">
                <p class="metric-label">Outside service area</p>
                <p class="metric-value"><?= (int)$outsideCount ?></p>
                <p class="metric-sub">Auto-routed to mail</p>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="filter-bar">
            <?php
            $filters = ['all' => 'All', 'submitted' => 'Submitted', 'in_review' => 'In review',
                        'approved' => 'Approved', 'fulfilled' => 'Fulfilled', 'flagged' => 'Flagged'];
            foreach ($filters as $key => $label): ?>
            <a href="?filter=<?= urlencode($key) ?>" class="filter-pill <?= $filter === $key ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <div class="search-box">
                <form method="GET">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search events, orgs...">
                </form>
            </div>
        </div>

        <!-- Queue table -->
        <div class="card" style="padding:0; margin-bottom:var(--space-lg);">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Event / requestor</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Date</th>
                            <th>AI flag</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="7" class="text-center text-muted" style="padding:2rem;">No requests found.</td></tr>
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
                            <td class="td-muted"><?= htmlspecialchars(date('M j, Y', strtotime($req['event_date']))) ?></td>
                            <td>
                                <?php if (!empty($req['ai_flags'])): ?>
                                <span class="ai-flag"><?= htmlspecialchars(substr($req['ai_flags'], 0, 28)) ?></span>
                                <?php else: ?>
                                <span class="td-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <a href="?filter=<?= urlencode($filter) ?>&id=<?= $req['id'] ?>" class="btn btn-secondary btn-sm">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detail panel -->
        <?php if ($selected): ?>
        <div class="card" id="detail-panel">
            <div class="card-header">
                <h3><?= htmlspecialchars($selected['event_name']) ?></h3>
                <a href="?filter=<?= urlencode($filter) ?>" class="btn btn-secondary btn-sm">Close</a>
            </div>

            <?php if (!empty($selected['ai_flags'])): ?>
            <div class="alert alert-danger mb-md">
                <strong>AI flag:</strong> <?= htmlspecialchars($selected['ai_flags']) ?>
                <?php if (!empty($selected['ai_classification'])): ?>
                <br><strong>Classification:</strong> <?= htmlspecialchars($selected['ai_classification']) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- AI panel -->
            <div class="ai-panel mb-md">
                <p class="ai-panel-title">AI assessment</p>
                <div class="ai-row"><span class="ai-label">Classification</span><span class="ai-value"><?= htmlspecialchars($selected['ai_classification'] ?? 'Pending') ?></span></div>
                <div class="ai-row"><span class="ai-label">Routing recommendation</span><span class="ai-value"><?= htmlspecialchars($selected['ai_routing_recommendation'] ?? 'Pending') ?></span></div>
                <div class="ai-row"><span class="ai-label">Priority score</span><span class="ai-value"><?= (int)($selected['ai_priority_score'] ?? 0) ?> / 10</span></div>
                <div class="ai-row"><span class="ai-label">Service area</span><span class="ai-value"><?= $selected['in_service_area'] ? 'Yes &mdash; eligible for staff deployment' : 'No &mdash; outside service area' ?></span></div>
            </div>

            <!-- Details grid -->
            <div class="form-grid mb-md">
                <div><label>Requestor</label><p><?= htmlspecialchars($selected['requestor_name']) ?></p></div>
                <div><label>Organization</label><p><?= htmlspecialchars($selected['organization']) ?></p></div>
                <div><label>Email</label><p><?= htmlspecialchars($selected['requestor_email']) ?></p></div>
                <div><label>Phone</label><p><?= htmlspecialchars($selected['requestor_phone'] ?? '&mdash;') ?></p></div>
                <div><label>Event date</label><p><?= htmlspecialchars(date('F j, Y', strtotime($selected['event_date']))) ?></p></div>
                <div><label>Location</label><p><?= htmlspecialchars($selected['city'] . ', ' . $selected['zip_code']) ?></p></div>
                <div><label>Participants</label><p><?= (int)$selected['estimated_attendees'] ?></p></div>
                <div><label>Material category</label><p><?= htmlspecialchars($selected['material_category'] ?? '&mdash;') ?></p></div>
                <?php if (!empty($selected['notes'])): ?>
                <div class="form-full"><label>Notes</label><p><?= htmlspecialchars($selected['notes']) ?></p></div>
                <?php endif; ?>
            </div>

            <!-- Status update form -->
            <form method="POST" action="?filter=<?= urlencode($filter) ?>&id=<?= $selectedId ?>">
                <input type="hidden" name="request_id" value="<?= $selectedId ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Update status</label>
                        <select name="new_status">
                            <option value="in_review">In review</option>
                            <option value="approved">Approved</option>
                            <option value="sent_to_qualtrics">Send to Qualtrics</option>
                            <option value="fulfilled">Fulfilled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Admin note (optional)</label>
                        <input type="text" name="admin_note" placeholder="Add a note..." value="<?= htmlspecialchars($selected['admin_notes'] ?? '') ?>">
                    </div>
                </div>
                <div class="d-flex gap-sm">
                    <button type="submit" name="update_status" class="btn btn-primary">Update status</button>
                    <a href="?filter=<?= urlencode($filter) ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

            <!-- Status history -->
            <?php if (!empty($history)): ?>
            <hr class="divider">
            <h4>Status history</h4>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Status</th><th>Changed by</th><th>Date</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><span class="badge <?= $statusBadge[$h['status']]['class'] ?? 'badge-submitted' ?>"><?= $statusBadge[$h['status']]['label'] ?? ucfirst($h['status']) ?></span></td>
                            <td class="td-muted"><?= htmlspecialchars($h['changed_by']) ?></td>
                            <td class="td-muted"><?= htmlspecialchars(date('M j, Y g:ia', strtotime($h['changed_at']))) ?></td>
                            <td class="td-muted"><?= htmlspecialchars($h['notes'] ?? '&mdash;') ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
