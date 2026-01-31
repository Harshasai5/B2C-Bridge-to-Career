<?php
session_start();
include 'db.php';

// Ensure student logged in
if (!isset($_SESSION['reg_no'])) {
    header("Location: std_login.php");
    exit();
}
$reg_no = mysqli_real_escape_string($conn, $_SESSION['reg_no']);

// ✅ Resume Upload
if (isset($_POST['upload_resume'])) {
    if (!empty($_FILES['resume']['name'])) {
        $fileName = time() . "_" . basename($_FILES['resume']['name']);
        $targetPath = "uploads/resumes/" . $fileName;
        if (!is_dir("uploads/resumes")) mkdir("uploads/resumes", 0777, true);

        if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
            $conn->query("UPDATE students SET resume='$targetPath' WHERE reg_no='$reg_no'");
        }
    }
    header("Location: std_dashboard.php");
    exit();
}

// ✅ Photo Upload
if (isset($_POST['upload_photo'])) {
    if (!empty($_FILES['photo']['name'])) {
        $fileName = time() . "_" . basename($_FILES['photo']['name']);
        $targetPath = "uploads/photos/" . $fileName;
        if (!is_dir("uploads/photos")) mkdir("uploads/photos", 0777, true);

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $conn->query("UPDATE students SET passport_photo='$targetPath' WHERE reg_no='$reg_no'");
        }
    }
    header("Location: std_dashboard.php");
    exit();
}

// ✅ Profile
$profile = $conn->query("SELECT * FROM students WHERE reg_no='$reg_no'")->fetch_assoc();

