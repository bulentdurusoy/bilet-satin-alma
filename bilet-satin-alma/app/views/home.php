<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';

$user = currentUser();

if ($user) {
    // Kullanıcı güncel bakiye ve rol bilgisi
    $stmt = $db->prepare("SELECT * FROM User WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user'] = $user;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bilet Satın Alma Platformu</title>
<style>
body { margin: 0; font-family: Arial, sans-serif; background: #fafafa; color: #333; }
.navbar {
  background-color: #1a73e8;
  color: white;
  padding: 12px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
}
.navbar a {
  color: white;
  text-decoration: none;
  margin: 0 10px;
  font-weight: bold;
}
.navbar a:hover { text-decoration: underline; }
.navbar .user-info { font-weight: normal; color: #fff; font-size: 14px; }
.nav-links { display: flex; align-items: center; gap: 8px; }

.container { padding: 24px; max-width: 900px; margin: auto; }
h1 { color: #222; }
form { margin-top: 16px; }
input, button { padding: 8px; margin: 4px; border-radius: 4px; border: 1px solid #ccc; }
button { background: #1a73e8; color: white; border: none; cursor: pointer; }
button:hover { background: #1559b2; }

table { border-collapse: collapse; width: 100%; margin-top: 16px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background: #f5f5f5; }

@media (max-width: 600px) {
  .nav-links { flex-direction: column; align-items: flex-start; }
  table, thead, tbody, tr, th, td { display: block; }
  tr { margin-bottom: 12px; border: 1px solid #ddd; border-radius: 6px; padding: 8px; }
  td { border: none; padding-left: 40%; position: relative; text-align: right; }
  td::before { content: attr(data-label); position: absolute; left: 8px; font-weight: bold; }
}
</style>
</head>
<body>

<!-- 🧭 NAVBAR -->
<div class="navbar">
  <div class="logo">
    🚌 <strong>Bilet Platformu</strong>
  </div>

  <div class="nav-links">
    <a href="/bilet-satin-alma/public/index.php?page=home">Ana Sayfa</a>
    <a href="/bilet-satin-alma/public/index.php?page=tickets">Biletlerim</a>
    <?php if (isLoggedIn()): ?>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="index.php?page=admin_dashboard">Admin Paneli</a>
      <?php elseif ($user['role'] === 'company_admin'): ?>
        <a href="index.php?page=company_dashboard">Firma Paneli</a>
      <?php endif; ?>
      <span class="user-info">
        👤 <?= htmlspecialchars($user['full_name']) ?> — 💰 <?= number_format($user['balance'], 2) ?> ₺
      </span>
      <a href="/bilet-satin-alma/public/index.php?page=logout">Çıkış</a>
    <?php else: ?>
      <a href="/bilet-satin-alma/public/index.php?page=login">Giriş Yap</a>
      <a href="/bilet-satin-alma/public/index.php?page=register">Kayıt Ol</a>
    <?php endif; ?>
  </div>
</div>

<div class="container">
<h1>🚌 Bilet Satın Alma Platformu</h1>

<h2>🔍 Sefer Ara</h2>
<form method="GET" action="">
  <input type="hidden" name="page" value="home">
  <label>Nereden:</label>
  <input type="text" name="departure_city" placeholder="Kalkış Şehri" required>
  <label>Nereye:</label>
  <input type="text" name="destination_city" placeholder="Varış Şehri" required>
  <button type="submit">Ara</button>
</form>

<?php if (isset($_SESSION['flash'])): ?>
  <p style="padding:10px; border-radius:6px; <?= $_SESSION['flash']['type'] === 'ok' ? 'background:#e9f7ef;color:#1e7e34;' : 'background:#fdecea;color:#a71d2a;' ?>">
    <?= $_SESSION['flash']['msg'] ?>
  </p>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<hr>

<?php
// --- Sefer arama ---
if (!empty($_GET['departure_city']) && !empty($_GET['destination_city'])) {
    $from = trim($_GET['departure_city']);
    $to = trim($_GET['destination_city']);

    $stmt = $db->prepare("
        SELECT t.*, c.name AS company_name
        FROM Trip t
        LEFT JOIN Company c ON t.company_id = c.id
        WHERE t.from_city LIKE :from AND t.to_city LIKE :to AND t.status = 'active'
        ORDER BY t.departure_time ASC
    ");
    $stmt->execute([':from' => "%$from%", ':to' => "%$to%"]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>📅 Arama Sonuçları</h3>";

    if (!$trips) {
        echo "<p style='color:red'>❌ Sefer bulunamadı.</p>";
    } else {
        echo "<table><tr><th>Firma</th><th>Güzergah</th><th>Kalkış</th><th>Varış</th><th>Fiyat</th><th>İşlem</th></tr>";
        foreach ($trips as $trip) {
    echo "<tr>
            <td data-label='Firma'>" . htmlspecialchars($trip['company_name'] ?? 'Bilinmiyor') . "</td>
            <td data-label='Güzergah'>" . htmlspecialchars($trip['from_city']) . " → " . htmlspecialchars($trip['to_city']) . "</td>
            <td data-label='Kalkış'>" . htmlspecialchars(date('d.m.Y H:i', strtotime($trip['departure_time']))) . "</td>
            <td data-label='Varış'>" . htmlspecialchars(date('d.m.Y H:i', strtotime($trip['arrival_time']))) . "</td>
            <td data-label='Fiyat'>" . number_format($trip['price'], 2) . " ₺</td>
            <td data-label='İşlem'>";

    if (isLoggedIn()) {
        echo '<a href="/bilet-satin-alma/public/index.php?page=select_seat&id=' . urlencode($trip['id']) . '" 
               style="background:#1a73e8;color:white;padding:6px 10px;border-radius:6px;text-decoration:none;">
               Koltuk Seç
               </a>';
    } else {
        echo '<a href="/bilet-satin-alma/public/index.php?page=login" style="color:#1a73e8;">Giriş yaparak satın al</a>';
    }

    echo "</td></tr>";
}
echo "</table>";
    }

    echo "<script>
setTimeout(() => {
    const url = new URL(window.location.href);
    url.searchParams.delete('departure_city');
    url.searchParams.delete('destination_city');
    // Sadece bu iki parametreyi temizle, diğerlerini koru
    window.history.replaceState({}, '', url);
}, 200);
</script>";

}

// --- Bakiye yükleme ---
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    $userId = $user['id'];

    if ($amount <= 0) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => '❌ Geçerli bir tutar giriniz.'];
    } else {
        $db->prepare("UPDATE User SET balance = balance + :a WHERE id = :uid")
           ->execute([':a' => $amount, ':uid' => $userId]);

        // Güncel bakiye
        $stmt = $db->prepare("SELECT * FROM User WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

        $_SESSION['flash'] = ['type' => 'ok', 'msg' => '✅ ' . number_format($amount, 2) . ' ₺ başarıyla yüklendi!'];
    }

    header("Location: /bilet-satin-alma/public/index.php?page=home");
    exit;
}
?>

<?php if (isLoggedIn()): ?>
  <h2>💰 Bakiye Yükle</h2>
  <form method="POST" style="margin-bottom:20px;">
    <input type="number" name="amount" step="0.01" placeholder="Yüklenecek tutar (₺)" required>
    <button type="submit" name="add_balance">Yükle</button>
  </form>
<?php endif; ?>

</div>
</body>
</html>
