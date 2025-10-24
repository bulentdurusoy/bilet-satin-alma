<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireRole(['admin']); // sadece genel admin erişebilir

$ok = $err = null;

// --- Yeni Firma Ekle ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $err = "Lütfen firma adını girin.";
    } else {
        // Aynı isimde firma var mı kontrol et
        $check = $db->prepare("SELECT * FROM Company WHERE name = :n");
        $check->execute([':n' => $name]);
        if ($check->fetch()) {
            $err = "Bu isimde bir firma zaten mevcut.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO Company (id, name, description)
                VALUES (:id, :n, :d)
            ");
            $stmt->execute([
                ':id' => uniqid('c_'),
                ':n'  => $name,
                ':d'  => $description
            ]);
            $ok = "Firma başarıyla eklendi.";
        }
    }
}

// --- Firma Sil ---
if (isset($_GET['delete'])) {
    $del = $db->prepare("DELETE FROM Company WHERE id = :id");
    $del->execute([':id' => $_GET['delete']]);
    header("Location: index.php?page=admin_companies");
    exit;
}

// --- Firmaları Listele ---
$companies = $db->query("SELECT * FROM Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Firmalar Yönetimi</title>
<style>
body { font-family: Arial, sans-serif; margin: 24px; }
h1 { color: #333; }
table { border-collapse: collapse; width: 100%; margin-top: 16px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background: #f5f5f5; }
form { margin-top: 20px; }
.msg { margin: 8px 0; padding: 8px; border-radius: 6px; }
.ok { background: #e9f7ef; color: #1e7e34; }
.err { background: #fdecea; color: #a71d2a; }
a { color: #1a73e8; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>

<h1>Firma Yönetimi</h1>
<p><a href="index.php?page=admin">← Geri Dön</a></p>

<?php if ($ok): ?><div class="msg ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<h2>Yeni Firma Ekle</h2>
<form method="post">
  <label>Firma Adı:</label>
  <input name="name" required>
  <label>Açıklama:</label>
  <input name="description">
  <button type="submit" name="add_company">Ekle</button>
</form>

<h2>Mevcut Firmalar</h2>
<table>
  <tr>
    <th>ID</th>
    <th>Ad</th>
    <th>Açıklama</th>
    <th>İşlem</th>
  </tr>
  <?php if (!$companies): ?>
    <tr><td colspan="4">Henüz firma eklenmemiş.</td></tr>
  <?php else: foreach ($companies as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c['id']) ?></td>
      <td><?= htmlspecialchars($c['name']) ?></td>
      <td><?= htmlspecialchars($c['description'] ?? '-') ?></td>
      <td><a href="index.php?page=admin_companies&delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu firmayı silmek istediğinize emin misiniz?')">Sil</a></td>
    </tr>
  <?php endforeach; endif; ?>
</table>

</body>
</html>
