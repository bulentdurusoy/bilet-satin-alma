<?php
require_once __DIR__ . '/config/database.php';

$id = 'u_firma1';
$full_name = 'Firma Admin';
$email = 'firma@firma.com';
$role = 'company';
$password = password_hash('123456', PASSWORD_DEFAULT);
$company_id = 'c1';

$stmt = $db->prepare("
    INSERT INTO User (id, full_name, email, role, password, company_id)
    VALUES (:id, :full_name, :email, :role, :password, :company_id)
");

$stmt->execute([
    ':id' => $id,
    ':full_name' => $full_name,
    ':email' => $email,
    ':role' => $role,
    ':password' => $password,
    ':company_id' => $company_id
]);

echo "✅ Firma admin başarıyla eklendi!";
