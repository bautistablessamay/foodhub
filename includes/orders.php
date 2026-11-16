<?php
// Shared by seller/orders.php and customer/orders.php (status updates + chat)
require_once __DIR__ . '/auth.php';

function status_label($o) {
    return ['pending' => 'Order placed', 'preparing' => 'Preparing', 'ready' => $o['mode'] === 'pickup' ? 'Ready for pickup' : 'Out for delivery', 'completed' => 'Completed', 'cancelled' => 'Cancelled'][$o['status']];
}
function change_status($o, $role, $to) {
    $ok = ['pending' => ['preparing', 'cancelled'], 'preparing' => ['ready', 'cancelled'], 'ready' => ['completed']];
    if (!in_array($to, $ok[$o['status']] ?? [], true)) return;
    if ($role === 'customer' && $to !== 'cancelled') return;
    run('UPDATE orders SET status=? WHERE id=?', 'si', $to, $o['id']);
    $o['status'] = $to;
    $t = $to === 'cancelled' ? "Order cancelled by $role." : 'Update: ' . status_label($o);
    run('INSERT INTO messages(order_id,sender_id,text,created_at) VALUES(?,0,?,?)', 'isi', $o['id'], $t, time());
}
function orders_page($role) {
    require_role($role);
    $u = $_SESSION['user']; $col = $role === 'seller' ? 'seller_id' : 'customer_id';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
        $id = (int)($_POST['id'] ?? 0);
        $o = rows("SELECT * FROM orders WHERE id=? AND $col=?", 'ii', $id, $u['id'])[0] ?? null;
        if ($o) {
            if (isset($_POST['status'])) change_status($o, $role, $_POST['status']);
            $m = trim($_POST['msg'] ?? '');
            if ($m !== '') run('INSERT INTO messages(order_id,sender_id,text,created_at) VALUES(?,?,?,?)', 'iisi', $id, $u['id'], mb_substr($m, 0, 500), time());
        }
        go('orders.php?chat=' . $id);
    }
    $os = rows("SELECT o.*,c.name cname,s.name sname FROM orders o JOIN users c ON c.id=o.customer_id JOIN users s ON s.id=o.seller_id WHERE o.$col=? ORDER BY o.id DESC", 'i', $u['id']);
    $open = (int)($_GET['chat'] ?? 0);
    $pageTitle = $role === 'seller' ? 'Orders' : 'My orders'; $refresh = 10;
    include __DIR__ . '/header.php';
    if (!$os) echo '<p class="m">No orders yet.</p>';
    foreach ($os as $o):
        $next = ['pending' => ['preparing', 'Start preparing'], 'preparing' => ['ready', $o['mode'] === 'pickup' ? 'Ready for pickup' : 'Out for delivery'], 'ready' => ['completed', 'Mark completed']][$o['status']] ?? null;
        $canCancel = in_array($o['status'], ['pending', 'preparing'], true); ?>
  <div class="card">
    <div class="r sp"><b><?= (int)$o['qty'] ?>× <?= e($o['food_name']) ?></b><span class="b st-<?= e($o['status']) ?>"><?= e(status_label($o)) ?></span></div>
    <div class="m"><?= $role === 'seller' ? 'Customer: ' . e($o['cname']) : 'Seller: ' . e($o['sname']) ?> · <?= $o['mode'] === 'pickup' ? 'Pick-up' : 'Delivery' ?> · <?= peso($o['total']) ?><?= $role === 'seller' ? ' (full amount to you)' : '' ?> · <?= date('M j, g:i A', $o['created_at']) ?></div>
    <div class="r" style="margin-top:8px">
      <?php if ($role === 'seller' && $next): ?><form class="in" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="p" name="status" value="<?= $next[0] ?>"><?= e($next[1]) ?></button></form><?php endif; ?>
      <?php if ($canCancel): ?><form class="in" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="d" name="status" value="cancelled"><?= $role === 'customer' ? 'Cancel order' : 'Cancel' ?></button></form><?php endif; ?>
      <a class="btn" href="orders.php?chat=<?= $o['id'] ?>">💬 Chat</a>
    </div>
    <?php if ($open === (int)$o['id']): $ms = rows('SELECT m.*,u.name FROM messages m LEFT JOIN users u ON u.id=m.sender_id WHERE m.order_id=? ORDER BY m.id', 'i', $o['id']); ?>
    <div class="chat"><div class="msgs">
      <?php foreach ($ms as $m): ?><?php if (!$m['sender_id']): ?><div class="msg sys"><?= e($m['text']) ?></div><?php else: ?><div class="msg <?= $m['sender_id'] == $u['id'] ? 'me' : '' ?>"><small><?= $m['sender_id'] == $u['id'] ? 'You' : e($m['name']) ?></small><br><?= e($m['text']) ?></div><?php endif; ?><?php endforeach; ?>
      <?php if (!$ms): ?><div class="m">No messages yet.</div><?php endif; ?>
    </div>
    <form method="post" class="r"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><input name="msg" placeholder="Type a message…" style="flex:1;margin:0" autocomplete="off" required><button class="p">Send</button></form></div>
    <?php endif; ?>
  </div>
<?php endforeach;
    include __DIR__ . '/footer.php';
}
