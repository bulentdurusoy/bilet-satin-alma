<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireRole(['company_admin']);

$user = $_SESSION['user'];
$companyId = $user['company_id'] ?? null;
if (!$companyId) die('Firma ID bulunamadı.');

$ok = $err = null;

// === SEFER EKLE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trip'])) {
    $from = trim($_POST['from_city'] ?? '');
    $to = trim($_POST['to_city'] ?? '');
    $dep = trim($_POST['departure_time'] ?? '');
    $arr = trim($_POST['arrival_time'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $capacity = (int)($_POST['capacity'] ?? 40);

    if ($from === '' || $to === '' || $dep === '' || $arr === '' || $price <= 0) {
        $err = "Lütfen tüm alanları doldurun.";
    } else {
        $stmt = $db->prepare("
            INSERT INTO Trip (id, company_id, from_city, to_city, departure_time, arrival_time, price, capacity, status)
            VALUES (:id, :cid, :from, :to, :dep, :arr, :price, :cap, 'active')
        ");
        $stmt->execute([
            ':id' => uniqid('t_'),
            ':cid' => $companyId,
            ':from' => $from,
            ':to' => $to,
            ':dep' => $dep,
            ':arr' => $arr,
            ':price' => $price,
            ':cap' => $capacity
        ]);
        $_SESSION['ok'] = "Sefer başarıyla eklendi.";
        header("Location: trips.php");
        exit;
    }
}

// === SEFER GÜNCELLE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_trip'])) {
    $upd = $db->prepare("
        UPDATE Trip SET 
            from_city = :from,
            to_city = :to,
            departure_time = :dep,
            arrival_time = :arr,
            price = :price,
            capacity = :cap
        WHERE id = :id AND company_id = :cid
    ");
    $upd->execute([
        ':from' => $_POST['from_city'],
        ':to' => $_POST['to_city'],
        ':dep' => $_POST['departure_time'],
        ':arr' => $_POST['arrival_time'],
        ':price' => (float)$_POST['price'],
        ':cap' => (int)$_POST['capacity'],
        ':id' => $_POST['trip_id'],
        ':cid' => $companyId
    ]);
    $_SESSION['ok'] = "Sefer başarıyla güncellendi.";
    header("Location: trips.php");
    exit;
}

// === SEFER SİL ===
if (isset($_GET['delete'])) {
    $del = $db->prepare("DELETE FROM Trip WHERE id = :id AND company_id = :cid");
    $del->execute([':id' => $_GET['delete'], ':cid' => $companyId]);
    $_SESSION['ok'] = "Sefer silindi.";
    header("Location: trips.php");
    exit;
}

// === FİRMA VE SEFERLER ===
$cStmt = $db->prepare("SELECT name FROM Company WHERE id = :cid");
$cStmt->execute([':cid' => $companyId]);
$company = $cStmt->fetch(PDO::FETCH_ASSOC);

$tStmt = $db->prepare("SELECT * FROM Trip WHERE company_id = :cid ORDER BY departure_time DESC");
$tStmt->execute([':cid' => $companyId]);
$trips = $tStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>🚌 Sefer Yönetimi</title>
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: #f4f6f8;
  margin: 0;
  padding: 24px;
  color: #333;
}
h1 { color: #222; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #f1f3f4; font-weight: 600; }
tr:hover { background: #f9fafc; }
.msg { padding: 10px; border-radius: 8px; margin-bottom: 12px; }
.ok { background: #eaf7ea; color: #216e39; }
.err { background: #fdeaea; color: #b91c1c; }
button, .btn {
  background: #1a73e8;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 14px;
  cursor: pointer;
}
button:hover, .btn:hover { background: #125cc1; }
a { color: #1a73e8; text-decoration: none; }
a:hover { text-decoration: underline; }

/* === MODAL === */
.modal {
  display: none; 
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  justify-content: center;
  align-items: center;
}
.modal-content {
  background: white;
  padding: 24px;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  position: relative;
}
.close {
  position: absolute;
  top: 10px; right: 14px;
  cursor: pointer;
  font-size: 18px;
  color: #888;
}
.close:hover { color: #000; }

input, select {
  width: 100%;
  padding: 8px;
  margin: 6px 0;
  border: 1px solid #ccc;
  border-radius: 6px;
}

@media (max-width: 768px) {
  table, thead, tbody, th, td, tr { display: block; }
  tr { margin-bottom: 12px; padding: 8px; border: 1px solid #eee; border-radius: 8px; }
  td { border: none; padding-left: 45%; position: relative; text-align: right; }
  td::before {
    content: attr(data-label);
    position: absolute;
    left: 10px;
    font-weight: bold;
  }
}
</style>
</head>
<body>
<div class="container">
<h1><?= htmlspecialchars($company['name'] ?? 'Firma') ?> – Sefer Yönetimi</h1>
<p>Hoş geldiniz, <strong><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></strong></p>

<?php
if (isset($_SESSION['ok'])) {
    echo "<div class='msg ok'>" . htmlspecialchars($_SESSION['ok']) . "</div>";
    unset($_SESSION['ok']);
}
if ($err) echo "<div class='msg err'>$err</div>";
?>

<h2>Yeni Sefer Ekle</h2>
<form method="post">
  <input name="from_city" placeholder="Kalkış Şehri" required>
  <input name="to_city" placeholder="Varış Şehri" required>
  <input type="datetime-local" name="departure_time" required>
  <input type="datetime-local" name="arrival_time" required>
  <input type="number" name="price" step="0.01" placeholder="Fiyat (₺)" required>
  <input type="number" name="capacity" placeholder="Kapasite" value="40" min="1" required>
  <button type="submit" name="add_trip">➕ Ekle</button>
</form>

<h2>Mevcut Seferler</h2>
<table>
  <tr>
    <th>Güzergah</th><th>Kalkış</th><th>Varış</th><th>Fiyat</th><th>Kapasite</th><th>İşlem</th>
  </tr>
  <?php if (!$trips): ?>
    <tr><td colspan="6">Henüz sefer eklenmemiş.</td></tr>
  <?php else: foreach ($trips as $t): ?>
    <tr>
      <td data-label="Güzergah"><?= htmlspecialchars($t['from_city']) ?> → <?= htmlspecialchars($t['to_city']) ?></td>
      <td data-label="Kalkış"><?= htmlspecialchars($t['departure_time']) ?></td>
      <td data-label="Varış"><?= htmlspecialchars($t['arrival_time']) ?></td>
      <td data-label="Fiyat"><?= number_format((float)$t['price'], 2) ?> ₺</td>
      <td data-label="Kapasite"><?= htmlspecialchars($t['capacity']) ?></td>
      <td data-label="İşlem">
        <button class="btn edit-btn" 
            data-id="<?= $t['id'] ?>"
            data-from="<?= htmlspecialchars($t['from_city']) ?>"
            data-to="<?= htmlspecialchars($t['to_city']) ?>"
            data-dep="<?= htmlspecialchars($t['departure_time']) ?>"
            data-arr="<?= htmlspecialchars($t['arrival_time']) ?>"
            data-price="<?= htmlspecialchars($t['price']) ?>"
            data-cap="<?= htmlspecialchars($t['capacity']) ?>"
        >✏ Düzenle</button>
        <a href="trips.php?delete=<?= urlencode($t['id']) ?>" onclick="return confirm('Bu sefer silinsin mi?')">🗑 Sil</a>
      </td>
    </tr>
  <?php endforeach; endif; ?>
</table>
</div>

<!-- === MODAL (POPUP) === -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeModal">&times;</span>
    <h3>✏ Sefer Düzenle</h3>
    <form method="post">
      <input type="hidden" name="trip_id" id="edit_id">
      <label>Kalkış Şehri:</label>
      <input name="from_city" id="edit_from" required>
      <label>Varış Şehri:</label>
      <input name="to_city" id="edit_to" required>
      <label>Kalkış:</label>
      <input type="datetime-local" name="departure_time" id="edit_dep" required>
      <label>Varış:</label>
      <input type="datetime-local" name="arrival_time" id="edit_arr" required>
      <label>Fiyat (₺):</label>
      <input type="number" step="0.01" name="price" id="edit_price" required>
      <label>Kapasite:</label>
      <input type="number" name="capacity" id="edit_cap" required>
      <button type="submit" name="update_trip">Kaydet</button>
    </form>
  </div>
</div>

<script>
const modal = document.getElementById('editModal');
const closeModal = document.getElementById('closeModal');
document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    modal.style.display = 'flex';
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_from').value = btn.dataset.from;
    document.getElementById('edit_to').value = btn.dataset.to;
    document.getElementById('edit_dep').value = btn.dataset.dep;
    document.getElementById('edit_arr').value = btn.dataset.arr;
    document.getElementById('edit_price').value = btn.dataset.price;
    document.getElementById('edit_cap').value = btn.dataset.cap;
  });
});
closeModal.onclick = () => modal.style.display = 'none';
window.onclick = e => { if (e.target == modal) modal.style.display = 'none'; };
</script>
</body>
</html>
