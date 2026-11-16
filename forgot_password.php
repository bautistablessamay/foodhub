<?php
require_once 'includes/auth.php';
require_once 'includes/notify.php';
if (!empty($_SESSION['user'])) go(home_for_role($_SESSION['user']['role']));

const OTP_TTL = 600;      // code valid for 10 minutes
const OTP_TRIES = 5;      // wrong guesses allowed
const OTP_RESEND = 60;    // seconds between codes

$error = '';
$R = $_SESSION['reset'] ?? null;
if ($R && $R['exp'] < time()) { unset($_SESSION['reset']); $R = null; $error = 'Your code expired. Please request a new one.'; }
$act = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $error = 'Your session expired. Please try again.';
    elseif ($act === 'cancel') { unset($_SESSION['reset']); go('forgot_password.php'); }

    // Step 1 (send) / resend
    elseif ($act === 'send' || ($act === 'resend' && $R)) {
        $em = normalize_email($act === 'resend' ? $R['ident'] : ($_POST['ident'] ?? ''));
        if (!$em) $error = 'Enter a valid, active email address.';
        elseif ($act === 'resend' && time() - $R['sent'] < OTP_RESEND) $error = 'Please wait ' . (OTP_RESEND - (time() - $R['sent'])) . ' seconds before asking for a new code.';
        else {
            $u = rows('SELECT id,active FROM users WHERE email=?', 's', $em)[0] ?? null;
            $otp = sprintf('%06d', random_int(0, 999999)); $salt = bin2hex(random_bytes(8));
            $uid = ($u && $u['active']) ? (int)$u['id'] : 0;
            $_SESSION['reset'] = ['uid' => $uid, 'ident' => $em, 'salt' => $salt, 'hash' => hash_hmac('sha256', $otp, $salt),
                                  'exp' => time() + OTP_TTL, 'sent' => time(), 'tries' => 0, 'ok' => false];
            if ($uid && !deliver_otp($em, $otp)) { unset($_SESSION['reset']); $error = 'We could not send the code right now. Please try again later.'; }
            if (!$error) flash($act === 'resend' ? 'A new code was sent.' : 'If that account exists, we sent a 6-digit code.');
            if (!$error) go('forgot_password.php');
        }
    }

    // Step 2: check the code
    elseif ($act === 'verify' && $R && !$R['ok']) {
        $code = preg_replace('/\D/', '', $_POST['otp'] ?? '');
        $_SESSION['reset']['tries']++;
        if ($R['uid'] && hash_equals($R['hash'], hash_hmac('sha256', $code, $R['salt']))) { $_SESSION['reset']['ok'] = true; flash('Code verified. Choose a new password.'); go('forgot_password.php'); }
        $left = OTP_TRIES - $_SESSION['reset']['tries'];
        if ($left <= 0) { unset($_SESSION['reset']); $error = 'Too many wrong codes. Please start again.'; }
        else $error = "Wrong code. $left " . ($left === 1 ? 'try' : 'tries') . ' left.';
    }

    // Step 3: set the new password
    elseif ($act === 'setpw' && $R && $R['ok'] && $R['uid']) {
        $p = $_POST['password'] ?? '';
        if (strlen($p) < 4) $error = 'Password must be at least 4 characters.';
        elseif ($p !== ($_POST['password2'] ?? '')) $error = 'Passwords do not match.';
        else {
            run('UPDATE users SET password_hash=? WHERE id=? AND active=1', 'si', password_hash($p, PASSWORD_DEFAULT), $R['uid']);
            unset($_SESSION['reset']); session_regenerate_id(true);
            flash('Password changed successfully. You can log in now.'); go('login.php');
        }
    }
    $R = $_SESSION['reset'] ?? null;
}
$step = !$R ? 1 : (!$R['ok'] ? 2 : 3);
$pageTitle = 'Forgot password'; include 'includes/header.php'; ?>
<div class="card auth">
  <h2 style="margin-top:0">Forgot password</h2>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

  <?php if ($step === 1): ?>
  <p class="m">Enter the email address you registered with and we’ll send you a 6-digit code.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="send">
    <label for="ident">Email</label>
    <input id="ident" name="ident" type="email" value="<?= e($_POST['ident'] ?? '') ?>" placeholder="you@example.com" required autofocus autocapitalize="none">
    <button class="p" style="width:100%">Send code</button>
  </form>

  <?php elseif ($step === 2): ?>
  <p class="m">We emailed a 6-digit code to <b><?= e(mask_email($R['ident'])) ?></b> (if an account matches). Check your Inbox or Spam folder. It expires in 10 minutes.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify">
    <label for="otp">Enter code</label>
    <input id="otp" class="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
    <button class="p" style="width:100%">Verify code</button>
  </form>
  <div class="r" style="margin-top:12px;justify-content:space-between">
    <form class="in" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><button type="submit">Resend code</button></form>
    <form class="in" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><button type="submit">Use another</button></form>
  </div>

  <?php else: ?>
  <p class="m">Choose a new password for your account.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="setpw">
    <label for="password">New password</label><input id="password" name="password" type="password" required autofocus>
    <label for="password2">Confirm new password</label><input id="password2" name="password2" type="password" required>
    <button class="p" style="width:100%">Change password</button>
  </form>
  <?php endif; ?>

  <p class="m" style="text-align:center;margin-bottom:0"><a href="login.php">Back to log in</a></p>
</div>
<?php include 'includes/footer.php'; ?>
