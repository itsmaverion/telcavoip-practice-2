<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

$service_id = (int)($_GET['service'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT s.*, c.name AS customer_name
     FROM services s JOIN customers c ON c.id = s.customer_id
     WHERE s.id = ?'
);
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    die('Nie znaleziono uslugi.');
}

// historia odnowien
$stmt = $pdo->prepare(
    'SELECT r.*, u.name AS user_name
     FROM renewals r LEFT JOIN users u ON u.id = r.registered_by
     WHERE r.service_id = ?
     ORDER BY r.renewal_date DESC, r.id DESC'
);
$stmt->execute([$service_id]);
$list = $stmt->fetchAll();

$page = 'services';
require 'includes/header.php';
?>

<div class="page-head">
  <h1>Historia odnowien</h1>
  <a href="services.php" class="btn btn-secondary">&larr; Powrot do uslug</a>
</div>

<p style="color:var(--muted); margin-top:-10px;">
  Klient: <strong><?= e($service['customer_name']) ?></strong> &nbsp;|&nbsp;
  Usluga: <strong><?= e($service['type']) ?></strong> &nbsp;|&nbsp;
  Aktualna waznosc: <strong><?= e($service['expiry_date']) ?></strong>
</p>

<table>
  <thead>
    <tr><th>Data odnowienia</th><th>Poprzednia waznosc</th><th>Nowa waznosc</th><th>Wykonal</th></tr>
  </thead>
  <tbody>
    <?php foreach ($list as $r): ?>
    <tr>
      <td><?= e($r['renewal_date']) ?></td>
      <td><?= e($r['old_expiry_date']) ?></td>
      <td><?= e($r['new_expiry_date']) ?></td>
      <td><?= e($r['user_name'] ?? ('#' . $r['registered_by'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?>
      <tr><td colspan="4" style="text-align:center; color:var(--muted);">Brak odnowien dla tej uslugi</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require 'includes/footer.php'; ?>
