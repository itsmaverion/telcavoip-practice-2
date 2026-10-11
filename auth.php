<?php
session_start();

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_role()
{
    return $_SESSION['role'] ?? null;
}

// dostep tylko dla podanych rol, reszta dostaje 403
function require_role(...$allowed)
{
    require_login();
    if (!in_array(current_role(), $allowed, true)) {
        if (isset($GLOBALS['pdo']) && function_exists('write_audit')) {
            write_audit($GLOBALS['pdo'], $_SESSION['user_id'], 'denied', 'access', 0);
        }
        http_response_code(403);
        die('Brak uprawnien do tej operacji.');
    }
}

function can_edit()
{
    return in_array(current_role(), ['administrator', 'operator'], true);
}
