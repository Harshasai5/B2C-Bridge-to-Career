<?php
session_start();
include 'db.php';
require '../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

$msg = "";



// -------------------- CSV EXPORT --------------------
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Build your query (filters if needed)
    $sql = "SELECT j.id, j.reg_no, j.job_id, j.status, j.remarks, j.uploaded_at, s.name, s.branch 
            FROM job_applications j
            JOIN students s ON j.reg_no = s.reg_no
            ORDER BY j.uploaded_at DESC";
    $res = $conn->query($sql);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="applications.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Reg No','Name','Branch','Job ID','Status','Remarks','Uploaded At']);
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['reg_no'],
            $row['name'],
            $row['branch'],
            $row['job_id'],
            $row['status'],
            $row['remarks'],
            $row['uploaded_at']
        ]);
    }
    fclose($output);
    exit; // stop execution here to prevent HTML output
}
// // -------------------- UPLOAD EXCEL --------------------
// if (isset($_POST['upload'])) {
//     if (!empty($_FILES['excel_file']['tmp_name'])) {
//         $file = $_FILES['excel_file']['tmp_name'];

//         try {
//             $spreadsheet = IOFactory::load($file);
//             $sheet = $spreadsheet->getActiveSheet();
//             $rows = $sheet->toArray();

//             // Skip header
//             for ($i = 1; $i < count($rows); $i++) {
//                 $reg_no   = $rows[$i][0];
//                 $job_id   = $rows[$i][1];
//                 $status   = $rows[$i][2];
//                 $remarks  = $rows[$i][3] ?? null;

//                 if (!empty($reg_no) && !empty($job_id)) {
//                     $stmt = $conn->prepare("INSERT INTO job_applications 
//                         (reg_no, job_id, status, remarks, uploaded_at) 
//                         VALUES (?, ?, ?, ?, NOW())");
//                     $stmt->bind_param("siss", $reg_no, $job_id, $status, $remarks);
//                     $stmt->execute();
//                 }
//             }
//             $msg = "✅ File uploaded successfully!";
//         } catch (Exception $e) {
//             $msg = "❌ Error reading file: " . $e->getMessage();
//         }
//     } else {
//         $msg = "⚠️ Please upload a file.";
//     }
// }

