<?php
require_once __DIR__ . '/app/config/database.php';

$email = 'admin@admin.com';
$pw = '123456'; // denediğin düz şifre

$stmt = $db->prepare("SELECT password, salt FROM User WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Kullanıcı yok\n");
}

$db_pw = $user['password'];
$salt = $user['salt'] ?? '';

echo "db_pw: $db_pw\n";
echo "salt: $salt\n\n";

$calc1 = hash('sha256', $salt . $pw);   // salt + password
$calc2 = hash('sha256', $pw . $salt);   // password + salt

echo "calc(salt+pw): $calc1\n";
echo "calc(pw+salt): $calc2\n\n";

echo "match salt+pw: " . (($calc1 === $db_pw) ? 'YES' : 'NO') . "\n";
echo "match pw+salt: " . (($calc2 === $db_pw) ? 'YES' : 'NO') . "\n";
