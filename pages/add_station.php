<?php
include("../includes/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $device_sensor_id = trim($_POST['device_sensor_id']);

    if (!empty($name) && !empty($location) && !empty($device_sensor_id)) {
        $stmt = $conn->prepare("INSERT INTO refilling_stations (name, location, device_sensor_id, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $location, $device_sensor_id, $user_id);

        if ($stmt->execute()) {
            echo "<script>alert('Station added successfully.'); window.location='stations.php';</script>";
        } else {
            echo "<script>alert('Error adding station. Please try again.'); window.location='stations.php';</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('All fields are required.'); window.location='stations.php';</script>";
    }
}
$conn->close();
?>
