<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?page=login');
        exit;
    }
}
function currentUser() {
    return $_SESSION['user'] ?? null;
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['user']['role'], (array)$roles)) {
        die('Bu sayfaya erişim yetkiniz yok.');
    }
}
