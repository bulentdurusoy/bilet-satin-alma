<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../controllers/FirmaAdminController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    FirmaAdminController::store($_POST);
}

if (isset($_GET['delete'])) {
    FirmaAdminController::delete($_GET['delete']);
}

$user = currentUser();
?>

<h2><?= htmlspecialchars($user['full_name']) ?> - Sefer Yönetimi</h2>
<p><a href="index.php">Ana Sayfa</a> | <a href="index.php?page=logout">Çıkış Yap</a></p>

<hr>

<h3>Yeni Sefer Ekle</h3>
<form method="POST">
    <label>Kalkış Şehri:</label>
    <input type="text" name="departure_city" required><br>

    <label>Varış Şehri:</label>
    <input type="text" name="destination_city" required><br>

    <label>Kalkış Saati:</label>
    <input type="datetime-local" name="departure_time" required><br>

    <label>Varış Saati:</label>
    <input type="datetime-local" name="arrival_time" required><br>

    <label>Fiyat (₺):</label>
    <input type="number" name="price" required><br>

    <label>Kapasite:</label>
    <input type="number" name="capacity" required><br>

    <button type="submit">Sefer Ekle</button>
</form>

<hr>

<h3>Mevcut Seferler</h3>

<?php
global $db;
$stmt = $db->prepare("SELECT * FROM Trips WHERE company_id = :cid");
$stmt->execute([':cid' => $user['company_id']]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($trips):
?>
<table border="1" cellpadding="5">
    <tr>
        <th>Kalkış</th><th>Varış</th><th>Kalkış Saati</th><th>Fiyat</th><th>Kapasite</th><th>Sil</th>
    </tr>
    <?php foreach ($trips as $trip): ?>
        <tr>
            <td><?= htmlspecialchars($trip['departure_city']) ?></td>
            <td><?= htmlspecialchars($trip['destination_city']) ?></td>
            <td><?= htmlspecialchars(date('H:i d.m.Y', strtotime($trip['departure_time']))) ?></td>
            <td><?= htmlspecialchars($trip['price']) ?> ₺</td>
            <td><?= htmlspecialchars($trip['capacity']) ?></td>
            <td><a href="index.php?page=firma_trips&delete=<?= $trip['id'] ?>">🗑 Sil</a></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
    <p>Henüz sefer eklenmemiş.</p>
<?php endif; ?>
