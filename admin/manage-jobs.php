<?php
include 'db.php'; // Your database connection

// -------------------- ADD OR UPDATE JOB --------------------
if (isset($_POST['save_job'])) {
    $company_id = $_POST['company_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $skills_required = $_POST['skills_required'];
    $min_cgpa = $_POST['min_cgpa'];
    $eligible_branches = $_POST['eligible_branches'];
    $job_role = $_POST['job_role'];
    $package = $_POST['package'];
    $application_link = $_POST['application_link'];

    // File upload for logo
    $logo = '';
    if (!empty($_FILES['logo']['name'])) {
        $targetDir = "uploads/logos/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $logo = time() . "_" . basename($_FILES["logo"]["name"]);
        $targetFilePath = $targetDir . $logo;
        move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFilePath);
    } else {
        $logo = $_POST['old_logo'] ?? '';
    }

    if (!empty($_POST['edit_id'])) {
        $edit_id = $_POST['edit_id'];
        $query = "UPDATE companies SET company_id=?, logo=?, name=?, description=?, skills_required=?, min_cgpa=?, eligible_branches=?, job_role=?, package=?, application_link=? WHERE company_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssdssssi", $company_id, $logo, $name, $description, $skills_required, $min_cgpa, $eligible_branches, $job_role, $package, $application_link, $edit_id);
        $stmt->execute();
    } else {
        $query = "INSERT INTO companies (company_id, logo, name, description, skills_required, min_cgpa, eligible_branches, job_role, package, application_link, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssdssss", $company_id, $logo, $name, $description, $skills_required, $min_cgpa, $eligible_branches, $job_role, $package, $application_link);
        $stmt->execute();
    }
    header("Location: manage-jobs.php");
    exit;
}

// -------------------- DELETE JOB --------------------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM companies WHERE company_id=$id");
    header("Location: manage-jobs.php");
    exit;
}

