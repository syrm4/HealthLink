<?php
// HealthLink — Staff Portal (James persona)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_role('staff', 'admin', 'leader');

$user   = current_user();
$db     = getDB();
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
if ($filter === 'flagged') {
    $where[] = "ai_flags IS NOT NULL AND ai_flags != ''";
} elseif (in_array($filter, ['submitted','in_review','approved','fulfilled'], true)) {
    $where[]  = 'status = ?';
    $params[] = $filter;
}
if ($search !== '') {
    $where[]  = '(event_name LIKE ? OR requestor_name LIKE ? OR organization LIKE ?)';
    $like     = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}

$sql = 'SELECT r.*, u.full_name AS user_full_name FROM requests r LEFT JOIN users u ON r.user_id = u.id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY ai_priority_score DESC, created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$newCount  = $db->query("SELECT COUNT(*) FROM requests WHERE status = 'submitted'")->fetchColumn();
$flagCount = $db->query("SELECT COUNT(*) FROM requests WHERE ai_flags IS NOT NULL AND ai_flags != ''")->fetchColumn();

function priorityClass(int $score): string {
    if ($score >= 9) return 'priority-urgent';
    if ($score >= 7) return 'priority-high';
    if ($score >= 5) return 'priority-medium';
    return 'priority-low';
}

$statusBadge = [
    'submitted'         => ['label' => 'Submitted',    'class' => 'badge-submitted'],
    'in_review'         => ['label' => 'In review',    'class' => 'badge-in-review'],
    'approved'          => ['label' => 'Approved',     'class' => 'badge-approved'],
    'sent_to_qualtrics' => ['label' => 'To Qualtrics', 'class' => 'badge-qualtrics'],
    'fulfilled'         => ['label' => 'Fulfilled',    'class' => 'badge-fulfilled'],
];
$typeLabel = ['mailing'=>'Mailing','presentation'=>'Presentation','inperson_support'=>'In-person'];
$typeBadge = ['mailing'=>'badge-mailing','presentation'=>'badge-presentation','inperson_support'=>'badge-inperson'];

