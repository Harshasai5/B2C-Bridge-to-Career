<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: DP-login.php");
    exit();
}

include 'db.php';

// Department from session
$department = $_SESSION['department'] ?? 'CSE';

// 1) Total students vs Placed students
$total_students = intval($conn->query("
    SELECT COUNT(*) as total 
    FROM students 
    WHERE branch='$department'
")->fetch_assoc()['total']);

$placed_students = intval($conn->query("
    SELECT COUNT(DISTINCT ja.reg_no) as placed
    FROM job_applications ja
    INNER JOIN students s ON ja.reg_no = s.reg_no
    WHERE s.branch='$department' AND ja.status='selected'
")->fetch_assoc()['placed']);

$not_placed = max(0, $total_students - $placed_students);

// 2) Eligible companies
$eligible_companies = intval($conn->query("
    SELECT COUNT(*) as companies
    FROM companies
    WHERE FIND_IN_SET('$department', eligible_branches)
")->fetch_assoc()['companies']);

// 3) Students applied vs not applied
$students_applied = intval($conn->query("
    SELECT COUNT(DISTINCT ja.reg_no) as applied
    FROM job_applications ja
    INNER JOIN students s ON ja.reg_no = s.reg_no
    WHERE s.branch='$department'
")->fetch_assoc()['applied']);

$students_not_applied = max(0, $total_students - $students_applied);

// 4) Applications & Selections per job
$job_summary = $conn->query("
    SELECT c.name AS company_name, c.job_role, 
           COUNT(ja.id) AS total_applied,
           SUM(CASE WHEN ja.status='selected' THEN 1 ELSE 0 END) AS selected
    FROM companies c
    LEFT JOIN job_applications ja ON c.company_id = ja.job_id
    WHERE FIND_IN_SET('$department', c.eligible_branches)
    GROUP BY c.company_id
");

// 5) Job details for the department
$job_details = $conn->query("
    SELECT name, job_role, package, skills_required
    FROM companies
    WHERE FIND_IN_SET('$department', eligible_branches)
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Department Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

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
    .sidebar .nav-links li { margin: 10px 0; }
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
    .sidebar .nav-links a:hover i { color: #fff; }
    .sidebar .logout {
      margin-top: 30px;
      background: #e63946;
      color: #fff !important;
    }
    .sidebar .logout:hover {
      background: #ff4d6d !important;
      transform: translateX(5px);
    }

    /* Main */
    .main {
      margin-left: 260px;
      padding: 20px;
      flex: 1;
    }
    .main h1 {
      font-size: 26px;
      margin-bottom: 20px;
      color: #2c3e50;
    }

    /* Metric Cards */
    .metric-cards {
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
      gap: 20px; 
      margin-bottom: 25px; 
    }
    .metric {
      padding: 20px;
      border-radius: 15px;
      color: #fff;
      font-weight: bold;
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      text-align: center;
      transition: transform 0.2s;
    }
    .metric:hover { transform: translateY(-5px); }
    .metric h3 { margin: 0; font-size: 16px; font-weight: 500; }
    .metric p { margin: 8px 0 0; font-size: 32px; font-weight: bold; }

    .students { background: linear-gradient(135deg, #3498db, #2980b9); }
    .companies { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
    .placements { background: linear-gradient(135deg, #27ae60, #1e8449); }

    /* Layout */
    .dashboard {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
    }

    /* Cards */
    .card { 
      background: #fff; 
      padding: 15px; 
      border-radius: 12px; 
      box-shadow: 0 2px 6px rgba(0,0,0,0.1); 
    }
    h2 { margin-bottom: 10px; font-size: 16px; color: #2c3e50; }

    /* Compact containers */
    .small-card { height: 250px; }
    .small-card canvas { max-height: 180px; }
    .summary-card { max-height: 300px; overflow-y: auto; }

    /* Table */
    table { 
      width: 100%; 
      border-collapse: collapse; 
      margin-top: 10px; 
      font-size: 13px;
    }
    th { background: #2c3e50; color: white; }
    table, th, td { border: 1px solid #ddd; }
    th, td { padding: 8px; text-align: center; }
    tr:nth-child(even) { background: #f9f9f9; }
    tr:hover { background: #f1f1f1; }

    /* Job details */
    .job-details { max-height: 750px; overflow-y: auto; }
    .job-card {
      background: #ffffff;
      border-radius: 10px;
      padding: 12px;
      margin-bottom: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }
    .job-card:hover { transform: translateY(-5px); }
    .job-card h3 { margin: 0 0 8px; font-size: 15px; color: #2c3e50; }
    .job-card p { margin: 3px 0; font-size: 13px; color: #555; }
    .job-details::-webkit-scrollbar { width: 6px; }
    .job-details::-webkit-scrollbar-thumb { background: #888; border-radius: 5px; }
    .job-details::-webkit-scrollbar-thumb:hover { background: #555; }
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

</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2><i class="fa fa-building"></i> Department</h2>
  <ul class="nav-links">
    <li><a href="DP-dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
    <li><a href="students_list.php"><i class="fa fa-users"></i> Students List</a></li>
    <li><a href="applications_list.php"><i class="fa fa-file-alt"></i> Applications</a></li>
    <li><a href="DP-logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</div>

<!-- Main -->
<div class="main">
  <h1>📊 Dashboard - <?= htmlspecialchars($department) ?></h1>

  <!-- Metrics -->
  <div class="metric-cards">
    <div class="metric students">
      <h3>Total Students</h3>
      <p><?= $total_students ?></p>
    </div>
    <div class="metric companies">
      <h3>Eligible Companies</h3>
      <p><?= $eligible_companies ?></p>
    </div>
    <div class="metric placements">
      <h3>Total Placements</h3>
      <p><?= $placed_students ?></p>
    </div>
  </div>

  <!-- Dashboard Grid -->
  <div class="dashboard">
    <!-- Left Section -->
    <div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div class="card small-card">
          <h2>Students Placement Status</h2>
          <canvas id="studentsChart"></canvas>
        </div>

        <div class="card small-card">
          <h2>Application Status</h2>
          <canvas id="applyChart"></canvas>
        </div>
      </div>

      <div class="card summary-card" style="margin-top:20px;">
        <h2>Job Applications Summary</h2>
        <table>
          <tr><th>Company</th><th>Job Role</th><th>Applied</th><th>Selected</th></tr>
          <?php while($row = $job_summary->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['company_name']) ?></td>
              <td><?= htmlspecialchars($row['job_role']) ?></td>
              <td><?= $row['total_applied'] ?></td>
              <td><?= $row['selected'] ?></td>
            </tr>
          <?php endwhile; ?>
        </table>
      </div>
    </div>

    <!-- Right Section: Job Details -->
    <div class="card job-details">
      <h2>💼 Job Details for <?= htmlspecialchars($department) ?></h2>
      <?php while($job = $job_details->fetch_assoc()): ?>
        <div class="job-card">
          <h3><?= htmlspecialchars($job['name']) ?></h3>
          <p><strong>Role:</strong> <?= htmlspecialchars($job['job_role']) ?></p>
          <p><strong>Package:</strong> <?= htmlspecialchars($job['package']) ?></p>
          <p><strong>Skills:</strong> <?= htmlspecialchars($job['skills_required']) ?></p>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<script>
// 1. Students vs Placed
new Chart(document.getElementById('studentsChart'), {
  type: 'doughnut',
  data: {
    labels: ['Placed', 'Not Placed'],
    datasets: [{
      data: [<?= $placed_students ?: 0 ?>, <?= $not_placed ?: 0 ?>],
      backgroundColor: ['#27ae60','#e74c3c']
    }]
  },
  options: { 
    responsive: true, 
    plugins: { legend: { position: 'bottom' } } 
  }
});

// 2. Applied vs Not Applied
new Chart(document.getElementById('applyChart'), {
  type: 'bar',
  data: {
    labels: ['Applied','Not Applied'],
    datasets: [{
      label: 'Students',
      data: [<?= $students_applied ?>, <?= $students_not_applied ?>],
      backgroundColor: ['#2980b9','#f39c12']
    }]
  },
  options: { 
    responsive: true, 
    plugins: { legend: { display: false } } 
  }
});
</script>

</body>
</html>
