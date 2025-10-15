<?php
include("../includes/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get form inputs
$current_password = $_POST['current-password'] ?? '';
$new_password     = $_POST['new-password'] ?? '';
$confirm_password = $_POST['confirm-password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo "<script>alert('All fields are required.'); window.location='settings.php';</script>";
    exit();
}

// Fetch current password hash from DB
$stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($hashed_password);
$stmt->fetch();
$stmt->close();

// Verify current password
if (!password_verify($current_password, $hashed_password)) {
    echo "<script>alert('Current password is incorrect.'); window.location='settings.php';</script>";
    exit();
}

// Check new passwords match
if ($new_password !== $confirm_password) {
    echo "<script>alert('New passwords do not match.'); window.location='settings.php';</script>";
    exit();
}

// Hash new password
$new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update in DB
$update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
$update->bind_param("si", $new_hashed_password, $user_id);
if ($update->execute()) {
    echo "<script>alert('Password updated successfully!'); window.location='settings.php';</script>";
} else {
    echo "<script>alert('Error updating password. Please try again.'); window.location='settings.php';</script>";
}
$update->close();
$conn->close();
?>
