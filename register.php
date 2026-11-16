<?php
require_once 'includes/auth.php';
require_once 'includes/notify.php';
if (!empty($_SESSION['user'])) go(home_for_role($_SESSION['user']['role']));

const OTP_TTL = 600;      // code valid for 10 minutes
const OTP_TRIES = 5;      // wrong guesses allowed
const OTP_RESEND = 60;    // seconds between codes

$error = '';
$P = $_SESSION['signup'] ?? null;   // pending registration (nothing is saved to the database until the code is verified)
if ($P && $P['exp'] < time()) { unset($_SESSION['signup']); $P = null; $error = 'Your code expired. Please register again.'; }
$act = $_POST['action'] ?? 'start';

function send_signup_code($em) {
    $otp = sprintf('%06d', random_int(0, 999999)); $salt = bin2hex(random_bytes(8));
    if (!deliver_otp($em, $otp, 'registration')) return false;
    $_SESSION['signup']['salt'] = $salt; $_SESSION['signup']['hash'] = hash_hmac('sha256', $otp, $salt);
    $_SESSION['signup']['exp'] = time() + OTP_TTL; $_SESSION['signup']['sent'] = time(); $_SESSION['signup']['tries'] = 0;
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $error = 'Your session expired. Please try again.';

    // Go back and edit the details
    elseif ($act === 'cancel') { unset($_SESSION['signup']); go('register.php'); }

    // Step 2: check the emailed code, then create the customer account
    elseif ($act === 'verify' && $P) {
        $code = preg_replace('/\D/', '', $_POST['otp'] ?? '');
        $_SESSION['signup']['tries']++;
        if (hash_equals($P['hash'], hash_hmac('sha256', $code, $P['salt']))) {
            if (rows('SELECT id FROM users WHERE username=? OR email=?', 'ss', $P['username'], $P['email'])) { unset($_SESSION['signup']); $error = 'That username or email was just taken. Please register again.'; }
            else {
                run('INSERT INTO users(name,username,email,password_hash,role) VALUES(?,?,?,?,?)', 'sssss', $P['name'], $P['username'], $P['email'], $P['pw'], 'customer');
                unset($_SESSION['signup']); flash('Email verified! Your account is ready. You can log in now.'); go('login.php');
            }
        } else {
            $left = OTP_TRIES - $_SESSION['signup']['tries'];
            if ($left <= 0) { unset($_SESSION['signup']); $error = 'Too many wrong codes. Please register again.'; }
            else $error = "Wrong code. $left " . ($left === 1 ? 'try' : 'tries') . ' left.';
        }
    }

    // Resend the code
    elseif ($act === 'resend' && $P) {
        if (time() - $P['sent'] < OTP_RESEND) $error = 'Please wait ' . (OTP_RESEND - (time() - $P['sent'])) . ' seconds before asking for a new code.';
        elseif (!send_signup_code($P['email'])) $error = 'We could not send the code right now. Please try again later.';
        else { flash('A new code was sent.'); go('register.php'); }
    }

    // Step 1: validate the form, then email the code
    elseif ($act === 'start') {
        $n = trim($_POST['name'] ?? ''); $un = strtolower(trim($_POST['username'] ?? '')); $p = $_POST['password'] ?? '';
        $em = normalize_email($_POST['email'] ?? '');
        if ($n === '' || !preg_match('/^[a-z0-9_.-]{3,20}$/', $un)) $error = 'Enter your name and a username (3–20 letters, numbers, _ . -).';
        elseif (!$em) $error = 'Enter a valid, active email address (e.g. you@gmail.com). We send a verification code there.';
        elseif (strlen($p) < 4) $error = 'Password must be at least 4 characters.';
        elseif ($p !== ($_POST['password2'] ?? '')) $error = 'Passwords do not match.';
        elseif (rows('SELECT id FROM users WHERE username=?', 's', $un)) $error = 'Username already taken.';
        elseif (rows('SELECT id FROM users WHERE email=?', 's', $em)) $error = 'That email is already registered.';
        else {
            $_SESSION['signup'] = ['name' => mb_substr($n, 0, 80), 'username' => $un, 'email' => $em, 'pw' => password_hash($p, PASSWORD_DEFAULT), 'exp' => time() + OTP_TTL, 'sent' => 0];
            if (!send_signup_code($em)) { unset($_SESSION['signup']); $error = 'We could not send the verification code. Check the email address and try again.'; }
            else { flash('We sent a 6-digit code to your email.'); go('register.php'); }
        }
    }
    $P = $_SESSION['signup'] ?? null;
}
$pageTitle = 'Register'; include 'includes/header.php'; ?>
<div class="card auth">
  <h2 style="margin-top:0">Create your account 🎉</h2>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

  <?php if (!$P): ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="start">
    <label>Full name</label><input name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
    <label>Username</label><input name="username" value="<?= e($_POST['username'] ?? '') ?>" required autocapitalize="none">
    <label>Email</label><input name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com" required autocapitalize="none">
    <p class="m" style="margin:-6px 0 12px;font-size:13px">Use a real, active email — we send a verification code there.</p>
    <label>Password</label><input name="password" type="password" required>
    <label>Confirm password</label><input name="password2" type="password" required>
    <button class="p" style="width:100%">Send verification code</button>
  </form>

  <?php else: ?>
  <p class="m">We emailed a 6-digit code to <b><?= e(mask_email($P['email'])) ?></b>. Check your Inbox or Spam folder. It expires in 10 minutes.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify">
    <label for="otp">Enter code</label>
    <input id="otp" class="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
    <button class="p" style="width:100%">Verify &amp; create account</button>
  </form>
  <div class="r" style="margin-top:12px;justify-content:space-between">
    <form class="in" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><button type="submit">Resend code</button></form>
    <form class="in" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><button type="submit">Change details</button></form>
  </div>
  <?php endif; ?>

  <p class="m" style="text-align:center">Have an account? <a href="login.php">Log in</a></p>
</div>
<?php include 'includes/footer.php'; ?>
