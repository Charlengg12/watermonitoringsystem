<?php
session_start();

// Persist selected station across pages using session
if (isset($_GET['station_id'])) {
  $_SESSION['station_id'] = (int)$_GET['station_id'];
}
$station_id = $_SESSION['station_id'] ?? null; // no default

// helper for appending station_id to internal links
$sid_q = $station_id ? '?station_id=' . (int)$station_id : '';

// NOTE: Ensure these includes point to your correct files
// include("../includes/db.php"); // Uncomment in your final setup
// include("../includes/fetch_user.php"); // Uncomment in your final setup

// Mocking includes for standalone code execution
class MockDB
{
  public function prepare($sql)
  {
    return (object)['execute' => fn() => true, 'bind_param' => fn() => true, 'get_result' => fn() => (object)['fetch_assoc' => fn() => null, 'num_rows' => 0, 'free_result' => fn() => true], 'close' => fn() => true, 'error' => ''];
  }
  public function query($sql)
  {
    return true;
  }
  public function close()
  {
    return true;
  }
}
$conn = new MockDB();

// Default/mock user data if not fetched
if (!isset($user)) {
  $user = ['profile_pic' => 'https://cdn-icons-png.flaticon.com/512/847/847969.png'];
}

// -----------------------------
// Auto-test settings save (AJAX POST to this file)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_autotest') {
  header('Content-Type: application/json; charset=utf-8');

  $sid = isset($_POST['station_id']) ? (int)$_POST['station_id'] : 0;
  $mode = $_POST['mode'] ?? 'hourly';
  $interval_hours = ($_POST['interval_hours'] !== '') ? (int)$_POST['interval_hours'] : null;
  $interval_days = ($_POST['interval_days'] !== '') ? (int)$_POST['interval_days'] : null;
  $interval_months = ($_POST['interval_months'] !== '') ? (int)$_POST['interval_months'] : null;
  $day_of_month = ($_POST['day_of_month'] !== '') ? (int)$_POST['day_of_month'] : null;
  $time_of_day = $_POST['time_of_day'] !== '' ? $_POST['time_of_day'] : null;
  $enabled = isset($_POST['enabled']) && ($_POST['enabled'] === '1' || $_POST['enabled'] === 'true') ? 1 : 0;

  if (!$sid) {
    echo json_encode(['success' => false, 'message' => 'No station id provided.']);
    exit;
  }

  // NOTE: In a real environment, uncomment the database creation and insert/update logic
  // Mock successful save
  echo json_encode(['success' => true, 'message' => 'Settings saved. (MOCKED)']);
  exit;
}

// -----------------------------
// Defaults & fetch settings
// -----------------------------
$settings = [
  'mode' => 'hourly',
  'interval_hours' => 1,
  'interval_days' => 1,
  'daily_time' => '00:00',
  'interval_months' => 1,
  'day_of_month' => 1,
  'monthly_time' => '00:00',
  'enabled' => 0
];

$station = null;
if ($station_id) {
  // Mock station data fetch
  $station = [
    'station_id' => $station_id,
    'name' => 'Refilling Station ' . $station_id,
    'location' => 'Makati City, PH',
    'device_sensor_id' => 'ESP32-' . $station_id
  ];

  // Mock settings fetch (if database were connected)
  if ($station_id % 2 === 0) {
    $settings['enabled'] = 1;
    $settings['mode'] = 'daily';
    $settings['daily_time'] = '08:00';
  }
}

