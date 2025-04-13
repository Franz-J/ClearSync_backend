<?php
// Database connection setup - local
//$servername = "localhost";
//$username = "root";
//$password = "";
//$dbname = "clearsync_db_v2"; // Updated to use clearsync_db_v2

//Database connection - host
$servername = "sql204.infinityfree.com";
$username = "if0_38701457";
$password = "f82W229j";
$dbname = "if0_38701457_clearsync_db";
           

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Function to register a user
function registerUser($conn, $name, $email, $password, $role) {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
    return $stmt->execute();
}

// Function to login a user
function loginUser($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

// Function to get user profile
function getUserProfile($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function for instructor to upload signature
function uploadSignature($conn, $instructorId, $signaturePath) {
    $stmt = $conn->prepare("INSERT INTO signatures (instructor_id, signature_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE signature_path = VALUES(signature_path)");
    $stmt->bind_param("is", $instructorId, $signaturePath);
    return $stmt->execute();
}

// Function to get instructor signature
function getInstructorSignature($conn, $instructorId) {
    $stmt = $conn->prepare("SELECT signature_path FROM signatures WHERE instructor_id = ?");
    $stmt->bind_param("i", $instructorId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to clear student clearance
function clearStudent($conn, $studentId, $classId) {
    $stmt = $conn->prepare("UPDATE clearance SET status = 'cleared' WHERE student_id = ? AND class_id = ?");
    $stmt->bind_param("ii", $studentId, $classId);
    return $stmt->execute();
}

// Function to get student clearance status
function getClearanceStatus($conn, $studentId) {
    $stmt = $conn->prepare("SELECT c.class_id, c.status, s.signature_path FROM clearance c LEFT JOIN classes cl ON c.class_id = cl.id LEFT JOIN signatures s ON cl.instructor_id = s.instructor_id WHERE c.student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>
