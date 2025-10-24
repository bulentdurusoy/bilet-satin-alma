<?php
require_once __DIR__ . '/../app/config.php';
requireLogin();
global $db;

$user = currentUser();

// Kullanıcıya ait biletleri getir
$stmt = $db->prepare("
    SELECT 
        t.id AS ticket_id,
        t.seat_numbers,
        t.total_price,
        s.departure_city,
        s.arrival_city,
        s.departure_time,
        s.arrival_time
    FROM Tickets t
    JOIN Trips s ON t.trip_id = s.id
    WHERE t.user_id = :uid
    ORDER BY s.departure_time DESC
");
$stmt->execute([':uid' => $user['id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Biletlerim</title>
</head>
<body>
<h1>Biletlerim</h1>
<p><a href="index.php">Ana Sayfa</a> | <a href="logout.php">Çıkış Yap</a></p>
<hr>

<?php if (!$tickets): ?>
    <p>Henüz bilet satın almadınız.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Sefer</th>
            <th>Kalkış</th>
            <th>Varış</th>
            <th>Koltuklar</th>
            <th>Toplam</th>
            <th>İşlem</th>
        </tr>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['arrival_city']) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($t['arrival_time'])) ?></td>
                <td><?= htmlspecialchars($t['seat_numbers']) ?></td>
                <td><?= number_format($t['total_price'], 2) ?> ₺</td>
                <td>
                    <a href="bilet_pdf.php?id=<?= urlencode($t['ticket_id']) ?>">🧾 PDF</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</body>
</html>