$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected   = null;
if ($selectedId) {
    $s = $db->prepare('SELECT * FROM requests WHERE id = ?');
    $s->execute([$selectedId]);
    $selected = $s->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="d-flex align-center justify-between">
            <div><h1>Staff Request Queue</h1><p>Review, triage, and forward community health requests</p></div>
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
                <?php foreach ([
                    'all'       => 'All requests',
                    'flagged'   => 'Flagged',
                    'submitted' => 'Submitted',
                    'in_review' => 'In review',
                    'approved'  => 'Approved',
                    'fulfilled' => 'Fulfilled',
                ] as $key => $lbl): ?>
                <a href="?filter=<?= urlencode($key) ?>" class="btn btn-block btn-<?= $filter === $key ? 'dark' : 'secondary' ?> btn-sm mb-sm"><?= $lbl ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- Main content -->
        <div>
            <!-- Filter bar: pills + count + search — consistent with admin dashboard -->
            <div class="filter-bar">
                <?php foreach ([
                    'all'       => 'All',
                    'submitted' => 'Submitted',
                    'in_review' => 'In review',
                    'approved'  => 'Approved',
                    'fulfilled' => 'Fulfilled',
                    'flagged'   => 'Flagged',
                ] as $key => $lbl): ?>
                <a href="?filter=<?= urlencode($key) ?>" class="filter-pill <?= $filter === $key ? 'active' : '' ?>"><?= $lbl ?></a>
                <?php endforeach; ?>
                <span class="text-muted text-small" style="margin-left:var(--space-sm);"><?= count($requests) ?> result<?= count($requests) !== 1 ? 's' : '' ?></span>
                <div class="search-box">
                    <form method="GET">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search events, contacts...">
                    </form>
                </div>
            </div>

            <div class="card" style="padding:0;">
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Event / contact</th><th>Type</th><th>Status</th><th>Priority</th><th>Date</th><th>Items</th><th>AI flag</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">No requests found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($requests as $req):
                            $sb  = $statusBadge[$req['status']] ?? ['label' => ucfirst($req['status']), 'class' => 'badge-submitted'];
                            $tl  = $typeLabel[$req['request_type']] ?? $req['request_type'];
                            $tb  = $typeBadge[$req['request_type']] ?? 'badge-submitted';
                            $pc  = priorityClass((int)($req['ai_priority_score'] ?? 0));
                            $pct = min(100, (int)($req['ai_priority_score'] ?? 0) * 10);
                            $isSel = ($selectedId === (int)$req['id']);
                        ?>
                        <tr class="<?= $isSel ? 'selected' : '' ?>" onclick="location.href='?filter=<?= urlencode($filter) ?>&id=<?= $req['id'] ?>'">
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
                                <span class="ai-flag"><?= htmlspecialchars(substr($req['ai_flags'], 0, 24)) ?></span>
                                <?php else: ?>
                                <span class="td-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="/pages/admin_dashboard.php?id=<?= $req['id'] ?>" class="btn btn-secondary btn-sm" onclick="event.stopPropagation();">Send to admin</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($selected):
                $att     = (int)($selected['estimated_attendees'] ?? 0);
                $stype   = $selected['request_type'] ?? '';
                $aiStaff = $att >= 200 ? '4-5 staff recommended'
                         : ($att >= 100 ? '2-3 staff recommended'
                         : ($stype === 'mailing' ? 'No staff — mailing pathway' : '1-2 staff recommended'));
                $aiMats  = '~' . $att . ' material packets — ' . ($selected['material_category'] ?? '');
                $aiRoom  = $stype === 'mailing' ? 'N/A — mailing only' : ($att >= 200 ? 'Large venue / multi-room likely' : 'Standard event space');
                $aiSetup = $stype === 'mailing' ? 'Packing + shipping est. 1-2 business days' : ($att >= 200 ? '45+ min setup' : '20-30 min setup');
            ?>
            <div class="card mt-md" id="detail-panel">
                <div class="card-header">
                    <h3><?= htmlspecialchars($selected['event_name']) ?></h3>
                    <a href="?filter=<?= urlencode($filter) ?>" class="btn btn-secondary btn-sm">Close</a>
                </div>

                <?php if (!empty($selected['ai_flags'])): ?>
                <div class="alert alert-danger"><strong>AI flag:</strong> <?= htmlspecialchars($selected['ai_flags']) ?></div>
                <?php endif; ?>

                <div class="ai-panel mb-md">
                    <p class="ai-panel-title">AI summary</p>
                    <div class="ai-row"><span class="ai-label">Staff prediction</span><span class="ai-value"><?= htmlspecialchars($aiStaff) ?></span></div>
                    <div class="ai-row"><span class="ai-label">Materials required</span><span class="ai-value"><?= htmlspecialchars($aiMats) ?></span></div>
                    <div class="ai-row"><span class="ai-label">Room / space needed</span><span class="ai-value"><?= htmlspecialchars($aiRoom) ?></span></div>
                    <div class="ai-row"><span class="ai-label">Estimated setup time</span><span class="ai-value"><?= htmlspecialchars($aiSetup) ?></span></div>
                    <div class="ai-row"><span class="ai-label">Routing recommendation</span><span class="ai-value"><?= htmlspecialchars($selected['ai_routing_recommendation'] ?? 'Pending') ?></span></div>
                </div>

                <div class="form-grid mb-md">
                    <div class="form-group"><label>Contact</label><p><?= htmlspecialchars($selected['requestor_name']) ?></p></div>
                    <div class="form-group"><label>Organization</label><p><?= htmlspecialchars($selected['organization']) ?></p></div>
                    <div class="form-group"><label>Event date</label><p><?= htmlspecialchars(date('F j, Y', strtotime($selected['event_date']))) ?></p></div>
                    <div class="form-group"><label>Location</label><p><?= htmlspecialchars($selected['city'] . ', ' . $selected['zip_code']) ?></p></div>
                    <div class="form-group"><label>Participants</label><p><?= (int)$selected['estimated_attendees'] ?></p></div>
                    <div class="form-group"><label>Items requested</label><p><?= htmlspecialchars($selected['material_category'] ?? '—') ?></p></div>
                    <?php if (!empty($selected['notes'])): ?>
                    <div class="form-group form-full"><label>Notes</label><p><?= htmlspecialchars($selected['notes']) ?></p></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-sm">
                    <a href="/pages/admin_dashboard.php?id=<?= $selected['id'] ?>" class="btn btn-primary">Send to admin (Sarah)</a>
                    <a href="?filter=<?= urlencode($filter) ?>" class="btn btn-secondary">Close detail</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
