<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration (adjust these to your cPanel MySQL settings)
$db_host = 'localhost';
$db_user = 'your_db_user';
$db_pass = 'your_db_password';
$db_name = 'your_db_name';

// File-based storage as fallback (if not using database)
$data_file = 'sensor_readings.json';

// === POST: Receive data from ESP32 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit();
    }

    // Validate required fields
    $required_fields = [
        'TDS_Value',
        'TDS_Status',
        'PH_Value',
        'PH_Status',
        'Turbidity_Value',
        'Turbidity_Status',
        'Lead_Value',
        'Lead_Status',
        'Color_Result',
        'Color_Status'
    ];

    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing field: $field"]);
            exit();
        }
    }

    // Add timestamp
    $data['timestamp'] = date('Y-m-d H:i:s');

    // Option 1: Store in database
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }

        $stmt = $conn->prepare("INSERT INTO sensor_readings 
            (tds_value, tds_status, ph_value, ph_status, turbidity_value, 
             turbidity_status, lead_value, lead_status, color_result, 
             color_status, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "dssdsdsdsss",
            $data['TDS_Value'],
            $data['TDS_Status'],
            $data['PH_Value'],
            $data['PH_Status'],
            $data['Turbidity_Value'],
            $data['Turbidity_Status'],
            $data['Lead_Value'],
            $data['Lead_Status'],
            $data['Color_Result'],
            $data['Color_Status'],
            $data['timestamp']
        );

        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Fallback to file storage if database fails
        file_put_contents($data_file, json_encode($data));
    }

    // Option 2: Store in file (simpler, no database needed)
    file_put_contents($data_file, json_encode($data));

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Data stored successfully']);
    exit();
}

// === GET: Retrieve latest sensor data ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Option 1: Retrieve from database
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }

        $result = $conn->query("SELECT * FROM sensor_readings ORDER BY timestamp DESC LIMIT 1");

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response = [
                'TDS_Value' => $row['tds_value'],
                'TDS_Status' => $row['tds_status'],
                'PH_Value' => $row['ph_value'],
                'PH_Status' => $row['ph_status'],
                'Turbidity_Value' => $row['turbidity_value'],
                'Turbidity_Status' => $row['turbidity_status'],
                'Lead_Value' => $row['lead_value'],
                'Lead_Status' => $row['lead_status'],
                'Color_Result' => $row['color_result'],
                'Color_Status' => $row['color_status'],
                'timestamp' => $row['timestamp']
            ];
            echo json_encode($response);
        } else {
            http_response_code(503);
            echo json_encode(['error' => 'No data available']);
        }

        $conn->close();
        exit();
    } catch (Exception $e) {
        // Fallback to file storage
    }

    // Option 2: Retrieve from file
    if (file_exists($data_file)) {
        $data = file_get_contents($data_file);
        echo $data;
    } else {
        http_response_code(503);
        echo json_encode(['error' => 'No data available']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
