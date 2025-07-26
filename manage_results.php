<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$conn->set_charset("utf8mb4");

$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

$message = '';
$selected_batch_id = '';
$selected_class_id = '';
$selected_student_id = '';
$batch_year = '';
$result_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_year'], $_POST['student_id'], $_POST['class_id'], $_POST['marks'], $_POST['result_type'])) {
    $batch_year = $_POST['batch_year'];
    $selected_batch_id = $_POST['batch_id'] ?? '';
    $selected_class_id = intval($_POST['class_id']);
    $selected_student_id = intval($_POST['student_id']);
    $marksArr = $_POST['marks'];
    $result_type = $_POST['result_type'];

    if (!preg_match('/^\d{4}$/', $batch_year)) {
        die('Invalid batch year.');
    }

    $class_name_res = $conn->query("SELECT name FROM classes WHERE id = $selected_class_id");
    if ($class_name_res && ($row = $class_name_res->fetch_assoc()) && !empty($row['name'])) {
        $class_name = strtolower(str_replace(' ', '_', $row['name']));
    } else {
        die('Class not found.');
    }

    $result_type_clean = strtolower(str_replace(' ', '_', $result_type));
    $table_name = "results_{$batch_year}_{$class_name}_{$result_type_clean}";

    $table_exists_res = $conn->query("SHOW TABLES LIKE '$table_name'");
    $table_exists = $table_exists_res && $table_exists_res->num_rows > 0;

    if (!$table_exists) {
        $create_table_sql = "CREATE TABLE `$table_name` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `class_id` INT NOT NULL,
            `subject_id` INT NOT NULL,
            `marks` INT NOT NULL,
            `exam_type` VARCHAR(50) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if (!$conn->query($create_table_sql)) {
            die("Error creating table: " . $conn->error);
        }
    }

    $delete_stmt = $conn->prepare("DELETE FROM `$table_name` WHERE student_id = ? AND class_id = ? AND exam_type = ?");
    $delete_stmt->bind_param("iis", $selected_student_id, $selected_class_id, $result_type);
    $delete_stmt->execute();
    $delete_stmt->close();

    $stmt = $conn->prepare("INSERT INTO `$table_name` (student_id, class_id, subject_id, marks, exam_type) VALUES (?, ?, ?, ?, ?)");
    $errors = [];
    foreach ($marksArr as $subject_id => $mark) {
        $subject_id = intval($subject_id);
        $mark = intval($mark);
        if (!$stmt->bind_param("iiiis", $selected_student_id, $selected_class_id, $subject_id, $mark, $result_type)) {
            $errors[] = "Bind param failed: " . $stmt->error;
            continue;
        }
        if (!$stmt->execute()) {
            $errors[] = "Insert failed: " . $stmt->error;
        }
    }
    $stmt->close();

    if (count($errors) === 0) {
        $subjectsResult = $conn->query("SELECT id, name FROM subjects WHERE class_id = $selected_class_id ORDER BY id ASC");
        $subjectNames = [];
        $subjectIds = [];
        while ($row = $subjectsResult->fetch_assoc()) {
            $subjectIds[] = $row['id'];
            $subjectNames[] = $row['name'];
        }

        $student_roll_res = $conn->query("SELECT roll FROM students WHERE id = $selected_student_id");
        $student_roll = ($student_roll_res && ($roll_row = $student_roll_res->fetch_assoc()) && !empty($roll_row['roll'])) ? $roll_row['roll'] : 'N/A';

        $rowData = [$student_roll, $class_name];
        foreach ($subjectIds as $sid) {
            $rowData[] = $marksArr[$sid] ?? '';
        }
        $rowData[] = $result_type;
        $rowData[] = date('Y-m-d H:i:s');

        $excelFolder = 'uploads/excel/';
        if (!is_dir($excelFolder)) {
            mkdir($excelFolder, 0777, true);
        }
        $excelFile = $excelFolder . $table_name . '.xlsx';

        if (file_exists($excelFile)) {
            $spreadsheet = IOFactory::load($excelFile);
            $sheet = $spreadsheet->getActiveSheet();
            $row = $sheet->getHighestRow() + 1;
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $headers = ['Student Roll', 'Class Name'];
            foreach ($subjectNames as $subjName) {
                $headers[] = $subjName;
            }
            $headers[] = 'Exam Type';
            $headers[] = 'Created At';
            $sheet->fromArray($headers, NULL, 'A1');
            $row = 2;
        }

        $sheet->fromArray($rowData, NULL, 'A' . $row);
        $writer = new Xlsx($spreadsheet);
        $writer->save($excelFile);

        $message = "✅ Results saved and Excel exported successfully.";
    } else {
        $message = "⚠️ Some errors occurred:<br>" . implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Results</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="assets/css/manage_results.css">
</head>

<body>
    <form method="post">
        <div class="container">
            <div class="left">
                <h2>Selection</h2>
                <?php if ($message): ?>
                    <div class="message"><?= $message ?></div>
                <?php endif; ?>

                <label>Batch:</label>
                <select name="batch_id" id="batch_id" required>
                    <option value="">Select Batch</option>
                    <?php while ($b = $batches->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>" <?= $b['id'] == $selected_batch_id ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Class:</label>
                <select name="class_id" id="class_id" required>
                    <option value="">Select Class</option>
                    <?php $classes->data_seek(0);
                    while ($c = $classes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selected_class_id ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Student:</label>
                <select name="student_id" id="student_id" required>
                    <option value="">Select Student</option>
                </select>

                <label>Result Type:</label>
                <select name="result_type" id="result_type" required>
                    <option value="Final" <?= $result_type == 'Final' ? 'selected' : '' ?>>Final</option>
                    <option value="Half Yearly" <?= $result_type == 'Half Yearly' ? 'selected' : '' ?>>Half Yearly</option>
                </select>

                <input type="hidden" name="batch_year" id="batch_year" value="<?= htmlspecialchars($batch_year) ?>">
            </div>

            <div class="right">
                <h2>Enter Marks</h2>
                <div id="subjectTableArea"></div>
                <button type="submit" id="submitBtn" style="display:none;">Submit Results</button>
            </div>
        </div>
    </form>

    <script>
        $(document).ready(function() {
            function loadStudents(batchId, classId) {
                if (!batchId || !classId) {
                    $('#student_id').html('<option value="">Select Student</option>');
                    return;
                }
                $.post('get_students_by_batch_class.php', {
                    batch_id: batchId,
                    class_id: classId
                }, function(data) {
                    $('#student_id').html(data);
                });
            }

            function loadSubjects(classId, studentId, batchYear, resultType) {
                if (!classId || !studentId || !batchYear || !resultType) {
                    $('#subjectTableArea').html('');
                    $('#submitBtn').hide();
                    return;
                }
                $.post('get_subjects_for_class.php', {
                    class_id: classId,
                    student_id: studentId,
                    batch_year: batchYear,
                    result_type: resultType
                }, function(data) {
                    $('#subjectTableArea').html(data);
                    $('#submitBtn').show();
                });
            }

            $('#batch_id, #class_id').on('change', function() {
                let batchId = $('#batch_id').val();
                let classId = $('#class_id').val();
                $('#student_id').html('<option value="">Select Student</option>');
                $('#subjectTableArea').html('');
                $('#submitBtn').hide();
                if (batchId && classId) {
                    $.post('get_batch_year.php', {
                        batch_id: batchId
                    }, function(data) {
                        if (data && data.year) {
                            $('#batch_year').val(data.year);
                            loadStudents(batchId, classId);
                        }
                    }, 'json');
                }
            });

            $('#student_id, #result_type').on('change', function() {
                let batchYear = $('#batch_year').val();
                let classId = $('#class_id').val();
                let studentId = $('#student_id').val();
                let resultType = $('#result_type').val();
                loadSubjects(classId, studentId, batchYear, resultType);
            });
        });
    </script>
</body>

</html>