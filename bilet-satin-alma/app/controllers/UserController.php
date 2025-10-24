<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

class UserController {
 public static function balance() {
    requireLogin();
    global $db;

    $user = currentUser();
    $message = $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            $error = 'Geçerli bir tutar girin.';
        } else {
            $stmt = $db->prepare("UPDATE User SET balance = balance + :amt WHERE id = :uid");
            $stmt->execute([':amt' => $amount, ':uid' => $user['id']]);

            // Oturumdaki bakiyeyi güncelle
            $_SESSION['user']['balance'] = $_SESSION['user']['balance'] + $amount;

            // 💬 Yönlendirme ekledik
            header("Location: index.php?page=balance&success=1");
            exit;
        }
    }

    include __DIR__ . '/../views/user/balance.php';
}

}
