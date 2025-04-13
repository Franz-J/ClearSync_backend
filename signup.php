<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = strtolower(trim($_POST['email'])); // Normalize email
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department = trim($_POST['department']);

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $error = "Error: Email already exists!";
    } else {
        // Generate a unique username
        $username = strtolower($first_name . '.' . $last_name);

        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password, $role);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // Insert into user_profiles table
            $stmt_profile = $conn->prepare("INSERT INTO user_profiles (user_id, first_name, middle_name, last_name) VALUES (?, ?, ?, ?)");
            $stmt_profile->bind_param("isss", $user_id, $first_name, $middle_name, $last_name);
            $stmt_profile->execute();
            $stmt_profile->close();

            if ($role === "student") {
                $student_number = trim($_POST['student_number']);
                $program = trim($_POST['program']);
                $year_level = (int)$_POST['year'];
                $block = trim($_POST['block']);

                $stmt2 = $conn->prepare("INSERT INTO students (user_id, student_number, program, year_level, block) VALUES (?, ?, ?, ?, ?, )");
                $stmt2->bind_param("ississ", $user_id, $student_number, $program, $year_level, $block);

                if (!$stmt2->execute()) {
                    error_log("Error inserting into students table: " . $stmt2->error);
                }
                $stmt2->close();
            } elseif ($role === "instructor") {
                $stmt3 = $conn->prepare("INSERT INTO instructors (user_id, department) VALUES (?, ?)");
                $stmt3->bind_param("is", $user_id, $department);

                if (!$stmt3->execute()) {
                    error_log("Error inserting into instructors table: " . $stmt3->error);
                }
                $stmt3->close();
            }

            header("Location: index.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    $check_stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <script>
        function toggleStudentFields() {
            var role = document.getElementById("role").value;
            var studentFields = document.getElementById("student_fields");

            if (role === "student") {
                studentFields.style.display = "block";
            } else {
                studentFields.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <h2>Sign Up for ClearSync</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    
    <form method="post">
        <label>First Name:</label>
        <input type="text" name="first_name" required><br>

        <label>Middle Name:</label>
        <input type="text" name="middle_name"><br>

        <label>Last Name:</label>
        <input type="text" name="last_name" required><br>

        <label>Email:</label>
        <input type="email" name="email" required><br>

        <label>Password:</label>
        <input type="password" name="password" required><br>

        <label>Role:</label>
        <select name="role" id="role" onchange="toggleStudentFields()">
            <option value="student">Student</option>
            <option value="instructor">Instructor</option>
        </select><br>

        <label>Department:</label>
        <input type="text" name="department" required><br>

        <div id="student_fields">
            <label>Student Number:</label>
            <input type="text" name="student_number"><br>

            <label>Program:</label>
            <input type="text" name="program"><br>

            <label>Year Level:</label>
            <input type="number" name="year" min="1"><br>

            <label>Block:</label>
            <input type="text" name="block"><br>
        </div>

        <button type="submit">Sign Up</button>
    </form>

    <script>
        // Ensure correct state on page load
        toggleStudentFields();
    </script>
</body>
</html>