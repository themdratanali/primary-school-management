<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    echo json_encode(['count' => 0, 'subjects' => []]);
    exit;
}

$class_id = (int)$_GET['class_id'];

$stmt = $conn->prepare("SELECT id, name, code, total_mark FROM subjects WHERE class_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = [
        'id' => $row['id'],
        'name' => htmlspecialchars($row['name'], ENT_QUOTES),
        'code' => htmlspecialchars($row['code'], ENT_QUOTES),
        'total_mark' => (int)$row['total_mark'],
    ];
}

echo json_encode([
    'count' => count($subjects),
    'subjects' => $subjects,
]);
