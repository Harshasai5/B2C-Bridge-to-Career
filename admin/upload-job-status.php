<?php
include 'db.php'; // DB connection
require '../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = "";

if (isset($_POST['upload'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $fileName = $_FILES['excel_file']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($fileName);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $isFirstRow = true;
            $inserted = 0;
            $updated = 0;

            foreach ($rows as $row) {
                if ($isFirstRow) {
                    $isFirstRow = false;
                    continue;
                }

                $reg_no   = trim($row[0]);
                $job_id   = intval($row[1]);
                $status   = trim($row[4]);
                $remarks  = trim($row[3]);

                if (!empty($reg_no) && !empty($job_id) && !empty($status)) {
                    $check = $conn->prepare("SELECT id FROM job_applications WHERE reg_no=? AND job_id=?");
                    $check->bind_param("si", $reg_no, $job_id);
                    $check->execute();
                    $result = $check->get_result();

                    if ($result->num_rows > 0) {
                        $update = $conn->prepare("UPDATE job_applications 
                                                  SET status=?, remarks=?, uploaded_at=NOW() 
                                                  WHERE reg_no=? AND job_id=?");
                        $update->bind_param("sssi", $status, $remarks, $reg_no, $job_id);
                        $update->execute();
                        $updated++;
                    } else {
                        $insert = $conn->prepare("INSERT INTO job_applications (reg_no, job_id, status, remarks, uploaded_at) 
                                                  VALUES (?, ?, ?, ?, NOW())");
                        $insert->bind_param("siss", $reg_no, $job_id, $status, $remarks);
                        $insert->execute();
                        $inserted++;
                    }
                }
            }

            $message = "✅ Upload successful! Inserted: $inserted, Updated: $updated";
        } catch (Exception $e) {
            $message = "❌ Error reading file: " . $e->getMessage();
        }
    } else {
        $message = "⚠ Please upload a valid Excel file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Student Status</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, sans-serif;
      margin: 0;
      background: #f4f6f9;
      color: #333;
      display: flex;
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
    .main-content {
      margin-left: 240px;
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px;
    }
    header {
      background: #00d9ff;
      color: black;
      padding: 15px;
      font-size: 22px;
      font-weight: bold;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      width: 100%;
      text-align: center;
      border-radius: 8px;
    }
    .container {
      max-width: 600px;
      width: 100%;
      margin-top: 40px;
      padding: 25px;
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      text-align: center;
    }
    h2 {
      font-size: 24px;
      margin-bottom: 20px;
      color: black;
    }
    .message {
      margin: 15px 0;
      padding: 12px;
      border-radius: 8px;
      font-weight: 500;
      text-align: left;
    }
    .message.success {
      background: #eafaf1;
      color: #27ae60;
      border-left: 6px solid #27ae60;
    }
    .message.error {
      background: #fdecea;
      color: #e74c3c;
      border-left: 6px solid #e74c3c;
    }
    label {
      font-weight: 600;
      display: block;
      margin: 15px 0 8px;
      text-align: left;
      color: #333;
    }
    input[type="file"] {
      padding: 12px;
      width: 100%;
      border: 2px dashed #3498db;
      border-radius: 10px;
      background: #f8f9fb;
      cursor: pointer;
      transition: 0.3s;
    }
    input[type="file"]:hover {
      background: #eef6fc;
      border-color: #2980b9;
    }
    button {
      margin-top: 20px;
      background: #00d9ff;
      color: black;
      border: none;
      padding: 12px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 500;
      width: 100%;
      transition: 0.3s;
    }
    button:hover {
      background: #2980b9;
      transform: translateY(-2px);
    }
    .back-link {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      color: #3498db;
      font-weight: bold;
      transition: 0.3s;
    }
    .back-link:hover {
      color: #2980b9;
      text-decoration: underline;
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
  <header>📤 Upload Student Status</header>

  <div class="container">
    <h2>Upload Excel File</h2>

    <?php if($message): ?>
      <div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <label for="excel_file">Select Excel File:</label>
      <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required>
      <button type="submit" name="upload"><i class="fas fa-upload"></i> Upload</button>
    </form>

  </div>
</div>

</body>
</html>
