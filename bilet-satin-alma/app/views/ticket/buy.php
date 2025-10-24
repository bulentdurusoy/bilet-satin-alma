<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sefer Satın Alma</title>

  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f7fa;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px;
      margin: 0;
    }

    h2, h3 {
      text-align: center;
      color: #333;
    }

    form {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-width: 650px;
      width: 100%;
      margin-top: 20px;
    }

    fieldset {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 8px;
    }

    label {
      position: relative;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      width: 48px;
      height: 48px;
      background: #e0e0e0;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
      user-select: none;
    }

    label input {
      display: none;
    }

    /* Boş koltuk */
    label {
      background: #e0e0e0;
      color: #333;
    }

    /* Dolu koltuk (önceden alınmış) */
    label.booked {
      background: #27ae60;
      color: #fff;
      cursor: not-allowed;
      opacity: 0.8;
    }

    /* Seçilen koltuk */
    label input:checked + span {
      background: #e74c3c !important;
      color: white;
      border-radius: 8px;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    label:hover:not(.booked) {
      background: #3498db;
      color: white;
    }

    input[type="text"] {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 5px;
      width: 60%;
      max-width: 250px;
    }

    button {
      display: block;
      background: #007bff;
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      margin: 20px auto 0;
      transition: background 0.3s;
    }

    button:hover {
      background: #0056b3;
    }

    @media (max-width: 600px) {
      fieldset {
        gap: 6px;
      }

      label {
        width: 40px;
        height: 40px;
        font-size: 14px;
      }

      input[type="text"] {
        width: 100%;
        max-width: 100%;
      }
    }
  </style>
</head>

<body>
<?php
// Değişkenler: $trip, $bookedSeats, $message, $error
?>
<h2>Sefer Satın Alma</h2>
<p>
  <a href="/bilet-satin-alma/public/index.php?page=home">Ana Sayfa</a> | 
  <a href="index.php?page=logout">Çıkış Yap</a>
</p>

<hr>

<h3><?= htmlspecialchars($trip['company_name']) ?> |
    <?= htmlspecialchars($trip['from_city']) ?> → <?= htmlspecialchars($trip['to_city']) ?>
</h3>
<p>
  Kalkış: <strong><?= date('H:i d.m.Y', strtotime($trip['departure_time'])) ?></strong> |
  Varış: <strong><?= date('H:i d.m.Y', strtotime($trip['arrival_time'])) ?></strong> |
  Fiyat: <strong><?= (int)$trip['price'] ?> ₺</strong> |
  Kapasite: <strong><?= (int)$trip['capacity'] ?></strong>
</p>

<?php if (!empty($message)): ?>
  <p style="color:green; text-align:center;"><strong><?= htmlspecialchars($message) ?></strong></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <p style="color:red; text-align:center;"><strong><?= htmlspecialchars($error) ?></strong></p>
<?php endif; ?>

<form method="POST">
  <fieldset>
    <legend>Koltuk Seçimi</legend>
    <?php
    $cap = (int)$trip['capacity'];
    $booked = $bookedSeats ?? [];
    for ($i = 1; $i <= $cap; $i++):
        $isBooked = in_array($i, $booked, true);
        $bookedClass = $isBooked ? 'booked' : '';
    ?>
        <label class="<?= $bookedClass ?>">
          <input type="checkbox" name="seats[]" value="<?= $i ?>" <?= $isBooked ? 'disabled' : '' ?>>
          <span><?= $i ?></span>
        </label>
    <?php endfor; ?>
  </fieldset>

  <p style="margin-top:12px; text-align:center;">
    Kupon Kodu (opsiyonel): 
    <input type="text" name="coupon" placeholder="ORNEK10 (ör. %10)">
  </p>

  <button type="submit">Satın Al</button>
</form>

<p style="margin-top:8px; font-size:13px; text-align:center;">
  🟩 Dolu Koltuk | 🟥 Seçtiğin Koltuk | ⬜ Boş Koltuk
</p>

</body>
</html>
