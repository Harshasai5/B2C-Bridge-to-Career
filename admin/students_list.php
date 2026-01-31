<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: DP-dashboard.php");
    exit();
}

include 'db.php';

// Department from session
$department = $_SESSION['department'] ?? 'CSE';

// ================== Handle Add Student Form ==================
if (isset($_POST['add_student'])) {
    $reg_no = $_POST['reg_no'];
    $name = $_POST['name'];
    $branch = $_POST['branch'];
    $cgpa = !empty($_POST['cgpa']) ? $_POST['cgpa'] : null;
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $aadhaar_id = $_POST['aadhaar_id'];
    $pan_id = $_POST['pan_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // ✅ Handle resume upload
    $resume = null;
    if (!empty($_FILES['resume']['name'])) {
        $resume_name = time() . '_' . basename($_FILES['resume']['name']);
        move_uploaded_file($_FILES['resume']['tmp_name'], '../user/' . $resume_name);
        $resume = $resume_name;
    }

    // ✅ Handle passport photo upload
    $passport_photo = null;
    if (!empty($_FILES['passport_photo']['name'])) {
        $photo_name = time() . '_' . basename($_FILES['passport_photo']['name']);
        move_uploaded_file($_FILES['passport_photo']['tmp_name'], '../user/' . $photo_name);
        $passport_photo = $photo_name;
    }

    // ✅ Insert Query
    $stmt = $conn->prepare("
        INSERT INTO students 
        (reg_no, name, branch, cgpa, phone, email, aadhaar_id, pan_id, resume, passport_photo, password, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssssssssss", $reg_no, $name, $branch, $cgpa, $phone, $email, $aadhaar_id, $pan_id, $resume, $passport_photo, $password);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Student added successfully!'); window.location.href='students_list.php';</script>";
        exit();
    } else {
        echo "<script>alert('❌ Error: " . $stmt->error . "');</script>";
    }
}

// Total students in department
$total_students = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE branch='$department'")->fetch_assoc()['cnt'];

// Students who applied for at least one job
$applied_students = $conn->query("
    SELECT COUNT(DISTINCT s.reg_no) as cnt
    FROM students s
    INNER JOIN job_applications ja ON s.reg_no = ja.reg_no
    WHERE s.branch = '$department'
")->fetch_assoc()['cnt'];

// List of all students
$students = $conn->query("SELECT * FROM students WHERE branch='$department' ORDER BY name ASC");

// Fetch all departments for branch dropdown
$dept_sql = "SELECT department FROM admins WHERE role='spe_department' ORDER BY department ASC";
$dept_result = $conn->query($dept_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Department - Student List</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>

body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f6f9; }

/* SIDEBAR */
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

/* MAIN */
.main { margin-left: 250px; flex: 1; padding: 20px; }

/* CARDS */
.cards { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
.card { background: #fff; padding: 20px; border-radius: 10px; flex: 1; min-width: 180px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: transform 0.2s; }
.card:hover { transform: translateY(-5px); }
.card h3 { margin: 0; font-size: 18px; color: #555; }
.card p { font-size: 22px; font-weight: bold; margin-top: 10px; color: #333; }

/* TABLE */
table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: center; }
th { background: #2c3e50; color: white; }
tr:hover { background: #f1f1f1; }
.actions a { margin: 0 5px; text-decoration: none; padding: 6px 10px; border-radius: 5px; display: inline-block; }
.view { background: #3498db; color: white; }
.edit { background: #f39c12; color: white; }
.delete { background: #e74c3c; color: white; }

/* MODAL */

/* FORM */
.edit-form .form-group { margin-bottom: 15px; }
.edit-form label { display: block; font-weight: 600; margin-bottom: 5px; color: #333; }
.edit-form input[type="text"],
.edit-form input[type="email"],
.edit-form input[type="number"],
.edit-form input[type="password"],
.edit-form select,
.edit-form input[type="file"] {
    width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; outline: none; transition: 0.2s;
}
.edit-form input:focus,
.edit-form select:focus { border-color: #3498db; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }
.edit-form small { color: #888; font-size: 12px; }

/* BUTTON */
.btn-save { background: #27ae60; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 16px; transition: 0.3s; }
.btn-save:hover { background: #219150; }

/* ANIMATION */
@keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }

/* RESPONSIVE */
@media (max-width: 600px) { .modal-content { padding: 20px; } .cards { flex-direction: column; } }
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
<link rel="icon" type="image/png" href="../b2c_logo.png">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">


</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <h2><i class="fa fa-building"></i> Department</h2>
  <ul class="nav-links">
    <li><a href="DP-dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
    <li><a href="students_list.php" class="active"><i class="fa fa-users"></i> Students List</a></li>
    <li><a href="applications_list.php"><i class="fa fa-file-alt"></i> Applications</a></li>
    <li><a href="DP-logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</div>

<!-- MAIN -->
<div class="main">
    <h1>🎓 Student List - <?= htmlspecialchars($department) ?> Department</h1>

    <!-- CARDS -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="cards d-flex gap-3">
            <div class="card">
                <h3>Total Students</h3>
                <p><?= $total_students ?></p>
            </div>
            <div class="card">
                <h3>Applied for Jobs</h3>
                <p><?= $applied_students ?></p>
            </div>
        </div>
        <!-- Add New Student Button -->
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">➕ Add New Student</button>
    </div>

    <!-- TABLE -->
    <h2>All Students</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Reg No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>CGPA</th>
                <th>Resume</th>
                <th>Photo</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $students->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['reg_no']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['cgpa']) ?></td>
                <td>
                    <?= $row['resume'] ? '<a class="view btn btn-sm btn-primary" href="../user/'.$row['resume'].'" target="_blank"><i class="fa fa-file"></i> View</a>' : 'N/A'; ?>
                </td>
                <td>
                    <?= $row['passport_photo'] ? '<img src="../user/'.$row['passport_photo'].'" width="50" height="50" class="rounded-circle">' : 'N/A'; ?>
                </td>
                <td>
                    <a class="btn btn-warning btn-sm" href="#">Edit</a>
                    <a class="btn btn-danger btn-sm" href="delete_student.php?reg_no=<?= $row['reg_no'] ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<!-- ADD STUDENT MODAL -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">➕ Add New Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Reg No</label>
            <input type="text" name="reg_no" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Branch</label>
            <select name="branch" class="form-control" required>
              <option value="">Select Branch</option>
              <?php while ($branch_row = $dept_result->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($branch_row['department']) ?>">
                  <?= htmlspecialchars($branch_row['department']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">CGPA</label>
            <input type="number" step="0.01" max="10" name="cgpa" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Aadhaar ID</label>
            <input type="text" name="aadhaar_id" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">PAN ID</label>
            <input type="text" name="pan_id" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Resume</label>
            <input type="file" name="resume" accept=".pdf,.doc,.docx" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Passport Photo</label>
            <input type="file" name="passport_photo" accept="image/*" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
