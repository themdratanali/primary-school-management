<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$conn->set_charset("utf8mb4");

function getReadableCategory($category)
{
    $map = [
        'monthly' => 'Monthly',
        'exam' => 'Exam',
        'other' => 'Other',
        'admission' => 'Admission',
        'registration' => 'Registration',
        'library' => 'Library',
        'lab' => 'Lab'
    ];
    return isset($map[strtolower($category)]) ? $map[strtolower($category)] : ucfirst($category);
}

$batches = [];
$classes = [];

$res_batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
while ($row = $res_batches->fetch_assoc()) {
    $batches[] = $row;
}

$res_classes = $conn->query("SELECT id, name FROM classes ORDER BY name");
while ($row = $res_classes->fetch_assoc()) {
    $classes[] = $row;
}

$fees = [];
$student_name = '';
$student_roll = '';
$student_photo = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['batch_id'], $_GET['class_id'], $_GET['student_id'])) {
    $batch_id = intval($_GET['batch_id']);
    $class_id = intval($_GET['class_id']);
    $student_id = intval($_GET['student_id']);

    $batch_name_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
    $batch_name = $batch_name_res->num_rows ? strtolower(str_replace(' ', '_', $batch_name_res->fetch_assoc()['name'])) : '';

    $class_name_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");
    $class_name = $class_name_res->num_rows ? strtolower(str_replace(' ', '_', $class_name_res->fetch_assoc()['name'])) : '';

    $student_res = $conn->query("SELECT name, roll, photo FROM students WHERE id = $student_id");
    if ($student_res->num_rows) {
        $student_data = $student_res->fetch_assoc();
        $student_name = $student_data['name'];
        $student_roll = $student_data['roll'];
        $student_photo = $student_data['photo'];
    }

    if ($batch_name && $class_name) {
        $table_like = "fees_" . $batch_name . "_" . $class_name . "_%";
        $tables_res = $conn->query("SHOW TABLES LIKE '$table_like'");
        while ($table_row = $tables_res->fetch_array()) {
            $table_name = $table_row[0];
            $fee_res = $conn->query("SELECT student_name, fee_type_category, fee_type_detail, amount, created_at FROM `$table_name` WHERE student_id = $student_id ORDER BY created_at DESC");
            while ($row = $fee_res->fetch_assoc()) {
                $fees[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>View Student Fees</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/fee_see.css">
</head>

<body>
    <div class="container">
        <h2 class="text-center mb-4">View Student Fee Records</h2>
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Select Batch</label>
                <select name="batch_id" class="form-select" required>
                    <option value="">-- Select Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= (isset($batch_id) && $batch_id == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Select Class</label>
                <select name="class_id" class="form-select" required>
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (isset($class_id) && $class_id == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Select Student</label>
                <select name="student_id" id="student_id" class="form-select" required>
                    <option value="">-- Select Student --</option>
                    <!-- Populated via AJAX -->
                </select>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary px-5">View Fee Records</button>
            </div>
        </form>

        <?php if ($student_name): ?>
            <div class="d-flex align-items-center mb-4 gap-3">
                <?php if ($student_photo && file_exists($student_photo)): ?>
                    <img src="<?= htmlspecialchars($student_photo) ?>" alt="Student Photo" class="student-photo">
                <?php else: ?>
                    <img src="uploads/students/default-photo.jpg" alt="Default Photo" class="student-photo">
                <?php endif; ?>
                <div>
                    <h5 class="mb-0"><?= htmlspecialchars($student_name) ?></h5>
                    <small>Roll: <?= htmlspecialchars($student_roll) ?></small>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($fees)): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Category</th>
                            <th>Detail</th>
                            <th>Amount (৳)</th>
                            <th>Paid At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fees as $i => $fee): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($fee['student_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(getReadableCategory($fee['fee_type_category'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars($fee['fee_type_detail'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($fee['amount'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($fee['created_at'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif (isset($_GET['student_id'])): ?>
            <div class="alert alert-warning text-center mt-4">
                No fee records found for <strong><?= htmlspecialchars($student_name) ?></strong> in this batch and class.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(function() {
            function loadStudents(batchId, classId, selectedStudentId = null) {
                if (!batchId || !classId) {
                    $('#student_id').html('<option value="">-- Select Student --</option>');
                    return;
                }
                $.post('get_students_by_batch_class.php', {
                    batch_id: batchId,
                    class_id: classId
                }, function(data) {
                    $('#student_id').html(data);
                    if (selectedStudentId) {
                        $('#student_id').val(selectedStudentId);
                    }
                });
            }
            $('select[name="batch_id"], select[name="class_id"]').on('change', function() {
                loadStudents($('select[name="batch_id"]').val(), $('select[name="class_id"]').val());
            });
            <?php if (isset($batch_id, $class_id, $student_id)): ?>
                loadStudents(<?= $batch_id ?>, <?= $class_id ?>, <?= $student_id ?>);
            <?php endif; ?>
        });
    </script>
</body>

</html>