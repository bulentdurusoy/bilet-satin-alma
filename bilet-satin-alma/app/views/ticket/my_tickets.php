<?php
require_once __DIR__ . '/../../helpers/auth.php';
requireLogin();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Biletlerim • Bilet Platformu</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
  }

  .container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 25px 35px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }

  h2 {
    color: #1a73e8;
    margin-bottom: 10px;
  }

  .nav-links {
    margin-bottom: 20px;
  }

  .nav-links a {
    text-decoration: none;
    color: #1a73e8;
    font-weight: bold;
    margin-right: 10px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }

  th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
  }

  th {
    background-color: #1a73e8;
    color: white;
  }

  tr:nth-child(even) {
    background-color: #f2f2f2;
  }

  .btn {
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
  }

  .btn-pdf {
    background-color: #1a73e8;
    color: #fff;
  }

  .btn-cancel {
    background-color: #e53935;
    color: #fff;
  }

  .btn-disabled {
    background-color: #ccc;
    color: #666;
    cursor: not-allowed;
  }

  .message {
    color: green;
    font-weight: bold;
    margin-bottom: 10px;
  }

  .warning {
    color: #d32f2f;
    font-weight: bold;
    margin-top: 5px;
  }

  @media (max-width: 600px) {
    table, thead, tbody, tr, th, td {
      display: block;
    }
    tr {
      margin-bottom: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 10px;
    }
    td {
      border: none;
      text-align: right;
      padding-left: 50%;
      position: relative;
    }
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
  <h2>🎟️ Biletlerim</h2>

  <div class="nav-links">
    <a href="index.php">Ana Sayfa</a> |
    <a href="index.php?page=logout">Çıkış Yap</a>
  </div>

  <?php if (isset($_GET['canceled'])): ?>
    <p class="message">✅ Bilet iptal edildi, ücret iade edildi.</p>
  <?php endif; ?>

  <?php if (isset($_GET['error']) && $_GET['error'] === 'too_late'): ?>
    <p class="warning">⚠️ Kalkışa 1 saatten az kaldı, bu bilet iptal edilemez!</p>
  <?php endif; ?>

  <?php if (!$tickets): ?>
    <p>Henüz biletiniz yok.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Bilet No</th>
        <th>Firma</th>
        <th>Güzergah</th>
        <th>Kalkış</th>
        <th>Koltuklar</th>
        <th>Tutar</th>
        <th>Durum</th>
        <th>İşlem</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($tickets as $tk): ?>
      <?php
        $now = new DateTime('now');
        $limit = (new DateTime($tk['departure_time']))->modify('-1 hour');
        $canCancel = ($tk['status'] === 'active' && $now < $limit);
      ?>
      <tr>
        <td data-label="Bilet No"><?= htmlspecialchars($tk['id']) ?></td>
        <td data-label="Firma"><?= htmlspecialchars($tk['company_name']) ?></td>
        <td data-label="Güzergah"><?= htmlspecialchars($tk['departure_city']) ?> → <?= htmlspecialchars($tk['destination_city']) ?></td>
        <td data-label="Kalkış"><?= date('H:i d.m.Y', strtotime($tk['departure_time'])) ?></td>
        <td data-label="Koltuklar"><?= htmlspecialchars(implode(', ', $tk['seats'])) ?></td>
        <td data-label="Tutar"><?= (int)$tk['total_price'] ?> ₺</td>
        <td data-label="Durum">
          <?php if ($tk['status'] === 'active'): ?>
            <span style="color:green;">Aktif</span>
          <?php else: ?>
            <span style="color:red;">İptal</span>
          <?php endif; ?>
        </td>
        <td data-label="İşlem">
          <a href="index.php?page=ticket_pdf&id=<?= urlencode($tk['id']) ?>" class="btn btn-pdf" target="_blank">PDF</a>
          <?php if ($canCancel): ?>
            <a href="index.php?page=cancel_ticket&id=<?= urlencode($tk['id']) ?>"
               class="btn btn-cancel"
               onclick="return confirm('Bu bileti iptal etmek istediğinize emin misiniz?');">İptal Et</a>
          <?php elseif ($tk['status'] === 'active'): ?>
            <p class="warning">⚠️ Kalkışa 1 saatten az kaldı</p>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

</body>
</html>
