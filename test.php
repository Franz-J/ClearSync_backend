<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "sql204.infinityfree.com";
$username = "if0_38701457";
$password = "f82W229j";
$dbname = "if0_38701457_clearsync_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "✅ Connected successfully!";
?>
