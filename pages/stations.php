<?php
session_start(); // Ensure session is started here
include("../includes/db.php");
include("../includes/fetch_user.php");

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Default/mock user data if not fetched
if (!isset($user)) {
  $user = ['profile_pic' => 'https://cdn-icons-png.flaticon.com/512/847/847969.png'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Stations - Water Quality Monitor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body {
      background: #0e1117;
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
      margin-top: 40px;
    }

    .card {
      background-color: #1f2733;
      color: #fff;
      border: 1px solid #00c6ff;
      border-radius: 15px;
    }

    .card:hover {
      box-shadow: 0 0 15px #00c6ff;
      cursor: pointer;
    }

    .add-btn {
      background-color: #00c6ff;
      border: none;
    }

    .add-btn:hover {
      background-color: #00a3cc;
    }

    .delete-btn {
      background-color: #dc3545;
      border: none;
    }

    .edit-btn {
      background-color: #ffc107;
      border: none;
      color: #000;
    }

    .search-input {
      background-color: #1f2733;
      color: #fff;
      border: 1px solid #00c6ff;
    }

    .search-input:focus {
      background-color: #1f2733;
      color: #fff;
      box-shadow: 0 0 5px #00c6ff;
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
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
          <li class="nav-item"><a class="nav-link" href="stations.php"><i class="fas fa-building"></i> Stations</a></li>
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="account.php" style="gap: 8px;">
              <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Account" class="rounded-circle account-icon">
              <span class="account-text">Account</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container">

    <?php if (isset($_GET['success']) || isset($_GET['error']) || isset($_GET['status'])): ?>
      <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">

        <?php if (isset($_GET['status']) && $_GET['status'] == 'added'): ?>
          <div class="toast align-items-center text-bg-success border-0 show" role="alert">
            <div class="d-flex">
              <div class="toast-body"> Station registered successfully!</div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
          <div class="toast align-items-center text-bg-success border-0 show" role="alert">
            <div class="d-flex">
              <div class="toast-body"> Station updated successfully!</div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
          <div class="toast align-items-center text-bg-danger border-0 show mt-2" role="alert">
            <div class="d-flex">
              <div class="toast-body">
                <?php if ($_GET['error'] == 'invalid_input'): ?>
                  Invalid input. Please fill out all fields.
                <?php elseif ($_GET['error'] == 'unauthorized'): ?>
                  You are not allowed to edit this station.
                <?php elseif ($_GET['error'] == 'update_failed'): ?>
                  Something went wrong while updating the station.
                <?php elseif ($_GET['error'] == 'add_failed'): ?>
                  Failed to add station.
                <?php else: ?>
                  An unknown error occurred.
                <?php endif; ?>
                <?php if (isset($_GET['msg'])) echo " (" . htmlspecialchars(urldecode($_GET['msg'])) . ")"; // Display detailed error from add_station.php 
                ?>
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Water Sensor Station/s</h2>
      <div class="d-flex">
        <form style="max-width: 350px; margin-right: 10px;">
          <div class="input-group" style="height: 38px;">
            <input type="text" class="form-control search-input" placeholder="Search station..." id="searchInput" style="height: 38px; padding: 6px 12px;">
            <button class="btn btn-primary" type="button" id="searchBtn" disabled style="height: 38px;"><i class="fas fa-search"></i></button>
            <button class="btn btn-secondary" type="button" id="clearBtn" style="height: 38px; display: none;"><i class="fas fa-times"></i></button>
          </div>
        </form>

        <button class="btn add-btn me-2" data-bs-toggle="modal" data-bs-target="#addExistingStationModal">
          <i class="fas fa-link"></i> Add Existing Station
        </button>

        <button class="btn add-btn" data-bs-toggle="modal" data-bs-target="#addStationModal">
          <i class="fas fa-plus"></i> Register Station
        </button>
      </div>
    </div>

    <div id="stationsContainer" class="row">
      <?php
      // Database connection check (for safety)
      if (empty($conn->connect_error)) {
        $stmt = $conn->prepare("
          SELECT r.* FROM refilling_stations r
          INNER JOIN user_stations us ON r.station_id = us.station_id
          WHERE us.user_id = ?
          ORDER BY r.name ASC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0):
          while ($station = $result->fetch_assoc()):
      ?>
            <div class="col-md-4 mb-4 station-card">
              <a href="dashboard.php?station_id=<?= $station['station_id'] ?>" class="card p-3 text-decoration-none text-white">
                <h5><i class="fas fa-building"></i> <?= htmlspecialchars($station['name']) ?></h5>
                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($station['location']) ?></p>
                <p><i class="fas fa-microchip"></i> Sensor ID: <?= htmlspecialchars($station['device_sensor_id']) ?></p>
              </a>

              <div class="d-flex gap-2">
                <form method="POST" action="delete_station.php" onsubmit="return confirm('Are you sure you want to delete this station?');">
                  <input type="hidden" name="station_id" value="<?= $station['station_id'] ?>">
                  <button class="btn btn-sm delete-btn mt-2"><i class="fas fa-trash"></i> Delete</button>
                </form>

                <button class="btn btn-sm edit-btn mt-2"
                  data-bs-toggle="modal"
                  data-bs-target="#editStationModal"
                  data-id="<?= $station['station_id'] ?>"
                  data-name="<?= htmlspecialchars($station['name']) ?>"
                  data-location="<?= htmlspecialchars($station['location']) ?>">
                  <i class="fas fa-edit"></i> Edit
                </button>
              </div>
            </div>
          <?php endwhile;
        else: ?>
          <p class="text-center">No stations found.</p>
      <?php endif;
        $stmt->close();
      } else {
        echo '<p class="text-danger text-center">Database connection failed: ' . htmlspecialchars($conn->connect_error) . '</p>';
      }
      ?>
    </div>
  </div>

  <div class="modal fade" id="addStationModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" method="POST" action="add_station.php">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Add Station</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <div class="mb-3"><label class="form-label">Station Name</label><input type="text" class="form-control" name="name" required></div>
          <div class="mb-3"><label class="form-label">Location</label><input type="text" class="form-control" name="location" required></div>
          <div class="mb-3"><label class="form-label">Device Sensor ID</label><input type="text" class="form-control" name="device_sensor_id" required></div>
        </div>
        <div class="modal-footer bg-dark"><button type="submit" class="btn btn-success">Add Station</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="addExistingStationModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" method="POST" action="add_existing_station.php">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Add Existing Station</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <div class="mb-3">
            <label class="form-label">Enter Device Sensor ID</label>
            <input type="text" class="form-control" name="device_sensor_id" required>
          </div>
        </div>
        <div class="modal-footer bg-dark">
          <button type="submit" class="btn btn-success">Add Station</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="editStationModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" method="POST" action="update_station.php">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Edit Station</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <input type="hidden" name="station_id" id="editStationId">
          <div class="mb-3"><label class="form-label">Station Name</label><input type="text" class="form-control" name="name" id="editStationName" required></div>
          <div class="mb-3"><label class="form-label">Location</label><input type="text" class="form-control" name="location" id="editStationLocation" required></div>
        </div>
        <div class="modal-footer bg-dark"><button type="submit" class="btn btn-success">Save Changes</button></div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const searchInput = document.getElementById("searchInput");
    const searchBtn = document.getElementById("searchBtn");
    const clearBtn = document.getElementById("clearBtn");
    const stationsContainer = document.getElementById("stationsContainer");

    function updateButtons() {
      const value = searchInput.value.trim();
      searchBtn.disabled = value === "";
      clearBtn.style.display = value === "" ? "none" : "inline-flex";
    }
    updateButtons();

    searchInput.addEventListener("input", function() {
      updateButtons();
      const value = this.value.trim();
      const xhr = new XMLHttpRequest();
      // Assumes you have a search_stations.php file for AJAX filtering
      xhr.open("GET", "search_stations.php?search=" + encodeURIComponent(value), true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          stationsContainer.innerHTML = xhr.responseText;
        }
      };
      xhr.send();
    });

    clearBtn.addEventListener("click", function() {
      searchInput.value = "";
      updateButtons();
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "search_stations.php?search=", true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          stationsContainer.innerHTML = xhr.responseText;
        }
      };
      xhr.send();
    });

    //  Fill modal with station data
    const editModal = document.getElementById('editStationModal');
    editModal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      document.getElementById('editStationId').value = button.getAttribute('data-id');
      document.getElementById('editStationName').value = button.getAttribute('data-name');
      document.getElementById('editStationLocation').value = button.getAttribute('data-location');
    });

    //  Auto-hide floating toasts after 3s
    document.querySelectorAll('.toast').forEach(toastEl => {
      // Use bootstrap's built-in functionality to initialize and show the toast
      const bsToast = new bootstrap.Toast(toastEl, {
        delay: 3000
      });
      bsToast.show();

      // Set a timeout to manually hide if the user doesn't close it, though the 'delay' option should handle this
      setTimeout(() => {
        bsToast.hide();
      }, 3000);
    });
  </script>
</body>

</html>