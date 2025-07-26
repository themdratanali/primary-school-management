<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$batches = $conn->query("SELECT * FROM batches ORDER BY name");
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$admit_type = isset($_GET['admit_type']) ? $_GET['admit_type'] : 'Final';

$exam_title = ($admit_type === 'Half Yearly') ? 'Half Yearly' : 'Final';
$watermark_text = ($admit_type === 'Half Yearly') ? 'HALF YEARLY' : 'FINAL';

$student_table = "students";
if ($batch_id > 0 && $class_id > 0) {
    $batch_name_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
    $class_name_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");
    if ($batch_name_res && $class_name_res) {
        $batch_name = strtolower(str_replace(' ', '_', $batch_name_res->fetch_assoc()['name']));
        $class_name = strtolower(str_replace(' ', '_', $class_name_res->fetch_assoc()['name']));
        $possible_table = "Student_{$batch_name}_{$class_name}";
        $check_table = $conn->query("SHOW TABLES LIKE '$possible_table'");
        if ($check_table->num_rows > 0) {
            $student_table = $possible_table;
        }
    }
}

$students = [];
if ($conn->query("SHOW TABLES LIKE '$student_table'")->num_rows > 0) {
    $student_sql = "SELECT id, name FROM `$student_table`";
    $student_sql .= " ORDER BY name ASC";
    $student_result = $conn->query($student_sql);
    if ($student_result) {
        while ($row = $student_result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

$student = null;
$results = null;
if ($student_id > 0) {
    $stmt = $conn->prepare("
        SELECT s.*, c.name AS class_name, b.name AS batch_name
        FROM `$student_table` s
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN batches b ON s.batch_id = b.id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_res = $stmt->get_result();
    if ($student_res->num_rows > 0) {
        $student = $student_res->fetch_assoc();
        $results = $conn->query("
            SELECT code, name, total_mark
            FROM subjects
            WHERE class_id = {$student['class_id']}
            ORDER BY name ASC
        ");
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Admit Card</title>
    <link rel="stylesheet" href="assets/css/admit_card.css">
</head>

<body>
    <div class="container-flex">
        <div class="form-container">
            <form method="get">
                <label>Batch:</label>
                <select name="batch_id" onchange="this.form.submit()">
                    <option value="0">-- All Batches --</option>
                    <?php while ($batch = $batches->fetch_assoc()): ?>
                        <option value="<?= $batch['id'] ?>" <?= $batch_id == $batch['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($batch['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Class:</label>
                <select name="class_id" onchange="this.form.submit()">
                    <option value="0">-- All Classes --</option>
                    <?php while ($class = $classes->fetch_assoc()): ?>
                        <option value="<?= $class['id'] ?>" <?= $class_id == $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['name']) ?>
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

                <label>Admit Type:</label>
                <select name="admit_type">
                    <option value="Final" <?= $admit_type === 'Final' ? 'selected' : '' ?>>Final</option>
                    <option value="Half Yearly" <?= $admit_type === 'Half Yearly' ? 'selected' : '' ?>>Half Yearly</option>
                </select>

                <button class="generatebutton" type="submit" style="margin-top: 15px;">Generate Admit Card</button>
            </form>
        </div>

        <?php if ($student): ?>
            <div class="admit-container">
                <div id="admitCard">
                    <div class="watermark"><?= htmlspecialchars($watermark_text) ?></div>
                    <div class="header">
                        <img src="<?= htmlspecialchars($student['photo'] ?? 'student_photo.png') ?>" alt="Student Photo">
                        <div class="header-center">
                            <h2>Apex Model School</h2>
                            <p style="margin: 2px 0 0 5px; font-size: 12px;">Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
                            <p style="margin: 3px 0 3px 0; font-size: 23px;">Admit Card</p>
                            <p style="margin: 5px auto 5px auto;padding: 1px;font-size: 20px;border: 0.1px solid #000;align-content: center;width: 65%;border-radius: 5px;font-style: bold;font-weight: bold;"><?= htmlspecialchars($exam_title) ?></p>
                        </div>
                        <img src="logo.png" alt="School Logo">
                    </div>
                    <hr>
                    <div class="row">
                        <p><strong>Name: </strong> <?= htmlspecialchars($student['name']) ?></p>
                        <p><strong>Roll: </strong> <?= htmlspecialchars($student['roll']) ?></p>
                    </div>
                    <div class="row">
                        <p><strong>Batch: </strong> <?= htmlspecialchars($student['batch_name']) ?></p>
                        <p><strong>Class: </strong> <?= htmlspecialchars($student['class_name']) ?></p>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Sl.</th>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Total Mark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sl = 1;
                            while ($r = $results->fetch_assoc()): ?>
                                <tr>
                                    <td style="width: 5%; font-size: 13px;"><?= $sl++ ?></td>
                                    <td style="width: 20%; font-size: 13px;"><?= htmlspecialchars($r['code']) ?></td>
                                    <td style="width: 55%; font-size: 15px;"><?= htmlspecialchars($r['name']) ?></td>
                                    <td style="width: 15%; font-size: 13px; text-align: center;"><?= htmlspecialchars($r['total_mark']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div class="instructions-title">INSTRUCTIONS FOR THE EXAMINEES</div>
                    <ol class="instructions">
                        <li>Bring your admit card and student ID to the exam hall.</li>
                        <li>Arrive at least 30 minutes before the exam starts.</li>
                        <li>Electronic devices are strictly prohibited during the exam.</li>
                        <li>Follow all instructions given by the invigilator.</li>
                        <li>Maintain silence and discipline inside the exam hall.</li>
                        <li>Admit card must be produced when demanded by the invigilator.</li>
                    </ol>
                </div>

                <div style="text-align: center; margin-top: 10px;">
                    <button id="downloadBtn" onclick="downloadAdmitCard()">⬇️ Download Admit Card</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadAdmitCard() {
            const element = document.getElementById('admitCard');
            const opt = {
                margin: [5, 0, 0, 0],
                filename: 'admit_card_<?= $student_id ?>.pdf',
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