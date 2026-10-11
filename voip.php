<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

$error = '';

$statuses = ['active' => 'aktywne', 'suspended' => 'zawieszone', 'terminated' => 'zakonczone'];

// nowe konto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    require_role('administrator', 'operator');
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $number = trim($_POST['number'] ?? '');
    $status = $_POST['status'] ?? 'active';

    $stmt = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
    $stmt->execute([$customer_id]);
    if (!$stmt->fetch()) {
        $error = 'Wybrany klient nie istnieje';
    } elseif (!isset($statuses[$status])) {
        $error = 'Nieznany status';
    } else {
        $stmt = $pdo->prepare('INSERT INTO voip_accounts (customer_id, number, status) VALUES (?, ?, ?)');
        $stmt->execute([$customer_id, $number, $status]);
        write_audit($pdo, $_SESSION['user_id'], 'create', 'voip', $pdo->lastInsertId());
        redirect('voip.php?ok=1');
    }
}

// edycja - mozna tez przepiac na innego klienta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    require_role('administrator', 'operator');
    $id = (int)($_POST['id'] ?? 0);
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $number = trim($_POST['number'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM voip_accounts WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
    $stmt->execute([$customer_id]);

    if (!$current) {
        $error = 'Nie znaleziono konta VoIP';
    } elseif (!$stmt->fetch()) {
        $error = 'Wybrany klient nie istnieje';
    } else {
        $stmt = $pdo->prepare('UPDATE voip_accounts SET customer_id = ?, number = ? WHERE id = ?');
        $stmt->execute([$customer_id, $number, $id]);
        // zmiana klienta -> osobny wpis w audycie
        if ((int)$current['customer_id'] !== $customer_id) {
            write_audit($pdo, $_SESSION['user_id'], 'reassign', 'voip', $id);
        }
        write_audit($pdo, $_SESSION['user_id'], 'update', 'voip', $id);
        redirect('voip.php?ok=1');
    }
}

// zmiana statusu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    require_role('administrator', 'operator');
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    if (isset($statuses[$status])) {
        $stmt = $pdo->prepare('UPDATE voip_accounts SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        write_audit($pdo, $_SESSION['user_id'], 'update', 'voip', $id);
    }
    redirect('voip.php?ok=1');
}

$success = isset($_GET['ok']) ? 'Zapisano zmiany' : '';

$list = $pdo->query(
    'SELECT v.*, c.name AS customer_name
     FROM voip_accounts v
     JOIN customers c ON c.id = v.customer_id
     ORDER BY v.created_at DESC'
)->fetchAll();

$customers = $pdo->query('SELECT id, name FROM customers WHERE active = 1 ORDER BY name')->fetchAll();

// dane do edycji
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM voip_accounts WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$page = 'voip';
require 'includes/header.php';
?>

<div class="page-head">
  <h1>Konta VoIP</h1>
  <?php if (can_edit()): ?>
    <a href="#" class="btn" onclick="openModal('modal'); return false;">+ Dodaj konto</a>
  <?php endif; ?>
</div>

<?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ok-msg"><?= e($success) ?></div><?php endif; ?>

<table>
  <thead>
    <tr><th>Numer</th><th>Klient</th><th>Status</th><?php if (can_edit()): ?><th>Zmien status</th><?php endif; ?></tr>
  </thead>
  <tbody>
    <?php foreach ($list as $v): ?>
    <tr>
      <td><?= e($v['number']) ?></td>
      <td><?= e($v['customer_name']) ?></td>
      <td><span class="badge <?= $v['status'] === 'active' ? 'badge-active' : 'badge-closed' ?>"><?= e($statuses[$v['status']]) ?></span></td>
      <?php if (can_edit()): ?>
      <td>
        <form method="post" style="display:flex; gap:6px;">
          <input type="hidden" name="action" value="status">
          <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
          <select name="status" style="width:140px;">
            <?php foreach ($statuses as $key => $label): ?>
              <option value="<?= $key ?>" <?= $v['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-small" type="submit">OK</button>
          <a class="btn btn-small btn-secondary" href="voip.php?edit=<?= (int)$v['id'] ?>">Edytuj</a>
        </form>
      </td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?>
      <tr><td colspan="4" style="text-align:center; color:var(--muted);">Brak kont VoIP</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php if (can_edit()): ?>
<div class="modal-bg" id="modal">
  <div class="modal">
    <h2>Nowe konto VoIP</h2>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <label>Klient</label>
        <select name="customer_id" required>
          <option value="">-- wybierz klienta --</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label>Numer / identyfikator</label><input type="text" name="number" required></div>
      <div class="form-row">
        <label>Status</label>
        <select name="status">
          <?php foreach ($statuses as $key => $label): ?><option value="<?= $key ?>"><?= $label ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="voip.php">Anuluj</a>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</div>

<!-- Okno edycji konta (przepiecie klienta / zmiana numeru) -->
<div class="modal-bg <?= $edit ? 'show' : '' ?>" id="modal-edit">
  <div class="modal">
    <h2>Edytuj konto VoIP</h2>
    <form method="post" onsubmit="return confirm('Zapisac zmiany? Zmiana klienta zostanie odnotowana w audycie.');">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
      <div class="form-row">
        <label>Klient</label>
        <select name="customer_id" required>
          <?php foreach ($customers as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($edit && (int)$edit['customer_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label>Numer / identyfikator</label><input type="text" name="number" value="<?= e($edit['number'] ?? '') ?>" required></div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="voip.php">Anuluj</a>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
