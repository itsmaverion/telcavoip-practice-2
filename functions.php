<?php

function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// zapis do dziennika - wpisy tylko dodajemy
function write_audit($pdo, $actor_id, $action, $entity, $entity_id)
{
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (actor_id, action, entity, entity_id) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$actor_id, $action, $entity, $entity_id]);
}

function role_name($role)
{
    $names = [
        'administrator' => 'Administrator',
        'operator' => 'Operator',
        'technician' => 'Technik',
    ];
    return $names[$role] ?? $role;
}
