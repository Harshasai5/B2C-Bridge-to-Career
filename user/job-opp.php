<?php
session_start();
include 'db.php';

// Ensure student logged in
if (!isset($_SESSION['reg_no'])) {
    header("Location: std_login.php");
    exit();
}

$reg_no = $_SESSION['reg_no'];

// ✅ Fetch student profile
$profile = $conn->query("SELECT * FROM students WHERE reg_no='$reg_no'")->fetch_assoc();

// ✅ Eligible Jobs
$jobs = $conn->query("
    SELECT c.company_id, c.logo, c.name, c.description, c.skills_required, c.job_role, c.min_cgpa, c.application_link, c.created_at
    FROM companies c
    WHERE FIND_IN_SET('{$profile['branch']}', c.eligible_branches)
      AND (c.min_cgpa IS NULL OR {$profile['cgpa']} >= c.min_cgpa)
    ORDER BY c.created_at DESC
");

// ✅ Notifications for sidebar
$latest_note = $conn->query("SELECT id FROM notifications ORDER BY id DESC LIMIT 1")->fetch_assoc();
$latest_note_id = $latest_note ? $latest_note['id'] : 0;
$new_notifications = ($latest_note_id > $profile['last_seen_notification_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Job Opportunities</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
/* 🌟 Global */
body {
  background-color: #e9eff4;
  font-family: Arial, Helvetica, sans-serif;
  color: #222;
}

/* 🌟 Sidebar */
.sidebar {
  background: #1d2851ff;
  border-radius: 12px;
  min-height: 100vh;
}

.sidebar-nav .nav-link {
  font-size: 1.2rem;
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

/* 🌟 Job Cards */
.job-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); 
  gap: 20px; 
}
.job-card { 
  border: 1px solid #ccc; 
  border-radius: 15px; 
  padding: 15px 20px; 
  background: white; 
  transition: 0.3s; 
}
.job-card:hover { 
  transform: translateY(-5px); 
  box-shadow: 0 4px 12px rgba(0,0,0,0.2); 
}
.job-title { 
  text-align: center; 
  font-size: 18px; 
  font-weight: bold; 
  margin-bottom: 12px; 
  color: #1e2a5a; 
}
.job-content { display: flex; justify-content: space-between; align-items: flex-start; }
.job-info { flex: 1; padding-right: 15px; }
.job-info p { margin: 6px 0; font-size: 14px; }
.job-info b { color: #333; }
.job-side { text-align: center; width: 120px; }
.job-logo { width: 90px; height: 90px; border: 1px solid #ddd; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto; background: #f5f5f5; }
.job-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
.apply-btn { background: red; color: white; font-weight: bold; border: none; border-radius: 8px; padding: 6px 18px; font-size: 14px; transition: 0.3s; }
.apply-btn:hover { background: darkred; }

/* 🌟 Main Content */
.col-md-9 {
  padding: 50px !important; /* increased padding */
  font-size: 16px; /* larger base text */
  line-height: 1.6; /* better readability */
}

/* 🌟 Job Cards */
.job-info p { 
  margin: 10px 0; 
  font-size: 16px; /* was 14px */
}
.job-title { 
  font-size: 28px; /* was 18px */
}
.apply-btn { 
  font-size: 15px; /* larger button text */
  padding: 6px 17px; /* more clickable */
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
      <h3 class="mb-4">Welcome, <?php echo htmlspecialchars($profile['name']); ?></h3>
      <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
          <a href="std_dashboard.php#profile" class="nav-link text-white">🧑‍🎓 Profile</a>
        </li>
        <li class="nav-item">
          <a href="job-opp.php" class="nav-link text-white active">💼 Job Opportunities</a>
        </li>
        <li class="nav-item">
          <a href="std_dashboard.php#applications" class="nav-link text-white">📑 My Applications</a>
        </li>
        <li class="nav-item">
          <a href="run_ats.php" class="nav-link text-white">⚡ Check ATS Score</a>
        </li>
        <li class="nav-item">
          <a href="notification.php" class="nav-link text-white">
            🔔 Notifications
            <?php if($new_notifications) { ?>
              <span class="badge bg-danger ms-2">New</span>
            <?php } ?>
          </a>
        </li>
        <li class="nav-item">
          <a href="logout.php" class="nav-link text-danger">🚪 Logout</a>
        </li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 p-4">
      <h2>Eligible Job Opportunities</h2>
      <div class="job-grid">
        <?php if ($jobs->num_rows > 0): ?>
          <?php while ($job = $jobs->fetch_assoc()): ?>
            <div class="job-card">
              <div class="job-title"><?php echo htmlspecialchars($job['name']); ?></div>
              <div class="job-content">
                <!-- Left side: Info -->
                <div class="job-info">
                  <p><b>Skills:</b> <?php echo htmlspecialchars($job['skills_required']); ?></p>
                  <p><b>Job Role:</b> <?php echo htmlspecialchars($job['job_role']); ?></p>
                  <p><b>Description:</b> <?php echo htmlspecialchars($job['description']); ?></p>
                  <p><b>Min CGPA:</b> <?php echo $job['min_cgpa'] ?: "Not specified"; ?></p>
                </div>
                <!-- Right side: Logo + Apply -->
                <div class="job-side">
                  <div class="job-logo">
                    <?php if (!empty($job['logo'])): ?>
                      <img src="../admin/uploads/logos/<?php echo htmlspecialchars($job['logo']); ?>" alt="Logo">
                    <?php else: ?>
                      LOGO
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($job['application_link'])): ?>
                    <a href="<?php echo htmlspecialchars($job['application_link']); ?>" target="_blank">
                      <button class="apply-btn">Apply</button>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No job opportunities available for your profile.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
</body>
</html>
