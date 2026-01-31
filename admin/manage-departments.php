<?php
session_start();
include 'db.php';

// ✅ Only placement_cell can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'placement_cell') {
    header("Location: PC-login.php");
    exit();
}

$msg = "";

// ------------------ ADD DEPARTMENT LOGIN ------------------
if (isset($_POST['add_department'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $department = $conn->real_escape_string($_POST['department']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!empty($department) && !empty($email) && !empty($_POST['password'])) {
        $conn->query("INSERT INTO admins (email, password, role, department) 
                      VALUES ('$email', '$password', 'spe_department', '$department')");
        $msg = "✅ Department login created successfully!";
    } else {
        $msg = "⚠ All fields are required.";
    }
}

// ------------------ UPDATE DEPARTMENT LOGIN ------------------
if (isset($_POST['update_department'])) {
    $id = intval($_POST['user_id']);
    $email = $conn->real_escape_string($_POST['email']);
    $department = $conn->real_escape_string($_POST['department']);

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $conn->query("UPDATE admins SET email='$email', department='$department', password='$password' WHERE user_id=$id");
    } else {
        $conn->query("UPDATE admins SET email='$email', department='$department' WHERE user_id=$id");
    }
    $msg = "✏️ Department updated successfully!";
}

// ------------------ DELETE DEPARTMENT LOGIN ------------------
if (isset($_GET['delete_department'])) {
    $id = intval($_GET['delete_department']);
    $conn->query("DELETE FROM admins WHERE user_id=$id AND role='spe_department'");
    $msg = "🗑 Department deleted successfully!";
}

// ------------------ FETCH DATA ------------------
$departments = $conn->query("SELECT * FROM admins WHERE role='spe_department' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Departments</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      display: flex;
      min-height: 100vh;
      margin: 0;
    }
    /* Sidebar */
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

    /* Content */
    .content {
      margin-left: 260px;
      padding: 20px;
      flex: 1;
    }
    .dept-card {
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      padding: 20px;
      transition: transform 0.2s;
    }
    .dept-card:hover {
      transform: scale(1.03);
    }
    /* Button Group */
.dept-actions {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

/* General Button Look */
.dept-actions .btn {
  flex: 1;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  padding: 6px 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.3s ease;
  border: none;
}

/* Edit Button */
.dept-actions .btn-edit {
  background-color: #007bff;
  color: #fff;
}
.dept-actions .btn-edit:hover {
  background-color: #0062cc;
  transform: translateY(-2px);
}

/* Delete Button */
.dept-actions .btn-delete {
  background-color: #ff4757;
  color: #fff;
}
.dept-actions .btn-delete:hover {
  background-color: #e84118;
  transform: translateY(-2px);
}

/* View Students Button */
.dept-actions .btn-view {
  background-color: #00bcd4;
  color: #fff;
}
.dept-actions .btn-view:hover {
  background-color: #00bcd4;
  color: black;
  transform: translateY(-2px);
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

  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <link rel="icon" type="image/png" href="../b2c_logo.png">

</head>
<body class="bg-light">

<!-- ✅ Sidebar -->
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

<!-- ✅ Main Content -->
<div class="content">
  <h2 class="mb-3">🏫 Manage Departments</h2>

  <?php if ($msg): ?>
    <div class="alert alert-info"><?= $msg; ?></div>
  <?php endif; ?>

  <!-- Add Department Button -->
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addDeptModal">➕ Add Department</button>

  <!-- Department Cards -->
  <div class="row g-3 mb-4">
    <?php while ($dept = $departments->fetch_assoc()): ?>
      <div class="col-md-4">
        <div class="dept-card bg-white">
          <h5><?= htmlspecialchars($dept['department']); ?></h5>
          <p><strong>Email:</strong> <?= htmlspecialchars($dept['email']); ?></p>
          <p><small>Created: <?= $dept['created_at']; ?></small></p>
          <div class="dept-actions">
  <button class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#editDept<?= $dept['user_id']; ?>">
    <i class="fas fa-edit"></i> Edit
  </button>
  <a href="?delete_department=<?= $dept['user_id']; ?>" class="btn btn-delete btn-sm" onclick="return confirm('Delete this department login?')">
    <i class="fas fa-trash"></i> Delete
  </a>
  <a href="department-students.php?dept=<?= urlencode($dept['department']); ?>" class="btn btn-view btn-sm">
    <i class="fas fa-users"></i> View Students
  </a>
</div>

        </div>
      </div>

      <!-- Edit Department Modal -->
      <div class="modal fade" id="editDept<?= $dept['user_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="post">
              <div class="modal-header">
                <h5 class="modal-title">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="user_id" value="<?= $dept['user_id']; ?>">
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($dept['email']); ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Department</label>
                  <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($dept['department']); ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">New Password (leave blank to keep old)</label>
                  <input type="password" name="password" class="form-control">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="update_department" class="btn btn-success">Save Changes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- Add Department Modal -->
  <div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Add Department</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Department Code/Name</label>
              <input type="text" name="department" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Department Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="add_department" class="btn btn-primary">Add</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
