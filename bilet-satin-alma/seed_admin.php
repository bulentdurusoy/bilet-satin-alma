<?php
// seed_admin.php
require_once __DIR__ . '/app/config/database.php';

// SHA256 + salt kullanan şema: password = sha256(salt . plain)
$email   = 'admin@admin.com';
$full    = 'Yönetici Hesabı';
$role    = 'admin';
$salt    = 'mysalt123';             // dilediğin sabit salt olabilir
$plain   = '123456';
$hash    = hash('sha256', $salt . $plain);

try {
    // aynı email varsa sil (idempotent çalışsın)
    $db->prepare("DELETE FROM User WHERE email = :e")->execute([':e' => $email]);

    $stmt = $db->prepare("
        INSERT INTO User (id, full_name, email, role, password, salt, balance, created_at)
        VALUES (:id, :full, :email, :role, :pass, :salt, :bal, datetime('now'))
    ");
    $stmt->execute([
        ':id'    => 'admin_sha256',
        ':full'  => $full,
        ':email' => $email,
        ':role'  => $role,
        ':pass'  => $hash,
        ':salt'  => $salt,
        ':bal'   => 0,
    ]);

    echo "✅ Admin eklendi: {$email} / 123456";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
