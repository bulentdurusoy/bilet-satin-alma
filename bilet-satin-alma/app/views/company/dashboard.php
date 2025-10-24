<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireRole(['company_admin']); // sadece firma adminleri erişebilir

$user = $_SESSION['user'];
$companyId = $user['company_id'] ?? null;
if (!$companyId) {
    die('Firma ID bulunamadı. Hesabınız bir firmaya atanmalı.');
}

$ok = $err = null;


// --- Sefer Güncelle ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_trip'])) {
    $id = trim($_POST['trip_id']);
    $from = trim($_POST['from_city']);
    $to = trim($_POST['to_city']);
    $dep = trim($_POST['departure_time']);
    $arr = trim($_POST['arrival_time']);
    $price = (float)$_POST['price'];
    $capacity = (int)$_POST['capacity'];

    if ($from === '' || $to === '' || $dep === '' || $arr === '' || $price <= 0) {
        $err = "Lütfen tüm alanları doldurun.";
    } else {
        $stmt = $db->prepare("
            UPDATE Trip
            SET from_city = :from, to_city = :to, departure_time = :dep, arrival_time = :arr,
                price = :price, capacity = :cap
            WHERE id = :id AND company_id = :cid
        ");
        $stmt->execute([
            ':from' => $from,
            ':to' => $to,
            ':dep' => $dep,
            ':arr' => $arr,
            ':price' => $price,
            ':cap' => $capacity,
            ':id' => $id,
            ':cid' => $companyId
        ]);

        $_SESSION['flash'] = ['type' => 'ok', 'msg' => '✅ Sefer başarıyla güncellendi.'];
        header("Location: /bilet-satin-alma/app/views/company/dashboard.php");
        exit;
    }
}

