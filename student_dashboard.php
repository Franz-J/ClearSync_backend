<?php
// Start output buffering to prevent sending headers early
ob_start();

session_start();

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

include 'db_connection.php';

$user_id = $_SESSION['user_id'];

// Fetch the first name from the user_profiles table
$profile_query = "SELECT first_name FROM user_profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_query);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_stmt->bind_result($first_name);
$profile_stmt->fetch();
$profile_stmt->close();

// Fetch student ID from students table
$student_query = "SELECT student_id FROM students WHERE user_id = ?";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
$student_stmt->bind_result($student_id);
$student_stmt->fetch();
$student_stmt->close();

// Fetch student classes along with clearance status and instructor name
$sql = "SELECT c.class_name, 
               COALESCE(cl.is_cleared, 0) AS is_cleared, 
               CONCAT(up.first_name, ' ', up.last_name) AS instructor_name, 
               i.signature 
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        LEFT JOIN clearances cl ON e.student_id = cl.student_id AND e.class_id = cl.class_id
        LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
        LEFT JOIN user_profiles up ON i.user_id = up.user_id
        WHERE e.student_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($first_name); ?> (Student)</h2>
    <h3>Your Classes & Clearance Status</h3>
    <table border="1">
        <tr>
            <th>Class</th>
            <th>Instructor</th>
            <th>Clearance Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                <td><?php echo htmlspecialchars($row['instructor_name']); ?></td>
                <td>
                    <?php echo ($row['is_cleared'] == 1) ? '<b style="color:green;">Cleared</b>' : '<b style="color:red;">Not Cleared</b>'; ?>
                </td>
            </tr>
        <?php } ?>
    </table>

    <br>
    <a href="download_clearance.php" target="_blank">
        <button>Download Clearance PDF</button>
    </a>

    <br><br>
    <a href="index.php">Logout</a>
</body>
</html>

<?php
// End output buffering and flush any output
ob_end_flush();
?>
