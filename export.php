<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

// eksport do CSV
$type = $_GET['type'] ?? '';

if ($type === 'customers') {
    $filename = 'klienci.csv';
    $header = ['ID', 'Nazwa', 'NIP', 'Email', 'Telefon', 'Adres', 'Aktywny'];
    $rows = $pdo->query('SELECT id, name, vat, email, phone, address, active FROM customers ORDER BY id')->fetchAll();
} elseif ($type === 'services') {
    $filename = 'uslugi.csv';
    $header = ['ID', 'Klient', 'Typ', 'Start', 'Waznosc', 'Status'];
    $today = date('Y-m-d');
    $raw = $pdo->query(
        'SELECT s.id, c.name AS customer, s.type, s.start_date, s.expiry_date
         FROM services s JOIN customers c ON c.id = s.customer_id ORDER BY s.id'
    )->fetchAll();
    $rows = [];
    foreach ($raw as $r) {
        $r['status'] = ($r['expiry_date'] >= $today) ? 'aktywna' : 'wygasla';
        $rows[] = $r;
    }
} else {
    die('Nieznany typ eksportu.');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// BOM dla polskich znakow w Excelu
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, $header);
foreach ($rows as $row) {
    fputcsv($out, array_values($row));
}
fclose($out);
exit;