// --- Sefer Ekle ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trip'])) {
    $from = trim($_POST['from_city'] ?? '');
    $to = trim($_POST['to_city'] ?? '');
    $dep = trim($_POST['departure_time'] ?? '');
    $arr = trim($_POST['arrival_time'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $capacity = (int)($_POST['capacity'] ?? 40);

    if ($from === '' || $to === '' || $dep === '' || $arr === '' || $price <= 0) {
        $err = "Lütfen tüm alanları doğru doldurun.";
    } else {
        $stmt = $db->prepare("
            INSERT INTO Trip (id, company_id, from_city, to_city, departure_time, arrival_time, price, capacity, status)
            VALUES (:id, :cid, :from, :to, :dep, :arr, :price, :cap, 'active')
        ");
        $stmt->execute([
            ':id' => $id,
            ':cid' => $companyId,
            ':from' => $from,
            ':to' => $to,
            ':dep' => $dep,
            ':arr' => $arr,
            ':price' => $price,
            ':cap' => $capacity
        ]);

        // Başarılı mesajı session’a kaydet
        $_SESSION['flash'] = ['type' => 'ok', 'msg' => '✅ Sefer başarıyla eklendi.'];

        // 🚀 Sayfa yenileme hatasını önle → yönlendir
        header("Location: /bilet-satin-alma/app/views/company/dashboard.php");
        exit;
    }
}

// --- Sefer Sil ---
if (isset($_GET['delete'])) {
    $del = $db->prepare("DELETE FROM Trip WHERE id = :id AND company_id = :cid");
    $del->execute([':id' => $_GET['delete'], ':cid' => $companyId]);
    header("Location: /bilet-satin-alma/app/views/company/dashboard.php");
    exit;
}

// --- Firma Bilgisi ---
$cStmt = $db->prepare("SELECT name FROM Company WHERE id = :cid");
$cStmt->execute([':cid' => $companyId]);
$company = $cStmt->fetch(PDO::FETCH_ASSOC);

// --- Seferleri Listele ---
$tStmt = $db->prepare("SELECT * FROM Trip WHERE company_id = :cid ORDER BY departure_time DESC");
$tStmt->execute([':cid' => $companyId]);
$trips = $tStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Firma Paneli</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* === GENEL === */
body {
  font-family: 'Segoe UI', Roboto, sans-serif;
  margin: 0;
  background: #f7f9fb;
  color: #222;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px;
}
.container {
  width: 100%;
  max-width: 1100px;
  background: #fff;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
h1, h2 {
  text-align: center;
  color: #1a202c;
}
a {
  color: #2563eb;
  text-decoration: none;
}
a:hover { text-decoration: underline; }
.top-bar {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 24px;
}
.logout {
  background: #dc2626;
  color: #fff;
  padding: 8px 14px;
  border-radius: 6px;
  transition: 0.3s;
}
.logout:hover { background: #b91c1c; }
.btn {
  display: inline-block;
  padding: 8px 14px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: 0.2s;
}
.btn-primary { background: #2563eb; color: white; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #9ca3af; color: white; }
.btn-secondary:hover { background: #6b7280; }

/* === MESAJLAR === */
.msg {
  padding: 12px 16px;
  border-radius: 8px;
  margin: 16px 0;
}
.ok { background: #e6f4ea; color: #166534; }
.err { background: #fee2e2; color: #b91c1c; }

/* === FORM === */
form {
  display: grid;
  gap: 10px;
  grid-template-columns: 1fr 1fr;
}
form input, form button {
  padding: 10px;
  font-size: 15px;
  border: 1px solid #ccc;
  border-radius: 6px;
  width: 100%;
}
form button {
  grid-column: span 2;
}

/* === TABLO === */
table {
  border-collapse: collapse;
  width: 100%;
  margin-top: 20px;
  border-radius: 8px;
  overflow: hidden;
}
th, td {
  padding: 12px 10px;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
}
th {
  background: #f3f4f6;
  font-weight: 600;
}
tr:hover { background: #f9fafb; }

/* === MODAL === */
.modal {
  display: none;
  position: fixed;
  z-index: 999;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.6);
  justify-content: center;
  align-items: center;
}
.modal-content {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  width: 90%;
  max-width: 450px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}
.modal-content h2 { text-align: center; }
.modal-content input {
  width: 100%;
  margin: 8px 0;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
.modal-content button {
  margin-top: 10px;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
  form { grid-template-columns: 1fr; }
  .top-bar { flex-direction: column; align-items: flex-start; gap: 10px; }
  table, thead, tbody, th, td, tr {
    display: block;
  }
  th { display: none; }
  tr {
    margin-bottom: 12px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 10px;
  }
  td {
    border: none;
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
  }
  td::before {
    content: attr(data-label);
    font-weight: 600;
  }
}
</style>
</head>
<body>
<div class="container">
  <div class="top-bar">
    <h1><?= htmlspecialchars($company['name'] ?? 'Firma') ?> Paneli</h1>
    <div>
      <a href="/bilet-satin-alma/app/views/company/kuponlar.php" class="btn btn-primary">🎟 Kupon Yönetimi</a>
      <a href="/bilet-satin-alma/public/index.php?page=logout" class="logout">Çıkış Yap</a>
    </div>
  </div>

  <p>Hoş geldiniz, <strong><?= htmlspecialchars($user['full_name']) ?></strong></p>

  <?php if ($ok): ?><div class="msg ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <h2>Yeni Sefer Ekle</h2>
  <form method="post">
    <input name="from_city" placeholder="Kalkış Şehri" required>
    <input name="to_city" placeholder="Varış Şehri" required>
    <input type="datetime-local" name="departure_time" required>
    <input type="datetime-local" name="arrival_time" required>
    <input type="number" name="price" step="0.01" placeholder="Fiyat (₺)" required>
    <input type="number" name="capacity" value="40" min="1" placeholder="Kapasite" required>
    <button type="submit" name="add_trip" class="btn btn-primary">➕ Sefer Ekle</button>
  </form>

  <h2> Mevcut Seferler</h2>
  <table>
    <thead>
      <tr><th>ID</th><th>Güzergah</th><th>Kalkış</th><th>Varış</th><th>Fiyat</th><th>Kapasite</th><th>İşlem</th></tr>
    </thead>
    <tbody>
      <?php if (!$trips): ?>
        <tr><td colspan="7">Henüz sefer eklenmemiş.</td></tr>
      <?php else: foreach ($trips as $t): ?>
      <tr>
        <td data-label="ID"><?= htmlspecialchars($t['id']) ?></td>
        <td data-label="Güzergah"><?= htmlspecialchars($t['from_city']) ?> → <?= htmlspecialchars($t['to_city']) ?></td>
        <td data-label="Kalkış"><?= htmlspecialchars($t['departure_time']) ?></td>
        <td data-label="Varış"><?= htmlspecialchars($t['arrival_time']) ?></td>
        <td data-label="Fiyat"><?= number_format((float)$t['price'], 2) ?> ₺</td>
        <td data-label="Kapasite"><?= htmlspecialchars($t['capacity']) ?></td>
        <td data-label="İşlem">
          <a href="/bilet-satin-alma/public/index.php?page=company_dashboard&delete=<?= urlencode($t['id']) ?>"
             onclick="return confirm('Bu sefer silinsin mi?')">🗑 Sil</a> |
          <a href="#" onclick='openEditModal(<?= json_encode($t) ?>)'>✏️ Düzenle</a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Düzenleme Modalı -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <h2>Sefer Düzenle</h2>
    <form method="POST">
      <input type="hidden" name="trip_id" id="edit_trip_id">
      <input type="text" name="from_city" id="edit_from_city" placeholder="Kalkış Şehri" required>
      <input type="text" name="to_city" id="edit_to_city" placeholder="Varış Şehri" required>
      <input type="datetime-local" name="departure_time" id="edit_departure_time" required>
      <input type="datetime-local" name="arrival_time" id="edit_arrival_time" required>
      <input type="number" step="0.01" name="price" id="edit_price" placeholder="Fiyat (₺)" required>
      <input type="number" name="capacity" id="edit_capacity" min="1" placeholder="Kapasite" required>
      <button type="submit" name="update_trip" class="btn btn-primary">Kaydet</button>
      <button type="button" class="btn btn-secondary" onclick="closeModal()">İptal</button>
    </form>
  </div>
</div>

<script>
function openEditModal(trip) {
  document.getElementById('edit_trip_id').value = trip.id;
  document.getElementById('edit_from_city').value = trip.from_city;
  document.getElementById('edit_to_city').value = trip.to_city;
  document.getElementById('edit_departure_time').value = trip.departure_time.replace(' ', 'T');
  document.getElementById('edit_arrival_time').value = trip.arrival_time.replace(' ', 'T');
  document.getElementById('edit_price').value = trip.price;
  document.getElementById('edit_capacity').value = trip.capacity;
  document.getElementById('editModal').style.display = 'flex';
}
function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}
</script>
</body>
</html>

