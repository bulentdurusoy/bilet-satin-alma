<?php
require_once __DIR__ . '/app/config/database.php';

echo "DB: $dbPath<br>";
echo file_exists($dbPath) ? "✅ Var, boyut: " . filesize($dbPath) . " bytes<br>" : "❌ Yok<br>";

$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
var_dump($tables);

$users = $db->query("SELECT id, email, role, salt FROM User LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>"; print_r($users); echo "</pre>";
