<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';

class AuthController {

    // 🔐 Kullanıcı Giriş
    public static function login($email, $password) {
        global $db;

        $stmt = $db->prepare("SELECT * FROM User WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false; // kullanıcı bulunamadı
        }

        // --- SHA256 kontrollü eski kayıtlar ---
        if (!empty($user['salt']) && strlen($user['password']) === 64) {
            $calc = hash('sha256', $user['salt'] . $password);
            if (hash_equals($user['password'], $calc)) {
                $_SESSION['user'] = $user;
                return true; // yönlendirmeyi login.php yapacak
            }
        }

        // --- bcrypt kontrollü modern kayıtlar ---
        if (str_starts_with($user['password'], '$2y$')) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                return true; // yönlendirme yok
            }
        }

        return false; // eşleşme olmadı
    }

    // 🧾 Yeni Kullanıcı Kaydı
    public static function register($data) {
        global $db;

        $full_name = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($full_name === '' || $email === '' || $password === '') {
            return false;
        }

        // Aynı e-posta var mı?
        $check = $db->prepare("SELECT id FROM User WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            return false;
        }

        // bcrypt hash
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("
            INSERT INTO User (id, full_name, email, password, role, created_at)
            VALUES (:id, :full_name, :email, :password, 'user', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':password' => $hash
        ]);

        return true;
    }

    // 🚪 Oturum kapatma
    public static function logout() {
        session_destroy();
        header("Location: /bilet-satin-alma/public/index.php?page=login");
        exit;
    }
}
