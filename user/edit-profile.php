<?php
session_start();
include 'db.php';

// Ensure student is logged in
if (!isset($_SESSION['reg_no'])) {
    header("Location: std_dashboard.php");
    exit;
}

$reg_no = $_SESSION['reg_no'];

// Force autocommit to avoid lock wait timeout
$conn->query("SET autocommit=1");

// Fetch student details
$result = $conn->query("SELECT * FROM students WHERE reg_no='$reg_no' LIMIT 1");
$student = $result->fetch_assoc();

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name       = trim($_POST['name']);
    $branch     = trim($_POST['branch']);
    $cgpa       = floatval($_POST['cgpa']);   // ✅ ensure float
    $phone      = trim($_POST['phone']);
    $email      = trim($_POST['email']);
    $aadhaar_id = trim($_POST['aadhaar_id']);
    $pan_id     = trim($_POST['pan_id']);

    // Keep old resume
    $resume = $student['resume'];
    if (!empty($_FILES['resume']['name'])) {
        $targetDir = "uploads/resumes/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $resume = $targetDir . $reg_no . "_" . basename($_FILES["resume"]["name"]);
        move_uploaded_file($_FILES["resume"]["tmp_name"], $resume);
    }

    // Keep old passport photo
    $passport_photo = $student['passport_photo'];
    if (!empty($_FILES['passport_photo']['name'])) {
        $photoDir = "uploads/photos/";
        if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);

        $passport_photo = $photoDir . $reg_no . "_" . basename($_FILES["passport_photo"]["name"]);
        move_uploaded_file($_FILES["passport_photo"]["tmp_name"], $passport_photo);
    }

    // ✅ Use correct bind_param types (cgpa as double `d`)
    $stmt = $conn->prepare("UPDATE students 
        SET name=?, branch=?, cgpa=?, phone=?, email=?, aadhaar_id=?, pan_id=?, resume=?, passport_photo=?
        WHERE reg_no=? LIMIT 1");

    $stmt->bind_param("ssdsssssss", 
        $name, $branch, $cgpa, $phone, $email, $aadhaar_id, $pan_id, $resume, $passport_photo, $reg_no
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: std_dashboard.php?msg=Profile Updated");
        exit;
    } else {
        echo "❌ Error: " . $stmt->error;
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            border-radius: 12px;
            padding: 25px 35px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: #444;
        }
        input[type="text"],
        input[type="email"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 4px rgba(0,123,255,0.4);
        }
        a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        a:hover {
            text-decoration: underline;
        }
        button {
            margin-top: 20px;
            width: 100%;
            background: #007bff;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #0056b3;
        }
        .resume-link, .photo-preview {
            margin-top: 10px;
            display: inline-block;
            font-size: 14px;
        }
        .photo-preview img {
            max-width: 120px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-top: 5px;
        }
        header {
            background: #0066cc;
            color: white;
            padding: 15px;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            position: relative;
        }
        .back-btn {
            position: absolute;
            left: 15px;
            top: 15px;
            background: white;
            color: #0066cc;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }
        .back-btn:hover {
            background: #e6e6e6;
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
    <header>
        <a href="std_dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
        Edit Profile
    </header>
    <div class="container">
        <h2>Edit Profile</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Full Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>

            <label>Branch:</label>
            <select name="branch" required>
                <option value="">--Select Branch--</option>
                <option value="CSE" <?= $student['branch']=="CSE"?"selected":"" ?>>CSE</option>
                <option value="IT" <?= $student['branch']=="IT"?"selected":"" ?>>IT</option>
                <option value="AIML" <?= $student['branch']=="AIML"?"selected":"" ?>>AIML</option>
                <option value="AIDS" <?= $student['branch']=="AIDS"?"selected":"" ?>>AIDS</option>
                <option value="CSBS" <?= $student['branch']=="CSBS"?"selected":"" ?>>CSBS</option>
                <option value="CIC" <?= $student['branch']=="CIC"?"selected":"" ?>>CIC</option>
                <option value="CSD" <?= $student['branch']=="CSD"?"selected":"" ?>>CSD</option>
                <option value="CSIT" <?= $student['branch']=="CSIT"?"selected":"" ?>>CSIT</option>
                <option value="ECE" <?= $student['branch']=="ECE"?"selected":"" ?>>ECE</option>
                <option value="EEE" <?= $student['branch']=="EEE"?"selected":"" ?>>EEE</option>
                <option value="MECH" <?= $student['branch']=="MECH"?"selected":"" ?>>MECH</option>
                <option value="CIVIL" <?= $student['branch']=="CIVIL"?"selected":"" ?>>CIVIL</option>
            </select>

            <label>CGPA:</label>
            <input type="text" name="cgpa" value="<?= htmlspecialchars($student['cgpa']) ?>">

            <label>Phone Number:</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($student['phone']) ?>">

            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>

            <label>Aadhaar ID:</label>
            <input type="text" name="aadhaar_id" value="<?= htmlspecialchars($student['aadhaar_id']) ?>">

            <label>PAN ID:</label>
            <input type="text" name="pan_id" value="<?= htmlspecialchars($student['pan_id']) ?>">

            <label>Resume (PDF):</label>
            <?php if (!empty($student['resume'])): ?>
                <div class="resume-link">
                    <a href="<?= $student['resume'] ?>" target="_blank">📄 View Current Resume</a>
                </div>
            <?php endif; ?>
            <input type="file" name="resume" accept="application/pdf">

            <label>Passport Photo:</label>
            <?php if (!empty($student['passport_photo'])): ?>
                <div class="photo-preview">
                    <img src="<?= $student['passport_photo'] ?>" alt="Passport Photo">
                </div>
            <?php endif; ?>
            <input type="file" name="passport_photo" accept="image/*">

            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>
</html>
