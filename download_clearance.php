<?php
session_start();
require_once __DIR__ . '/fpdf/fpdf.php';
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student details
$sql = "SELECT CONCAT(up.last_name, ', ', up.first_name, ' ', COALESCE(up.middle_name, '')) AS student_name, 
               s.student_id, s.student_number, s.program, s.year_level, s.block, s.department 
        FROM students s
        JOIN user_profiles up ON s.user_id = up.user_id
        WHERE s.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("❌ Error: Student record not found.");
}

$student_id = $student['student_id'];
$student_name = $student['student_name'];
$student_number = $student['student_number'];
$program = $student['program'];
$year_level = $student['year_level'];
$block = $student['block'];
$department = $student['department'];
$year_suffix = ($year_level == 1) ? "st" : (($year_level == 2) ? "nd" : (($year_level == 3) ? "rd" : "th"));
$formatted_year = "{$year_level}{$year_suffix} Year, Block {$block}";

// Fetch enrolled classes and clearance status
$sql = "SELECT c.class_name, 
               COALESCE(cl.is_cleared, 0) AS is_cleared, 
               CONCAT(up.first_name, ' ', up.last_name) AS instructor_name, 
               i.signature 
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        LEFT JOIN clearances cl ON e.student_id = cl.student_id AND c.class_id = cl.class_id
        LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
        LEFT JOIN user_profiles up ON i.user_id = up.user_id
        WHERE e.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    die("❌ Error: No clearance records found for this student.");
}

// ✅ Generate PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Add logos
$logo1 = __DIR__ . '/logos/1.png'; // Replace with the actual path to logo 1
$logo2 = __DIR__ . '/logos/2.png'; // Replace with the actual path to logo 2
$pdf->Image($logo1, 10, 10, 30, 30); // Left logo
$pdf->Image($logo2, 170, 10, 30, 30); // Right logo with same size as logo 1

// College Header
$pdf->Cell(190, 10, "College of ClearSync Studies", 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(190, 8, "42 Sync Avenue, Cloud City, Cyberstate 2025", 0, 1, 'C');
$pdf->Ln(4);

// Report Title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(190, 10, "Student Clearance Report", 0, 1, 'C');
$pdf->Ln(6);

// Student Info Section
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 8, "Student Name:", 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(60, 8, $student_name, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 8, "Student Number:", 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, $student_number, 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 8, "Program:", 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(60, 8, $program, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 8, "Year & Block:", 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, $formatted_year, 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 8, "Department:", 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(60, 8, $department, 0, 1);
$pdf->SetDrawColor(0, 0, 0); // black line
$pdf->SetLineWidth(0.7);     // thickness of the line
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY()); // (x1, y1, x2, y2)

// Table Header
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetDrawColor(0, 0, 0); // Black border
$pdf->SetLineWidth(0.6); // Thick bottom border for the header
$pdf->Cell(70, 8, "Class", 'B', 0, 'C'); // Centered header
$pdf->Cell(40, 8, "Clearance Status", 'B', 0, 'C'); // Centered header
$pdf->Cell(40, 8, "Instructor", 'B', 0, 'C'); // Centered header
$pdf->Cell(40, 8, "Signature", 'B', 1, 'C'); // Centered header

// Table Rows
$pdf->SetFont('Arial', '', 10);
$pdf->SetLineWidth(0.1); // Thin border for rows

while ($row = $result->fetch_assoc()) {
    // Prepare values
    $class_name = $row['class_name'];
    $instructor_name = $row['instructor_name'];
    $status_text = $row['is_cleared'] ? "Cleared" : "Not Cleared";
    $signature_path = $row['signature'];
    $has_signature = $row['is_cleared'] && !empty($signature_path) && file_exists($signature_path);

    // Estimate line height (assumes 8pt per line)
    $lineHeight = 8;

    // Calculate the number of lines for each column
    $class_lines = $pdf->GetStringWidth($class_name) / 70 > 1 ? ceil($pdf->GetStringWidth($class_name) / 70) : 1;
    $instructor_lines = $pdf->GetStringWidth($instructor_name) / 40 > 1 ? ceil($pdf->GetStringWidth($instructor_name) / 40) : 1;

    // Determine the maximum number of lines in the row
    $max_lines = max($class_lines, $instructor_lines, 1); // 1 for single-line columns like "Clearance Status"
    $row_height = $lineHeight * $max_lines;

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Class Name
    $pdf->Cell(70, $row_height, $class_name, 'B', 0, 'C'); // Centered and vertically aligned

    // Clearance Status
    $pdf->Cell(40, $row_height, $status_text, 'B', 0, 'C'); // Centered Clearance Status

    // Instructor Name
    $pdf->Cell(40, $row_height, $instructor_name, 'B', 0, 'C'); // Centered Instructor Name

    // Signature
    if ($has_signature) {
        $pdf->Cell(40, $row_height, '', 'B'); // Placeholder for image bounds

        // Center image in the cell
        $imageWidth = 50;
        $imageHeight = 20;
        $centerX = $x + 150 + (40 - $imageWidth) / 2;
        $centerY = $y + ($row_height - $imageHeight) / 2;
        $pdf->Image($signature_path, $centerX, $centerY, $imageWidth, $imageHeight);
    } else {
        $pdf->Cell(40, $row_height, "-------", 'B', 0, 'C'); // Centered placeholder for no signature
    }

    // Move to the next row
    $pdf->Ln($row_height);
}

// Add footer
$pdf->SetY(-32.5); // Position the footer 15mm from the bottom
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, "Generated on " . date("Y-m-d H:i:s") . " | ClearSync System", 0, 0, 'C');

// Output PDF
$pdf->Output('D', "Clearance_Report_$student_name.pdf");
exit();
?>
