<?php
include 'db.php';

header('Content-Type: application/json');

if (!isset($_POST['batch_id'])) {
    echo json_encode(['error' => 'Batch ID missing']);
    exit;
}

$batch_id = intval($_POST['batch_id']);

$stmt = $conn->prepare("SELECT name FROM batches WHERE id = ?");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$stmt->bind_result($batch_name);

if ($stmt->fetch()) {
    preg_match('/\d{4}/', $batch_name, $matches);
    $year = $matches[0] ?? null;

    if ($year) {
        echo json_encode(['year' => $year]);
    } else {
        echo json_encode(['error' => 'Year not found in batch name']);
    }
} else {
    echo json_encode(['error' => 'Batch not found']);
}

$stmt->close();
