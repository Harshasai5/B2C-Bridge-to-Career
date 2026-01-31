<?php
session_start();
include 'db.php'; // ✅ use your project’s DB file

// ✅ Check login
if (!isset($_SESSION['reg_no'])) {
    header("Location: std_login.php");
    exit();
}

// ✅ Fetch ATS results for logged-in student
$stmt = $conn->prepare("
    SELECT a.ats_score, a.missing_keywords, a.suggestions, 
           c.name AS company_name, c.job_role, a.created_at
    FROM ats_results a
    JOIN companies c ON a.company_id = c.company_id
    WHERE a.reg_no = ?
    ORDER BY a.created_at DESC
");
$stmt->bind_param("s", $_SESSION['reg_no']);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ATS Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        tr:nth-child(even) { background: #fafafa; }
        a { text-decoration: none; color: #007BFF; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>ATS Results for <?php echo htmlspecialchars($_SESSION['name']); ?></h2>

    <?php if ($results->num_rows === 0): ?>
        <p>No ATS runs yet. Run ATS to see your results.</p>
        <p><a href="run_ats.php">➡ Run ATS Now</a></p>
    <?php else: ?>
        <table>
            <tr>
                <th>Company</th>
                <th>Job Role</th>
                <th>ATS Score (%)</th>
                <th>Missing Skills</th>
                <th>Suggestions</th>
                <th>Analyzed At</th>
            </tr>
            <?php while ($row = $results->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['job_role']); ?></td>
                    <td><?php echo intval($row['ats_score']); ?>%</td>
                    <td><?php echo htmlspecialchars($row['missing_keywords']); ?></td>
                    <td><?php echo htmlspecialchars($row['suggestions']); ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        <p><a href="std_dashboard.php">⬅ Back to Dashboard</a></p>
    <?php endif; ?>
</body>
</html>
