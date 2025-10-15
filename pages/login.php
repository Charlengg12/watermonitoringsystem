<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["user_id"];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ Invalid email.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Water Quality Testing & Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
      background: #001f3f;
      position: relative;
      color: #f0f0f0;
    }
    .waves {
      position: absolute;
      bottom: 0;
      width: 100%;
      height: 150px;
      background: #0077be;
      border-radius: 100% 100% 0 0;
      animation: waveMove 6s ease-in-out infinite alternate;
      opacity: 0.4;
      z-index: 0;
    }
    .waves::before,
    .waves::after {
      content: '';
      position: absolute;
      top: 0;
      width: 100%;
      height: 100%;
      background: inherit;
      border-radius: inherit;
      opacity: 0.6;
    }
    .waves::before { animation: waveMove 8s ease-in-out infinite alternate-reverse; }
    .waves::after { animation: waveMove 10s ease-in-out infinite alternate; }
    @keyframes waveMove {
      0% { transform: translateX(0) scaleY(1); }
      100% { transform: translateX(-50%) scaleY(1.05); }
    }
    .login-container {
      background-color: rgba(0, 0, 0, 0.75);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 0 30px rgba(0, 191, 255, 0.3);
      width: 100%;
      max-width: 400px;
      z-index: 2;
      position: relative;
    }
    .login-container h2 {
      text-align: center;
      margin-bottom: 30px;
      font-weight: 600;
      color: #00d4ff;
      font-size: 28px;
      letter-spacing: 1px;
    }
    .form-label { color: #ccc; }
    .form-control {
      border-radius: 10px;
      background-color: #1e1e1e;
      border: 1px solid #333;
      color: #fff;
    }
    .form-control:focus {
      background-color: #1e1e1e;
      color: #fff;
      box-shadow: 0 0 10px #00e6e6;
      border-color: #00e6e6;
    }
    .btn-login {
      background-color: #00bfff;
      color: #000;
      font-weight: bold;
      border-radius: 10px;
      padding: 10px;
      transition: background-color 0.3s ease;
    }
    .btn-login:hover {
      background-color: #00e6ff;
      box-shadow: 0 0 12px #00e6ff;
    }
    .footer-links {
      margin-top: 20px;
      text-align: center;
      font-size: 14px;
    }
    .footer-links a {
      color: #00e6e6;
      text-decoration: none;
      margin: 0 8px;
    }
    .footer-links a:hover { text-decoration: underline; }
    .footer-text {
      text-align: center;
      margin-top: 20px;
      color: #aaa;
      font-size: 13px;
    }
    .droplet {
      text-align: center;
      margin-bottom: 20px;
      font-size: 40px;
      color: #00bfff;
    }
    .password-wrapper {
      position: relative;
    }
    .toggle-password {
      position: absolute;
      top: 70%;
      right: 12px;
      transform: translateY(-40%);
      cursor: pointer;
      font-size: 1rem;
      color: #aaa;
      transition: color 0.3s ease;
    }
    .toggle-password:hover {
      color: #00e6ff;
    }
    /* smooth fade for error */
    #errorAlert {
      opacity: 1;
      transition: opacity 0.8s ease, transform 0.8s ease;
    }
    #errorAlert.fade-out {
      opacity: 0;
      transform: translateY(-10px);
    }
  </style>
</head>
<body>

  <div class="waves"></div>

  <div class="login-container">
    <div class="droplet">💧</div>
    <h2><span style="font-size: 25px;">WATER QUALITY TESTING & MONITORING SYSTEM</span></h2>

    <?php if (!empty($error)): ?>
      <div id="errorAlert" class="alert alert-danger text-center">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <div class="mb-3">
        <label for="username" class="form-label">Email</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>
      <div class="mb-3 password-wrapper">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <i class="fa-solid fa-eye-slash toggle-password" id="togglePassword"></i>
      </div>
      <button type="submit" class="btn btn-login w-100">Login</button>
    </form>

    <div class="footer-links">
      <a href="forgot_password.php">Forgot Password?</a> |
      <a href="register.php">Register</a>
    </div>

    <div class="footer-text">© 2025 Water Quality Testing & Monitoring System</div>
  </div>

<script>
const togglePassword = document.getElementById("togglePassword");
const passwordField = document.getElementById("password");

togglePassword.addEventListener("click", function () {
  const isPassword = passwordField.type === "password";
  const cursorPos = passwordField.selectionStart; // save caret
  passwordField.type = isPassword ? "text" : "password";
  this.classList.toggle("fa-eye");
  this.classList.toggle("fa-eye-slash");
  // restore focus and caret
  passwordField.focus();
  passwordField.setSelectionRange(cursorPos, cursorPos);
});

// auto-fade error after 2s
document.addEventListener("DOMContentLoaded", function() {
  const alertBox = document.getElementById("errorAlert");
  if (alertBox) {
    setTimeout(() => {
      alertBox.classList.add("fade-out");
      setTimeout(() => alertBox.remove(), 800); // remove after animation
    }, 2000);
  }
});
</script>

</body>
</html>
