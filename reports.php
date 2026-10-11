<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+30 days'));

// 1. Uslugi wygasajace w ciagu 30 dni.
$stmt = $pdo->prepare(
    'SELECT s.*, c.name AS customer_name
     FROM services s JOIN customers c ON c.id = s.customer_id
     WHERE s.expiry_date >= ? AND s.expiry_date <= ?
     ORDER BY s.expiry_date ASC'
);
$stmt->execute([$today, $soon]);
$expiring = $stmt->fetchAll();

// 2. Otwarte interwencje (zalegle = otwarte dluzej niz 7 dni).
$open = $pdo->query(
    "SELECT i.*, c.name AS customer_name
     FROM interventions i JOIN customers c ON c.id = i.customer_id
     WHERE i.status = 'open'
     ORDER BY i.opened_at ASC"
)->fetchAll();
$overdue_limit = date('Y-m-d H:i:s', strtotime('-7 days'));

// 3. Mozliwe duplikaty klientow - ten sam NIP u wiecej niz jednego klienta.
$dups = $pdo->query(
    'SELECT vat, COUNT(*) AS cnt, GROUP_CONCAT(name SEPARATOR ", ") AS names
     FROM customers GROUP BY vat HAVING cnt > 1'
)->fetchAll();

$page = 'reports';
require 'includes/header.php';
?>

<h1>Raporty operacyjne</h1>

<h2 style="margin-top:20px;">Uslugi wygasajace w ciagu 30 dni (<?= count($expiring) ?>)</h2>
<table>
  <thead><tr><th>Klient</th><th>Typ</th><th>Data waznosci</th></tr></thead>
  <tbody>
    <?php foreach ($expiring as $s): ?>
      <tr><td><?= e($s['customer_name']) ?></td><td><?= e($s['type']) ?></td><td><?= e($s['expiry_date']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$expiring): ?><tr><td colspan="3" style="text-align:center; color:var(--muted);">Brak</td></tr><?php endif; ?>
  </tbody>
</table>

<h2 style="margin-top:30px;">Otwarte interwencje (<?= count($open) ?>)</h2>
<table>
  <thead><tr><th>Klient</th><th>Typ</th><th>Otwarta od</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($open as $i): ?>
      <?php $is_overdue = $i['opened_at'] < $overdue_limit; ?>
      <tr>
        <td><?= e($i['customer_name']) ?></td>
        <td><?= e($i['type']) ?></td>
        <td><?= e($i['opened_at']) ?></td>
        <td>
          <?php if ($is_overdue): ?><span class="badge badge-expired">zalegla</span>
          <?php else: ?><span class="badge badge-open">otwarta</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$open): ?><tr><td colspan="4" style="text-align:center; color:var(--muted);">Brak</td></tr><?php endif; ?>
  </tbody>
</table>

<h2 style="margin-top:30px;">Mozliwe duplikaty klientow (<?= count($dups) ?>)</h2>
<table>
  <thead><tr><th>NIP</th><th>Ile razy</th><th>Klienci</th></tr></thead>
  <tbody>
    <?php foreach ($dups as $d): ?>
      <tr><td><?= e($d['vat']) ?></td><td><?= (int)$d['cnt'] ?></td><td><?= e($d['names']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$dups): ?><tr><td colspan="3" style="text-align:center; color:var(--muted);">Brak duplikatow</td></tr><?php endif; ?>
  </tbody>
</table>

<?php require 'includes/footer.php'; ?>
