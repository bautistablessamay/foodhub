<?php
require_once '../includes/auth.php'; require_role('seller'); require_once '../includes/sales.php';
$id = $_SESSION['user']['id'];
$pageTitle = 'Seller dashboard'; $refresh = 15; include '../includes/header.php';
$p = rows("SELECT COUNT(*) c FROM orders WHERE seller_id=? AND status IN ('pending','preparing','ready')", 'i', $id)[0]['c']; ?>
<div class="card r sp"><span><b><?= (int)$p ?></b> active order(s)</span><span class="r"><a class="btn p" href="menu.php">Edit today’s menu</a><a class="btn" href="orders.php">View orders</a></span></div>
<?php sales_report('seller_id=?', 'i', $id);
include '../includes/footer.php'; ?>
