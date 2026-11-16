<?php
require_once __DIR__ . '/auth.php';
$user = $_SESSION['user'] ?? null;
$nav = [
  'admin'    => ['dashboard' => 'Dashboard', 'users' => 'Users', 'sales' => 'Sales & Earnings'],
  'seller'   => ['dashboard' => 'Dashboard', 'menu' => 'Today’s Menu', 'orders' => 'Orders'],
  'customer' => ['dashboard' => 'Dashboard', 'menu' => 'Today’s Menu', 'orders' => 'My Orders'],
];
$cur = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Home') ?> | Abia’s Eatery</title>
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body data-refresh="<?= (int)($refresh ?? 0) ?>">
<div class="w">
<header>
  <div><a href="<?= BASE_URL ?>/index.php" style="text-decoration:none"><span class="logo">Abia’s <span>Eatery</span></span></a><div class="m">Home-cooked favorites, served fresh 🍲</div></div>
  <?php if ($user): ?><div class="r"><span class="m">Hi, <?= e($user['name']) ?> (<?= e($user['role']) ?>)</span><a class="btn" href="<?= BASE_URL ?>/logout.php">Log out</a></div><?php endif; ?>
</header>
<?php if ($user): ?>
<nav class="tabs"><?php foreach ($nav[$user['role']] as $p => $l): ?><a class="<?= $cur === $p ? 'on' : '' ?>" href="<?= BASE_URL . '/' . $user['role'] . '/' . $p ?>.php"><?= e($l) ?></a><?php endforeach; ?></nav>
<?php endif; ?>
<?php if ($f = flash()): ?><div class="note <?= e($f[1]) ?>"><?= e($f[0]) ?></div><?php endif; ?>
