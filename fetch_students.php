<?php
include 'db_connection.php';

// Get filter values
$selected_program = $_GET['program'] ?? '';
$selected_year = $_GET['year'] ?? '';
$selected_block = $_GET['block'] ?? '';
$search_query = $_GET['search'] ?? ''; // Get the search query

// Updated query to join with user_profiles and fetch individual name columns
$students_query = "
    SELECT s.student_id, 
           up.first_name, 
           up.middle_name, 
           up.last_name, 
           s.program, 
           s.year_level, 
           s.block 
    FROM students s
    JOIN user_profiles up ON s.user_id = up.user_id
    WHERE 1=1
";

if ($selected_program) {
    $students_query .= " AND s.program = '" . $conn->real_escape_string($selected_program) . "'";
}
if ($selected_year) {
    $students_query .= " AND s.year_level = " . (int)$selected_year;
}
if ($selected_block) {
    $students_query .= " AND s.block = '" . $conn->real_escape_string($selected_block) . "'";
}
if ($search_query) {
    $search_query = $conn->real_escape_string($search_query);
    $students_query .= " AND (up.first_name LIKE '%$search_query%' 
                              OR up.middle_name LIKE '%$search_query%' 
                              OR up.last_name LIKE '%$search_query%')";
}

$students_query .= " ORDER BY s.program, s.year_level, s.block";
$students_result = $conn->query($students_query);

while ($student = $students_result->fetch_assoc()) {
    $year_level = $student['year_level'];
    $suffix = ($year_level == 1) ? "st" : (($year_level == 2) ? "nd" : (($year_level == 3) ? "rd" : "th"));
    echo "<tr>
            <td><input type='checkbox' name='students[]' value='{$student['student_id']}'></td>
            <td>" . htmlspecialchars($student['first_name']) . "</td>
            <td>" . htmlspecialchars($student['middle_name']) . "</td>
            <td>" . htmlspecialchars($student['last_name']) . "</td>
            <td>" . htmlspecialchars($student['program']) . "</td>
            <td>{$year_level}{$suffix} Year</td>
            <td>" . htmlspecialchars($student['block']) . "</td>
          </tr>";
}
?>