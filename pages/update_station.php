<?php
include("../includes/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $station_id = intval($_POST['station_id']);
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $user_id = $_SESSION['user_id'];

    if (!empty($station_id) && !empty($name) && !empty($location)) {
        // Verify that this station belongs to the logged-in user
        $check = $conn->prepare("
            SELECT r.station_id 
            FROM refilling_stations r
            INNER JOIN user_stations us ON r.station_id = us.station_id
            WHERE r.station_id = ? AND us.user_id = ?
        ");
        $check->bind_param("ii", $station_id, $user_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // Update station
            $stmt = $conn->prepare("UPDATE refilling_stations SET name = ?, location = ? WHERE station_id = ?");
            $stmt->bind_param("ssi", $name, $location, $station_id);

            if ($stmt->execute()) {
                header("Location: stations.php?success=1");
                exit();
            } else {
                echo "Error updating station.";
            }
        } else {
            echo "Unauthorized action.";
        }
    } else {
        echo "Invalid input.";
    }
} else {
    header("Location: stations.php");
    exit();
}
