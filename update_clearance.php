<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $class_id   = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

    if ($student_id === 0 || $class_id === 0) {
        die("❌ Error: Missing student_id or class_id");
    }

    $status = 1; // Use 1 for cleared

    // Debugging - Check if the student and class ID are correctly received
    var_dump($student_id, $class_id);

    // Check if record exists
    $check_sql = "SELECT * FROM clearances WHERE student_id = ? AND class_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $class_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $exists = ($result->num_rows > 0);
    $check_stmt->close();

    if ($exists) {
        // Update clearance status
        $update_sql = "UPDATE clearances SET is_cleared = ?, cleared_at = NOW() WHERE student_id = ? AND class_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iii", $status, $student_id, $class_id);
        if (!$update_stmt->execute()) {
            die("❌ Update Error: " . $update_stmt->error);
        }
        $update_stmt->close();
        echo "✅ Clearance updated!";
    } else {
        // Insert new clearance record
        $insert_sql = "INSERT INTO clearances (student_id, class_id, is_cleared, cleared_at) VALUES (?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $student_id, $class_id, $status);
        if (!$insert_stmt->execute()) {
            die("❌ Insert Error: " . $insert_stmt->error);
        }
        $insert_stmt->close();
        echo "✅ New clearance record created!";
    }

    $conn->close();
}
?>
