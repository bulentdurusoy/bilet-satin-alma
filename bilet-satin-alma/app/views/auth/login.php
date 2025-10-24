<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/session.php';
$user = $_SESSION['user'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (AuthController::login($email, $password)) {
        $user = $_SESSION['user']; // login sonrası kullanıcı session’a yazılmış olur

        // 🔹 Role göre yönlendirme
        if ($user['role'] === 'admin') {
            header("Location: /bilet-satin-alma/public/index.php?page=admin_dashboard");
            exit;
        } elseif ($user['role'] === 'company_admin') {
            // Firma adminleri doğrudan app/views altındaki dashboard’a gider
            header("Location: /bilet-satin-alma/app/views/company/dashboard.php");
            exit;
        } else {
            // Normal kullanıcılar home sayfasına
            header("Location: /bilet-satin-alma/public/index.php?page=home");
            exit;
        }
    } else {
        $error = "❌ E-posta veya şifre hatalı!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Giriş Yap • Bilet Platformu</title>

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

    /* === SAYFA VE FORM === */
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

    .login-box {
      background: white;
      padding: 40px 50px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    .login-box h2 {
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

    .login-box a {
      color: #1a73e8;
      text-decoration: none;
    }
    .login-box a:hover { text-decoration: underline; }

    .error {
      color: #d32f2f;
      margin-top: 8px;
      font-weight: bold;
    }

    @media (max-width: 600px) {
      .login-box {
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
    <a href="/bilet-satin-alma/public/index.php?page=register">Kayıt Ol</a>
  </div>
</div>

<!-- 💼 LOGIN FORM -->
<div class="container">
  <div class="login-box">
    <h2>Giriş Yap</h2>
    <form method="POST">
      <input type="email" name="email" placeholder="E-posta" required><br>
      <input type="password" name="password" placeholder="Şifre" required><br>
      <button type="submit">Giriş Yap</button>
    </form>
    <p class="error"><?= $error ?? '' ?></p>
    <p>Hesabın yok mu? <a href="/bilet-satin-alma/public/index.php?page=register">Kayıt Ol</a></p>
  </div>
</div>

</body>
</html>
