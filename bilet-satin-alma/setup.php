<?php
// setup.php
require_once __DIR__ . '/config/database.php';

$sqlFile = __DIR__ . '/database/seed.sql';
$sql = file_get_contents($sqlFile);

try {
    $db->exec($sql);
    echo "<h3>✅ Veritabanı başarıyla oluşturuldu!</h3>";
} catch (PDOException $e) {
    echo "<h3>❌ Hata oluştu: </h3>" . $e->getMessage();
}
?>
