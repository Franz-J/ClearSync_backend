<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

include 'db_connection.php';

$user_id = $_SESSION['user_id'];

// Get the actual instructor_id from the instructors table
$instructor_id_query = "SELECT instructor_id, signature FROM instructors WHERE user_id = ?";
$instructor_stmt = $conn->prepare($instructor_id_query);
$instructor_stmt->bind_param("i", $user_id);
$instructor_stmt->execute();
$instructor_stmt->bind_result($instructor_id, $signature);
$instructor_stmt->fetch();
$instructor_stmt->close();

if (!$instructor_id) {
    echo "<p style='color:red;'>No instructor record found for this user. Please check your database.</p>";
    exit();
}

// Get first name for greeting
$profile_query = "SELECT first_name FROM user_profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_query);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_stmt->bind_result($first_name);
$profile_stmt->fetch();
$profile_stmt->close();

// Fetch instructor's classes
$sql = "SELECT c.class_id, c.class_name, COUNT(e.student_id) AS student_count
        FROM classes c
        LEFT JOIN enrollments e ON c.class_id = e.class_id
        WHERE c.instructor_id = ?
        GROUP BY c.class_id, c.class_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

// Get available programs, years, and blocks for filtering
$programs_query = "SELECT DISTINCT program FROM students ORDER BY program";
$programs_result = $conn->query($programs_query);

$years_query = "SELECT DISTINCT year_level FROM students ORDER BY year_level"; // Fixed column name
$years_result = $conn->query($years_query);

$blocks_query = "SELECT DISTINCT block FROM students ORDER BY block";
$blocks_result = $conn->query($blocks_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        button {
            padding: 8px 12px;
            margin-bottom: 5px;
        }
    </style>
    <script>
        // Update student list dynamically
        function updateStudentList() {
            var program = document.getElementById("program").value;
            var year = document.getElementById("year").value;
            var block = document.getElementById("block").value;
            var search = document.getElementById("search").value;

            var xhr = new XMLHttpRequest();
            xhr.open("GET", "fetch_students.php?program=" + program + "&year=" + year + "&block=" + block + "&search=" + search, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4) {
                    document.getElementById("studentTableBody").innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        // Function to toggle all checkboxes
        function toggleSelectAll() {
            var checkboxes = document.querySelectorAll("#studentTableBody input[type='checkbox']");
            var allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => cb.checked = !allChecked);
            document.getElementById("selectAllBtn").textContent = allChecked ? "Select All" : "Deselect All";
        }

        // Ensure the student list is updated on page load
        document.addEventListener("DOMContentLoaded", function () {
            updateStudentList();
            document.getElementById("selectAllBtn").addEventListener("click", toggleSelectAll);
        });
    </script>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($first_name); ?> (Instructor)</h2>

    <h3>Your Classes</h3>
    <table border="1">
    <tr>
        <th>Class Name</th>
        <th>Student Count</th>
        <th>Actions</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                <td><?php echo $row['student_count']; ?></td>
                <td><a href="student_list.php?class_id=<?php echo $row['class_id']; ?>">View Students</a></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="3">No classes found.</td>
        </tr>
    <?php endif; ?>
</table>


    <h3>Create a New Class</h3>
    <form action="create_class.php" method="POST">
        <label for="class_name">Class Name:</label>
        <input type="text" name="class_name" required>

        <h3>Filter Students</h3>
        <label for="search">Search:</label>
        <input type="text" id="search" onkeyup="updateStudentList()" placeholder="Search by name...">

        <label for="program">Program:</label>
        <select id="program" name="program" onchange="updateStudentList()">
            <option value="">All</option>
            <?php while ($prog = $programs_result->fetch_assoc()) { ?>
                <option value="<?php echo $prog['program']; ?>"><?php echo $prog['program']; ?></option>
            <?php } ?>
        </select>

        <label for="year">Year Level:</label>
        <select id="year" name="year" onchange="updateStudentList()">
            <option value="">All</option>
            <?php while ($yr = $years_result->fetch_assoc()) { ?>
                <option value="<?php echo $yr['year_level']; ?>"><?php echo $yr['year_level']; ?><?php echo ($yr['year_level'] == 1) ? "st" : (($yr['year_level'] == 2) ? "nd" : (($yr['year_level'] == 3) ? "rd" : "th")); ?> Year</option>
            <?php } ?>
        </select>

        <label for="block">Block:</label>
        <select id="block" name="block" onchange="updateStudentList()">
            <option value="">All</option>
            <?php while ($bl = $blocks_result->fetch_assoc()) { ?>
                <option value="<?php echo $bl['block']; ?>"><?php echo $bl['block']; ?></option>
            <?php } ?>
        </select>

        <h4>Select Students:</h4>
        <button type="button" id="selectAllBtn">Select All</button>

        <table border="1">
            <tr>
                <th>Select</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Program</th>
                <th>Year Level</th>
                <th>Block</th>
            </tr>
            <tbody id="studentTableBody">
                <!-- Students will be loaded dynamically via AJAX -->
            </tbody>
        </table>

        <input type="hidden" name="instructor_id" value="<?php echo $instructor_id; ?>">
        <button type="submit">Create Class</button>
    </form>

    <h3>Upload Your Signature</h3>
    <form action="upload_signature.php" method="POST" enctype="multipart/form-data">
        <label for="signature">Upload Signature (PNG only):</label>
        <input type="file" name="signature" id="signature" accept="image/png" required>
        <button type="submit">Upload</button>
    </form>

    <?php if (!empty($signature)): ?>
        <h4>Current Signature:</h4>
        <img src="<?php echo htmlspecialchars($signature); ?>" alt="Instructor Signature" width="200">
    <?php endif; ?>

    <br>
    <a href="index.php">Logout</a>
</body>
</html>