<?php
include 'db.php';

if (!isset($_POST['batch_id'])) {
    echo '';
    exit;
}

$batch_id = intval($_POST['batch_id']);

if ($batch_id <= 0) {
    echo '';
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM classes WHERE batch_id = ? ORDER BY name");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$result = $stmt->get_result();

$options = '';
while ($row = $result->fetch_assoc()) {
    $options .= '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
}

echo $options;

$stmt->close();
$conn->close();
