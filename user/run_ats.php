<?php
session_start();
include 'db.php'; // ✅ DB connection inside user/

if (!isset($_SESSION['reg_no'])) {
    header("Location: std_login.php");
    exit();
}

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = $_POST['company_id'] ?? '';

    // ✅ Fetch resume file for logged-in student
    $stmt = $conn->prepare("SELECT resume FROM students WHERE reg_no = ?");
    $stmt->bind_param("s", $_SESSION['reg_no']);
    $stmt->execute();
    $resumeData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $resume = $resumeData['resume'] ?? null;

    if (!$resume) {
        $err = "⚠️ You have not uploaded a resume yet. Please upload from your profile.";
    } else {
        // ✅ Resume absolute path
        $resume_path = dirname(__DIR__) . "/user/" . $resume;

        if (!is_file($resume_path)) {
            $err = "⚠️ Resume file not found! Expected at: " . htmlspecialchars($resume_path);
        } else {
            // ✅ Run Python ATS analyzer
            $python = "python"; // Or "python3" if needed
            $script = dirname(__DIR__) . "/python_scripts/ats_analyzer.py";

            $command = escapeshellcmd("$python $script " 
                . escapeshellarg($_SESSION['reg_no']) . " "
                . escapeshellarg($company_id) . " "
                . escapeshellarg($resume_path));

            $output = shell_exec($command);

            if ($output === null) {
                $err = "❌ Failed to run ATS script. Check Python setup.";
            } else {
                $success = "<strong>✅ ATS analysis completed!</strong><br><pre>" . htmlspecialchars($output) . "</pre>";
            }
        }
    }
}

// ✅ Fetch companies for dropdown
$companies = $conn->query("SELECT company_id, name FROM companies ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Run ATS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f4f6f9;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            width: 500px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 22px;
        }
        label {
            font-weight: 500;
            display: block;
            margin-bottom: 6px;
            color: #444;
        }
        select, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        select:focus {
            border-color: #3498db;
            outline: none;
        }
        button {
            background: #3498db;
            border: none;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .error, .success {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .error { background: #ffe5e5; color: #e74c3c; }
        .success { background: #e8f9f1; color: #27ae60; }
        pre {
            background: #f4f6f9;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .back-link {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: #3498db;
            font-weight: 500;
        }
        .back-link:hover {
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
    
    <div class="container">
        <h2><i class="fa-solid fa-bolt"></i> Run ATS</h2>

        <?php if ($err): ?>
            <div class="error"><?= $err ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="company_id"><i class="fa-solid fa-building"></i> Select Company:</label>
            <select name="company_id" id="company_id" required>
                <option value="">-- Select --</option>
                <?php while($row = $companies->fetch_assoc()): ?>
                    <option value="<?= $row['company_id']; ?>">
                        <?= htmlspecialchars($row['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit"><i class="fa-solid fa-play"></i> Run ATS</button>
        </form>

        <a href="std_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
