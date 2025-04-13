<?php
ob_start(); // Start output buffering
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if database connection is valid
    if (!$conn || $conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE email = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = strtolower($user['role']); // Normalize role to lowercase
            $_SESSION['username'] = $user['username'];

            // Debugging: Check session values
            // Uncomment the following line to debug session variables
            // var_dump($_SESSION); exit();

            // Redirect based on role
            if ($_SESSION['role'] === 'instructor') {
                header("Location: instructor_dashboard.php");
                exit(); // Ensure no further code runs after redirection
            } elseif ($_SESSION['role'] === 'student') {
                header("Location: student_dashboard.php");
                exit(); // Ensure no further code runs after redirection
            } else {
                $error = "Invalid role.";
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }

    $stmt->close();
}
ob_end_flush(); // Flush output buffer

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login to ClearSync</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
        <label>Email:</label>
        <input type="email" name="email" required>
        <br>
        <label>Password:</label>
        <input type="password" name="password" required>
        <br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
</body>
</html>
