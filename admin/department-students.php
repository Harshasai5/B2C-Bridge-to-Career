<?php
include 'db.php';

// ✅ Validate department parameter
if (!isset($_GET['dept'])) {
    die("Department not specified.");
}
$department = urldecode($_GET['dept']);

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

    // ✅ Prepare Insert Query (matching table columns exactly)
    $stmt = $conn->prepare("
        INSERT INTO students 
        (reg_no, name, branch, cgpa, phone, email, aadhaar_id, pan_id, resume, passport_photo, password, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        "sssssssssss",
        $reg_no,
        $name,
        $branch,
        $cgpa,
        $phone,
        $email,
        $aadhaar_id,
        $pan_id,
        $resume,
        $passport_photo,
        $password
    );

    if ($stmt->execute()) {
        echo "<script>alert('✅ Student added successfully!'); window.location.href='department-students.php?dept=" . urlencode($department) . "';</script>";
        exit();
    } else {
        echo "<script>alert('❌ Error: " . $stmt->error . "');</script>";
    }
}

// ✅ Fetch students of selected dept
$sql = "SELECT * FROM students WHERE branch = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Fetch all departments from admins table
$dept_sql = "SELECT department FROM admins WHERE role='spe_department' ORDER BY department ASC";
$dept_result = $conn->query($dept_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../b2c_logo.png">

  <title><?= htmlspecialchars($department) ?> Students</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  
  <style>
   body {
    font-family: 'Poppins', Arial, sans-serif;
   display: flex;
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
      width: 240px; height: 100vh; background: #1e1e2d; color: #fff;
      position: fixed; top: 0; left: 0; padding-top: 20px;
      box-shadow: 2px 0 8px rgba(0,0,0,0.2);
    }
    .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 20px; color: #00d9ff; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .sidebar .nav-links { list-style: none; padding: 0; margin: 0; }
    .sidebar .nav-links li { margin: 10px 0; }
    .sidebar .nav-links a {
      display: flex; align-items: center; padding: 12px 20px; color: #ddd;
      text-decoration: none; font-size: 15px; border-radius: 8px; transition: all 0.3s;
    }
    .sidebar .nav-links a:hover { background: #00bcd4; color: #fff; transform: translateX(5px); }
    .sidebar .nav-links .active { background: #0097a7; color: #fff; }
    .content { margin-left: 260px; padding: 20px; flex-grow: 1; }
    .btn-back {
      display: inline-block; padding: 10px 15px; font-weight: 600; font-size: 15px;
      color: #fff; background: linear-gradient(135deg, #0d6efd, #0b5ed7);
      border: none; border-radius: 8px; text-decoration: none;
      transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .btn-back:hover {
      background: linear-gradient(135deg, #0b5ed7, #0a58ca);
      transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.15);
      color: #fff;
    }
  </style>
</head>
<body class="bg-light">

<!-- Sidebar -->
<div class="sidebar">
  <h2><i class="fas fa-user-tie"></i> Placement Cell</h2>
  <ul class="nav-links">
    <li><a href="manage-departments.php" class="btn-back w-100 mb-3">⬅ Back</a></li>
    <?php while ($dept = $dept_result->fetch_assoc()): ?>
      <li>
        <a href="department-students.php?dept=<?= urlencode($dept['department']) ?>" 
           class="<?= ($department === $dept['department']) ? 'active' : '' ?>">
          <?= htmlspecialchars($dept['department']) ?>
        </a>
      </li>
    <?php endwhile; ?>
  </ul>
</div>

<!-- Content Area -->
<div class="content">
  <h2 class="mb-4">Students of <?= htmlspecialchars($department) ?> Department</h2>

  <!-- Row with Add Button & Count Card -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">➕ Add New Student</button>

    <div class="card text-center shadow-sm border-success" style="width: 220px;">
      <div class="card-body">
        <h5 class="card-title text-success">👥 Total Students</h5>
        <p class="fs-4 fw-bold mb-0"><?= $result->num_rows ?></p>
      </div>
    </div>
  </div>

  <!-- Add Student Modal -->
  <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
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
                <?php
                  // ✅ Fetch branch list dynamically
                  $branch_sql = "SELECT department FROM admins WHERE role='spe_department' ORDER BY department ASC";
                  $branch_result = $conn->query($branch_sql);
                  while ($branch_row = $branch_result->fetch_assoc()):
                ?>
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

  <!-- Students Table -->
  <div class="card shadow-sm mt-4">
    <div class="card-body">
      <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-success">
              <tr>
                <th>Reg No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>CGPA</th>
                <th>Branch</th>
                <th>Resume</th>
                <th>Passport Photo</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($student = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($student['reg_no']) ?></td>
                  <td><?= htmlspecialchars($student['name']) ?></td>
                  <td><?= htmlspecialchars($student['email']) ?></td>
                  <td><?= htmlspecialchars($student['phone']) ?></td>
                  <td><?= htmlspecialchars($student['cgpa']) ?></td>
                  <td><?= htmlspecialchars($student['branch']) ?></td>
                  <td>
                    <?php if (!empty($student['resume'])): ?>
                      <a href="../user/<?= htmlspecialchars($student['resume']) ?>" target="_blank">📄 View</a>
                    <?php else: ?>
                      <span class="text-muted">N/A</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($student['passport_photo'])): ?>
                      <img src="../user/<?= htmlspecialchars($student['passport_photo']) ?>" alt="Photo" width="50" height="50" class="rounded-circle">
                    <?php else: ?>
                      <span class="text-muted">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted">No students found in this department.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
