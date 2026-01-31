<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND role = 'placement_cell'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['user_id'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = $admin['role'];

            header("Location: PC-dashboard.php");
            exit;
        } else {
            $error = "❌ Invalid placement cell login credentials!";
        }
    } else {
        $error = "⚠️ Please enter both email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Placement Cell Login</title>
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
        .login-box {
            position: relative;
            background: #fff;
            padding: 40px 30px 30px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            text-align: center;
            width: 320px;
        }
        .logo-container {
            position: absolute;
            top: -60px; /* Move logo up */
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .logo-container img {
            width: 125px;
            height: 125px;
            border-radius: 50%;
            object-fit: cover;
        }
        .login-box h2 {
            margin-top: 60px; /* Push text below logo */
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        .login-box input {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
        }
        .login-box button {
            width: 95%;
            padding: 10px;
            background: #b6c9ef;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
        }
        .login-box button:hover {
            background: #8faee2;
        }
        .error {
            color: red;
            margin-bottom: 10px;
            font-size: 14px;
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
    <div class="login-box">
        <div class="logo-container">
            <img src="../b2c_logo.png" alt="College Logo">
        </div>
        <h2>PLACEMENT CELL LOGIN</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter Email" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit">Login</button>
        </form>
        <?php if (!empty($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>
    </div>
</body>
</html>
