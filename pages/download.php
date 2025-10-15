<?php
session_start();
include("../includes/db.php"); // mysqli connection $conn

$stationId = isset($_GET['station_id']) ? (int)$_GET['station_id'] : null;

if (!$stationId) {
  echo "Missing station ID.";
  exit;
}

// Fetch latest data
$sql = "SELECT * FROM water_data WHERE station_id = ? ORDER BY timestamp DESC LIMIT 1";
$results = [];

if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $stationId);
  $stmt->execute();
  $res = $stmt->get_result();
  $results = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

// If ?download=1 → trigger CSV
if (isset($_GET['download']) && !empty($results)) {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="water_parameters_station_' . $stationId . '.csv"');

  $output = fopen('php://output', 'w');
  fputcsv($output, array_keys($results[0]));
  foreach ($results as $row) {
    fputcsv($output, $row);
  }
  fclose($output);
  exit;
}

// fallback if no results
echo "No data available for this station.";
exit;
