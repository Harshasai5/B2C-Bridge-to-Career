<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: DP-dashboard.php");
    exit();
}

include 'db.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_no      = $_POST['reg_no'];
    $name        = $_POST['name'];
    $branch      = $_POST['branch'];
    $email       = $_POST['email'];
    $phone       = $_POST['phone'];
    $cgpa        = $_POST['cgpa'];
    $aadhaar_id  = $_POST['aadhaar_id'];
    $pan_id      = $_POST['pan_id'];
    $password    = $_POST['password']; // Optional

    // Fetch current files to retain if not updated
    $student = $conn->query("SELECT resume, passport_photo FROM students WHERE reg_no='$reg_no'")->fetch_assoc();
    $resume_file = $student['resume'];
    $photo_file  = $student['passport_photo'];

    // Handle resume upload
    if (!empty($_FILES['resume']['name'])) {
        $resume_name = time() . '_' . basename($_FILES['resume']['name']);
        $resume_target = '../user/' . $resume_name;
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $resume_target)) {
            $resume_file = $resume_name;
        }
    }

    // Handle passport photo upload
    if (!empty($_FILES['photo']['name'])) {
        $photo_name = time() . '_' . basename($_FILES['photo']['name']);
        $photo_target = '../user/' . $photo_name;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_target)) {
            $photo_file = $photo_name;
        }
    }

    // Hash password if provided
    $password_sql = '';
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $password_sql = ", password='$hashed_password'";
    }

    // Update query
    $stmt = $conn->prepare("UPDATE students SET 
        name=?, branch=?, email=?, phone=?, cgpa=?, aadhaar_id=?, pan_id=?, resume=?, passport_photo=? $password_sql 
        WHERE reg_no=?");

    // Bind parameters (excluding password if not provided)
    if (!empty($password)) {
        $stmt->bind_param("ssssssssss", $name, $branch, $email, $phone, $cgpa, $aadhaar_id, $pan_id, $resume_file, $photo_file, $reg_no);
    } else {
        $stmt = $conn->prepare("UPDATE students SET 
            name=?, branch=?, email=?, phone=?, cgpa=?, aadhaar_id=?, pan_id=?, resume=?, passport_photo=? 
            WHERE reg_no=?");
        $stmt->bind_param("ssssssssss", $name, $branch, $email, $phone, $cgpa, $aadhaar_id, $pan_id, $resume_file, $photo_file, $reg_no);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Student details updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating student: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: students_list.php");
    exit();
}
?>
