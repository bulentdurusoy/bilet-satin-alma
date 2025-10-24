<?php
require_once __DIR__ . '/../config/database.php';

class CouponController {
    public static function create($data) {
        global $db;
        $stmt = $db->prepare("
            INSERT INTO Coupon (id, code, discount_percent, valid_from, valid_to, usage_limit)
            VALUES (:id, :code, :discount, :from, :to, :limit)
        ");
        $stmt->execute([
            ':id' => uniqid('c_'),
            ':code' => strtoupper(trim($data['code'])),
            ':discount' => (float)$data['discount_percent'],
            ':from' => $data['valid_from'] ?? null,
            ':to' => $data['valid_to'] ?? null,
            ':limit' => (int)($data['usage_limit'] ?? 1)
        ]);
    }

    public static function delete($id) {
        global $db;
        $stmt = $db->prepare("DELETE FROM Coupon WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public static function update($data) {
        global $db;
        $stmt = $db->prepare("
            UPDATE Coupon SET
                code = :code,
                discount_percent = :discount,
                valid_from = :from,
                valid_to = :to,
                usage_limit = :limit,
                status = :status
            WHERE id = :id
        ");
        $stmt->execute([
            ':code' => strtoupper(trim($data['code'])),
            ':discount' => (float)$data['discount_percent'],
            ':from' => $data['valid_from'] ?? null,
            ':to' => $data['valid_to'] ?? null,
            ':limit' => (int)($data['usage_limit'] ?? 1),
            ':status' => $data['status'] ?? 'active',
            ':id' => $data['id']
        ]);
    }
}
?>
