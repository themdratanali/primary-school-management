<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$conn->set_charset("utf8mb4");

$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'Final';

$exam_title = ($exam_type === 'Half Yearly') ? 'Half Yearly' : 'Final';
$watermark_text = strtoupper($exam_title);

$students = [];

function sanitize_table_part($str)
{
    return preg_replace('/[^a-zA-Z0-9]/', '_', trim($str));
}

if ($batch_id && $class_id) {
    $stmt_batch = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $stmt_batch->bind_param("i", $batch_id);
    $stmt_batch->execute();
    $stmt_batch->bind_result($batch_name);
    $stmt_batch->fetch();
    $stmt_batch->close();
    $stmt_class = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt_class->bind_param("i", $class_id);
    $stmt_class->execute();
    $stmt_class->bind_result($class_name);
    $stmt_class->fetch();
    $stmt_class->close();

    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);

    $student_table = "Student_" . $batch_clean . "_" . $class_clean;

    $table_exists = $conn->query("SHOW TABLES LIKE '$student_table'")->num_rows > 0;

    if (!$table_exists) {
        echo "<p style='color:red; font-weight:bold;'>Student table '$student_table' does not exist.</p>";
    } else {
        if (!$stmt = $conn->prepare("SELECT id, name FROM `$student_table` ORDER BY name ASC")) {
            echo "<p style='color:red;'>Prepare failed: (" . $conn->errno . ") " . $conn->error . "</p>";
        } else {
            $stmt->execute();
            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

$student = null;
$results = [];

if ($student_id > 0 && $batch_id > 0 && $class_id > 0) {
    if (empty($batch_name) || empty($class_name)) {
        $stmt_batch = $conn->prepare("SELECT name FROM batches WHERE id = ?");
        $stmt_batch->bind_param("i", $batch_id);
        $stmt_batch->execute();
        $stmt_batch->bind_result($batch_name);
        $stmt_batch->fetch();
        $stmt_batch->close();

        $stmt_class = $conn->prepare("SELECT name FROM classes WHERE id = ?");
        $stmt_class->bind_param("i", $class_id);
        $stmt_class->execute();
        $stmt_class->bind_result($class_name);
        $stmt_class->fetch();
        $stmt_class->close();
    }

    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);

    $student_table = "Student_" . $batch_clean . "_" . $class_clean;

    $table_exists = $conn->query("SHOW TABLES LIKE '$student_table'")->num_rows > 0;

    if (!$table_exists) {
        echo "<p style='color:red; font-weight:bold;'>Student table '$student_table' does not exist.</p>";
    } else {
        $stmt_student = $conn->prepare("SELECT * FROM `$student_table` WHERE id = ?");
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $student = $stmt_student->get_result()->fetch_assoc();
        $stmt_student->close();

        if ($student) {
            $student['batch_name'] = $batch_name;
            $student['class_name'] = $class_name;
            $year = date('Y');
            if (preg_match('/\d{4}/', $batch_name, $matches)) {
                $year = $matches[0];
            }

            $table_name = "results_" . $year . "_" . $class_clean . "_" . strtolower(str_replace(' ', '_', $exam_type));
            $table_exists = $conn->query("SHOW TABLES LIKE '$table_name'")->num_rows > 0;

            if ($table_exists) {
                $results = $conn->query("
                    SELECT sub.code, sub.name, sub.total_mark, res.marks
                    FROM `$table_name` res
                    JOIN subjects sub ON res.subject_id = sub.id
                    WHERE res.student_id = $student_id
                    ORDER BY sub.name ASC
                ");
            } else {
                echo "<p style='color:red; font-weight:bold;'>Results table '$table_name' does not exist.</p>";
            }
        } else {
            echo "<p style='color:red; font-weight:bold;'>Student not found in table '$student_table'.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Mark Sheet</title>

    <link rel="stylesheet" href="assets/css/admit_card.css">
    <link rel="stylesheet" href="assets/css/marksheet.css">
</head>

<body>
    <div class="container-flex">
        <div class="form-container">
            <form method="get">
                <label>Batch:</label>
                <select name="batch_id" onchange="this.form.submit()">
                    <option value="0">-- Select Batch --</option>
                    <?php $batches->data_seek(0);
                    while ($b = $batches->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>" <?= $batch_id == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Class:</label>
                <select name="class_id" onchange="this.form.submit()">
                    <option value="0">-- Select Class --</option>
                    <?php $classes->data_seek(0);
                    while ($c = $classes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $class_id == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Student:</label>
                <select name="student_id">
                    <option value="0">-- Select Student --</option>
                    <?php foreach ($students as $stu): ?>
                        <option value="<?= $stu['id'] ?>" <?= $student_id == $stu['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($stu['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Exam Type:</label>
                <select name="exam_type">
                    <option value="Final" <?= $exam_type === 'Final' ? 'selected' : '' ?>>Final</option>
                    <option value="Half Yearly" <?= $exam_type === 'Half Yearly' ? 'selected' : '' ?>>Half Yearly</option>
                </select>

                <button class="generatebutton" type="submit" style="margin-top: 15px;">Create Marksheet</button>
            </form>
        </div>

        <?php if ($student && $results && $results->num_rows > 0): ?>
            <div class="admit-container">
                <div id="markSheet">
                    <div class="watermark"><?= htmlspecialchars($watermark_text) ?></div>
                    <div class="header" style="text-align:center;">
                        <img src="<?= htmlspecialchars($student['photo'] ?? 'student_photo.png') ?>" alt="Student Photo" width="100" height="100" style="float:left;">
                        <div class="header-center">
                            <h2>Apex Model School</h2>
                            <p style="margin: 2px 0; font-size: 12px;">Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
                            <p style="margin: 3px 0; font-size: 23px;">Mark Sheet</p>
                            <p style="margin: 5px auto; padding: 2px; font-size: 15px; border: 1px solid #000; width: fit-content; border-radius: 5px;"><?= htmlspecialchars($exam_title) ?></p>
                        </div>
                        <img src="logo.png" alt="School Logo" width="80" height="80" style="float:right;">
                    </div>
                    <hr style="clear:both;">
                    <div class="row">
                        <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                        <p><strong>Roll:</strong> <?= htmlspecialchars($student['roll']) ?></p>
                    </div>
                    <div class="row">
                        <p><strong>Batch:</strong> <?= htmlspecialchars($student['batch_name']) ?></p>
                        <p><strong>Class:</strong> <?= htmlspecialchars($student['class_name']) ?></p>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Sl.</th>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Total Mark</th>
                                <th>Obtained Mark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sl = 1;
                            while ($r = $results->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $sl++ ?></td>
                                    <td><?= htmlspecialchars($r['code']) ?></td>
                                    <td><?= htmlspecialchars($r['name']) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($r['total_mark']) ?></td>
                                    <td style="text-align:center;"><?= htmlspecialchars($r['marks']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div class="signature-section">
                        <div class="signature-box">Student Signature</div>
                        <div class="signature-box">Guardian Signature</div>
                        <div class="signature-box">Teacher Signature</div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 10px;">
                    <button id="downloadBtn" onclick="downloadMarkSheet()">⬇️ Download Marksheet</button>
                </div>
            </div>
        <?php elseif ($student_id): ?>
            <p style="color:red; text-align:center;">No marks found for this student and exam type.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadMarkSheet() {
            const element = document.getElementById('markSheet');
            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'marksheet_<?= $student_id ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    useCORS: true
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>