// -----------------------------
// Fetch available test runs (water_data) only if station selected
// -----------------------------
$testRunsResult = null;
if ($station_id) {
  // Mock test runs result
  $mockTestRuns = [
    ['waterdata_id' => 101, 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['waterdata_id' => 102, 'timestamp' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
    ['waterdata_id' => 103, 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day'))],
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Water Quality Monitor</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <style>
    body {
      background: #0e1117;
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
    }

    .navbar {
      background-color: #1f2733;
      box-shadow: 0 2px 10px rgba(0, 198, 255, 0.2);
    }

    .navbar-brand,
    .nav-link,
    .btn {
      color: #fff;
    }

    .navbar-nav .nav-link:hover {
      color: #00c6ff;
    }

    .container {
      background: #1f2733;
      border-radius: 20px;
      padding: 30px;
      margin-top: 30px;
      box-shadow: 0 0 30px rgba(0, 198, 255, 0.1);
    }

    /* Header row (Start+Settings | Center info | Results) */
    .header-bar {
      gap: 16px;
    }

    .header-bar .middle {
      flex: 1 1 520px;
      min-width: 320px;
    }

    .header-bar .btn {
      white-space: nowrap;
    }

    .btn-gear {
      width: 60px;
    }

    .station-name {
      font-size: 22px;
      font-weight: bold;
      color: #00c6ff;
      text-align: center;
    }

    .station-address {
      font-size: 14px;
      color: #ccc;
      text-align: center;
      margin-bottom: 4px;
    }

    .sensor-id {
      text-align: center;
    }

    .sensor-id p {
      margin: 0;
    }

    .timestamp {
      text-align: center;
      font-size: 14px;
      color: #aaa;
      margin-top: 6px;
    }

    /* Status Colors */
    .status-safe {
      background-color: #0d3d19;
      color: #38c172;
      /* Green */
    }

    .status-neutral {
      background-color: #4b3d0a;
      color: #ffed4a;
      /* Yellow */
    }

    .status-warning {
      background-color: #6d4b00;
      color: #ffa000;
      /* Orange */
    }

    .status-failed {
      background-color: #5d0f1a;
      color: #e3342f;
      /* Red */
    }

    /* Sensor Status Label */
    .status-label {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 20px;
      font-weight: bold;
      margin: 0 auto 6px auto;
      padding: 6px 20px;
      border: 2px solid #00c6ff;
      /* Match primary color */
      border-radius: 30px;
      background: #14181f;
      color: white;
      box-shadow: 0 0 10px rgba(0, 198, 255, 0.5);
    }

    /* Specific styling for the status below each gauge */
    .gauge-label+.status-label {
      border: none;
      background: #14181f;
      padding: 4px 10px;
      font-size: 16px !important;
      border-radius: 15px;
      box-shadow: none;
    }

    .status-dot {
      display: inline-block;
      width: 14px;
      height: 14px;
      border-radius: 50%;
    }

    .status-online .status-dot {
      background: #28a745;
    }

    /* green dot */
    .status-offline .status-dot {
      background: #dc3545;
    }

    /* red dot */


    /* Gauges (The Wheel Structure) */
    .gauge {
      position: relative;
      width: 300px;
      height: 300px;
      border-radius: 50%;
      background: conic-gradient(from 220deg,
          #00c6ff 0deg,
          #28a745 70deg,
          #ffc107 140deg,
          #dc3545 210deg,
          #dc3545 280deg,
          transparent 280deg,
          transparent 360deg);
      border: 10px solid rgb(46, 42, 42);
      box-shadow: inset 0 0 10px #000, 0 0 20px #000;
    }

    .gauge::after {
      content: "";
      position: absolute;
      top: 35px;
      left: 35px;
      right: 35px;
      bottom: 35px;
      background: #1e1e1e;
      border-radius: 50%;
      z-index: 1;
    }

    .gauge::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      border-radius: 50%;
      border: 12px solid #1e1e1e;
      z-index: 2;
    }

    .needle {
      position: absolute;
      width: 4px;
      height: 120px;
      background: #ccc;
      bottom: 50%;
      left: 50%;
      transform-origin: bottom center;
      /* Initial state is handled by JS but set here for safety */
      transform: translate(-50%, 0) rotate(-130deg);
      transition: transform 0.5s ease-out;
      /* Smooth movement */
      z-index: 5;
      /* Increased Z-index to be on top of LCD */
    }

    .center-dot {
      position: absolute;
      width: 20px;
      height: 20px;
      background: #333;
      border-radius: 50%;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 6;
      /* On top of needle */
    }

    .lcd-display {
      position: absolute;
      top: 70%;
      left: 50%;
      transform: translateX(-50%);
      padding: 0 12px;
      background: #0E0F1A;
      color: #00FFCC;
      font-family: 'Courier New', monospace;
      font-size: 36px;
      font-weight: bold;
      border-radius: 10px;
      border: 2px solid rgb(0, 157, 255);
      box-shadow: 0 0 10px #00A3FF, inset 0 0 5px #004d40, 0 0 20px rgba(0, 255, 204, 0.3);
      z-index: 4;
      transition: all 0.4s ease-in-out;
    }

    .status-svg {
      position: absolute;
      top: 0;
      left: 0;
      width: 290px;
      height: 290px;
      pointer-events: none;
      z-index: 4;
    }

    .arc-text {
      font-size: 14px;
      font-weight: bold;
      letter-spacing: 1px;
      fill: white;
    }

    .safe-text {
      fill: #00c6ff;
    }

    .neutral-text {
      fill: #28a745;
    }

    .warning-text {
      fill: #ffc107;
    }

    .fail-text {
      fill: #dc3545;
    }

    .gauge-label {
      margin-top: 10px;
      color: white;
      font-size: 24px;
      /* Reduced font size to accommodate unit */
      text-align: center;
      font-weight: bold;
      animation: glow 1s infinite alternate;
    }

    .gauge-label small {
      display: block;
      font-size: 14px;
      font-weight: normal;
      color: #aaa;
    }

    @keyframes glow {
      from {
        text-shadow: 0 0 5px #00f7ff, 0 0 10px #00e0ff;
      }

      to {
        text-shadow: 0 0 15px #00f7ff, 0 0 25px #00e0ff;
      }
    }

    @media (max-width: 700px) {
      .gauge {
        width: 220px;
        height: 220px;
      }

      .lcd-display {
        font-size: 28px;
      }
    }

    /* small helper for hover cards */
    .hover-card:hover {
      background-color: #1c1c1c !important;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
      transition: 0.2s ease;
    }

    .account-icon {
      width: 24px;
      height: 24px;
      border: 1px solid white;
      box-shadow: 0 0 8px white;
      object-fit: cover;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><i class="fas fa-tint"></i> Water Quality Testing & Monitoring System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="dashboard.php<?= $sid_q ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="notifications.php<?= $sid_q ?>"><i class="fas fa-bell"></i> Notifications</a></li>
          <li class="nav-item"><a class="nav-link" href="stations.php<?= $sid_q ?>"><i class="fas fa-building"></i> Stations</a></li>

          <li class="nav-item">
            <?php
            $profilePic = (!empty($user['profile_pic'])) ? $user['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
            ?>
            <a class="nav-link d-flex align-items-center" href="account.php<?= $sid_q ?>" style="gap: 8px;">
              <img src="<?= htmlspecialchars($profilePic) ?>" alt="Account" class="rounded-circle account-icon">
              <span class="account-text">Account</span>
            </a>
          </li>

        </ul>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <div class="header-bar d-flex justify-content-between align-items-start flex-wrap mb-4">
      <div class="btn-group">
        <button id="startBtn" class="btn btn-primary btn-lg px-4" style="background:#00c6ff; border:none; font-weight:bold;">
          <i class="fas fa-play"></i> Start Testing
        </button>
        <button id="openSettingsBtn" class="btn btn-secondary btn-lg btn-gear" style="background:#005f7f; border:none;"
          data-bs-toggle="modal" data-bs-target="#settingsModal" title="Auto Test Settings">
          <i class="fas fa-cog"></i>
        </button>
      </div>

      <div class="middle text-center mx-auto">
        <?php if ($station): ?>
          <div class="status-label status-online" id="sensor-status-label">ONLINE <span class="status-dot"></span></div>
          <div class="station-name"><?= htmlspecialchars($station['name']) ?></div>
          <div class="station-address"><?= htmlspecialchars($station['location']) ?></div>
          <div class="sensor-id">
            <p><strong>Sensor ID:</strong> <?= htmlspecialchars($station['device_sensor_id']) ?></p>
          </div>
        <?php else: ?>
          <div class="status-label status-offline" id="sensor-status-label">OFFLINE <span class="status-dot"></span></div>
          <div class="station-name">No Station Selected</div>
          <div class="station-address">—</div>
          <div class="sensor-id">
            <p class="text-warning m-0">Please Select Station First!</p>
          </div>
        <?php endif; ?>
        <div class="timestamp">Last Update: <span id="last-update-timestamp">N/A</span></div>
      </div>

      <button id="resultsBtn" class="btn btn-success btn-lg px-4"
        style="background:#28a745; border:none; font-weight:bold;">
        <i class="fas fa-clipboard-list"></i> Test Results
      </button>

    </div>

    <div id="selectStationAlert" class="alert alert-warning mt-3 d-none text-center">
      Please select a station first before viewing test results.
    </div>

    <div class="d-flex flex-wrap justify-content-center text-center mt-5" style="gap: 40px;">
      <?php
      // Define ALL FIVE parameters for the gauge loop
      $parameters = [
        ['label' => 'TDS', 'unit' => 'mg/L', 'id' => 'tds'],
        ['label' => 'pH', 'unit' => '', 'id' => 'ph'],
        ['label' => 'Turbidity', 'unit' => 'NTU', 'id' => 'turbidity'],
        ['label' => 'Lead', 'unit' => 'mg/L', 'id' => 'lead'],
        ['label' => 'Color', 'unit' => 'Analysis', 'id' => 'color']
      ];

      foreach ($parameters as $param):
        $id = $param['id'];
        $label = $param['label'];
        $unit = $param['unit'];
      ?>
        <div class="mb-5">
          <div class="gauge">
            <div class="needle" id="needle-<?= $id ?>"></div>
            <div class="center-dot"></div>
            <div class="lcd-display" id="<?= $id ?>-gauge-value">--</div>

            <svg class="status-svg" viewBox="0 0 350 350">
              <defs>
                <path id="arc-safe-<?= $id ?>" d="M 75 230 A 125 125 0 0 1 140 60" />
                <path id="arc-neutral-<?= $id ?>" d="M 75 120 A 125 125 0 0 1 180 60" />
                <path id="arc-warning-<?= $id ?>" d="M 195 70 A 125 125 0 0 1 280 140" />
                <path id="arc-fail-<?= $id ?>" d="M 275 170 A 100 100 0 0 1 255 240" />
              </defs>
              <text class="arc-text safe-text">
                <textPath href="#arc-safe-<?= $id ?>">SAFE</textPath>
              </text>
              <text class="arc-text neutral-text">
                <textPath href="#arc-neutral-<?= $id ?>">NEUTRAL</textPath>
              </text>
              <text class="arc-text warning-text">
                <textPath href="#arc-warning-<?= $id ?>">WARNING</textPath>
              </text>
              <text class="arc-text fail-text">
                <textPath href="#arc-fail-<?= $id ?>">FAILED</textPath>
              </text>
            </svg>
          </div>
          <div class="gauge-label"><?= htmlspecialchars($label) ?>
            <small class="text-secondary"><?= htmlspecialchars($unit) ?></small>
          </div>
          <div id="<?= $id ?>-status" class="status-label mt-2">Connecting...</div>

        </div>
      <?php endforeach; ?>
      <p id="status-message" class="w-100 text-center text-muted mt-4">Connecting to ESP32...</p>
    </div>

    <div class="modal fade" id="resultsModal" tabindex="-1" aria-labelledby="resultsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:#1f2733; color:white; border-radius:15px;">
          <div class="modal-header border-0">
            <h5 class="modal-title" id="resultsModalLabel">
              <i class="fas fa-clipboard-check"></i> Water Quality Test Results
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">

            <div id="testListView" class="mt-3">
              <h5 class="text-white mb-3">
                <i class="fas fa-history me-2"></i> Available Test Runs
              </h5>

              <div class="d-flex align-items-center mb-3" style="max-width:260px;">
                <input type="date" id="filterDate" class="form-control bg-dark text-white border-secondary me-2">
                <button id="resetFilter" class="btn btn-outline-light d-none" title="Clear filter">&times;</button>
              </div>

              <div id="testRunsList" class="d-flex flex-column gap-3">
                <?php if ($station_id): ?>
                  <?php if (isset($mockTestRuns)): // Use the mock data 
                  ?>
                    <?php foreach ($mockTestRuns as $row): ?>
                      <?php
                      $date = date("Y-m-d", strtotime($row['timestamp']));
                      $time = date("h:i A", strtotime($row['timestamp']));
                      ?>
                      <div class="card bg-dark text-white shadow-sm border-0 rounded-3 hover-card view-test"
                        data-id="<?= $row['waterdata_id'] ?>" style="cursor:pointer;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                          <div>
                            <h6 class="mb-1 fw-bold">
                              <i class="fas fa-calendar-day me-2"></i> <?= $date ?>
                            </h6>
                            <small class="text-secondary">
                              <i class="fas fa-clock me-1"></i> <?= $time ?>
                            </small>
                          </div>
                          <a href="download.php?station_id=<?= $station_id ?>&test_id=<?= $row['waterdata_id'] ?>"
                            class="btn btn-sm btn-info" target="_blank">
                            <i class="fas fa-eye"></i> View
                          </a>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p class="text-white text-center">No available test runs for this station (MOCK).</p>
                  <?php endif; ?>
                <?php else: ?>
                  <p class="text-muted text-center">No station selected. Please select a station first to view test runs.</p>
                <?php endif; ?>
              </div>
            </div>

          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#1f2733; color:white; border-radius:15px;">
          <div class="modal-header border-0">
            <h5 class="modal-title" id="settingsModalLabel"><i class="fas fa-cog"></i> Auto Test Settings</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="autoTestForm">
              <div class="mb-3">
                <label class="form-label">Choose Frequency</label>
                <select id="frequencySelect" class="form-select bg-dark text-white border-secondary">
                  <option value="hourly">Every X Hours</option>
                  <option value="daily">Every X Days</option>
                  <option value="monthly">Every X Months</option>
                </select>
              </div>

              <div class="mb-3 freq-option" id="hourlyOption">
                <label class="form-label">Every how many hours?</label>
                <input id="hourlyHours" type="number" class="form-control bg-dark text-white border-secondary" min="1" value="<?= htmlspecialchars($settings['interval_hours']) ?>">
              </div>

              <div class="mb-3 freq-option d-none" id="dailyOption">
                <label class="form-label">Every how many days?</label>
                <input id="dailyDays" type="number" class="form-control bg-dark text-white border-secondary mb-2" min="1" value="<?= htmlspecialchars($settings['interval_days']) ?>">
                <label class="form-label">At what time?</label>
                <input id="dailyTime" type="time" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['daily_time']) ?>">
              </div>

              <div class="mb-3 freq-option d-none" id="monthlyOption">
                <label class="form-label">Every how many months?</label>
                <input id="monthlyMonths" type="number" class="form-control bg-dark text-white border-secondary mb-2" min="1" value="<?= htmlspecialchars($settings['interval_months']) ?>">
                <label class="form-label">On what day of the month?</label>
                <input id="monthlyDay" type="number" class="form-control bg-dark text-white border-secondary mb-2" min="1" max="31" value="<?= htmlspecialchars($settings['day_of_month']) ?>">
                <label class="form-label">At what time?</label>
                <input id="monthlyTime" type="time" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['monthly_time']) ?>">
              </div>

              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="autoEnable" <?= $settings['enabled'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="autoEnable">Enable automatic testing</label>
              </div>

              <div id="settingsAlert" class="alert d-none" role="alert"></div>

              <div class="d-grid">
                <button type="button" id="saveAutoTest" class="btn btn-primary" style="background:#00c6ff; border:none;">Save Settings</button>
              </div>
            </form>
          </div>
          <div class="modal-footer border-0">
            <small class="text-muted">Saved per station (if station selected) so IoT/scheduler can use it.</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mt-5">
    <img src="Water Quality Parameters Table.jpg" alt="Water Quality Parameters Table" class="img-fluid" style="max-width: 1000px;">
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // --- ESP32 DATA FETCHING AND UI UPDATES ---

    // IMPORTANT: This IP address is now set to 192.168.1.100
    const ESP32_IP = "http://192.168.1.100";
    const READINGS_URL = ESP32_IP + '/readings';
    const START_TEST_URL = ESP32_IP + '/start_test';

    const GAUGE_CONFIG = {
      // Parameter: [Max Value for Gauge, Rotation Offset (degrees), Decimals]
      'tds': [1000, -130, 0], // TDS up to 1000 mg/L
      'ph': [14, -130, 2],
      'turbidity': [10, -130, 2],
      'lead': [0.012, -130, 4], // Lead needs high precision
      'color': [100, -130, 0] // Color doesn't use needle for value, but needs a config entry
    };

    /**
     * Calculates the rotation degree for the needle based on the sensor value.
     * The gauge runs from -130 degrees (min value) to +130 degrees (max value).
     * @param {string} param - The sensor parameter key (e.g., 'tds').
     * @param {number} value - The current sensor reading.
     * @returns {number} The rotation in degrees.
     */
    function getNeedleRotation(param, value) {
      const [maxValue, minRotation] = GAUGE_CONFIG[param];
      const maxRotation = Math.abs(minRotation);
      const range = maxRotation - minRotation; // Total 260 degrees

      // Clamp the value within the measurable range
      let clampedValue = Math.min(Math.max(0, value), maxValue);

      // Calculate the percentage of the value within the max range
      let percentage = clampedValue / maxValue;

      // Convert percentage to a degree rotation within the -130 to +130 range
      // Rotation = minRotation + (percentage * range)
      return minRotation + (percentage * range);
    }

    /**
     * Rotates the CSS transform of a gauge needle element.
     * @param {string} param - The sensor parameter key (e.g., 'tds').
     * @param {number} value - The current sensor reading.
     */
    function rotateNeedle(param, value) {
      const needle = document.getElementById(needle - $ {
        param
      });
      if (!needle) return;

      // Special handling for the 'color' gauge: it's analysis, not a direct reading.
      // It will be reset to 0/min rotation in updateGaugeDisplay.
      if (param === 'color') {
        needle.style.transform = translate(-50 % , 0) rotate($ {
            GAUGE_CONFIG[param][1]
          }
          deg);
        return;
      }

      const rotation = getNeedleRotation(param, value);
      needle.style.transform = translate(-50 % , 0) rotate($ {
          rotation
        }
        deg);
    }

    /**
     * Updates the gauge display (LCD value, needle, and status label).
     * @param {string} param - The sensor parameter key.
     * @param {Object} data - The sensor data object from the ESP32.
     * @param {number} [decimals=0] - Number of decimals to display.
     */
    function updateGaugeDisplay(param, data, decimals = 0) {
      const paramValueKey = param.charAt(0).toUpperCase() + param.slice(1) + '_Value';
      const paramStatusKey = param.charAt(0).toUpperCase() + param.slice(1) + '_Status';

      let displayValue;
      let displayStatus;
      let sensorValue = 0; // Default sensor value for needle calculation

      if (param === 'color') {
        displayValue = data.Color_Result || '--';
        displayStatus = data.Color_Status || 'N/A';
        // Set needle to min rotation for color
        rotateNeedle(param, 0);
      } else {
        const value = parseFloat(data[paramValueKey]);
        sensorValue = isNaN(value) ? 0 : value;
        displayValue = isNaN(value) ? '--' : value.toFixed(decimals);
        displayStatus = data[paramStatusKey] || 'N/A';

        // Rotate the needle only if it's a numerical gauge
        rotateNeedle(param, sensorValue);
      }

      // Update LCD value
      const lcdElement = document.getElementById($ {
        param
      } - gauge - value);
      if (lcdElement) {
        lcdElement.textContent = displayValue;
      }

      // Update Status Label
      const statusElement = document.getElementById($ {
        param
      } - status);
      if (statusElement) {
        statusElement.className = 'status-label mt-2';

        const statusClass = displayStatus.toLowerCase();
        if (statusClass === 'safe') {
          statusElement.classList.add('status-safe');
        } else if (statusClass === 'neutral') {
          statusElement.classList.add('status-neutral');
        } else if (statusClass === 'warning') {
          statusElement.classList.add('status-warning');
        } else if (statusClass === 'failed' || statusClass === 'error') {
          statusElement.classList.add('status-failed');
        }
        statusElement.textContent = displayStatus;
      }
    }

    /**
     * Fetches the latest sensor readings from the ESP32 and updates the dashboard.
     */
    function fetchReadings() {
      const timestampElement = document.getElementById('last-update-timestamp');
      const statusMessageElement = document.getElementById('status-message');
      const sensorStatusLabel = document.getElementById('sensor-status-label');

      fetch(READINGS_URL)
        .then(response => {
          if (response.status === 200) {
            sensorStatusLabel.className = 'status-label status-online';
            sensorStatusLabel.innerHTML = 'ONLINE <span class="status-dot"></span>';
            statusMessageElement.textContent = Connected to ESP32 at $ {
              ESP32_IP
            };
            return response.json();
          } else if (response.status === 503) {
            statusMessageElement.textContent = ESP32 is busy running test cycle.Retrying in 5 s...;
            throw new Error('System Busy');
          } else {
            throw new Error(HTTP Status Error: $ {
              response.status
            });
          }
        })
        .then(data => {
          timestampElement.textContent = new Date().toLocaleTimeString();

          for (const param in GAUGE_CONFIG) {
            const [, , decimals] = GAUGE_CONFIG[param];
            updateGaugeDisplay(param, data, decimals);
          }
        })
        .catch(error => {
          console.error('Fetch Readings Error:', error);
          if (error.message !== 'System Busy') {
            sensorStatusLabel.className = 'status-label status-offline';
            sensorStatusLabel.innerHTML = 'OFFLINE <span class="status-dot"></span>';
            timestampElement.textContent = 'Connection Failed';
            statusMessageElement.textContent = CRITICAL: Could not reach ESP32 at $ {
              ESP32_IP
            }.Check power / network.;
          }
          // Reset gauges to error state
          for (const param in GAUGE_CONFIG) {
            rotateNeedle(param, GAUGE_CONFIG[param][1]); // Point to min rotation
            document.getElementById($ {
              param
            } - gauge - value).textContent = '--';
            document.getElementById($ {
              param
            } - status).textContent = (error.message.includes('System Busy')) ? 'BUSY' : 'Error';
            document.getElementById($ {
              param
            } - status).className = 'status-label mt-2 status-failed';
          }
        });
    }
    // -------------------------------------------------------------
    // --- DOCUMENT READY LOGIC ---
    // -------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
      const stationId = '<?= $station_id ?>';

      // 1. Initial setup and polling
      if (stationId) {
        fetchReadings();
        setInterval(fetchReadings, 5000); // Poll every 5 seconds
      } else {
        document.getElementById('status-message').textContent = 'Please select a refilling station to start monitoring.';
      }


      // 2. Handle "Start Testing" button action
      document.getElementById('startBtn').addEventListener('click', () => {
        if (!stationId) {
          alert("Please select a station first.");
          return;
        }

        fetch(START_TEST_URL, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          })
          .then(response => {
            if (response.status === 200) {
              alert("Test cycle successfully triggered.");
            } else if (response.status === 409) {
              alert("System is currently busy. Please wait for the current cycle to finish.");
            } else {
              alert("Failed to start test. Status: " + response.status);
            }
            fetchReadings();
          })
          .catch(error => {
            console.error('Start Test Error:', error);
            alert("Connection error: Could not reach ESP32. Check network and IP.");
          });
      });

      // 3. Auto Test Settings Modal Logic (Frequency switching)
      const frequencySelect = document.getElementById('frequencySelect');
      const options = {
        'hourly': document.getElementById('hourlyOption'),
        'daily': document.getElementById('dailyOption'),
        'monthly': document.getElementById('monthlyOption')
      };

      const initialMode = '<?= $settings['mode'] ?>';
      if (frequencySelect) {
        frequencySelect.value = initialMode;
        for (const mode in options) {
          options[mode].classList.add('d-none');
        }
        if (options[initialMode]) {
          options[initialMode].classList.remove('d-none');
        }
      }

      frequencySelect.addEventListener('change', (e) => {
        const selectedMode = e.target.value;
        for (const mode in options) {
          options[mode].classList.add('d-none');
        }
        if (options[selectedMode]) {
          options[selectedMode].classList.remove('d-none');
        }
      });

      // 4. Auto Test Save Button (AJAX)
      document.getElementById('saveAutoTest').addEventListener('click', () => {
        const sid = stationId;
        if (!sid) {
          document.getElementById('settingsAlert').className = 'alert alert-danger';
          document.getElementById('settingsAlert').textContent = 'Error: Please select a station first.';
          document.getElementById('settingsAlert').classList.remove('d-none');
          return;
        }

        const mode = frequencySelect.value;
        const enabled = document.getElementById('autoEnable').checked ? 1 : 0;
        const alertElement = document.getElementById('settingsAlert');
        alertElement.classList.add('d-none');

        const formData = {
          action: 'save_autotest',
          station_id: sid,
          mode: mode,
          enabled: enabled,
          interval_hours: mode === 'hourly' ? document.getElementById('hourlyHours').value : null,
          interval_days: mode === 'daily' ? document.getElementById('dailyDays').value : null,
          time_of_day: mode === 'daily' ? document.getElementById('dailyTime').value : null,
          interval_months: mode === 'monthly' ? document.getElementById('monthlyMonths').value : null,
          day_of_month: mode === 'monthly' ? document.getElementById('monthlyDay').value : null
        };
        if (mode === 'monthly') {
          formData.time_of_day = document.getElementById('monthlyTime').value;
        }

        // Simple validation
        if (enabled && ((mode === 'hourly' && !formData.interval_hours) ||
            (mode === 'daily' && (!formData.interval_days || !formData.time_of_day)) ||
            (mode === 'monthly' && (!formData.interval_months || !formData.day_of_month || !formData.time_of_day))
          )) {
          alertElement.className = 'alert alert-warning';
          alertElement.textContent = 'Please fill out all required fields for the selected automatic mode.';
          alertElement.classList.remove('d-none');
          return;
        }

        fetch('dashboard.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(formData)
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alertElement.className = 'alert alert-success';
              alertElement.textContent = data.message || 'Settings saved successfully.';
            } else {
              alertElement.className = 'alert alert-danger';
              alertElement.textContent = data.message || 'Failed to save settings.';
            }
            alertElement.classList.remove('d-none');
          })
          .catch(error => {
            console.error('Save error:', error);
            alertElement.className = 'alert alert-danger';
            alertElement.textContent = 'Network error while saving settings.';
            alertElement.classList.remove('d-none');
          });
      });

      // 5. Test Results Modal Logic
      const resultsBtn = document.getElementById('resultsBtn');
      const selectStationAlert = document.getElementById('selectStationAlert');
      const resultsModal = new bootstrap.Modal(document.getElementById('resultsModal'));

      resultsBtn.addEventListener('click', () => {
        if (stationId) {
          selectStationAlert.classList.add('d-none');
          resultsModal.show();
        } else {
          selectStationAlert.classList.remove('d-none');
        }
      });

      // 6. Results Modal Filtering Logic (Mock)
      const filterDate = document.getElementById('filterDate');
      const resetFilterBtn = document.getElementById('resetFilter');
      const testRunsList = document.getElementById('testRunsList');

      filterDate.addEventListener('change', (e) => {
        const filterValue = e.target.value;
        if (filterValue) {
          resetFilterBtn.classList.remove('d-none');
        } else {
          resetFilterBtn.classList.add('d-none');
        }

        testRunsList.querySelectorAll('.view-test').forEach(card => {
          // Mock: Hides all cards if a filter is set, shows all if cleared
          card.style.display = filterValue ? 'none' : 'flex';
        });
      });

      resetFilterBtn.addEventListener('click', () => {
        filterDate.value = '';
        resetFilterBtn.classList.add('d-none');
        testRunsList.querySelectorAll('.view-test').forEach(card => {
          card.style.display = 'flex';
        });
      });

    });
  </script>
  <script>
    document.getElementById("resultsBtn").addEventListener("click", function() {
      const stationId = <?php echo json_encode($station_id ?? null); ?>;
      if (!stationId) {
        alert("Please select a station first.");
        return;
      }

      // ✅ FIXED: remove extra "pages/"
      const downloadUrl = `download.php?station_id=${stationId}&download=1`;
      window.location.href = downloadUrl;
    });
  </script>


</body>

</html>