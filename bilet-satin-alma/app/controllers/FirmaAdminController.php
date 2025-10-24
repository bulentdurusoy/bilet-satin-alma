<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

class FirmaAdminController {

    public static function index() {
        global $db;
        $user = currentUser();

        if ($user['role'] !== 'company') {
            die('Yetkisiz erişim.');
        }

        $stmt = $db->prepare("SELECT * FROM Trips WHERE company_id = :cid");
        $stmt->execute([':cid' => $user['company_id']]);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../views/firma/trips.php';
    }

    public static function store($data) {
        global $db;
        $user = currentUser();

        if ($user['role'] !== 'company') {
            die('Yetkisiz işlem.');
        }

        $stmt = $db->prepare("
            INSERT INTO Trips (id, company_id, departure_city, destination_city, 
                               departure_time, arrival_time, price, capacity)
            VALUES (:id, :company_id, :dep, :dest, :dep_time, :arr_time, :price, :capacity)
        ");

        $stmt->execute([
            ':id' => uniqid('t_'),
            ':company_id' => $user['company_id'],
            ':dep' => $data['departure_city'],
            ':dest' => $data['destination_city'],
            ':dep_time' => $data['departure_time'],
            ':arr_time' => $data['arrival_time'],
            ':price' => $data['price'],
            ':capacity' => $data['capacity']
        ]);

        header("Location: /bilet-satin-alma/public/index.php?page=firma_trips");
    }

    public static function delete($id) {
        global $db;
        $stmt = $db->prepare("DELETE FROM Trips WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: /bilet-satin-alma/public/index.php?page=company_dashboard");
    }
}
?>
