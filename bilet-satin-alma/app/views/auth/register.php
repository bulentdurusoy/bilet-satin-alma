<?php
require_once __DIR__ . '/../../controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => trim($_POST['full_name']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password']
    ];

    if (AuthController::register($data)) {
        header("Location: /bilet-satin-alma/public/index.php?page=login");
        exit;
    } else {
        $error = "❌ Bu e-posta zaten kayıtlı olabilir.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Kayıt Ol • Bilet Platformu</title>

  <style>
    /* === NAVBAR === */
    .navbar {
      background-color: #1a73e8;
      color: white;
      padding: 12px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    .navbar a {
      color: white;
      text-decoration: none;
      margin: 0 10px;
      font-weight: bold;
    }
    .navbar a:hover { text-decoration: underline; }
    .nav-links { display: flex; align-items: center; gap: 8px; }

    /* === SAYFA & FORM === */
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .container {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }

    .register-box {
      background: white;
      padding: 40px 50px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    .register-box h2 {
      margin-bottom: 20px;
      color: #1a73e8;
    }

    input {
      width: 90%;
      padding: 10px;
      margin: 10px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 15px;
    }

    button {
      width: 95%;
      padding: 10px;
      margin-top: 12px;
      border-radius: 6px;
      border: none;
      background: #1a73e8;
      color: white;
      font-weight: bold;
      cursor: pointer;
      font-size: 16px;
    }

    button:hover { background: #1559b2; }

    .register-box a {
      color: #1a73e8;
      text-decoration: none;
    }
    .register-box a:hover { text-decoration: underline; }

    .error {
      color: #d32f2f;
      margin-top: 8px;
      font-weight: bold;
    }

    @media (max-width: 600px) {
      .register-box {
        width: 90%;
        padding: 30px 25px;
      }
    }
  </style>
</head>
<body>

<!-- 🧭 NAVBAR -->
<div class="navbar">
  <div class="logo">
    🚌 <strong>Bilet Platformu</strong>
  </div>
  <div class="nav-links">
    <a href="/bilet-satin-alma/public/index.php?page=home">Ana Sayfa</a>
    <a href="/bilet-satin-alma/public/index.php?page=login">Giriş Yap</a>
  </div>
</div>

<!-- 💼 REGISTER FORM -->
<div class="container">
  <div class="register-box">
    <h2>Kayıt Ol</h2>
    <form method="POST" onsubmit="return validateForm()">
  <input type="text" name="full_name" placeholder="Ad Soyad" required><br>

  <input type="email" name="email" placeholder="E-posta"
         pattern="^[\w\.-]+@([\w-]+\.)+[\w-]{2,}$"
         title="Geçerli bir e-posta adresi giriniz. (örn: isim@domain.com)"
         required><br>

  <input type="password" name="password" placeholder="Şifre"
         pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
         title="Şifre en az 8 karakter, 1 büyük harf, 1 küçük harf, 1 sayı ve 1 özel karakter içermelidir."
         required><br>

  <button type="submit">Kayıt Ol</button>
</form>


    <p class="error"><?= $error ?? '' ?></p>
    <p>Zaten hesabın var mı? <a href="/bilet-satin-alma/public/index.php?page=login">Giriş Yap</a></p>
  </div>
</div>

</body>
</html>
