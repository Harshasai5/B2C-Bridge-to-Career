<?php
session_start();

$conn = new mysqli("localhost", "root", "", "B2C");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ✅ Placement Cell check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'placement_cell') {
    header("Location: PC-dashboard.php");
    exit();
}

$msg = "";

// -------------------- ADD NOTIFICATION --------------------
if (isset($_POST['add_notification'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    if (!empty($title) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO notifications (title, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $message);
        $msg = $stmt->execute() ? "✅ Notification added successfully!" : "❌ Error: " . $stmt->error;
        $stmt->close();
    } else {
        $msg = "⚠ Please fill all fields.";
    }
}

// -------------------- UPDATE NOTIFICATION --------------------
if (isset($_POST['update_notification'])) {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    if (!empty($title) && !empty($message)) {
        $stmt = $conn->prepare("UPDATE notifications SET title=?, message=? WHERE id=?");
        $stmt->bind_param("ssi", $title, $message, $id);
        $msg = $stmt->execute() ? "✏️ Notification updated successfully!" : "❌ Error: " . $stmt->error;
        $stmt->close();
    } else {
        $msg = "⚠ Please fill all fields.";
    }
}

// -------------------- DELETE NOTIFICATION --------------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id=?");
    $stmt->bind_param("i", $id);
    $msg = $stmt->execute() ? "🗑 Notification deleted successfully!" : "❌ Error: " . $stmt->error;
    $stmt->close();
}

// -------------------- FETCH --------------------
$result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");

// Helper: limit words
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
  <link rel="icon" type="image/png" href="../b2c_logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <meta charset="UTF-8">
  <title>Manage Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Sidebar CSS -->
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f5f6fa;
      display: flex;
    }
    body {
    font-family: 'Poppins', Arial, sans-serif;
    margin:0; 
    background:#f4f6f9;
}

/* Headings use Lora */
h1, h2, h3, h4, h5, h6 {
    font-family: 'Lora', Georgia, serif;
}

.poppins-text {
    font-family: 'Poppins', Arial, sans-serif;
}

.lora-text {
    font-family: 'Lora', Georgia, serif;
}
    .sidebar {
      width: 240px;
      background: #1e1e2d;
      color: #fff;
      height: 100vh;
      position: fixed;
      top: 0; left: 0;
      padding-top: 20px;
      box-shadow: 2px 0 8px rgba(0,0,0,0.2);
    }
    .sidebar h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 20px;
      color: #00d9ff;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .sidebar .nav-links {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .sidebar .nav-links li {
      margin: 10px 0;
    }
    .sidebar .nav-links a {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: #ddd;
      text-decoration: none;
      font-size: 15px;
      border-radius: 8px;
      transition: all 0.3s;
    }
    .sidebar .nav-links a i {
      margin-right: 12px;
      font-size: 18px;
      color: #00d9ff;
    }
    .sidebar .nav-links a:hover {
      background: #00d9ff;
      color: black;
      transform: translateX(5px);
    }
    .sidebar .nav-links a:hover i {
      color: #fff;
    }
    .sidebar .logout {
      margin-top: 30px;
      background: #e63946;
      color: #fff !important;
    }
    .sidebar .logout:hover {
      background: #ff4d6d !important;
      transform: translateX(5px);
    }

    .content {
      margin-left: 260px;
      padding: 20px;
      width: calc(100% - 260px);
    }
  </style>

  <!-- ✅ TinyMCE -->
  <script src="https://cdn.tiny.cloud/1/n7v3h82y5ls28x0vm4ok4skfspwglcbc0hmd4vpsbnzpl8h3/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
  <script>
    function initTinyMCE() {
      tinymce.init({
        selector: '.richtext',
        menubar: false,
        plugins: 'lists link image preview',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link image | preview',
        height: 250,
        setup: function (editor) {
          editor.on('change', function () {
            editor.save();
          });
        }
      });
    }
    document.addEventListener("DOMContentLoaded", function() {
      initTinyMCE();
      document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('shown.bs.modal', function() {
          tinymce.remove();
          initTinyMCE();
        });
      });
    });
  </script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2><i class="fas fa-user-tie"></i> Placement Cell</h2>
    <ul class="nav-links">
        <li><a href="PC-dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="manage-jobs.php"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
        <li><a href="view-applications.php"><i class="fas fa-users"></i> View Applications</a></li>
        <li><a href="upload-job-status.php"><i class="fas fa-file-upload"></i> Upload Status</a></li>
        <li><a href="send-notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="manage-departments.php"><i class="fas fa-sitemap"></i> Departments</a></li>
        <li><a href="PC-logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- ✅ Content -->
<div class="content">
  <div class="container mt-3">

    <!-- Add Notification -->
    <div class="card p-4 shadow mb-4">
      <h3 class="mb-3">Add New Notification</h3>
      <?php if ($msg): ?>
        <div class="alert alert-info"><?= $msg; ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Message</label>
          <textarea name="message" class="form-control richtext" rows="5" required></textarea>
        </div>
        <button type="submit" name="add_notification" class="btn btn-primary">Send</button>
      </form>
    </div>

    <!-- Manage Notifications -->
    <div class="card p-4 shadow">
      <h3 class="mb-3">📋 Manage Notifications</h3>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Title</th>
            <th>Message</th>
            <th>Created At</th>
            <th width="200">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['title']); ?></td>
              <td>
                <?= limit_words($row['message'], 20); ?>
                <button class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#viewMsg<?= $row['id']; ?>">
                  Read More
                </button>
              </td>
              <td><?= $row['created_at']; ?></td>
              <td>
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>">Edit</button>
                <a href="?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this notification?')">Delete</a>
              </td>
            </tr>

            <!-- View Message Modal -->
            <div class="modal fade" id="viewMsg<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><?= htmlspecialchars($row['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                    <?= nl2br($row['message']); ?>
                  </div>
                  <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <form method="post">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Notification</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $row['id']; ?>">
                      <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($row['title']); ?>" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control richtext" rows="5" required><?= htmlspecialchars($row['message']); ?></textarea>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" name="update_notification" class="btn btn-success">Save Changes</button>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
