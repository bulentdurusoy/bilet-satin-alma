<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/session.php';

if (!isLoggedIn()) {
    header("Location: /bilet-satin-alma/public/index.php?page=login");
    exit;
}

$user = currentUser();
$tripId = $_GET['id'] ?? null;

if (!$tripId) {
    echo "<p style='color:red;'>Sefer ID bulunamadı.</p>";
    exit;
}

// 🚌 Sefer bilgisi
$stmt = $db->prepare("SELECT t.*, c.name AS company_name 
                      FROM Trip t 
                      LEFT JOIN Company c ON c.id = t.company_id 
                      WHERE t.id = :tid");
$stmt->execute([':tid' => $tripId]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    echo "<p style='color:red;'>Sefer bulunamadı.</p>";
    exit;
}

// 🔴 Dolu koltuklar
$stmt = $db->prepare("SELECT seat_number FROM Booked_Seats WHERE trip_id = :tid");
$stmt->execute([':tid' => $tripId]);
$bookedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Koltuk Seçimi - <?= htmlspecialchars($trip['company_name']) ?></title>
<style>
body { font-family: Arial; background:#f7f9fb; color:#222; }
.container { max-width: 800px; margin: 30px auto; padding: 20px; background:white; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#1e3a8a; }
.bus-layout { display:flex; flex-wrap:wrap; max-width:300px; margin:auto; justify-content:center; }
.seat {
  width:40px; height:40px; margin:6px; border-radius:6px;
  display:flex; align-items:center; justify-content:center;
  font-weight:bold; cursor:pointer; color:white;
}
.seat.available { background:#22c55e; }
.seat.available:hover { background:#16a34a; }
.seat.taken { background:#dc2626; cursor:not-allowed; opacity:0.8; }
.seat.selected { background:#2563eb; }
.legend { text-align:center; margin-top:10px; }
.legend span { display:inline-block; width:15px; height:15px; border-radius:3px; margin-right:5px; }
button {
  background:#1e3a8a; color:white; border:none; padding:10px 20px;
  border-radius:8px; cursor:pointer; margin-top:20px;
}
button:hover { background:#172554; }
</style>
</head>
<body>
<div class="container">
  <h2>🎟️ Koltuk Seçimi</h2>
  <p>
    <strong><?= htmlspecialchars($trip['company_name']) ?></strong><br>
    <?= htmlspecialchars($trip['from_city']) ?> → <?= htmlspecialchars($trip['to_city']) ?><br>
    Kalkış: <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?> |
    Fiyat: <?= number_format($trip['price'], 2) ?> ₺
  </p>

  <form method="POST" action="/bilet-satin-alma/public/index.php?page=select_seat">
    <input type="hidden" name="id" value="<?= $tripId ?>">
    <input type="hidden" id="selectedSeat" name="seat_number">

    <div class="bus-layout">
      <?php
      $capacity = (int)$trip['capacity'];
      for ($i = 1; $i <= $capacity; $i++) {
          $taken = in_array($i, $bookedSeats);
          $class = $taken ? 'taken' : 'available';
          $disabled = $taken ? 'style="pointer-events:none;"' : '';
          echo "<div class='seat $class' data-seat='$i' $disabled>$i</div>";
      }
      ?>
    </div>

    <div class="legend">
      <p>
        <span style="background:#22c55e"></span> Boş
        <span style="background:#dc2626"></span> Dolu
        <span style="background:#2563eb"></span> Seçili
      </p>
    </div>

    <p style="text-align:center;">
      Kupon (isteğe bağlı): <input type="text" name="coupon" placeholder="ORNEK10">
    </p>

    <div style="text-align:center;">
      <button type="submit">Satın Al</button>
    </div>
  </form>
</div>

<script>
document.querySelectorAll('.seat.available').forEach(seat => {
  seat.addEventListener('click', () => {
    document.querySelectorAll('.seat').forEach(s => s.classList.remove('selected'));
    seat.classList.add('selected');
    document.getElementById('selectedSeat').value = seat.dataset.seat;
  });
});
</script>
</body>
</html>
