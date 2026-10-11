<?php
// Obsluga logowania i uprawnien (RBAC) na sesjach PHP.
session_start();

// Czy uzytkownik jest zalogowany?
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Wymaga zalogowania - jak nie, przekierowuje na logowanie.
function require_login()
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Rola aktualnego uzytkownika.
function current_role()
{
    return $_SESSION['role'] ?? null;
}

// Sprawdza czy uzytkownik ma jedna z dozwolonych rol.
// Domyslnie odmawiamy (deny by default) - jak rola nie pasuje, blokujemy.
function require_role(...$allowed)
{
    require_login();
    if (!in_array(current_role(), $allowed, true)) {
        // Zapisujemy nieudana probe dostepu do dziennika (jesli baza dostepna).
        if (isset($GLOBALS['pdo']) && function_exists('write_audit')) {
            write_audit($GLOBALS['pdo'], $_SESSION['user_id'], 'denied', 'access', 0);
        }
        http_response_code(403);
        die('Brak uprawnien do tej operacji.');
    }
}

// Czy moze edytowac dane (admin lub operator)?
function can_edit()
{
    return in_array(current_role(), ['administrator', 'operator'], true);
}
