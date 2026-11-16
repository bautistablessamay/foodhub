<?php
require_once 'includes/auth.php';
if (!empty($_SESSION['user'])) go(home_for_role($_SESSION['user']['role']));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $error = 'Your session expired. Please try again.';
    else {
        $u = rows('SELECT * FROM users WHERE username=?', 's', strtolower(trim($_POST['username'] ?? '')))[0] ?? null;
        if (!$u || !password_verify($_POST['password'] ?? '', $u['password_hash'])) $error = 'Wrong username or password.';
        elseif (!$u['active']) $error = 'This account is deactivated. Please contact the admin.';
        else { session_regenerate_id(true); $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']]; go(home_for_role($u['role'])); }
    }
}
$pageTitle = 'Log in'; include 'includes/header.php'; ?>
<div class="card auth">
  <h2 style="margin-top:0">Welcome back</h2><p class="m">Log in as a customer, seller or admin.</p>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <label for="username">Username</label><input id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus autocapitalize="none">
    <label for="password">Password</label><input id="password" name="password" type="password" required>
    <div class="linkrow"><span></span><a href="forgot_password.php">Forgot password?</a></div>
    <button class="p" style="width:100%">Log in</button>
  </form>
  <p class="m" style="text-align:center">No account? <a href="register.php">Register</a></p>
</div>
<?php include 'includes/footer.php'; ?>
