<?php if (!isset($rows)) $rows = []; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Firma Yönetimi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .links{
        display: flex;
        justify-content: space-between;
    }
</style>
</head>
<body class="bg-light">
<div class="container mt-4">
  <h2 class="text-center mb-4">Firma Yönetimi</h2>

  <div class="links">
    <div>
    <a href="/bilet-satin-alma/public/index.php?page=admin_dashboard">Admin Paneline Dön</a>

    </div>
    <div>
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">+ Yeni Firma Ekle</button>

    </div>
  </div>
  

  <table class="table table-striped table-bordered bg-white">
    <thead class="table-primary">
      <tr>
        <th>ID</th>
        <th>Firma Adı</th>
        <th>Telefon</th>
        <th>E-posta</th>
        <th>Oluşturulma</th>
        <th>İşlem</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['id']) ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['phone'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['email'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td>
          <button class="btn btn-sm btn-warning editBtn"
                  data-id="<?= $r['id'] ?>"
                  data-name="<?= htmlspecialchars($r['name']) ?>"
                  data-phone="<?= htmlspecialchars($r['phone']) ?>"
                  data-email="<?= htmlspecialchars($r['email']) ?>"
                  data-bs-toggle="modal"
                  data-bs-target="#editModal">Düzenle</button>

          <button class="btn btn-sm btn-danger deleteBtn"
                  data-id="<?= $r['id'] ?>"
                  data-bs-toggle="modal"
                  data-bs-target="#deleteModal">Sil</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Yeni Firma Ekle -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="index.php?page=admin_companies&action=add" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Yeni Firma Ekle</h5></div>
      <div class="modal-body">
        <div class="mb-3"><label>Firma Adı:</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-3"><label>Telefon:</label><input type="text" name="phone" class="form-control"></div>
        <div class="mb-3"><label>E-posta:</label><input type="email" name="email" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        <button type="submit" class="btn btn-primary">Ekle</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Firma Düzenle -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="editForm" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Firmayı Düzenle</h5></div>
      <div class="modal-body">
        <div class="mb-3"><label>Firma Adı:</label><input type="text" name="name" id="editName" class="form-control" required></div>
        <div class="mb-3"><label>Telefon:</label><input type="text" name="phone" id="editPhone" class="form-control"></div>
        <div class="mb-3"><label>E-posta:</label><input type="email" name="email" id="editEmail" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        <button type="submit" class="btn btn-warning">Kaydet</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Firma Sil -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="deleteForm" class="modal-content">
      <div class="modal-header"><h5 class="modal-title text-danger">Firmayı Sil</h5></div>
      <div class="modal-body"><p>Bu firmayı silmek istediğinize emin misiniz?</p></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn btn-danger">Sil</button>
      </div>
    </form>
  </div>
</div>

<script>
// Düzenle modalına verileri aktar
document.querySelectorAll('.editBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('editName').value = btn.dataset.name;
    document.getElementById('editPhone').value = btn.dataset.phone;
    document.getElementById('editEmail').value = btn.dataset.email;
    document.getElementById('editForm').action = `index.php?page=admin_companies&action=edit&id=${btn.dataset.id}`;
  });
});

// Sil modalında ID ayarla
document.querySelectorAll('.deleteBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('deleteForm').action = `index.php?page=admin_companies&action=delete&id=${btn.dataset.id}`;
  });
});
</script>

</body>
</html>
