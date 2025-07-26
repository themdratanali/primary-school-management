<?php
include 'db.php';

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$paid_months = [];

if ($student_id > 0 && $batch_id > 0 && $class_id > 0) {
    $batch_stmt = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $batch_stmt->bind_param("i", $batch_id);
    $batch_stmt->execute();
    $batch_stmt->bind_result($batch_name);
    $batch_stmt->fetch();
    $batch_stmt->close();

    $class_stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $class_stmt->bind_param("i", $class_id);
    $class_stmt->execute();
    $class_stmt->bind_result($class_name);
    $class_stmt->fetch();
    $class_stmt->close();

    function sanitize_table_part($str)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '_', trim($str));
    }

    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    $table_name = "fees_{$batch_clean}_{$class_clean}_monthly";

    $check = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("SELECT fee_type_detail FROM `$table_name` WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $paid_months[] = $row['fee_type_detail'];
        }
        $stmt->close();
    }
}

$unpaid_months = array_values(array_diff($months, $paid_months));

header('Content-Type: application/json');
echo json_encode($unpaid_months);
