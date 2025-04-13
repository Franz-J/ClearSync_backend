<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["signature"])) {
    $uploadDir = "uploads/signatures/";

    // Create the directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmpPath = $_FILES["signature"]["tmp_name"];
    $fileName = "signature_" . $_SESSION['user_id'] . ".png"; // Unique filename
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmpPath, $filePath)) {
        // Save the file path in the database
        $sql = "UPDATE instructors SET signature = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $filePath, $_SESSION['user_id']);
        if ($stmt->execute()) {
            echo "✅ Signature uploaded successfully!";
        } else {
            echo "❌ Error saving signature to the database.";
        }
        $stmt->close();
    } else {
        echo "❌ Error uploading file.";
    }
}
?>
