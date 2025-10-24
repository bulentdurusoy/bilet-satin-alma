<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DATABASE CONNECTION
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}

// ---- HELPER FUNCTIONS ----
function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?page=login');
        exit;
    }
}


function currentUser() {
    return $_SESSION['user'] ?? null;
}
