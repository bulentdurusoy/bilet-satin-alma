<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
requireRole(['admin']); // sadece adminler erişebilir

$ok = $err = null;

// 🏢 Şirketleri çek
try {
    $companies = $db->query("SELECT id, name FROM Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Company tablosuna erişilemiyor: " . htmlspecialchars($e->getMessage()));
}

// 🧍 Yeni firma admini ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['update_id'])) {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $company_id = $_POST['company_id'] ?? '';

    if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $company_id === '') {
        $err = 'Lütfen tüm alanları doğru doldurun (şifre min. 6 karakter).';
    } else {
        // E-posta var mı?
        $chk = $db->prepare("SELECT 1 FROM User WHERE email = :email");
        $chk->execute([':email' => $email]);
        if ($chk->fetchColumn()) {
            $err = 'Bu e-posta zaten kayıtlı.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $db->prepare("
                INSERT INTO User (full_name, email, role, password, company_id, balance)
                VALUES (:full_name, :email, 'company_admin', :password, :company_id, 0)
            ");
            try {
                $ins->execute([
                    ':full_name'  => $full_name,
                    ':email'      => $email,
                    ':password'   => $hash,
                    ':company_id' => $company_id,
                ]);
                $ok = '✅ Firma admini eklendi.';
            } catch (PDOException $e) {
                $err = 'Kayıt başarısız: ' . $e->getMessage();
            }
        }
    }
}

// 🗑 Firma admini silme
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $del = $db->prepare("DELETE FROM User WHERE id = :id AND role = 'company_admin'");
    $del->execute([':id' => $deleteId]);
    header("Location: /bilet-satin-alma/public/index.php?page=admin_company_admins");
    exit;
}

// ✏️ Düzenlenecek admini çek
$editAdmin = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM User WHERE id = :id AND role = 'company_admin'");
    $stmt->execute([':id' => $editId]);
    $editAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 💾 Düzenleme kaydetme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $updateId  = (int)$_POST['update_id'];
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $company_id = $_POST['company_id'];

    $upd = $db->prepare("
        UPDATE User 
        SET full_name = :full_name, email = :email, company_id = :company_id 
        WHERE id = :id AND role = 'company_admin'
    ");
    $upd->execute([
        ':full_name' => $full_name,
        ':email' => $email,
        ':company_id' => $company_id,
        ':id' => $updateId
    ]);

    header("Location: /bilet-satin-alma/public/index.php?page=admin_company_admins");
    exit;
}

// 📋 Mevcut firma adminleri
$admins = $db->query("
    SELECT u.id, u.full_name, u.email, c.name AS company_name, u.created_at
    FROM User u
    LEFT JOIN Company c ON c.id = u.company_id
    WHERE u.role = 'company_admin'
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Firma Adminleri</title>
<style>
body { font-family: Arial, sans-serif; margin: 24px; background:#f8f9fa; }
h1 { margin-bottom: 8px; text-align: center; }
.form, table { margin-top: 16px; }
.form { display: flex; justify-content: center; flex-direction: column; align-items: center; }
table { border-collapse: collapse; width: 100%; background:white; box-shadow:0 2px 8px rgba(0,0,0,0.1); border-radius:8px; overflow:hidden; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #007bff; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
.msg { padding: 10px; border-radius: 6px; margin: 10px 0; width: 60%; text-align: center; }
.ok { background: #e9f7ef; color: #216e39; }
.err { background: #fdeaea; color: #b91c1c; }
input, select, button { padding: 8px; margin: 6px; border: 1px solid #ccc; border-radius: 4px; font-size:14px; }
button { background-color: #007bff; color: white; cursor: pointer; }
button:hover { background-color: #0056b3; }
a { color: #007bff; text-decoration:none; }
a:hover { text-decoration:underline; }
.new-admin-title { text-align: center; }
@media (max-width: 768px) {
  body { margin: 12px; }
  table, thead, tbody, th, td, tr { display: block; }
  thead tr { display: none; }
  tr { margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; padding: 10px; }
  td { border: none; position: relative; padding-left: 50%; text-align: right; }
  td::before { content: attr(data-label); position: absolute; left: 10px; width: 45%; font-weight: bold; text-align: left; }
  input, select, button { width: 100%; margin: 6px 0; }
}
</style>
</head>
<body>

<h1>Firma Adminleri</h1>
<p><a href="/bilet-satin-alma/public/index.php?page=admin_dashboard">← Admin paneline dön</a></p>

<?php if ($ok): ?><div class="msg ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="form">
  <h3 class="new-admin-title">Yeni Firma Admini Ekle</h3>
  <form method="post" action="/bilet-satin-alma/public/index.php?page=admin_company_admins">
    <input type="text" name="full_name" placeholder="Ad Soyad" required>
    <input type="email" name="email" placeholder="E-posta" required>
    <input type="password" name="password" placeholder="Şifre (min 6)" required>
    <select name="company_id" required>
      <option value="">— Firma seçin —</option>
      <?php foreach ($companies as $c): ?>
        <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Ekle</button>
  </form>
</div>

<h3>Mevcut Firma Adminleri</h3>
<table>
  <tr>
    <th>Ad Soyad</th><th>E-posta</th><th>Firma</th><th>Oluşturulma</th><th>İşlem</th>
  </tr>
  <?php if (!$admins): ?>
    <tr><td colspan="5" style="text-align:center;">Kayıt yok.</td></tr>
  <?php else: foreach ($admins as $a): ?>
    <tr>
      <td data-label="Ad Soyad"><?= htmlspecialchars($a['full_name']) ?></td>
      <td data-label="E-posta"><?= htmlspecialchars($a['email']) ?></td>
      <td data-label="Firma"><?= htmlspecialchars($a['company_name'] ?? '-') ?></td>
      <td data-label="Oluşturulma"><?= htmlspecialchars($a['created_at']) ?></td>
      <td data-label="İşlem">
        <a href="/bilet-satin-alma/public/index.php?page=admin_company_admins&delete=<?= htmlspecialchars($a['id']) ?>" 
           onclick="return confirm('Bu firmaya ait admin silinsin mi?')">Sil</a> |
        <a href="/bilet-satin-alma/public/index.php?page=admin_company_admins&edit=<?= htmlspecialchars($a['id']) ?>">Düzenle</a>
      </td>
    </tr>
  <?php endforeach; endif; ?>
</table>

<?php if ($editAdmin): ?>
<hr>
<h3>Firma Adminini Düzenle</h3>
<form method="post" action="/bilet-satin-alma/public/index.php?page=admin_company_admins">
  <input type="hidden" name="update_id" value="<?= htmlspecialchars($editAdmin['id']) ?>">
  <input type="text" name="full_name" value="<?= htmlspecialchars($editAdmin['full_name']) ?>" required>
  <input type="email" name="email" value="<?= htmlspecialchars($editAdmin['email']) ?>" required>
  <select name="company_id" required>
    <?php foreach ($companies as $c): ?>
      <option value="<?= htmlspecialchars($c['id']) ?>" <?= ($editAdmin['company_id'] == $c['id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Kaydet</button>
  <a href="/bilet-satin-alma/public/index.php?page=admin_company_admins">İptal</a>
</form>
<?php endif; ?>

</body>
</html>
