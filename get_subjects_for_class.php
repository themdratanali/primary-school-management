<?php
include 'db.php';

if (!isset($_POST['class_id'], $_POST['student_id'], $_POST['batch_year'], $_POST['result_type'])) {
    echo 'Invalid parameters';
    exit;
}

$class_id = intval($_POST['class_id']);
$student_id = intval($_POST['student_id']);
$batch_year = $_POST['batch_year'];
$result_type = trim($_POST['result_type']);

if (!preg_match('/^\d{4}$/', $batch_year)) {
    echo 'Invalid batch year';
    exit;
}

$allowed_types = ['Final', 'Half Yearly'];
if (!in_array($result_type, $allowed_types)) {
    echo 'Invalid result type';
    exit;
}

$table_name = "results_" . $batch_year . "_" . $class_id . "_" . strtolower(str_replace(' ', '_', $result_type));

$stmt = $conn->prepare("SELECT id, name FROM subjects WHERE class_id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No subjects found for this class.</p>";
    exit;
}

$marks = [];
$table_exists = $conn->query("SHOW TABLES LIKE '$table_name'")->num_rows > 0;
if ($table_exists) {
    $stmt2 = $conn->prepare("SELECT subject_id, marks FROM `$table_name` WHERE student_id = ? AND class_id = ? AND exam_type = ?");
    $stmt2->bind_param("iis", $student_id, $class_id, $result_type);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $marks[$row['subject_id']] = $row['marks'];
    }
    $stmt2->close();
}

echo '<table>';
echo '<thead><tr><th>Subject</th><th>Marks</th></tr></thead><tbody>';

while ($row = $result->fetch_assoc()) {
    $subject_id = $row['id'];
    $subject_name = htmlspecialchars($row['name']);
    $mark_val = isset($marks[$subject_id]) ? $marks[$subject_id] : '';
    echo '<tr>';
    echo '<td>' . $subject_name . '</td>';
    echo '<td><input type="number" min="0" max="100" name="marks[' . $subject_id . ']" value="' . htmlspecialchars($mark_val) . '" required></td>';
    echo '</tr>';
}
echo '</tbody></table>';

$stmt->close();
?>
