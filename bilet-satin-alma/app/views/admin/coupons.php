<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireRole(['admin']); // sadece genel admin erişebilir

$ok = $err = null;

// --- Yeni Kupon Ekle ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount = (float)($_POST['discount_percent'] ?? 0);
    $valid_from = trim($_POST['valid_from'] ?? '');
    $valid_to = trim($_POST['valid_to'] ?? '');
    $usage_limit = (int)($_POST['usage_limit'] ?? 1);
    $company_id = $_POST['company_id'] ?? '';

    if ($code === '' || $discount <= 0 || $valid_to === '') {
        $err = "Lütfen tüm alanları doldurun.";
    } else {
        // Aynı kod var mı?
        $check = $db->prepare("SELECT id FROM Coupon WHERE code = :c");
        $check->execute([':c' => $code]);
        if ($check->fetch()) {
            $err = "Bu kupon kodu zaten mevcut.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO Coupon (
                    code, discount_percent, valid_from, valid_to,
                    usage_limit, used_count, company_id, status
                ) VALUES (
                    :code, :discount_percent, :valid_from, :valid_to,
                    :usage_limit, 0, :company_id, 'active'
                )
            ");
            $stmt->execute([
                ':code' => $code,
                ':discount_percent' => $discount,
                ':valid_from' => $valid_from ?: date('Y-m-d'),
                ':valid_to' => $valid_to,
                ':usage_limit' => $usage_limit,
                ':company_id' => $company_id !== '' ? $company_id : null
            ]);
            $ok = "Kupon başarıyla eklendi.";
        }
    }
}

// --- Kupon Sil ---
if (isset($_GET['delete'])) {
    $del = $db->prepare("DELETE FROM Coupon WHERE id = :id");
    $del->execute([':id' => $_GET['delete']]);
    header("Location: index.php?page=admin_coupons");
    exit;
}

// --- Firma listesi ---
$companies = $db->query("SELECT id, name FROM Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Kuponları Listele ---
$coupons = $db->query("
    SELECT c.*, co.name AS company_name
    FROM Coupon c
    LEFT JOIN Company co ON c.company_id = co.id
    ORDER BY datetime(c.valid_to) DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>🎟️ Kupon Yönetimi</title>
<style>
:root {
  --primary: #2563eb;
  --primary-hover: #1e40af;
  --bg-light: #f9fafb;
  --bg-card: #ffffff;
  --border: #e5e7eb;
  --text-dark: #111827;
  --text-light: #6b7280;
  --success: #10b981;
  --error: #ef4444;
}

/* Genel */
body {
  font-family: "Inter", "Segoe UI", Arial, sans-serif;
  background: var(--bg-light);
  color: var(--text-dark);
  margin: 0;
  padding: 24px;
}

h1 {
  font-size: 1.8rem;
  text-align: center;
  color: var(--primary-hover);
  margin-bottom: 20px;
}

a {
  color: var(--primary);
  text-decoration: none;
  transition: color 0.2s ease;
}
a:hover {
  color: var(--primary-hover);
}

/* Container */
.container {
  max-width: 1100px;
  margin: 0 auto;
  background: var(--bg-card);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  padding: 24px 28px;
}

/* Başlık ve geri link */
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

/* Mesaj kutuları */
.msg {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-weight: 500;
}
.ok {
  background: #dcfce7;
  color: var(--success);
}
.err {
  background: #fee2e2;
  color: var(--error);
}

/* Form */
form {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 30px;
}
form label {
  display: block;
  font-weight: 600;
  margin: 8px 0 4px;
}
form input, form select {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid var(--border);
  outline: none;
  transition: all 0.2s ease;
}
form input:focus, form select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}
button {
  background: var(--primary);
  color: white;
  border: none;
  padding: 10px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  margin-top: 12px;
  transition: background 0.2s ease;
}
button:hover {
  background: var(--primary-hover);
}

/* Tablo */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
}
th, td {
  text-align: left;
  padding: 12px;
  border-bottom: 1px solid var(--border);
}
th {
  background: var(--primary);
  color: white;
  font-weight: 600;
}
tr:hover {
  background: #f1f5f9;
}
td[data-label] {
  color: var(--text-dark);
}

/* Mobil görünüm */
@media (max-width: 768px) {
  table, thead, tbody, th, td, tr {
    display: block;
  }
  thead {
    display: none;
  }
  tr {
    background: var(--bg-card);
    margin-bottom: 16px;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 10px 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
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
    color: var(--text-light);
  }
  td:last-child {
    border-top: 1px solid var(--border);
    margin-top: 8px;
    padding-top: 10px;
  }
}
</style>
</head>
<body>

<div class="container">
  <div class="top-bar">
    <h1>🎟️ Kupon Yönetimi</h1>
    <a href="index.php?page=admin">← Geri Dön</a>
  </div>

  <?php if ($ok): ?><div class="msg ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <h2>Yeni Kupon Ekle</h2>
  <form method="post">
    <label>Kod:</label>
    <input name="code" maxlength="20" required>

    <label>İndirim (0.01 - 1 arası):</label>
    <input type="number" step="0.01" name="discount_percent" min="0.01" max="1" required>

    <label>Başlangıç Tarihi:</label>
    <input type="date" name="valid_from">

    <label>Bitiş Tarihi:</label>
    <input type="date" name="valid_to" required>

    <label>Kullanım Limiti:</label>
    <input type="number" name="usage_limit" min="1" value="1" required>

    <label>Firma (opsiyonel):</label>
    <select name="company_id">
      <option value="">Tüm Firmalar (genel kupon)</option>
      <?php foreach ($companies as $c): ?>
        <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" name="add_coupon">➕ Kupon Ekle</button>
  </form>

  <h2>Mevcut Kuponlar</h2>
  <table>
    <thead>
      <tr>
        <th>Kod</th>
        <th>İndirim</th>
        <th>Firma</th>
        <th>Başlangıç</th>
        <th>Bitiş</th>
        <th>Kullanım Limiti</th>
        <th>Kullanıldı</th>
        <th>Durum</th>
        <th>İşlem</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$coupons): ?>
        <tr><td colspan="9">Henüz kupon yok.</td></tr>
      <?php else: foreach ($coupons as $c): ?>
        <tr>
          <td data-label="Kod"><?= htmlspecialchars($c['code']) ?></td>
          <td data-label="İndirim"><?= number_format($c['discount_percent'] * 100, 0) ?>%</td>
          <td data-label="Firma"><?= htmlspecialchars($c['company_name'] ?? 'Genel') ?></td>
          <td data-label="Başlangıç"><?= htmlspecialchars($c['valid_from']) ?></td>
          <td data-label="Bitiş"><?= htmlspecialchars($c['valid_to']) ?></td>
          <td data-label="Limit"><?= htmlspecialchars($c['usage_limit']) ?></td>
          <td data-label="Kullanıldı"><?= htmlspecialchars($c['used_count']) ?></td>
          <td data-label="Durum"><?= htmlspecialchars($c['status']) ?></td>
          <td data-label="İşlem">
            <a href="index.php?page=admin_coupons&delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Kupon silinsin mi?')">🗑 Sil</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
