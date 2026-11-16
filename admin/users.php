<?php
require_once '../includes/auth.php'; require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $uid = (int)($_POST['id'] ?? 0);
    if (isset($_POST['role'])) {
        $r = $_POST['role'] === 'seller' ? 'seller' : 'customer';
        run("UPDATE users SET role=? WHERE id=? AND role<>'admin'", 'si', $r, $uid);
        flash('Account set as ' . $r . '.');
    } else { run("UPDATE users SET active=1-active WHERE id=? AND role<>'admin'", 'i', $uid); flash('User updated.'); }
    go('users.php');
}
$us = rows("SELECT * FROM users WHERE role<>'admin' ORDER BY id DESC");
$pageTitle = 'Users'; include '../includes/header.php'; ?>
<div class="card tw"><table><tr><th>Name</th><th>Username</th><th>Email</th><th>Account type</th><th>Status</th><th></th></tr>
<?php foreach ($us as $u): ?><tr><td><?= e($u['name']) ?></td><td><?= e($u['username']) ?></td><td class="m"><?= e($u['email'] ?? '') ?></td><td><form class="in" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="role" value="<?= $u['role'] === 'seller' ? 'customer' : 'seller' ?>"><span class="b <?= $u['role'] === 'seller' ? 'ulam' : 'drinks' ?>"><?= $u['role'] === 'seller' ? '🍳 Seller' : '🛍️ Customer' ?></span> <button class="<?= $u['role'] === 'seller' ? '' : 'p' ?>"><?= $u['role'] === 'seller' ? 'Make customer' : 'Make seller' ?></button></form></td>
  <td style="color:var(--<?= $u['active'] ? 'ok' : 'bad' ?>)"><?= $u['active'] ? 'Active' : 'Deactivated' ?></td>
  <td><form class="in" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="<?= $u['active'] ? 'd' : 'p' ?>"><?= $u['active'] ? 'Deactivate' : 'Activate' ?></button></form></td></tr>
<?php endforeach; if (!$us): ?><tr><td colspan="6" class="m">No users yet.</td></tr><?php endif; ?></table></div>
<?php include '../includes/footer.php'; ?>
