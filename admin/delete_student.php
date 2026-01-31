<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: DP-dashboard.php");
    exit();
}

include 'db.php';

// Check if reg_no is provided
if (!isset($_GET['reg_no'])) {
    die("No student specified.");
}

$reg_no = $_GET['reg_no'];

// Fetch student to get file paths
$stmt = $conn->prepare("SELECT resume, passport_photo FROM students WHERE reg_no = ?");
$stmt->bind_param("s", $reg_no);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("Student not found.");
}
$student = $result->fetch_assoc();
$stmt->close();

// Delete resume file if exists
if (!empty($student['resume']) && file_exists("../user/".$student['resume'])) {
    unlink("../user/".$student['resume']);
}

// Delete photo file if exists
if (!empty($student['passport_photo']) && file_exists("../user/".$student['passport_photo'])) {
    unlink("../user/".$student['passport_photo']);
}

// Delete student record
$stmt = $conn->prepare("DELETE FROM students WHERE reg_no = ?");
$stmt->bind_param("s", $reg_no);
if ($stmt->execute()) {
    $stmt->close();
    $_SESSION['success_msg'] = "Student deleted successfully!";
    header("Location: students_list.php");
    exit();
} else {
    $stmt->close();
    die("Error deleting student: " . $conn->error);
}
?>
