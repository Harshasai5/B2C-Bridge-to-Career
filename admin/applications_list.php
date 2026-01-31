<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: DP-dashboard.php");
    exit();
}

include 'db.php';

// Department from session
$department = $_SESSION['department'] ?? 'CSE';

// Total students
$total_students = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE branch='$department'")->fetch_assoc()['cnt'];

// Total applications
$total_applications = $conn->query("
    SELECT COUNT(*) as cnt
    FROM job_applications ja
    INNER JOIN students s ON ja.reg_no = s.reg_no
    WHERE s.branch = '$department'
")->fetch_assoc()['cnt'];

// Students applied at least once
$students_applied = $conn->query("
    SELECT COUNT(DISTINCT s.reg_no) as cnt
    FROM students s
    INNER JOIN job_applications ja ON s.reg_no = ja.reg_no
    WHERE s.branch = '$department'
")->fetch_assoc()['cnt'];

// Fetch applications with student & company/job details
$sql = "
    SELECT ja.id, ja.reg_no, ja.job_id, ja.status, ja.remarks, ja.uploaded_at,
           s.name AS student_name, s.email AS student_email,
           c.name AS company_name, c.job_role
    FROM job_applications ja
    INNER JOIN students s ON ja.reg_no = s.reg_no
    INNER JOIN companies c ON ja.job_id = c.company_id
    WHERE s.branch = ?
    ORDER BY ja.uploaded_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$applications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applications List - <?= htmlspecialchars($department) ?> Dept</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<style>
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


/* Main content */
.main { flex:1; margin-left:250px; padding:20px; }

/* Cards */
.cards { display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap; }
.card { background:#fff; padding:20px; border-radius:10px; flex:1; min-width:180px; text-align:center; box-shadow:0 4px 10px rgba(0,0,0,0.1); transition: transform 0.2s; }
.card:hover { transform: translateY(-5px); }
.card h3 { margin:0; font-size:18px; color:#555; }
.card p { font-size:22px; font-weight:bold; margin-top:10px; color:#333; }

/* Table */
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; }
th, td { padding:10px; border-bottom:1px solid #ddd; text-align:center; }
th { background:#2c3e50; color:white; }
tr:hover { background:#f1f1f1; }
.actions a { margin:0 5px; text-decoration:none; padding:5px 8px; border-radius:5px; }
.view { background:#3498db; color:white; }
.edit { background:#f39c12; color:white; }
.delete { background:#e74c3c; color:white; }

.status-pending { color: #e67e22; font-weight:600; }
.status-approved { color: #27ae60; font-weight:600; }
.status-rejected { color: #e74c3c; font-weight:600; }
</style>
<link rel="icon" type="image/png" href="../b2c_logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

</head>
<body>
<div class="sidebar">
  <h2><i class="fa fa-building"></i> Department</h2>
  <ul class="nav-links">
    <li><a href="DP-dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
    <li><a href="students_list.php"><i class="fa fa-users"></i> Students List</a></li>
    <li><a href="applications_list.php"><i class="fa fa-file-alt"></i> Applications</a></li>
    <li><a href="DP-logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</div>

<div class="main">
    <h1>📝 Applications - <?= htmlspecialchars($department) ?> Department</h1>

    <!-- CARDS -->
    <div class="cards">
        <div class="card">
            <h3>Total Students</h3>
            <p><?= $total_students ?></p>
        </div>
        <div class="card">
            <h3>Total Applications</h3>
            <p><?= $total_applications ?></p>
        </div>
        <div class="card">
            <h3>Students Applied</h3>
            <p><?= $students_applied ?></p>
        </div>
    </div>

    <!-- Applications Table -->
    <table>
        <tr>
            <th>ID</th>
            <th>Reg No</th>
            <th>Student Name</th>
            <th>Email</th>
            <th>Company</th>
            <th>Job Role</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Uploaded At</th>
        </tr>
        <?php while($row = $applications->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['reg_no']) ?></td>
            <td><?= htmlspecialchars($row['student_name']) ?></td>
            <td><?= htmlspecialchars($row['student_email']) ?></td>
            <td><?= htmlspecialchars($row['company_name']) ?></td>
            <td><?= htmlspecialchars($row['job_role']) ?></td>
            <td class="status-<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['remarks']) ?></td>
            <td><?= $row['uploaded_at'] ?></td>
        </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>
