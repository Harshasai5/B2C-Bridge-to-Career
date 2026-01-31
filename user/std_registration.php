<?php
include 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reg_no = $_POST['reg_no'];
    $name = $_POST['name'];
    $branch = $_POST['branch'];
    $cgpa = $_POST['cgpa'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $aadhaar_id = $_POST['aadhaar_id'];
    $pan_id = $_POST['pan_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // 📄 Resume upload
    $resume = null;
    if (!empty($_FILES['resume']['name'])) {
        $targetDir = "uploads/resumes/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $resume = $targetDir . uniqid() . "_" . basename($_FILES["resume"]["name"]);
        move_uploaded_file($_FILES["resume"]["tmp_name"], $resume);
    }

    // 🖼 Passport photo upload
    $passport_photo = null;
    if (!empty($_FILES['passport_photo']['name'])) {
        $photoDir = "uploads/photos/";
        if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);

        $passport_photo = $photoDir . uniqid() . "_" . basename($_FILES["passport_photo"]["name"]);
        move_uploaded_file($_FILES["passport_photo"]["tmp_name"], $passport_photo);
    }

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO students 
        (reg_no, name, passport_photo, branch, cgpa, phone, email, aadhaar_id, pan_id, resume, password) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissssss", 
        $reg_no, $name, $passport_photo, $branch, $cgpa, $phone, $email, $aadhaar_id, $pan_id, $resume, $password
    );

    if ($stmt->execute()) {
        $message = "✅ Registration successful! You can now login.";
    } else {
        $message = "❌ Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #cfe2f3;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-box {
            position: relative;
            background: #fff;
            padding: 60px 30px 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 700px;
        }
        .logo-container {
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .logo-container img {
            width: 115px;
            height: 115px;
            border-radius: 50%;
            object-fit: cover;
        }
        .register-box h2 {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 25px;
            font-size: 20px;
            font-weight: bold;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 48% 48%; /* Two columns */
            justify-content: space-between;
            gap: 15px 0;
        }

        .form-grid input,
        .form-grid select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        /* File upload styling */
        .file-field {
            display: flex;
            flex-direction: column; /* ✅ label above input */
        }

        .file-field label {
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 13px;
        }

        .file-field input[type="file"] {
            padding: 8px;
            background: #f9f9f9;
            border: 1px dashed #bbb;
            border-radius: 8px;
            cursor: pointer;
        }



        button {
            display: block;
            width: 200px;
            margin: 20px auto 0;
            padding: 10px;
            background: #b6c9ef;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
        }
        button:hover {
            background: #8faee2;
        }
        .message {
            text-align: center;
            color: red;
            margin-bottom: 10px;
        }
        .register-box p {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .register-box a {
            color: #007bff;
            text-decoration: none;
        }
        .register-box a:hover {
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
    <div class="register-box">
        <div class="logo-container">
            <img src="../b2c_logo.png" alt="College Logo">
        </div>
        <h2>STUDENT REGISTRATION</h2>
        <p class="message"><?php echo $message; ?></p>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <input type="text" name="name" placeholder="Enter your Name" required>
                <input type="text" name="reg_no" placeholder="Enter your Registration Num" required>

                <!-- Add branch select here -->
                <select name="branch" required>
                    <option value="">Select Branch</option>
                    <option value="CSE">CSE</option>
                    <option value="IT">IT</option>
                    <option value="AIML">AIML</option>
                    <option value="AIDS">AIDS</option>
                    <option value="CSBS">CSBS</option>
                    <option value="CIC">CIC</option>
                    <option value="CSD">CSD</option>
                    <option value="CSIT">CSIT</option>
                </select>

                <input type="email" name="email" placeholder="Enter your Email" required>
                <input type="text" name="phone" placeholder="Enter your Phone Num">

               
                <input type="text" name="cgpa" placeholder="Enter your CGPA">

                <input type="text" name="aadhaar_id" placeholder="Enter your Aadhar Num">
                <input type="text" name="pan_id" placeholder="Enter your Pan ID">

                <!-- File upload fields -->
                <div class="file-field">
                    <label for="resume">Upload your Resume</label>
                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                </div>

                <div class="file-field">
                    <label for="passport_photo">Upload your Passport Photo</label>
                    <input type="file" id="passport_photo" name="passport_photo" accept="image/*">
                </div>
                 <input type="password" name="password" placeholder="Password (At least 6 characters)" required>
            </div>

            <button type="submit">Register</button>
        </form>
        <p>Already registered? <a href="std_login.php">Login here</a></p>
    </div>
</body>
</html>
