<?php
session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $department = ($role === 'spe_department') ? trim($_POST['department']) : NULL;

    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $message = "Email already exists!";
    } else {
        // Insert into DB
        $stmt = $conn->prepare("INSERT INTO admins (email, password, role, department) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $password, $role, $department);

        if ($stmt->execute()) {
            $message = "Registration successful! You can now login.";
        } else {
            $message = "Error: " . $stmt->error;
        }
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container {
            width: 400px; margin: 80px auto; background: #fff; padding: 20px;
            border-radius: 10px; box-shadow: 0px 0px 10px #aaa;
        }
        h2 { text-align: center; }
        label { display: block; margin-top: 10px; }
        input, select {
            width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%; padding: 10px; margin-top: 15px;
            background: #28a745; color: #fff; border: none; border-radius: 5px;
            cursor: pointer; font-size: 16px;
        }
        button:hover { background: #218838; }
        .message { margin-top: 15px; text-align: center; color: red; }
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
    <script>
        function toggleDepartmentField() {
            var role = document.getElementById("role").value;
            var deptField = document.getElementById("deptField");
            if (role === "spe_department") {
                deptField.style.display = "block";
            } else {
                deptField.style.display = "none";
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="../b2c_logo.png">

</head>
<body>
    <div class="container">
        <h2>Admin Registration</h2>
        <?php if ($message != "") echo "<p class='message'>$message</p>"; ?>
        <form method="POST">
            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>Role:</label>
            <select name="role" id="role" onchange="toggleDepartmentField()" required>
                <option value="">--Select Role--</option>
                <option value="placement_cell">Placement Cell</option>
                <option value="spe_department">Specific Department</option>
            </select>

            <div id="deptField" style="display:none;">
                <label>Department:</label>
                <input type="text" name="department">
            </div>

            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>
