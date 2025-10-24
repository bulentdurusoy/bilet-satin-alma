<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı oturumu var mı?
function isLoggedIn() {
    return isset($_SESSION['user']);
}



// Oturumu kapat
function logout() {
    session_destroy();
    $_SESSION = [];
}
