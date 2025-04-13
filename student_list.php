<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

include 'db_connection.php';

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    die("⚠️ No class ID provided.");
}

$class_id = (int) $_GET['class_id'];

// Fetch class name
$class_sql = "SELECT class_name FROM classes WHERE class_id = ?";
$class_stmt = $conn->prepare($class_sql);
$class_stmt->bind_param("i", $class_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class_name = "Unknown Class";
if ($class_row = $class_result->fetch_assoc()) {
    $class_name = $class_row['class_name'];
}
$class_stmt->close();

// Fetch enrolled students using enrollments (and clearance, if any)
$sql = "
    SELECT s.student_id, s.program, s.year_level, s.block, 
           COALESCE(c.is_cleared, 0) AS clearance_status 
    FROM students s  
    JOIN enrollments e ON s.student_id = e.student_id  
    LEFT JOIN clearances c ON s.student_id = c.student_id AND e.class_id = c.class_id
    WHERE e.class_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

// Debug: Uncomment the following line to check number of rows returned
// var_dump($result->num_rows);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student List - <?php echo htmlspecialchars($class_name); ?></title>
  <script>
    // This function sends an AJAX request to update the clearance status for one student
    function updateClearance(studentId, checkbox) {
      var xhr = new XMLHttpRequest();
      xhr.open("POST", "update_clearance.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      var status = checkbox.checked ? "Cleared" : "Not Cleared";
      var params = "student_id=" + encodeURIComponent(studentId) +
                   "&class_id=" + encodeURIComponent(<?php echo $class_id; ?>) +
                   "&status=" + encodeURIComponent(status);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          // Optionally, update the status cell in the table
          document.getElementById("status-" + studentId).textContent = status;
          console.log("Clearance updated for student " + studentId + " to " + status);
        }
      };
      xhr.send(params);
    }
  </script>
</head>
<body>
  <h2>Students in <?php echo htmlspecialchars($class_name); ?></h2>
  <?php if ($result->num_rows > 0) { ?>
    <form action="update_clearance.php" method="POST">
        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
        <table border="1">
            <tr>
                <th>Name</th>
                <th>Program</th>
                <th>Year</th>
                <th>Block</th>
                <th>Clearance Status</th>
                <th>Mark Cleared</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['program']); ?></td>
                    <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                    <td><?php echo htmlspecialchars($row['block']); ?></td>
                    <td><?php echo htmlspecialchars($row['clearance_status']); ?></td>
                    <td>
                        <button type="submit" name="student_id" value="<?php echo $row['student_id']; ?>">
                            Clear
                        </button>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </form>

  <?php } else { ?>
    <p>⚠️ No students found in this class.</p>
  <?php } ?>

  <br>
  <a href="instructor_dashboard.php">Back to Dashboard</a>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
