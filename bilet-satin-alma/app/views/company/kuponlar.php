<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
requireRole(['company_admin']); // sadece firma adminleri

$user = currentUser();
$company_id = $user['company_id'];
$ok = $err = null;

// Kupon silme
if (isset($_GET['delete'])) {
    $del = $db->prepare("DELETE FROM Coupon WHERE id = :id AND company_id = :cid");
    $del->execute([':id' => $_GET['delete'], ':cid' => $company_id]);
    header("Location: index.php?page=company_coupons");
    exit;
}

// Kupon ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount = (float)$_POST['discount'];
    $valid_from = $_POST['valid_from'];
    $valid_to = $_POST['valid_to'];
    $usage_limit = (int)$_POST['usage_limit'];

    if ($code === '' || $discount <= 0 || !$valid_from || !$valid_to) {
        $err = "Lütfen tüm alanları doldurun.";
    } else {
        $id = uniqid('cp_');
        $stmt = $db->prepare("
            INSERT INTO Coupon (id, code, discount_percent, valid_from, valid_to, usage_limit, company_id)
            VALUES (:id, :code, :discount_percent, :valid_from, :valid_to, :usage_limit, :cid)
        ");
        try {
            $stmt->execute([
                ':id' => $id,
                ':code' => $code,
                ':discount_percent' => $discount / 100,
                ':valid_from' => $valid_from,
                ':valid_to' => $valid_to,
                ':usage_limit' => $usage_limit,
                ':cid' => $company_id
            ]);
            $ok = "Kupon başarıyla oluşturuldu.";
        } catch (PDOException $e) {
            $err = "Kupon oluşturulamadı: " . $e->getMessage();
        }
    }
}



// Kuponları listele
$stmt = $db->prepare("SELECT * FROM Coupon WHERE company_id = :cid ORDER BY created_at DESC");
$stmt->execute([':cid' => $company_id]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🎟️ Kupon Yönetimi</title>
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
  max-width: 1000px;
  background: #fff;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
h1, h2, h3 {
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
.btn {
  display: inline-block;
  padding: 8px 14px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: 0.2s;
  text-decoration: none;
  font-size: 14px;
}
.btn-primary { background: #2563eb; color: white; }
.btn-primary:hover { background: #1d4ed8; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-danger:hover { background: #b91c1c; }
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
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
form input, form button {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 15px;
}
form button {
  grid-column: 1 / -1;
}

/* === TABLO === */
table {
  border-collapse: collapse;
  width: 100%;
  margin-top: 10px;
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

/* === RESPONSIVE === */
@media (max-width: 768px) {
  form {
    grid-template-columns: 1fr;
  }
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
    <h2>Kupon Yönetimi</h2>
    <a href="/bilet-satin-alma/app/views/company/dashboard.php" class="btn btn-secondary">← Firma Paneline Dön</a>
  </div>

  <?php if ($ok): ?><div class="msg ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <h3>Yeni Kupon Oluştur</h3>
  <form method="POST">
    <input type="text" name="code" placeholder="Kupon Kodu (örn: IND10)" required>
    <input type="number" name="discount" placeholder="İndirim (%)" required min="1" max="100">
    <input type="date" name="valid_from" required>
    <input type="date" name="valid_to" required>
    <input type="number" name="usage_limit" placeholder="Kullanım Limiti" min="1" value="10">
    <button type="submit" class="btn btn-primary">➕ Kupon Ekle</button>
  </form>

  <h3>Mevcut Kuponlar</h3>
  <table>
    <thead>
      <tr>
        <th>Kod</th><th>İndirim</th><th>Başlangıç</th><th>Bitiş</th><th>Kullanım Limiti</th><th>İşlem</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$coupons): ?>
        <tr><td colspan="5">Hiç kupon bulunamadı.</td></tr>
      <?php else: foreach ($coupons as $c): ?>
      <tr>
        <td data-label="Kod"><?= htmlspecialchars($c['code']) ?></td>
        <td data-label="İndirim"><?= (int)($c['discount_percent'] * 100) ?>%</td>
        <td data-label="Başlangıç"><?= htmlspecialchars($c['valid_from']) ?></td>
        <td data-label="Bitiş"><?= htmlspecialchars($c['valid_to']) ?></td>
        <td data-label="Kullanım Limiti"><?= htmlspecialchars($c['usage_limit']) ?></td>
        <td data-label="İşlem">
         <a href="/bilet-satin-alma/public/index.php?page=company_coupons&delete=<?= urlencode($c['id']) ?>"
   class="btn btn-danger"
   onclick="return confirm('Bu kuponu silmek istiyor musunuz?')">Sil</a>

        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