// -------------------- FETCH JOBS --------------------
$jobs = $conn->query("SELECT * FROM companies ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Jobs</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background: #f5f6fa;
      display: flex;
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

    /* Main content */
    .main-content {
      margin-left: 240px;
      flex: 1;
      padding: 20px;
      
    }

    header {
      background: #00d9ff;
      color: black;
      padding: 15px 25px;
      font-size: 22px;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .container {
      max-width: 1100px;
      margin: auto;
    }

    /* Add Job Button */
    #toggleFormBtn {
      background: #28a745;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 15px;
      transition: 0.3s;
      margin-bottom: 20px;
    }
    #toggleFormBtn:hover { background: #218838; }

    /* Form */
    .form-box {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    .form-box h3 {
      margin-bottom: 15px;
      color: #0066cc;
    }
    label { display: block; margin: 8px 0 4px; font-weight: 500; }
    input, textarea {
      width: 100%; padding: 10px; border: 1px solid #ddd;
      border-radius: 8px; margin-bottom: 12px; font-size: 14px;
    }
    button {
      background: #0066cc; color: white; padding: 10px 18px;
      border: none; border-radius: 8px; cursor: pointer; font-size: 15px;
    }
    button:hover { background: #004a99; }
    #cancelBtn { background: #dc3545; }

    /* Job Cards */
    .job-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }
    .job-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: transform 0.2s ease;
    }
    .job-card:hover { transform: translateY(-5px); }
    .job-logo img {
      max-width: 80px;
      border-radius: 8px;
      margin-bottom: 15px;
    }
    .job-info {
      text-align: center;
    }
    .job-info h4 { margin: 8px 0; color: #0066cc; }
    .job-info p { margin: 4px 0; font-size: 14px; }
    .job-actions {
      margin-top: 15px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .job-actions a {
      padding: 8px 14px;
      border-radius: 6px;
      font-size: 13px;
      text-decoration: none;
      color: white;
    }
    .job-actions a.apply  { background: #007bff; }  /* Primary blue for Apply */
    .job-actions a.edit   { background: #28a745; }  /* Success green for Edit (keep) */
    .job-actions a.delete { background: #dc3545; }  /* Danger red for Delete (keep) */
    .job-actions a:hover  { opacity: 0.85; }


    .job-logo img {
        width: 100px;       /* fixed width */
        height: 100px;      /* fixed height */
        object-fit: contain; /* keep aspect ratio without cropping */
        border-radius: 8px;
        margin-bottom: 15px;
        background: #f8f9fa; /* optional: adds a neutral background */
        padding: 5px;       /* optional: spacing inside */
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


<!-- Main Content -->
<div class="main-content">
  <header>
    📋 Manage Job Openings
  </header>

  <div class="container">
    <!-- Add Job Button -->
    <button id="toggleFormBtn">➕ Add Job</button>

    <!-- Job Form -->
    <div class="form-box" id="jobForm" style="display:none;">
      <h3>Add / Edit Job</h3>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" id="edit_id">
        <input type="hidden" name="old_logo" id="old_logo">
        <label>Company ID</label><input type="number" name="company_id" id="company_id" required>
        <label>Company Name</label><input type="text" name="name" id="name" required>
        <label>Description</label><textarea name="description" id="description" required></textarea>
        <label>Skills Required</label><input type="text" name="skills_required" id="skills_required" required>
        <label>Minimum CGPA</label><input type="number" step="0.01" name="min_cgpa" id="min_cgpa" required>
        <label>Eligible Branches</label><input type="text" name="eligible_branches" id="eligible_branches" required>
        <label>Job Role</label><input type="text" name="job_role" id="job_role" required>
        <label>Package (in LPA)</label><input type="text" name="package" id="package" required>
        <label>Application Link</label><input type="url" name="application_link" id="application_link" required>
        <label>Company Logo</label><input type="file" name="logo">
        <button type="submit" name="save_job">💾 Save Job</button>
        <button type="button" id="cancelBtn">✖ Cancel</button>
      </form>
    </div>

    <!-- Job Cards -->
    <div class="job-list">
      <?php while ($row = $jobs->fetch_assoc()) { ?>
        <div class="job-card">
          <div class="job-logo">
            <?php if ($row['logo']) { ?>
              <img src="uploads/logos/<?= $row['logo'] ?>" alt="logo">
            <?php } ?>
          </div>
          <div class="job-info">
            <h4><?= $row['name'] ?></h4>
            <p><b>Role:</b> <?= $row['job_role'] ?></p>
            <p><b>Package:</b> <?= $row['package'] ?> LPA</p>
            <p><b>CGPA:</b> <?= $row['min_cgpa'] ?></p>
            <p><b>Branches:</b> <?= $row['eligible_branches'] ?></p>
          </div>
          <div class="job-actions">
            <a href="<?= $row['application_link'] ?>" target="_blank" class="apply">🔗 Apply</a>
            <a href="#" class="edit" onclick='editJob(<?= json_encode($row) ?>)'>✏️ Edit</a>
            <a href="manage-jobs.php?delete=<?= $row['company_id'] ?>" class="delete" onclick="return confirm("Are you sure?")'>🗑️ Delete</a>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
</div>

<script>
const jobForm = document.getElementById('jobForm');
const toggleFormBtn = document.getElementById('toggleFormBtn');
const cancelBtn = document.getElementById('cancelBtn');

toggleFormBtn.addEventListener('click', () => {
  jobForm.style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

cancelBtn.addEventListener('click', () => {
  jobForm.style.display = 'none';
  document.getElementById('edit_id').value = '';
  document.querySelector('form').reset();
});

function editJob(job) {
  jobForm.style.display = 'block';
  document.getElementById('edit_id').value = job.company_id;
  document.getElementById('company_id').value = job.company_id;
  document.getElementById('name').value = job.name;
  document.getElementById('description').value = job.description;
  document.getElementById('skills_required').value = job.skills_required;
  document.getElementById('min_cgpa').value = job.min_cgpa;
  document.getElementById('eligible_branches').value = job.eligible_branches;
  document.getElementById('job_role').value = job.job_role;
  document.getElementById('package').value = job.package;
  document.getElementById('application_link').value = job.application_link;
  document.getElementById('old_logo').value = job.logo;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

</body>
</html>
