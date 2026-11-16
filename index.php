<?php
require_once 'includes/auth.php';
if (!empty($_SESSION['user'])) go(home_for_role($_SESSION['user']['role']));
$pageTitle = 'Home'; include 'includes/header.php'; ?>
<div class="hero"><h2>Welcome to Abia’s Eatery! 🍛</h2><h3 class="tag">Fresh food, ordered online.</h3><p class="m">See today’s ulam, desserts and drinks, order, and chat with the seller.</p>
  <div class="r" style="justify-content:center;margin-top:18px"><a class="btn p" href="login.php">Log in</a><a class="btn" href="register.php">Create account</a></div></div>
<?php include 'includes/footer.php'; ?>
