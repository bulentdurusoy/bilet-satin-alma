<?php
// app/config/database.php

// Proje kök dizinindeki veritabanı dosyasına bağlan
$dbPath = dirname(__DIR__, 2) . '/database.sqlite'; // => C:\xampp\htdocs\bilet-satin-alma\database.sqlite

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}
