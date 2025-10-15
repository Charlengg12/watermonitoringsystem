<?php
include("../includes/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $number = $_POST['number'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, number, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullname, $email, $number, $hashed_password);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Water Quality Testing & Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
    .waves::before {
      animation: waveMove 8s ease-in-out infinite alternate-reverse;
    }
    .waves::after {
      animation: waveMove 10s ease-in-out infinite alternate;
    }
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
    .form-label {
      color: #ccc;
    }
    .form-control {
      border-radius: 10px;
      background-color: #1e1e1e;
      border: 1px solid #333;
      color: #fff;
      padding-right: 40px; /* space for eye icon */
    }
    .form-control:focus {
      background-color: #1e1e1e;
      color: #fff;
      box-shadow: 0 0 10px #00e6e6;
      border-color: #00e6e6;
    }
    .password-toggle {
      position: absolute;
      top: 70%;
      right: 12px;
      transform: translateY(-40%);
      cursor: pointer;
      font-size: 1rem;
      color: #aaa;
      transition: color 0.3s ease, transform 0.2s;
    }
    .password-toggle:hover {
      color: #00e6e6;
      transform: translateY(-50%) scale(1.1);
    }
    .form-group {
      position: relative;
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
    .footer-links a:hover {
      text-decoration: underline;
    }
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
  </style>
</head>
<body>
  <div class="waves"></div>
  <div class="login-container">
    <div class="droplet">💧</div>
    <h2><span style="font-size: 25px;">WATER QUALITY TESTING & MONITORING SYSTEM</h2>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="fullname" class="form-label">Full Name</label>
        <input type="text" class="form-control" name="fullname" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" name="email" required>
      </div>
      <div class="mb-3">
        <label for="number" class="form-label">Phone Number</label>
        <input type="text" class="form-control" name="number" required>
      </div>
      <div class="mb-3 form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" class="form-control" name="password" required>
        <i class="fa-solid fa-eye-slash password-toggle" data-target="password"></i>
      </div>
      <div class="mb-3 form-group">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <input type="password" id="confirm_password" class="form-control" name="confirm_password" required>
        <i class="fa-solid fa-eye-slash password-toggle" data-target="confirm_password"></i>
      </div>
      <button class="btn btn-login w-100" type="submit">Register</button>
    </form>
    <div class="footer-links">
      <a href="login.php">Already have an account? Login</a>
    </div>
    <div class="footer-text">© 2025 Water Quality Testing & Monitoring System</div>
  </div>

  <script>
    document.querySelectorAll(".password-toggle").forEach(icon => {
      icon.addEventListener("click", () => {
        const targetId = icon.getAttribute("data-target");
        const input = document.getElementById(targetId);
        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        icon.classList.toggle("fa-eye", isPassword);
        icon.classList.toggle("fa-eye-slash", !isPassword);

        // keep focus on input
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
      });
    });
  </script>
</body>
</html>
