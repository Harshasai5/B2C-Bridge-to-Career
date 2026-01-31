<?php
session_start();
include 'db.php';

// Only placement_cell can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'placement_cell') {
    header("Location: PC-login.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$department = $_SESSION['department'] ?? 'N/A';
$email      = $_SESSION['email'] ?? 'Not Set';

// ---------------- Handle Notepad Save ---------------- //
if (isset($_POST['save_note'])) {
    $note = $conn->real_escape_string($_POST['note']);
    $check = $conn->query("SELECT * FROM notepad WHERE user_id='$user_id'");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE notepad SET content='$note' WHERE user_id='$user_id'");
    } else {
        $conn->query("INSERT INTO notepad (user_id, content) VALUES ('$user_id','$note')");
    }
}
$notepadContent = $conn->query("SELECT content FROM notepad WHERE user_id='$user_id'")->fetch_assoc()['content'] ?? "";

// ---------------- Fetch Dashboard Data ---------------- //
// All departments
$allDepartments = [];
$res = $conn->query("SELECT DISTINCT branch FROM students");
while ($row = $res->fetch_assoc()) {
    $allDepartments[] = $row['branch'];
}

// Students per department
$studentsPerDept = [];
$res = $conn->query("SELECT branch, COUNT(*) as cnt FROM students GROUP BY branch");
while ($row = $res->fetch_assoc()) {
    $studentsPerDept[$row['branch']] = $row['cnt'] ?? 0;
}

// Initialize deptStats
$deptStats = [];
foreach ($studentsPerDept as $branch => $cnt) {
    $deptStats[$branch] = ['students'=>$cnt,'applications'=>0,'placed'=>0,'jobs'=>0];
}

