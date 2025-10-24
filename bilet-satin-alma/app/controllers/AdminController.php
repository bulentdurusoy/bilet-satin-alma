<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

class AdminController {

    private static function checkAdmin() {
        requireLogin();
        if ($_SESSION['user']['role'] !== 'admin') {
            die('Bu sayfaya erişim yetkiniz yok.');
        }
        global $db;
        return $db;
    }

    public static function trips() {
        $db = self::checkAdmin();
        $rows = $db->query("SELECT * FROM Trips ORDER BY departure_time DESC")->fetchAll(PDO::FETCH_ASSOC);
        include __DIR__ . '/../views/admin/trips.php';
    }

    public static function users() {
        $db = self::checkAdmin();
        $rows = $db->query("SELECT id, full_name, email, role, balance FROM User")->fetchAll(PDO::FETCH_ASSOC);
        include __DIR__ . '/../views/admin/users.php';
    }

  public static function companies() {
    $db = self::checkAdmin();

    // Firma ekleme
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'add') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name !== '') {
            $stmt = $db->prepare("INSERT INTO Company (name, phone, email, created_at) VALUES (:n, :p, :e, datetime('now'))");
            $stmt->execute([':n' => $name, ':p' => $phone, ':e' => $email]);
            header("Location: index.php?page=admin_companies");
            exit;
        }
    }

    // Firma silme
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $stmt = $db->prepare("DELETE FROM Company WHERE id = :id");
        $stmt->execute([':id' => (int)$_GET['id']]);
        header("Location: index.php?page=admin_companies");
        exit;
    }

    // Firma düzenleme
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'edit' && isset($_GET['id'])) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name !== '') {
            $stmt = $db->prepare("UPDATE Company SET name = :n, phone = :p, email = :e WHERE id = :id");
            $stmt->execute([
                ':n' => $name,
                ':p' => $phone,
                ':e' => $email,
                ':id' => (int)$_GET['id']
            ]);
            header("Location: index.php?page=admin_companies");
            exit;
        }
    }

    // Listeleme
    $rows = $db->query("SELECT * FROM Company ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    include __DIR__ . '/../views/admin/companies.php';
}



    public static function coupons() {
        $db = self::checkAdmin();
        $rows = $db->query("SELECT * FROM Coupons")->fetchAll(PDO::FETCH_ASSOC);
        include __DIR__ . '/../views/admin/coupons.php';
    }
}
?>
