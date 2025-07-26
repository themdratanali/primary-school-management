<?php
include 'db.php';

if (isset($_POST['batch_id'], $_POST['class_id'])) {
    $batch_id = (int) $_POST['batch_id'];
    $class_id = (int) $_POST['class_id'];

    $batch_stmt = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $batch_stmt->bind_param("i", $batch_id);
    $batch_stmt->execute();
    $batch_result = $batch_stmt->get_result();
    $batch_row = $batch_result->fetch_assoc();
    $batch_stmt->close();

    $class_stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $class_stmt->bind_param("i", $class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    $class_row = $class_result->fetch_assoc();
    $class_stmt->close();

    if ($batch_row && $class_row) {
        $batch_name = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $batch_row['name']));
        $class_name = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $class_row['name']));
        $table_name = "Student_{$batch_name}_{$class_name}";

        $check = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check && $check->num_rows > 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `$table_name`");
            $stmt->execute();
            $stmt->bind_result($total);
            $stmt->fetch();
            $stmt->close();

            echo $total + 1;
        } else {
            echo 1;
        }
    } else {
        echo 1;
    }
}
