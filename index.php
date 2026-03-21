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
<div class="login-page">

    <!-- Left hero panel -->
    <div class="login-hero">
        <div class="login-logo">
            <img src="/assets/images/ihcHEALTHLINK.png" alt="HealthLink logo">
        </div>
        <h1>Community Health request management</h1>
        <p>Streamlining how community partners and internal staff request materials, presentations, and in-person support from Intermountain Health.</p>
    </div>

    <!-- Right form panel -->
    <div class="login-form-side">

        <h2>Sign in</h2>
        <p class="login-subtitle">Enter your username and password to continue.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">You are not authorized to view that page.</div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    required
                    autofocus
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </form>

        <hr class="divider">

        <p class="text-small text-muted mb-sm">Demo accounts &mdash; password: <strong>HealthLink2025!</strong></p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <button onclick="fill('maria.gonzalez')" class="btn btn-secondary btn-sm">Community partner</button>
            <button onclick="fill('james.thompson')" class="btn btn-secondary btn-sm">Staff</button>
            <button onclick="fill('sarah.admin')"    class="btn btn-secondary btn-sm">Admin</button>
            <button onclick="fill('dr.chen')"        class="btn btn-secondary btn-sm">Leader</button>
        </div>

    </div>
</div>

<script>
function fill(u) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = 'HealthLink2025!';
}
</script>
</body>
</html>
