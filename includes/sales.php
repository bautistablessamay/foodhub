<?php
// Sold-food history + earnings (admin sees all, seller sees own). $cond is a trusted SQL snippet.
function sales_report($cond = '1=1', $t = '', ...$a) {
    $isAdmin = $cond === '1=1';
    $w = "status='completed' AND $cond";
    $s = rows("SELECT COALESCE(SUM(total),0) t, COALESCE(SUM(qty),0) n, COUNT(*) c FROM orders WHERE $w", $t, ...$a)[0];
    $days  = rows("SELECT order_date d, SUM(qty) n, SUM(total) t FROM orders WHERE $w GROUP BY order_date ORDER BY order_date DESC", $t, ...$a);
    $foods = rows("SELECT food_name f, SUM(qty) n, SUM(total) t FROM orders WHERE $w GROUP BY food_name ORDER BY n DESC", $t, ...$a);
    $hist  = rows("SELECT o.*,c.name cname,s.name sname FROM orders o JOIN users c ON c.id=o.customer_id JOIN users s ON s.id=o.seller_id WHERE $w ORDER BY o.id DESC LIMIT 100", $t, ...$a); ?>
  <div class="stat"><div class="card"><span class="m"><?= $isAdmin ? 'Total sales (100% paid to sellers)' : 'Your earnings (100% of sales)' ?></span><b><?= peso($s['t']) ?></b></div><div class="card"><span class="m">Items sold</span><b><?= (int)$s['n'] ?></b></div><div class="card"><span class="m">Completed orders</span><b><?= (int)$s['c'] ?></b></div></div>
  <?php if ($isAdmin): $pay = rows("SELECT s.name sname, COUNT(*) c, SUM(o.qty) n, SUM(o.total) t FROM orders o JOIN users s ON s.id=o.seller_id WHERE $w GROUP BY o.seller_id ORDER BY t DESC", $t, ...$a); ?>
  <h2>Seller payouts <span class="m">— full amount, no deduction</span></h2><div class="card tw"><table><tr><th>Seller</th><th>Orders</th><th>Items</th><th>Paid to seller</th></tr>
    <?php foreach ($pay as $r): ?><tr><td><?= e($r['sname']) ?></td><td><?= (int)$r['c'] ?></td><td><?= (int)$r['n'] ?></td><td><b><?= peso($r['t']) ?></b></td></tr><?php endforeach; if (!$pay): ?><tr><td colspan="4" class="m">No sales yet.</td></tr><?php endif; ?></table></div>
  <?php endif; ?>
  <h2>Earnings by day</h2><div class="card tw"><table><tr><th>Date</th><th>Sold</th><th>Earnings</th></tr>
    <?php foreach ($days as $r): ?><tr><td><?= e($r['d']) ?></td><td><?= (int)$r['n'] ?></td><td><?= peso($r['t']) ?></td></tr><?php endforeach; if (!$days): ?><tr><td colspan="3" class="m">No sales yet.</td></tr><?php endif; ?></table></div>
  <h2>Sold foods</h2><div class="card tw"><table><tr><th>Food</th><th>Qty sold</th><th>Earnings</th></tr>
    <?php foreach ($foods as $r): ?><tr><td><?= e($r['f']) ?></td><td><?= (int)$r['n'] ?></td><td><?= peso($r['t']) ?></td></tr><?php endforeach; if (!$foods): ?><tr><td colspan="3" class="m">No sales yet.</td></tr><?php endif; ?></table></div>
  <h2>Sales history</h2><div class="card tw"><table><tr><th>Date</th><th>Food</th><th>Qty</th><th>Customer</th><th>Seller</th><th>Paid to seller</th></tr>
    <?php foreach ($hist as $r): ?><tr><td><?= e($r['order_date']) ?></td><td><?= e($r['food_name']) ?></td><td><?= (int)$r['qty'] ?></td><td><?= e($r['cname']) ?></td><td><?= e($r['sname']) ?></td><td><?= peso($r['total']) ?></td></tr><?php endforeach; if (!$hist): ?><tr><td colspan="6" class="m">No sales yet.</td></tr><?php endif; ?></table></div>
<?php }
