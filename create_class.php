<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_name = trim($_POST['class_name']);
    $user_id = $_POST['instructor_id']; // This is actually the `user_id` of the instructor

    // Fetch the correct `instructor_id` from the `instructors` table
    $stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($instructor_id);
    $stmt->fetch();
    $stmt->close();

    if (!$instructor_id) {
        die("Error: Instructor record not found.");
    }

    // Ensure students are selected
    if (!isset($_POST['students']) || !is_array($_POST['students']) || count($_POST['students']) === 0) {
        die("Error: No students selected!");
    }
    $students = $_POST['students'];

    // Generate a unique class code
    $class_code = strtoupper(substr(md5(uniqid($class_name, true)), 0, 8)); // Example: 8-character unique code

    // Insert the new class
    $stmt = $conn->prepare("INSERT INTO classes (class_name, class_code, instructor_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $class_name, $class_code, $instructor_id);

    if ($stmt->execute()) {
        $class_id = $stmt->insert_id;

        // Prepare the enrollment statement
        $enrollment_stmt = $conn->prepare("INSERT INTO enrollments (student_id, class_id) VALUES (?, ?)");

        // Enroll each selected student
        foreach ($students as $student_id) {
            $enrollment_stmt->bind_param("ii", $student_id, $class_id);
            $enrollment_stmt->execute();
        }

        $enrollment_stmt->close();
    } else {
        die("Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

    header("Location: instructor_dashboard.php");
    exit();
}
?>