// ✅ Eligible Job Opportunities
$jobs = $conn->query("
    SELECT 
        c.company_id AS id, 
        c.name AS company_name, 
        c.job_role AS job_title, 
        c.logo,
        c.application_link,
        c.created_at AS last_date
    FROM companies c
    WHERE FIND_IN_SET('{$profile['branch']}', c.eligible_branches)
      AND (c.min_cgpa IS NULL OR {$profile['cgpa']} >= c.min_cgpa)
");


// ✅ Applications
$applications = $conn->query("
    SELECT 
        c.name AS company_name, 
        c.job_role AS job_title, 
        s.status,
        s.uploaded_at
    FROM job_applications s
    JOIN companies c ON s.job_id = c.company_id
    WHERE s.reg_no = '$reg_no'
");

// ✅ Notifications
$notifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
$latest_note = $conn->query("SELECT id FROM notifications ORDER BY id DESC LIMIT 1")->fetch_assoc();
$latest_note_id = $latest_note ? $latest_note['id'] : 0;
$new_notifications = ($latest_note_id > $profile['last_seen_notification_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="icon" type="image/png" href="../b2c_logo.png">
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

/* 🌟 Global */
body {

  background-color: #e9eff4; /* light grayish background */
  font-family: Arial, Helvetica, sans-serif;
  color: #222;
}

/* 🌟 Sidebar */
/* Sidebar styling */
.sidebar {
  background: #1d2851ff; /* Dark background */
  border-radius: 12px;
  min-height: 100vh;
}

/* Sidebar links */
.sidebar-nav .nav-link {
  font-size: 1.2rem;  /* Bigger text */
  font-weight: 500;
  padding: 12px 15px;
  margin-bottom: 8px;
  border-radius: 8px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
}

/* Hover effect with animation */
.sidebar-nav .nav-link:hover {
  background: #7283beff;
  transform: translateX(6px);  /* Slide effect */
  color: white !important;   /* Highlighted text */
  box-shadow: 0px 4px 12px rgba(0,0,0,0.3);
}

/* Active link (optional if you want highlight when clicked) */
.sidebar-nav .nav-link.active {
  background: #5c6a9cff;
  color: whitesmoke !important;
  font-weight: 600;
}


/* 🌟 Profile Card */
.profile-card {
  background: #fff;
  padding: 15px 20px;
  border: 1px solid #bbb;
  border-radius: 6px;
  margin-bottom: 20px;
}

.profile-card h4 {
  font-weight: bold;
  margin-bottom: 15px;
}

/* 🌟 Profile Photo */
.profile-photo img {
  width: 140px;
  height: 160px;
  object-fit: cover;
  border: 1px solid #333;
  border-radius: 2px;
}

/* 🌟 Table */
.table {
  margin: 0;
  background: #fff;
  border: 1px solid #ccc;
}

.table th {
  background: #f2f2f2;
  font-weight: bold;
  text-align: left;
}

.table td {
  font-size: 0.9rem;
}

/* 🌟 Buttons */
.btn {
  border-radius: 6px;
  font-weight: 500;
  padding: 6px 14px;
}

.btn-outline-primary {
  background: #3f4e91;
  color: #fff;
  border: none;
}

.btn-outline-primary:hover {
  background: #2c3b73;
  color: #fff;
}

.btn-outline-success {
  background: #4d885e;
  color: #fff;
  border: none;
}

.btn-outline-success:hover {
  background: #396b49;
  color: #fff;
}

.btn-outline-warning {
  background: #e0a330;
  color: #fff;
  border: none;
}

.btn-outline-warning:hover {
  background: #c7811e;
}

.btn-primary {
  background: #3f4e91;
  border: none;
}

.btn-primary:hover {
  background: #2c3b73;
}

/* 🌟 Badges */
.badge {
  padding: 6px 10px;
  font-size: 0.8rem;
  border-radius: 6px;
}

/* 🌟 Job Cards Container */
.job-cards {
  display: flex;
  gap: 15px;
  overflow-x: auto;
  padding-bottom: 10px;
}

/* 🌟 Single Job Card */
.job-card {
  flex: 0 0 150px;
  border: 1px solid #ccc;
  border-radius: 10px;
  padding: 10px;
  background: #fff;
  text-align: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.job-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  transform: translateY(-2px);
  transition: 0.3s;
}

/* 🌟 Logo */
.job-logo img {
  width: 100%;
  max-width: 80px;
  height: 80px;
  object-fit: contain;
  margin-bottom: 8px;
}

/* 🌟 Company & Role */
.job-company {
  font-weight: bold;
  font-size: 0.95rem;
  margin: 0;
}

.job-role {
  font-size: 0.85rem;
  color: #666;
  margin: 4px 0 6px;
}

/* 🌟 Read More link */
.read-more {
  font-size: 0.8rem;
  color: #3f4e91;
  text-decoration: none;
}

.read-more:hover {
  text-decoration: underline;
}


  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">

    <!-- Sidebar -->
    <div class="col-md-3 sidebar text-white p-3">
      <h3 class="mb-4">Welcome, <?php echo htmlspecialchars($profile['name']); ?></h3>
      <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
          <a href="#profile" class="nav-link text-white">🧑‍🎓 Profile</a>
        </li>
        <li class="nav-item">
          <a href="job-opp.php" class="nav-link text-white">💼 Job Opportunities</a>
        </li>
        <li class="nav-item">
          <a href="#applications" class="nav-link text-white">📑 My Applications</a>
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

      <!-- Profile -->
      <section id="profile" class="mb-4">
        <div class="profile-card">
          <h4 class="mb-3">My Profile</h4>
          <div class="row">
            <!-- Left: Profile Info -->
            <div class="col-md-8">
              <table class="table table-bordered">
                <tr><th>Name</th><td><?php echo htmlspecialchars($profile['name']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($profile['email']); ?></td></tr>
                <tr><th>Phone</th><td><?php echo htmlspecialchars($profile['phone']); ?></td></tr>
                <tr><th>Department</th><td><?php echo htmlspecialchars($profile['branch']); ?></td></tr>
                <tr><th>CGPA</th><td><?php echo htmlspecialchars($profile['cgpa']); ?></td></tr>
              </table>
             <div class="d-flex flex-wrap gap-2 mt-3">
            <!-- Edit Profile -->
            <a href="edit-profile.php" class="btn btn-outline-primary d-flex align-items-center">
              <i class="bi bi-pencil-square me-2"></i> Edit Profile
            </a>

            <!-- Upload Resume -->
            <button class="btn btn-outline-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#resumeModal">
              <i class="bi bi-file-earmark-arrow-up me-2"></i> Upload Resume
            </button>

            <!-- Update Photo -->
            <button class="btn btn-outline-warning d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#photoModal">
              <i class="bi bi-image me-2"></i> Update Photo
            </button>
</div>

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

            </div>

            <!-- Right: Profile Photo -->
            <div class="col-md-4 text-center profile-photo">
              <?php if (!empty($profile['passport_photo'])) { ?>
                <img src="<?php echo $profile['passport_photo']; ?>" alt="Passport Photo">
              <?php } else { ?>
                <img src="default-photo.png" alt="No Photo">
                <p class="text-muted">No Photo Uploaded</p>
              <?php } ?>
            </div>
          </div>
        </div>
      </section>

      <!-- Job Opportunities -->
      <!-- Job Opportunities + My Applications -->
     <section id="jobs-apps" class="mb-4">
  <div class="row g-4">

    <!-- Job Opportunities (smaller width) -->
    <div class="col-md-4"> <!-- changed from col-md-6 -->
      <div class="profile-card h-100">
        <h4 class="mb-3">Job Opportunities</h4>
        <?php if ($jobs->num_rows > 0) { ?>
          <div class="job-cards">
            <?php while($job = $jobs->fetch_assoc()){ ?>
              <div class="job-card">
                <!-- Logo -->
                <div class="job-logo">
                  <?php if (!empty($job['logo'])) { ?>
                    <img src="../admin/uploads/logos/<?php echo htmlspecialchars($job['logo']); ?>" 
                         alt="Logo">
                  <?php } else { ?>
                    <span class="text-muted">No Logo</span>
                  <?php } ?>
                </div>

                <!-- Info -->
                <div class="job-info">
                  <h6 class="job-company"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                  <p class="job-role"><?php echo htmlspecialchars($job['job_title']); ?></p>
                  <a href="<?php echo htmlspecialchars($job['application_link']); ?>" 
                     target="_blank" 
                     class="read-more">Read more</a>
                </div>
              </div>
            <?php } ?>
          </div>

          <!-- View More -->
          <div class="text-center mt-3">
            <a href="job-opp.php" class="btn btn-primary px-4">View More</a>
          </div>

        <?php } else { ?>
          <p class="text-muted">No job opportunities available right now.</p>
        <?php } ?>
      </div>
    </div>

    <!-- My Applications (larger width) -->
    <div class="col-md-8"> <!-- changed from col-md-6 -->
      <div class="profile-card h-100">
        <h4 class="mb-3">My Applications</h4>
        <?php if ($applications->num_rows > 0) { ?>
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Company</th>
              <th>Role</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while($app = $applications->fetch_assoc()){ ?>
              <tr>
                <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                <td>
                  <?php 
                    $status = $app['status'];
                    if($status=="Applied") echo "<span class='badge bg-info'>$status</span>";
                    elseif($status=="Shortlisted") echo "<span class='badge bg-warning text-dark'>$status</span>";
                    elseif($status=="Selected") echo "<span class='badge bg-success'>$status</span>";
                    elseif($status=="Level-2") echo "<span class='badge bg-primary'>$status</span>";
                    else echo "<span class='badge bg-danger'>$status</span>";
                  ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
        <?php } else { ?>
          <p class="text-muted">You haven’t applied to any jobs yet.</p>
        <?php } ?>
      </div>
    </div>

  </div>
</section>



    </div>
  </div>
</div>

<!-- Resume Modal -->
<div class="modal fade" id="resumeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Upload Resume</h5></div>
      <div class="modal-body">
        <input type="file" name="resume" accept=".pdf,.doc,.docx" required class="form-control">
      </div>
      <div class="modal-footer">
        <button type="submit" name="upload_resume" class="btn btn-success">Upload</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Update Photo</h5></div>
      <div class="modal-body">
        <input type="file" name="photo" accept="image/*" required class="form-control">
      </div>
      <div class="modal-footer">
        <button type="submit" name="upload_photo" class="btn btn-warning">Upload</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