// Applications per department
$res = $conn->query("
    SELECT s.branch, COUNT(j.id) AS cnt
    FROM students s
    JOIN job_applications j ON s.reg_no = j.reg_no
    GROUP BY s.branch
");
while ($row = $res->fetch_assoc()) {
    $branch = $row['branch'];
    $cnt = $row['cnt'] ?? 0;
    if (!isset($deptStats[$branch])) $deptStats[$branch] = ['students'=>0,'applications'=>0,'placed'=>0,'jobs'=>0];
    $deptStats[$branch]['applications'] = $cnt;
}

// Placed students per department (status = 'selected')
$placedData = [];
$res = $conn->query("
    SELECT s.branch, COUNT(*) AS cnt
    FROM job_applications j
    JOIN students s ON j.reg_no = s.reg_no
    WHERE TRIM(LOWER(j.status)) = 'selected'
    GROUP BY s.branch
");

while ($row = $res->fetch_assoc()) {
    $branch = $row['branch'];
    $cnt    = $row['cnt'] ?? 0;
    if (!isset($deptStats[$branch])) $deptStats[$branch] = ['students'=>0,'applications'=>0,'placed'=>0,'jobs'=>0];
    $deptStats[$branch]['placed'] = $cnt;

    $placedData[$branch] = [
        'placed' => $cnt,
        'not_placed' => ($studentsPerDept[$branch] ?? 0) - $cnt
    ];
}

// Jobs per department (from companies)


// Summary stats
$totalStudents     = array_sum(array_column($deptStats, 'students')) ?: 0;
$totalApplications = array_sum(array_column($deptStats, 'applications')) ?: 0;
$totalPlaced       = array_sum(array_column($deptStats, 'placed')) ?: 0;

// Latest notification
$latestNotif = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Placement Cell Dashboard</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* body { font-family: Arial, sans-serif; margin:0; background:#f4f6f9; } */
/* For headings */
/* Body uses Poppins */
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
    .custom-btn {
        background-color: #00d9ff;
        color: black;
        border: none; /* optional: remove default border */
        transition: background-color 0.3s ease; /* smooth hover transition */
    }

    .custom-btn:hover {
        background-color: #00c1e6; /* slightly darker shade on hover */
        color: #fff; /* keep text white */
    }
.main-content { margin-left:220px; padding:20px;padding-left: 30px; }
header { background:#00d9ff; color:BLACK; padding:15px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; }
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; margin-bottom:20px; }
.stat-card { background:#fff; padding:18px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.05); text-align:center; }
.notif-task { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
.box { background:#fff; padding:15px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
.charts { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px; margin-top:15px; }
.chart-card { background:#fff; padding:8px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05); height:280px; }
.chart-card h3 { font-size:14px; margin-bottom:5px; color:#007bff; text-align:center; }
.chart-card canvas { max-height:220px; }
textarea { width:100%; height:150px; border-radius:8px; padding:10px; }
</style>
<link rel="icon" type="image/png" href="../b2c_logo.png">

</head>
<body>
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

<div class="main-content">
<header>
  <div>
    <h1>Welcome, Placement Cell</h1>
    <p>Email: <?= htmlspecialchars($email ?: "N/A") ?></p>
  </div>
  <div>
    <label class="text-black me-2">Filter by Department:</label>
    <select id="globalDept" class="form-select form-select-sm" style="width:160px; display:inline-block;">
      <option value="all">All</option>
      <?php foreach ($allDepartments as $dept): ?>
        <option value="<?= $dept ?>"><?= $dept ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</header>

<!-- Quick Stats -->
<div class="stats" id="statsSection">
  <div class="stat-card"><h4>👥 Students</h4><p id="statStudents"><?= $totalStudents ?></p></div>
  <div class="stat-card"><h4>📄 Applications</h4><p id="statApplications"><?= $totalApplications ?></p></div>
  <div class="stat-card"><h4>🎓 Placed</h4><p id="statPlaced"><?= $totalPlaced ?></p></div>
</div>

<!-- Notifications + Notepad -->
<div class="notif-task">
  <div class="box">
    <h4>🔔 Latest Notification</h4>
    <?php if ($latestNotif): 
      $words = explode(" ", strip_tags($latestNotif['message']));
      $shortMsg = implode(" ", array_slice($words,0,20)) . (count($words)>20?"...":"");
    ?>
      <p><?= htmlspecialchars($shortMsg) ?></p>
      <button class="btn btn-sm custom-btn" data-bs-toggle="modal" data-bs-target="#notifModal">
         <b>Read Full</b>
      </button>    <?php else: ?>
      <p>No notifications available.</p>
    <?php endif; ?>
  </div>
  <div class="box">
    <h4>📝 Notepad</h4>
    <form method="post">
      <textarea name="note" placeholder="Write your tasks here..."><?= htmlspecialchars($notepadContent) ?></textarea>
      <button type="submit" name="save_note" class="btn btn-sm mt-2 custom-btn">
          Save
      </button>
    </form>
  </div>
</div>

<!-- Charts -->
<div class="charts">
  <div class="chart-card">
    <h3 style="color: black;"> <b>Students per Department</b></h3>
    <canvas id="studentsDept"></canvas>
  </div>
  <div class="chart-card" >
    <h3 style="color: black;"> <b>Placement Ratio</b></h3>
    <canvas id="placementRatio"></canvas>
  </div>
</div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="notifModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Full Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?= $latestNotif ? $latestNotif['message'] : "No notification found." ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
let studentsLabels = <?= json_encode(array_keys($studentsPerDept)) ?>;
let studentsData   = <?= json_encode(array_values($studentsPerDept)) ?>;
let placedData     = <?= json_encode($placedData) ?>;
let deptStats      = <?= json_encode($deptStats) ?>;

// Students per Department Chart - Bar with cyan theme
const studentsDeptChart = new Chart(document.getElementById('studentsDept'), {
  type: 'bar',
  data: {
    labels: studentsLabels,
    datasets: [{
      label: 'Students',
      data: studentsData,
      backgroundColor: '#00d9ff', // theme color
      borderColor: '#007b8f',     // darker cyan border
      borderWidth: 1
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true },
      x: { ticks: { color: 'black' } }
    }
  }
});

// Placement Ratio Chart - Donut with cyan theme
const placementRatioChart = new Chart(document.getElementById('placementRatio'), {
  type: 'doughnut',
  data: {
    labels: ['Placed', 'Not Placed'],
    datasets: [{
      data: [<?= $totalPlaced ?>, <?= $totalStudents - $totalPlaced ?>],
      backgroundColor: ['#00d9ff', '#007b8f'], // theme colors
      borderColor: ['#007b8f', '#004d59'],    // optional darker border
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: { color: 'black' }
      }
    }
  }
});

// Department filter functionality
document.getElementById('globalDept').addEventListener('change', function(){
  let dept = this.value;

  // Students chart update
  let labels = studentsLabels.slice();
  let data = studentsData.slice();
  if(dept !== 'all'){
    let index = labels.indexOf(dept);
    labels = index>=0?[labels[index]]:['N/A'];
    data = index>=0?[data[index]]:[0];
  }
  studentsDeptChart.data.labels = labels;
  studentsDeptChart.data.datasets[0].data = data;
  studentsDeptChart.update();

  // Placement chart update
  let placed = 0, notPlaced = 0;
  if(dept !== 'all'){
    if(placedData[dept]){
      placed = placedData[dept].placed;
      notPlaced = placedData[dept].not_placed;
    }
  } else {
    placed = Object.values(placedData).reduce((a,b)=>a+(b.placed||0),0);
    notPlaced = Object.values(placedData).reduce((a,b)=>a+(b.not_placed||0),0);
  }
  placementRatioChart.data.datasets[0].data = [placed, notPlaced];
  placementRatioChart.update();

  // Update Stats
  if(dept !== 'all' && deptStats[dept]){
    document.getElementById('statStudents').innerText = deptStats[dept].students || 0;
    document.getElementById('statApplications').innerText = deptStats[dept].applications || 0;
    document.getElementById('statPlaced').innerText = deptStats[dept].placed || 0;
  } else {
    document.getElementById('statStudents').innerText = <?= $totalStudents ?>;
    document.getElementById('statApplications').innerText = <?= $totalApplications ?>;
    document.getElementById('statPlaced').innerText = <?= $totalPlaced ?>;
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
