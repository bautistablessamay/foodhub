<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
const CATS = ['ulam' => '🍛 Ulam', 'dessert' => '🍮 Dessert', 'drinks' => '🥤 Drinks'];

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function peso($n) { return '₱' . number_format((float)$n, 2); }
function go($url) { header('Location: ' . $url); exit; }
// flash('text') = green success note, flash('text', 'bad') = red error note
function flash($m = null, $type = 'ok') { if ($m !== null) { $_SESSION['flash'] = [$m, $type === 'bad' ? 'bad' : 'ok']; return null; } $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function csrf_field() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">'; }
function csrf_ok() { return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']); }
function home_for_role($r) { return BASE_URL . '/' . $r . '/dashboard.php'; }

// Prepared-statement helpers: rows() returns all rows, run() executes and returns insert id
function rows($sql, $t = '', ...$a) { global $db; $s = $db->prepare($sql); if ($t) $s->bind_param($t, ...$a); $s->execute(); $r = $s->get_result(); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; }
function run($sql, $t = '', ...$a) { global $db; $s = $db->prepare($sql); if ($t) $s->bind_param($t, ...$a); $s->execute(); return $s->insert_id; }

function require_role($role) {
    if (!empty($_SESSION['user'])) {
        $u = rows('SELECT active, role FROM users WHERE id=?', 'i', $_SESSION['user']['id'])[0] ?? null;
        if ($u && $u['active'] && $u['role'] === $role) return;
        if (!$u || !$u['active']) $_SESSION = [];   // deactivated -> logged out
    }
    go(BASE_URL . '/login.php');
}
