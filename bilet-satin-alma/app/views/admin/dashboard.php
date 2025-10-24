<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireRole(['admin']);

// ====== Genel İstatistikler ======
$stats = [
    'users' => 0,
    'trips' => 0,
    'tickets' => 0,
    'booked_seats' => 0
];

try {
    $stats['users'] = (int)$db->query("SELECT COUNT(*) FROM User")->fetchColumn();
    $stats['trips'] = (int)$db->query("SELECT COUNT(*) FROM Trip")->fetchColumn();
    $stats['tickets'] = (int)$db->query("SELECT COUNT(*) FROM Ticket")->fetchColumn();
    $stats['booked_seats'] = (int)$db->query("SELECT COUNT(*) FROM Booked_Seats")->fetchColumn();
} catch (PDOException $e) {
    echo "<p style='color:red'>Veri okunamadı: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ====== Grafik 1: Son 7 Günlük Bilet Satışı ======
$salesData = [];
try {
    $query = $db->query("
        SELECT strftime('%Y-%m-%d', created_at) as day, COUNT(*) as count
        FROM Ticket
        WHERE created_at >= date('now', '-7 day')
        GROUP BY day
        ORDER BY day ASC
    ");
    $salesData = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $salesData = [];
}

// ====== Grafik 2: Şehirlere Göre Sefer Dağılımı ======
$tripData = [];
try {
    $query = $db->query("
        SELECT departure_city, COUNT(*) as count
        FROM Trip
        GROUP BY departure_city
        ORDER BY count DESC
        LIMIT 7
    ");
    $tripData = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tripData = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Admin Paneli</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  body { font-family: Arial, sans-serif; margin: 24px; background:#fafafa; }
  h1 { color: #333; }
  .stats { display:flex; flex-wrap:wrap; gap:20px; margin-top:20px; }
  .stat-box {
    flex:1;
    background:#fff;
    border-radius:10px;
    padding:20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align:center;
    min-width:200px;
  }
  .stat-box h3 { margin:0; font-size:18px; color:#666; }
  .stat-box p { margin:8px 0 0; font-size:28px; font-weight:bold; color:#1a73e8; }
  .panel-links { display:flex; flex-wrap:wrap; gap:20px; margin-top:40px; justify-content: center; }
  .card {
    border: 1px solid #ccc;
    border-radius: 10px;
    padding: 20px;
    width: 260px;
    background: #fff;
    box-shadow: 2px 2px 8px rgba(0,0,0,0.05);
    transition: transform 0.2s;
  }
  .card:hover { transform: translateY(-4px); background: #f4f8ff; }
  .card a { text-decoration: none; color: #1a73e8; font-weight: bold; }
  .logout { margin-top: 30px; display: inline-block; color: #d00; text-decoration: none; }

  .charts { margin-top: 50px; display: flex; justify-content: center; }
  canvas { background: #fff; border-radius: 10px; padding: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .links{
    display: flex;
    justify-content: space-between;
  }
</style>
</head>
<body>
<div><h1>Yönetici Paneli</h1></div>
<div class="links">

<div><p>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Yönetici Hesabı') ?></strong></p></div>
<div>
<a href="index.php?page=logout" class="logout">Çıkış Yap</a>

</div>
</div>

<!-- İstatistik Kutuları -->
<div class="stats">
  <div class="stat-box"><h3>Kullanıcı Sayısı</h3><p><?= $stats['users'] ?></p></div>
  <div class="stat-box"><h3>Sefer Sayısı</h3><p><?= $stats['trips'] ?></p></div>
  <div class="stat-box"><h3>Bilet Sayısı</h3><p><?= $stats['tickets'] ?></p></div>
  <div class="stat-box"><h3>Rezerve Koltuklar</h3><p><?= $stats['booked_seats'] ?></p></div>
</div>

<!-- Yönetim Kartları -->
<div class="panel-links">
  <div class="card">
    <h3>Firmalar</h3>
    <p>Otobüs firmalarını listele, ekle veya düzenle.</p>
    <a href="index.php?page=admin_companies">Firmaları Yönet</a>
  </div>

  <div class="card">
    <h3>Firma Adminleri</h3>
    <p>Firma adminleri oluştur, ata veya güncelle.</p>
    <a href="index.php?page=admin_company_admins">Firma Adminlerini Yönet</a>
  </div>

  <div class="card">
    <h3>Kupon Yönetimi</h3>
    <p>İndirim kuponlarını oluştur veya düzenle.</p>
    <a href="index.php?page=admin_coupons">Kuponları Yönet</a>
  </div>
</div>

<!-- Grafikler -->
<div class="charts">
  <div>
    <h3>Son 7 Günlük Bilet Satışları</h3>
    <canvas id="ticketChart"></canvas>
  </div>
</div>


<script>
const salesLabels = <?= json_encode(array_column($salesData, 'day')) ?>;
const salesCounts = <?= json_encode(array_column($salesData, 'count')) ?>;
const tripLabels = <?= json_encode(array_column($tripData, 'departure_city')) ?>;
const tripCounts = <?= json_encode(array_column($tripData, 'count')) ?>;

new Chart(document.getElementById('ticketChart'), {
  type: 'line',
  data: {
    labels: salesLabels,
    datasets: [{
      label: 'Bilet Satışları',
      data: salesCounts,
      borderColor: '#1a73e8',
      backgroundColor: 'rgba(26,115,232,0.2)',
      fill: true,
      tension: 0.3
    }]
  }
});

</script>

</body>
</html>
