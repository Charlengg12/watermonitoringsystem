<?php
$host = "localhost"; // stays the same on cPanel
$user = "ehubph_Andrei";
$port = "3306";
$pass = "@Charles291";
$db   = "ehubph_water_monitoring";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
b 