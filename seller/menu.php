<?php
require_once '../includes/auth.php'; require_role('seller');
$id = $_SESSION['user']['id']; $today = date('Y-m-d');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if (isset($_POST['delete'])) {
        $f = rows('SELECT photo FROM foods WHERE id=? AND seller_id=?', 'ii', (int)$_POST['delete'], $id)[0] ?? null;
        if ($f) { run('DELETE FROM foods WHERE id=?', 'i', (int)$_POST['delete']); if ($f['photo']) @unlink(__DIR__ . '/../uploads/' . $f['photo']); flash('Food deleted.'); }
    } else {
        $n = trim($_POST['name'] ?? ''); $pr = (float)($_POST['price'] ?? 0); $c = $_POST['cat'] ?? ''; $photo = ''; $err = '';
        if ($n === '' || $pr <= 0 || !isset(CATS[$c])) $err = 'Enter a food name, a valid price and a label.';
        elseif (!empty($_FILES['photo']['tmp_name'])) {
            $f = $_FILES['photo']; $i = @getimagesize($f['tmp_name']);
            $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$i[2] ?? 0] ?? null;
            if (!$ext || $f['size'] > 3000000) $err = 'Photo must be a JPG, PNG or WEBP under 3 MB.';
            else { $photo = bin2hex(random_bytes(8)) . '.' . $ext; move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $photo); }
        }
        if ($err) flash($err, 'bad');
        else { run('INSERT INTO foods(seller_id,name,price,description,cat,photo,food_date) VALUES(?,?,?,?,?,?,?)', 'isdssss', $id, mb_substr($n, 0, 100), $pr, mb_substr(trim($_POST['description'] ?? ''), 0, 500), $c, $photo, $today); flash('Added to today’s menu.'); }
    }
    go('menu.php');
}
$fs = rows('SELECT * FROM foods WHERE seller_id=? AND food_date=? ORDER BY id DESC', 'is', $id, $today);
$pageTitle = 'Today’s menu'; include '../includes/header.php'; ?>
<div class="card"><b>Add food to today’s menu</b>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <label>Name</label><input name="name" required>
    <div class="r"><div style="flex:1"><label>Price (₱)</label><input name="price" type="number" min="0.5" step="0.5" required></div>
    <div style="flex:1"><label>Label</label><select name="cat"><?php foreach (CATS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div></div>
    <label>Description</label><textarea name="description" rows="2"></textarea>
    <label>Photo</label><input name="photo" type="file" accept="image/*">
    <button class="p">Add to menu</button></form></div>
<h2>Today’s menu (<?= e($today) ?>)</h2>
<div class="grid"><?php foreach ($fs as $f): ?><div class="card">
  <?php if ($f['photo']): ?><img class="ph" src="<?= BASE_URL ?>/uploads/<?= e($f['photo']) ?>" alt=""><?php else: ?><div class="ph">🍽️</div><?php endif; ?>
  <div class="r sp"><b><?= e($f['name']) ?></b><span class="b <?= e($f['cat']) ?>"><?= e(CATS[$f['cat']]) ?></span></div><b><?= peso($f['price']) ?></b><div class="m"><?= e($f['description']) ?></div>
  <form method="post"><?= csrf_field() ?><button class="d" name="delete" value="<?= $f['id'] ?>">Delete</button></form></div>
<?php endforeach; if (!$fs): ?><p class="m">Nothing added yet today.</p><?php endif; ?></div>
<?php include '../includes/footer.php'; ?>
