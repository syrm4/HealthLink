<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
startSession();

if (isLoggedIn()) {
    $map = ['community'=>'community_portal','staff'=>'staff_portal','admin'=>'admin_dashboard','leader'=>'leader_dashboard'];
    header('Location: /pages/' . ($map[$_SESSION['role']] ?? 'community_portal') . '.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['user']    = [
                'id'           => $user['id'],
                'full_name'    => $user['full_name'],
                'username'     => $user['username'],
                'email'        => $user['email'],
                'organization' => $user['organization'],
                'role'         => $user['role'],
            ];
            $map = ['community'=>'community_portal','staff'=>'staff_portal','admin'=>'admin_dashboard','leader'=>'leader_dashboard'];
            header('Location: /pages/' . ($map[$user['role']] ?? 'community_portal') . '.php');
            exit;
        }
        $error = 'Invalid username or password.';
    } else {
        $error = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthLink &mdash; Sign In</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">HealthLink</div>
        <div class="login-sub">Community Health Request Management &mdash; Intermountain Healthcare</div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">You are not authorized to view that page.</div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Username</label>
                <input type="text" name="username" placeholder="Enter your username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label">Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Sign in</button>
        </form>
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">Demo accounts &mdash; password: <strong>HealthLink2025!</strong></p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                <button onclick="fill('maria.gonzalez')" class="btn btn-secondary" style="font-size:11px;padding:5px 8px;">Community partner</button>
                <button onclick="fill('james.thompson')" class="btn btn-secondary" style="font-size:11px;padding:5px 8px;">Staff</button>
                <button onclick="fill('sarah.mitchell')" class="btn btn-secondary" style="font-size:11px;padding:5px 8px;">Admin</button>
                <button onclick="fill('dr.chen')" class="btn btn-secondary" style="font-size:11px;padding:5px 8px;">Leader</button>
            </div>
        </div>
    </div>
</div>
<script>
function fill(u) {
    document.querySelector('[name=username]').value = u;
    document.querySelector('[name=password]').value = 'HealthLink2025!';
}
</script>
</body>
</html>
