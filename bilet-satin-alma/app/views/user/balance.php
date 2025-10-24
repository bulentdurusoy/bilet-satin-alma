<?php $u = currentUser(); ?>
<h2>Bakiye Yükle</h2>
<p><a href="index.php">Ana Sayfa</a> | <a href="index.php?page=logout">Çıkış Yap</a></p>
<p>Mevcut bakiye: <strong><?= number_format((float)$u['balance'], 2) ?> ₺</strong></p>

<?php if (!empty($message)): ?>
  <p style="color:green;"><strong><?= htmlspecialchars($message) ?></strong></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <p style="color:red;"><strong><?= htmlspecialchars($error) ?></strong></p>
<?php endif; ?>

<form method="POST">
  <label>Tutar (₺):</label>
  <input type="number" name="amount" step="0.01" min="1" required>
  <button type="submit">Yükle</button>
</form>

<?php if (isset($_GET['success'])): ?>
  <p style="color:green;"><strong>Bakiye başarıyla güncellendi!</strong></p>
<?php endif; ?>
