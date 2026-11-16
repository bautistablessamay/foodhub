<?php
require_once '../includes/auth.php'; require_role('customer');
$id = $_SESSION['user']['id'];
$c = rows("SELECT COALESCE(SUM(status IN ('pending','preparing','ready')),0) a, COUNT(*) t FROM orders WHERE customer_id=?", 'i', $id)[0];
$pageTitle = 'Dashboard'; $refresh = 15; include '../includes/header.php'; ?>
<div class="stat"><div class="card"><span class="m">Active orders</span><b><?= (int)$c['a'] ?></b></div><div class="card"><span class="m">All orders</span><b><?= (int)$c['t'] ?></b></div></div>
<div class="r"><a class="btn p" href="menu.php">See today’s menu</a><a class="btn" href="orders.php">My orders</a></div>
<?php include '../includes/footer.php'; ?>
