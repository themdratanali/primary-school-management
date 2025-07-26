<?php
include 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['class_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing class_id parameter.'
    ]);
    exit;
}

$class_id = intval($_GET['class_id']);

$stmt = $conn->prepare("SELECT id, name FROM subjects WHERE class_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}

echo json_encode([
    'status' => 'success',
    'count' => count($subjects),
    'subjects' => $subjects
]);

$stmt->close();
