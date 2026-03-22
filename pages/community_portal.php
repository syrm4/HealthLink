<?php
// HealthLink — Community Partner Portal (Maria persona)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$isLoggedIn = is_logged_in();
$user       = $isLoggedIn ? current_user() : null;
$tab        = $_GET['tab'] ?? ($isLoggedIn ? 'dashboard' : 'guest');

$myRequests = [];
if ($isLoggedIn) {
    $stmt = getDB()->prepare(
        'SELECT id, event_name, event_date, request_type, estimated_attendees, status
         FROM requests WHERE user_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$user['id']]);
    $myRequests = $stmt->fetchAll();
}

$statusBadge = [
    'submitted'         => ['label' => 'Sent',        'class' => 'badge-submitted'],
    'in_review'         => ['label' => 'In progress', 'class' => 'badge-in-review'],
    'approved'          => ['label' => 'Confirmed',   'class' => 'badge-approved'],
    'sent_to_qualtrics' => ['label' => 'Confirmed',   'class' => 'badge-qualtrics'],
    'fulfilled'         => ['label' => 'Fulfilled',   'class' => 'badge-fulfilled'],
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1>Community Health Request Portal</h1>
        <p>Submit and track requests for health education support at your community events.</p>
    </div>
</div>

<main class="main-content">
    <div class="container-sm">

        <?php if (!$isLoggedIn): ?>
        <div class="card mb-md">
            <div class="d-flex gap-md" style="justify-content:center; flex-wrap:wrap;">
                <a href="<?= BASE_PATH ?>/index.php" class="btn btn-secondary">Sign in to my account</a>
                <a href="?tab=new" class="btn btn-primary">Create account &amp; request</a>
                <a href="?tab=guest" class="btn btn-dark">Continue as guest</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
        <div class="tabs">
            <button class="tab-btn <?= $tab !== 'new' ? 'active' : '' ?>" onclick="switchTab('dashboard')">My requests</button>
            <button class="tab-btn <?= $tab === 'new' ? 'active' : '' ?>" onclick="switchTab('new')">New request</button>
        </div>
        <?php endif; ?>

        <!-- Success / error alerts injected by JS after submission -->
        <div id="form-alert" style="display:none;"></div>

        <!-- DASHBOARD -->
        <div id="tab-dashboard" class="tab-pane <?= ($isLoggedIn && $tab !== 'new' && $tab !== 'guest') ? 'active' : '' ?>">
            <?php if ($isLoggedIn): ?>
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-center gap-sm">
                        <div class="avatar avatar-md avatar-community"><?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?></div>
                        <div>
                            <h3 class="mb-0">Welcome, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></h3>
                            <small class="text-muted"><?= htmlspecialchars($user['org'] ?? '') ?></small>
                        </div>
                    </div>
                    <a href="?tab=new" class="btn btn-primary btn-sm">+ New request</a>
                </div>
                <div class="d-flex gap-md mb-md" style="flex-wrap:wrap;">
                    <span><span class="badge badge-submitted">Sent</span> &mdash; Received</span>
                    <span><span class="badge badge-in-review">In progress</span> &mdash; Under review</span>
                    <span><span class="badge badge-approved">Confirmed</span> &mdash; Approved</span>
                </div>
                <?php if (empty($myRequests)): ?>
                <p class="text-muted text-center" style="padding:2rem 0;">No requests yet. <a href="?tab=new">Submit your first request.</a></p>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Event</th><th>Date</th><th>Type</th><th>Participants</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($myRequests as $req):
                            $sb    = $statusBadge[$req['status']] ?? ['label' => ucfirst($req['status']), 'class' => 'badge-submitted'];
                            $tlMap = ['mailing'=>'Mailing','presentation'=>'Presentation','inperson_support'=>'In-person support'];
                            $tl    = $tlMap[$req['request_type']] ?? $req['request_type'];
                            $tbMap = ['mailing'=>'mailing','presentation'=>'presentation','inperson_support'=>'inperson'];
                            $tb    = $tbMap[$req['request_type']] ?? 'submitted';
                        ?>
                        <tr>
                            <td class="td-name"><?= htmlspecialchars($req['event_name']) ?></td>
                            <td class="td-muted"><?= htmlspecialchars(date('M j, Y', strtotime($req['event_date']))) ?></td>
                            <td><span class="badge badge-<?= $tb ?>"><?= htmlspecialchars($tl) ?></span></td>
                            <td class="td-muted"><?= (int)$req['estimated_attendees'] ?></td>
                            <td><span class="badge <?= $sb['class'] ?>"><?= $sb['label'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- NEW REQUEST -->
        <div id="tab-new" class="tab-pane <?= $tab === 'new' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h3>Submit a new request</h3>
                    <small class="text-muted">Fields marked <span style="color:var(--color-vivid-red)">*</span> are required</small>
                </div>
                <div class="alert alert-ai"><div class="ai-indicator"></div><div>AI assist active &mdash; your request will be automatically classified and routed on submission.</div></div>
                <form id="new-request-form">
                    <h4 class="mb-md">Contact information</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Contact name <span class="required">*</span></label>
                            <input type="text" name="requestor_name" placeholder="Your full name" required>
                        </div>
                        <div class="form-group">
                            <label>Position / title <span class="required">*</span></label>
                            <input type="text" name="contact_title" placeholder="Your role or title" required>
                        </div>
                        <div class="form-group">
                            <label>Phone number <span class="required">*</span></label>
                            <input type="tel" name="requestor_phone" placeholder="801-555-0000" required>
                        </div>
                        <div class="form-group">
                            <label>Email address <span class="required">*</span></label>
                            <input type="email" name="requestor_email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group form-full">
                            <label>Organization <span class="required">*</span></label>
                            <input type="text" name="organization" value="<?= htmlspecialchars($user['org'] ?? '') ?>" required>
                        </div>
                    </div>
                    <hr class="divider">
                    <h4 class="mb-md">Event details</h4>
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label>Event name <span class="required">*</span></label>
                            <input type="text" name="event_name" placeholder="e.g. Spring Family Health Fair" required>
                        </div>
                        <div class="form-group">
                            <label>Event date <span class="required">*</span></label>
                            <input type="date" name="event_date" required>
                        </div>
                        <div class="form-group">
                            <label>Number of participants <span class="required">*</span></label>
                            <input type="number" name="estimated_attendees" placeholder="e.g. 75" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" placeholder="e.g. Salt Lake City" required>
                        </div>
                        <div class="form-group">
                            <label>ZIP code <span class="required">*</span></label>
                            <input type="text" name="zip_code" placeholder="e.g. 84101" maxlength="10" required>
                        </div>
                        <div class="form-group form-full">
                            <label>Audience type <span class="required">*</span></label>
                            <select name="audience_type" required>
                                <option value="">Select...</option>
                                <option value="General community">General community</option>
                                <option value="Youth / school-age">Youth / school-age</option>
                                <option value="Seniors">Seniors</option>
                                <option value="Families">Families</option>
                                <option value="Healthcare workers">Healthcare workers</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group form-full">
                            <label>Type of support requested <span class="required">*</span></label>
                            <select name="request_type" required>
                                <option value="">Select...</option>
                                <option value="mailing">Mailing of education materials or safety devices</option>
                                <option value="presentation">In-person or virtual presentation</option>
                                <option value="inperson_support">Community Health in-person support at event with education materials or safety devices</option>
                            </select>
                            <p class="field-hint">This determines how your request will be fulfilled and routed.</p>
                        </div>
                        <div class="form-group form-full">
                            <label>Materials needed <span class="required">*</span></label>
                            <select name="material_category" required>
                                <option value="">Select...</option>
                                <option value="Educational materials">Educational materials</option>
                                <option value="Safety devices">Safety devices</option>
                                <option value="Educational materials and safety devices">Educational materials and safety devices</option>
                                <option value="Presentation only">Presentation only</option>
                            </select>
                        </div>
                        <div class="form-group form-full">
                            <label>Additional notes</label>
                            <textarea name="notes" placeholder="Language preferences, special requirements, accessibility needs..."></textarea>
                        </div>
                    </div>
                    <div class="d-flex" style="justify-content:flex-end; gap:var(--space-sm); margin-top:var(--space-md);">
                        <a href="?tab=dashboard" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Submit request</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- GUEST -->
        <div id="tab-guest" class="tab-pane <?= $tab === 'guest' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h3>Guest request &mdash; no account needed</h3>
                    <span class="badge badge-role-community">Guest</span>
                </div>
                <form id="guest-request-form">
                    <div class="form-grid">
                        <div class="form-group"><label>Contact name <span class="required">*</span></label><input type="text" name="requestor_name" placeholder="Your full name" required></div>
                        <div class="form-group"><label>Position / title <span class="required">*</span></label><input type="text" name="contact_title" placeholder="Your role" required></div>
                        <div class="form-group"><label>Phone <span class="required">*</span></label><input type="tel" name="requestor_phone" placeholder="801-555-0000" required></div>
                        <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="requestor_email" required></div>
                        <div class="form-group form-full"><label>Organization</label><input type="text" name="organization" placeholder="Your organization"></div>
                        <div class="form-group form-full"><label>Event name <span class="required">*</span></label><input type="text" name="event_name" required></div>
                        <div class="form-group"><label>Event date <span class="required">*</span></label><input type="date" name="event_date" required></div>
                        <div class="form-group"><label>Number of participants <span class="required">*</span></label><input type="number" name="estimated_attendees" min="1" required></div>
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" placeholder="e.g. Salt Lake City" required>
                        </div>
                        <div class="form-group">
                            <label>ZIP code <span class="required">*</span></label>
                            <input type="text" name="zip_code" placeholder="e.g. 84101" maxlength="10" required>
                        </div>
                        <div class="form-group form-full">
                            <label>Audience type <span class="required">*</span></label>
                            <select name="audience_type" required>
                                <option value="">Select...</option>
                                <option value="General community">General community</option>
                                <option value="Youth / school-age">Youth / school-age</option>
                                <option value="Seniors">Seniors</option>
                                <option value="Families">Families</option>
                                <option value="Healthcare workers">Healthcare workers</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group form-full">
                            <label>Type of support <span class="required">*</span></label>
                            <select name="request_type" required>
                                <option value="">Select...</option>
                                <option value="mailing">Mailing of education materials or safety devices</option>
                                <option value="presentation">In-person or virtual presentation</option>
                                <option value="inperson_support">Community Health in-person support at event</option>
                            </select>
                        </div>
                        <div class="form-group form-full">
                            <label>Materials needed <span class="required">*</span></label>
                            <select name="material_category" required>
                                <option value="">Select...</option>
                                <option value="Educational materials">Educational materials</option>
                                <option value="Safety devices">Safety devices</option>
                                <option value="Educational materials and safety devices">Educational materials and safety devices</option>
                                <option value="Presentation only">Presentation only</option>
                            </select>
                        </div>
                        <div class="form-group form-full"><label>Additional notes</label><textarea name="notes" placeholder="Language preferences, special requirements..."></textarea></div>
                    </div>
                    <div class="d-flex" style="justify-content:flex-end; margin-top:var(--space-md);">
                        <button type="submit" class="btn btn-primary">Submit request</button>
                    </div>
                </form>
                <hr class="divider">
                <p class="text-muted text-small">Already have an account? <a href="<?= BASE_PATH ?>/index.php">Sign in</a> to track your request status.</p>
            </div>
        </div>

    </div>
</main>

<script>
function switchTab(name) {
    document.querySelectorAll('.tab-pane').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    var pane = document.getElementById('tab-' + name);
    if (pane) pane.classList.add('active');
    event.target.classList.add('active');
    history.replaceState(null, '', '?tab=' + name);
}

function showAlert(type, message) {
    var el = document.getElementById('form-alert');
    el.className = 'alert alert-' + type;
    el.innerHTML = message;
    el.style.display = 'block';
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function submitForm(formId) {
    var form = document.getElementById(formId);
    var btn  = form.querySelector('button[type="submit"]');
    btn.disabled    = true;
    btn.textContent = 'Submitting...';

    fetch('<?= BASE_PATH ?>/api/submit_request.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            form.reset();
            showAlert('success', '<strong>Request submitted!</strong> Your request has been received and is being reviewed. You will be notified when your status changes.');
        } else {
            showAlert('danger', '<strong>Submission error:</strong> ' + (data.error || 'Please check all required fields and try again.'));
            btn.disabled    = false;
            btn.textContent = 'Submit request';
        }
    })
    .catch(function() {
        showAlert('danger', '<strong>Network error:</strong> Could not reach the server. Please try again.');
        btn.disabled    = false;
        btn.textContent = 'Submit request';
    });
}

document.getElementById('new-request-form').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('new-request-form');
});

document.getElementById('guest-request-form').addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('guest-request-form');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
