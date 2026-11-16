<?php
require_once '../includes/auth.php'; require_role('customer');
$u = $_SESSION['user']; $today = date('Y-m-d');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $f = rows('SELECT f.* FROM foods f JOIN users s ON s.id=f.seller_id WHERE f.id=? AND f.food_date=? AND s.active=1', 'is', (int)($_POST['food'] ?? 0), $today)[0] ?? null;
    if ($f) {
        $q = max(1, min(99, (int)($_POST['qty'] ?? 1))); $m = ($_POST['mode'] ?? '') === 'delivery' ? 'delivery' : 'pickup';
        run('INSERT INTO orders(customer_id,seller_id,food_id,food_name,price,qty,total,mode,status,created_at,order_date) VALUES(?,?,?,?,?,?,?,?,?,?,?)', 'iiisdidssis', $u['id'], $f['seller_id'], $f['id'], $f['name'], $f['price'], $q, $f['price'] * $q, $m, 'pending', time(), $today);
        flash('Order placed! The seller will update you here.'); go('orders.php');
    }
    flash('That food is no longer available.', 'bad'); go('menu.php');
}
$cat = isset(CATS[$_GET['cat'] ?? '']) ? $_GET['cat'] : '';
$fs = $cat ? rows('SELECT f.*,s.name sname FROM foods f JOIN users s ON s.id=f.seller_id WHERE f.food_date=? AND s.active=1 AND f.cat=? ORDER BY f.id DESC', 'ss', $today, $cat)
           : rows('SELECT f.*,s.name sname FROM foods f JOIN users s ON s.id=f.seller_id WHERE f.food_date=? AND s.active=1 ORDER BY f.id DESC', 's', $today);
$pageTitle = 'Today’s menu'; include '../includes/header.php'; ?>
<nav class="tabs"><a class="<?= $cat ? '' : 'on' ?>" href="menu.php">All</a><?php foreach (CATS as $k => $l): ?><a class="<?= $cat === $k ? 'on' : '' ?>" href="menu.php?cat=<?= $k ?>"><?= e($l) ?></a><?php endforeach; ?></nav>
<div class="grid"><?php foreach ($fs as $f): ?><div class="card">
  <?php if ($f['photo']): ?><img class="ph" src="<?= BASE_URL ?>/uploads/<?= e($f['photo']) ?>" alt="<?= e($f['name']) ?>"><?php else: ?><div class="ph">🍽️</div><?php endif; ?>
  <div class="r sp"><b><?= e($f['name']) ?></b><span class="b <?= e($f['cat']) ?>"><?= e(CATS[$f['cat']]) ?></span></div>
  <div><b><?= peso($f['price']) ?></b> <span class="m">· <?= e($f['sname']) ?></span></div><div class="m"><?= e($f['description']) ?></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="food" value="<?= $f['id'] ?>">
    <div class="r"><input name="qty" type="number" min="1" value="1" style="width:72px;margin:0"><select name="mode" style="flex:1;margin:0"><option value="pickup">Pick-up</option><option value="delivery">Delivery</option></select></div>
    <button class="p" style="width:100%;margin-top:10px">Order</button></form></div>
<?php endforeach; if (!$fs): ?><p class="m">No food on today’s menu yet. Please check back later.</p><?php endif; ?></div>
<?php include '../includes/footer.php'; ?>