// -------------------- Filters --------------------
$deptFilter = $_GET['dept'] ?? '';
$jobFilter  = $_GET['job'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// -------------------- Pagination --------------------
$perPage = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// -------------------- Build Query --------------------
$where = [];
$params = [];
$types = '';

if ($deptFilter) {
    $where[] = "s.branch=?";
    $params[] = $deptFilter;
    $types .= 's';
}
if ($jobFilter) {
    $where[] = "j.job_id=?";
    $params[] = $jobFilter;
    $types .= 'i';
}
// if ($statusFilter) {
//     $where[] = "j.status=?";
//     $params[] = $statusFilter;
//     $types .= 's';
// }
if ($search) {
    $where[] = "(s.reg_no LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count total rows
$stmtCount = $conn->prepare("
    SELECT COUNT(*) 
    FROM job_applications j
    JOIN students s ON s.reg_no=j.reg_no
    $whereSQL
");
if ($params) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$totalRows = $stmtCount->get_result()->fetch_row()[0];
$totalPages = ceil($totalRows / $perPage);

// Fetch paginated results
// Fetch paginated results
$stmt = $conn->prepare("
    SELECT j.*, s.name, s.branch 
    FROM job_applications j
    JOIN students s ON s.reg_no=j.reg_no
    $whereSQL
    ORDER BY j.uploaded_at DESC
    LIMIT ?, ?
");

$allParams = $params ? array_merge($params, [$offset, $perPage]) : [$offset, $perPage];
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);

$stmt->execute();
$applications = $stmt->get_result();

// Fetch all departments and jobs for dropdowns
$departments = $conn->query("SELECT DISTINCT branch FROM students ORDER BY branch ASC");
$jobs = $conn->query("SELECT DISTINCT job_id FROM job_applications ORDER BY job_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../b2c_logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

<meta charset="UTF-8">
<title>View Applications</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; margin:0; }
.sidebar { width: 240px; background: #1e1e2d; color:#fff; position:fixed; top:0; left:0; height:100vh; padding-top:20px; }
.sidebar h2 { text-align:center; color:#00d9ff; margin-bottom:30px; }
.sidebar ul { list-style:none; padding:0; }
.sidebar ul li { margin:10px 0; }
.sidebar ul li a { color:#ddd; text-decoration:none; display:flex; align-items:center; padding:12px 20px; border-radius:8px; transition:0.3s; }
.sidebar ul li a i { margin-right:10px; color:#00d9ff; }
.sidebar ul li a:hover { background:#00d9ff; color:black; transform:translateX(5px); }
.sidebar .logout { margin-top:30px; background:#e63946 !important; color:#fff !important; }
.container { margin-left:260px; padding:20px; }
table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
th, td { padding:12px 10px; text-align:left; }
th { background:#00d9ff; color:black; }
tr:nth-child(even){ background:#f2f2f2; }
.msg { margin-bottom:15px; font-weight:bold; color:green; }
</style>
</head>
<body>

<div class="sidebar">
    <h2><i class="fas fa-user-tie"></i> Placement Cell</h2>
    <ul>
        <li><a href="PC-dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="manage-jobs.php"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
        <li><a href="view-applications.php"><i class="fas fa-users"></i> View Applications</a></li>
        <li><a href="upload-job-status.php"><i class="fas fa-file-upload"></i> Upload Status</a></li>
        <li><a href="send-notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="manage-departments.php"><i class="fas fa-sitemap"></i> Departments</a></li>
        <li><a href="PC-logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="container">
    <h3>📊 View Applications</h3>
    <?php if($msg) echo "<p class='msg'>$msg</p>"; ?>

    <!-- Filters -->
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Search by Reg No / Name" class="form-control">
        </div>
        <div class="col-md-2">
            <select name="dept" class="form-select">
                <option value="">All Departments</option>
                <?php while($d = $departments->fetch_assoc()): ?>
                    <option value="<?= $d['branch'] ?>" <?= $deptFilter==$d['branch']?'selected':'' ?>><?= $d['branch'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="job" class="form-select">
                <option value="">All Jobs</option>
                <?php while($j = $jobs->fetch_assoc()): ?>
                    <option value="<?= $j['job_id'] ?>" <?= $jobFilter==$j['job_id']?'selected':'' ?>><?= $j['job_id'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <!-- <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="Selected" <?= $statusFilter=='Selected'?'selected':'' ?>>Selected</option>
                <option value="Rejected" <?= $statusFilter=='Rejected'?'selected':'' ?>>Rejected</option>
                <option value="Pending" <?= $statusFilter=='Pending'?'selected':'' ?>>Pending</option>
            </select>
        </div> -->
        <div class="col-md-3">
            <button class="btn btn-primary" >Filter</button>
            <a href="?export=csv" class="btn btn-success">Export CSV</a>
        </div>
    </form>

    <!-- Applications Table -->
    <table id="applicationsTable">
        <tr>
            <th>ID</th>
            <th>Reg. No</th>
            <th>Name</th>
            <th>Branch</th>
            <th>Job ID</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Uploaded At</th>
        </tr>
        <?php while($row = $applications->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['reg_no'] ?></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['branch'] ?></td>
            <td><?= $row['job_id'] ?></td>
            <td><?= $row['status'] ?></td>
            <td><?= $row['remarks'] ?></td>
            <td><?= $row['uploaded_at'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation example" class="mt-3">
      <ul class="pagination">
        <?php for($p=1;$p<=$totalPages;$p++): ?>
            <li class="page-item <?= $p==$page?'active':'' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
      </ul>
    </nav>
</div>

<script>
    // Live search
    const searchInput = document.querySelector('input[name="search"]');
    const table = document.getElementById('applicationsTable');
    searchInput.addEventListener('keyup', function(){
        const val = this.value.toLowerCase();
        for(let i=1; i<table.rows.length; i++){
            const row = table.rows[i];
            const regNo = row.cells[1].innerText.toLowerCase();
            const name  = row.cells[2].innerText.toLowerCase();
            row.style.display = (regNo.includes(val) || name.includes(val)) ? '' : 'none';
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// -------------------- CSV Export --------------------
if(isset($_GET['export'])){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="applications.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Reg No','Name','Branch','Job ID','Status','Remarks','Uploaded At']);
    
    $applications->data_seek(0); // rewind result
    while($row = $applications->fetch_assoc()){
        fputcsv($out, [
            $row['id'],$row['reg_no'],$row['name'],$row['branch'],
            $row['job_id'],$row['status'],$row['remarks'],$row['uploaded_at']
        ]);
    }
    fclose($out);
    exit;
}
?>
