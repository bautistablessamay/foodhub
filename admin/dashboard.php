<?php
require_once '../includes/auth.php'; require_role('admin');
$c = rows("SELECT (SELECT COUNT(*) FROM users WHERE role<>'admin') users, (SELECT COUNT(*) FROM users WHERE active=0) off, (SELECT COALESCE(SUM(total),0) FROM orders WHERE status='completed') earn")[0];
$pageTitle = 'Admin dashboard'; include '../includes/header.php'; ?>
<div class="stat"><div class="card"><span class="m">Users</span><b><?= (int)$c['users'] ?></b></div><div class="card"><span class="m">Deactivated</span><b><?= (int)$c['off'] ?></b></div><div class="card"><span class="m">Total sales (100% to sellers)</span><b><?= peso($c['earn']) ?></b></div></div>
<div class="r"><a class="btn p" href="users.php">Manage users</a><a class="btn" href="sales.php">Sales &amp; earnings</a></div>
<?php include '../includes/footer.php'; ?>
