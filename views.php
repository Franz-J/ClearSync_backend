<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$stmt = $conn->prepare("SELECT first_name, last_name FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($role === 'Instructor') {
    echo "<h1>Instructor Dashboard</h1>";
    echo "Welcome, " . $user['first_name'] . " " . $user['last_name'] . "!";

    // List Classes Managed
    $stmt = $conn->prepare("SELECT class_id, class_name FROM classes WHERE instructor_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $classes = $stmt->get_result();

    echo "<h2>Your Classes</h2>";
    while ($class = $classes->fetch_assoc()) {
        echo "<p>" . $class['class_name'] . " - <a href='manage_clearance.php?class_id=" . $class['class_id'] . "'>Manage Clearance</a></p>";
    }
} else {
    echo "<h1>Student Dashboard</h1>";
    echo "Welcome, " . $user['first_name'] . " " . $user['last_name'] . "!";

    // List Enrolled Classes and Clearance Status
    $stmt = $conn->prepare("
        SELECT c.class_name, cl.is_cleared, i.signature 
        FROM clearances cl 
        LEFT JOIN classes c ON cl.class_id = c.class_id 
        LEFT JOIN instructors i ON c.instructor_id = i.instructor_id 
        WHERE cl.student_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $clearances = $stmt->get_result();

    echo "<h2>Your Clearance Status</h2>";
    while ($clearance = $clearances->fetch_assoc()) {
        echo "<p>Class: " . $clearance['class_name'] . " - Status: " . ($clearance['is_cleared'] ? 'Cleared' : 'Not Cleared');
        if ($clearance['is_cleared'] && $clearance['signature']) {
            echo " - <img src='" . $clearance['signature'] . "' alt='Instructor Signature' width='100'>";
        }
        echo "</p>";
    }
}
$conn->close();
?>
