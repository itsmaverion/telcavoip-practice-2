<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+30 days'));

$customers = $pdo->query('SELECT COUNT(*) FROM customers WHERE active = 1')->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM services WHERE expiry_date >= '$today'")->fetchColumn();
$expired = $pdo->query("SELECT COUNT(*) FROM services WHERE expiry_date < '$today'")->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM services WHERE expiry_date >= ? AND expiry_date <= ?');
$stmt->execute([$today, $soon]);
$expiring = $stmt->fetchColumn();

$open = $pdo->query("SELECT COUNT(*) FROM interventions WHERE status = 'open'")->fetchColumn();

$page = 'dashboard';
require 'includes/header.php';
?>

<h1>Pulpit</h1>

<div class="tiles">
  <div class="card tile">
    <div class="tile-icon" style="background:#e6eaf5; color:#0a1a4a;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="tile-text">
      <div class="tile-value"><?= (int)$customers ?></div>
      <div class="tile-label">Klienci</div>
    </div>
  </div>
  <div class="card tile">
    <div class="tile-icon" style="background:#e8f5d8; color:#6fa22c;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div class="tile-text">
      <div class="tile-value"><?= (int)$active ?></div>
      <div class="tile-label">Aktywne uslugi</div>
    </div>
  </div>
  <div class="card tile">
    <div class="tile-icon" style="background:#fdf3d6; color:#cf9a00;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div class="tile-text">
      <div class="tile-value"><?= (int)$expiring ?></div>
      <div class="tile-label">Wygasajace (30 dni)</div>
    </div>
  </div>
  <div class="card tile">
    <div class="tile-icon" style="background:#fce0e0; color:#e24b4b;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </div>
    <div class="tile-text">
      <div class="tile-value"><?= (int)$expired ?></div>
      <div class="tile-label">Uslugi wygasle</div>
    </div>
  </div>
  <div class="card tile">
    <div class="tile-icon" style="background:#e6eaf5; color:#1b2f66;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
    </div>
    <div class="tile-text">
      <div class="tile-value"><?= (int)$open ?></div>
      <div class="tile-label">Otwarte interwencje</div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
