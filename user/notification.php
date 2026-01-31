<?php
include 'db.php';
session_start();

if (!isset($_SESSION['reg_no'])) {
    header("Location: login.php");
    exit();
}

$conn->set_charset("utf8mb4");

$reg_no = $_SESSION['reg_no'];

// ✅ Fetch student profile
$profile = $conn->query("SELECT * FROM students WHERE reg_no='$reg_no'")->fetch_assoc();

// ✅ Fetch all notifications
$notifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");

// ✅ Mark latest as read
$latest_note = $conn->query("SELECT id FROM notifications ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($latest_note) {
    $stmt = $conn->prepare("UPDATE students SET last_seen_notification_id = ? WHERE reg_no = ?");
    $stmt->bind_param("is", $latest_note['id'], $reg_no);
    $stmt->execute();
    $stmt->close();
}

// ✅ Helper function to limit words
function limit_words($string, $word_limit = 20) {
    $words = explode(" ", strip_tags($string));
    if (count($words) > $word_limit) {
        return implode(" ", array_slice($words, 0, $word_limit)) . "...";
    } else {
        return implode(" ", $words);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <meta charset="UTF-8">
  <title>Notifications</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #f0f4ff, #e9eff4);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #222;
    }

    /* Sidebar */
    .sidebar {
      background: #1d2851ff;
      border-radius: 12px;
      min-height: 100vh;
      box-shadow: 0px 0px 15px rgba(0,0,0,0.3);
      position: sticky;
      top: 0;
    }
    .sidebar h3 {
      font-size: 1.4rem;
      font-weight: bold;
    }
    .sidebar-nav .nav-link {
      font-size: 1.1rem;
      font-weight: 500;
      padding: 12px 15px;
      margin-bottom: 8px;
      border-radius: 8px;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
    }
    .sidebar-nav .nav-link:hover {
      background: #7283beff;
      transform: translateX(6px);
      color: white !important;
      box-shadow: 0px 4px 12px rgba(0,0,0,0.3);
    }
    .sidebar-nav .nav-link.active {
      background: #5c6a9cff;
      color: whitesmoke !important;
      font-weight: 600;
    }

    /* Notification Cards */
    .notification-card {
      border-radius: 12px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      animation: fadeInUp 0.6s ease;
    }
    .notification-card:hover {
      transform: translateY(-6px) scale(1.02);
      box-shadow: 0px 8px 25px rgba(0,0,0,0.25);
    }
    .card-title {
      font-weight: 600;
      color: #1d2851;
    }

    /* Fade-in animation */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Modal Styling */
    .modal-content {
      border-radius: 12px;
      animation: scaleUp 0.3s ease;
    }
    @keyframes scaleUp {
      from { transform: scale(0.9); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

  </style>
  <link rel="icon" type="image/png" href="../b2c_logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

</head>
<body>
<div class="container-fluid">
  <div class="row">

    <!-- Sidebar -->
    <div class="col-md-3 sidebar text-white p-3">
      <h3 class="mb-4">👋 Welcome,<br> <?php echo htmlspecialchars($profile['name']); ?></h3>
      <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
          <a href="std_dashboard.php" class="nav-link text-white">🧑‍🎓 Profile</a>
        </li>
        <li class="nav-item">
          <a href="job-opp.php" class="nav-link text-white">💼 Job Opportunities</a>
        </li>
        <li class="nav-item">
          <a href="std_dashboard.php#applications" class="nav-link text-white">📑 My Applications</a>
        </li>
        <li class="nav-item">
          <a href="run_ats.php" class="nav-link text-white">⚡ Check ATS Score</a>
        </li>
        <li class="nav-item">
          <a href="notification.php" class="nav-link active">🔔 Notifications</a>
        </li>
        <li class="nav-item">
          <a href="logout.php" class="nav-link text-danger">🚪 Logout</a>
        </li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 p-4">
      <h3 class="mb-4 fw-bold">📢 Notifications</h3>
      <div class="row g-3 notification-container">
        <?php if ($notifications->num_rows > 0) { ?>
          <?php while($note = $notifications->fetch_assoc()){ ?>
            <div class="col-md-6 col-lg-4">
              <div class="card shadow-sm h-100 notification-card">
                <div class="card-body">
                  <h5 class="card-title"><?php echo htmlspecialchars($note['title']); ?></h5>
                  <p class="card-text text-muted"><?php echo limit_words($note['message'], 20); ?></p>
                  <button class="btn btn-sm btn-primary"
                          data-bs-toggle="modal" 
                          data-bs-target="#noteModal<?php echo $note['id']; ?>">
                    Read More
                  </button>
                </div>
                <div class="card-footer text-muted small">
                  <?php echo date("d-m-Y H:i", strtotime($note['created_at'])); ?>
                </div>
              </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="noteModal<?php echo $note['id']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><?php echo htmlspecialchars($note['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <?php echo nl2br($note['message']); ?>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          <?php } ?>
        <?php } else { ?>
          <p class="text-muted">No notifications yet.</p>
        <?php } ?>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
