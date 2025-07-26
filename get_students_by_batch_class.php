<?php
include 'db.php';

if (!isset($_POST['batch_id'], $_POST['class_id'])) {
    echo '<option value="">Invalid parameters</option>';
    exit;
}

$batch_id = intval($_POST['batch_id']);
$class_id = intval($_POST['class_id']);

$batch_name_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
$class_name_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");

$student_table = "students";

if ($batch_name_res && $class_name_res) {
    $batch_name = strtolower(str_replace(' ', '_', $batch_name_res->fetch_assoc()['name']));
    $class_name = strtolower(str_replace(' ', '_', $class_name_res->fetch_assoc()['name']));
    $possible_table = "Student_" . $batch_name . "_" . $class_name;

    $check_table = $conn->query("SHOW TABLES LIKE '$possible_table'");
    if ($check_table && $check_table->num_rows > 0) {
        $student_table = $possible_table;
    }
}

$stmt = $conn->prepare("SELECT id, name FROM $student_table WHERE batch_id = ? AND class_id = ? ORDER BY name ASC");
if (!$stmt) {
    echo '<option value="">Error: ' . htmlspecialchars($conn->error) . '</option>';
    exit;
}
$stmt->bind_param("ii", $batch_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

echo '<option value="">Select Student</option>';
while ($row = $result->fetch_assoc()) {
    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
}

$stmt->close();
