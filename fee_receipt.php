<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['fee_receipt'])) {
    $_SESSION['fee_receipt'] = [];
}

$batches = $conn->query("SELECT * FROM batches ORDER BY name");
$classes = $conn->query("SELECT * FROM classes ORDER BY name");

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$fee_type_category = isset($_GET['fee_type_category']) ? $_GET['fee_type_category'] : '';
$amount = isset($_GET['amount']) ? trim($_GET['amount']) : '';
$fee_type_detail = isset($_GET['fee_type_detail']) ? trim($_GET['fee_type_detail']) : '';

function sanitize_table_part($str)
{
    return preg_replace('/[^a-zA-Z0-9]/', '_', trim($str));
}

$batch_name = '';
$class_name = '';

if ($batch_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $stmt->bind_result($batch_name);
    $stmt->fetch();
    $stmt->close();
}

if ($class_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->bind_result($class_name);
    $stmt->fetch();
    $stmt->close();
}

$students = null;
if ($batch_id > 0 && $class_id > 0) {
    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    $student_table = "Student_{$batch_clean}_{$class_clean}";

    $result = $conn->query("SHOW TABLES LIKE '$student_table'");
    if ($result && $result->num_rows > 0) {
        $sql = "SELECT id, name FROM `$student_table` ORDER BY name ASC";
        $students = $conn->query($sql);
    }
}

$student = null;
if ($student_id > 0 && $batch_id > 0 && $class_id > 0) {
    $batch_clean = sanitize_table_part($batch_name);
    $class_clean = sanitize_table_part($class_name);
    $student_table = "Student_{$batch_clean}_{$class_clean}";

    $result = $conn->query("SHOW TABLES LIKE '$student_table'");
    if ($result && $result->num_rows > 0) {
        $stmt = $conn->prepare("SELECT * FROM `$student_table` WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($student) {
            $student['batch_name'] = $batch_name;
            $student['class_name'] = $class_name;
        }
    }
}

$message = "";

if (isset($_GET['reset'])) {
    $_SESSION['fee_receipt'] = [];
    $message = "<p class='success'>✅ Receipt cleared successfully.</p>";
}

if ($student && $fee_type_category && $fee_type_detail && $amount !== '') {

    $duplicate = false;
    foreach ($_SESSION['fee_receipt'] as $item) {
        if ($item['student_id'] == $student_id && $item['fee_type_category'] == $fee_type_category && $item['fee_type_detail'] == $fee_type_detail) {
            $duplicate = true;
            break;
        }
    }

    if ($duplicate) {
        $message = "<p class='notice'>⚠️ This fee has already been added for this receipt.</p>";
    } else {
        $_SESSION['fee_receipt'][] = [
            'student_id' => $student_id,
            'student_name' => $student['name'],
            'fee_type_category' => $fee_type_category,
            'fee_type_detail' => $fee_type_detail,
            'amount' => $amount
        ];

        $batch_clean = sanitize_table_part($batch_name);
        $class_clean = sanitize_table_part($class_name);
        $table_name = "fees_{$batch_clean}_{$class_clean}_" . strtolower($fee_type_category);

        $sql_create = "CREATE TABLE IF NOT EXISTS `$table_name` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            student_name VARCHAR(100) NOT NULL,
            fee_type_category VARCHAR(50) NOT NULL,
            fee_type_detail VARCHAR(100) NOT NULL,
            amount VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$conn->query($sql_create)) {
            $message = "<p class='notice'>⚠️ Failed to create fee table: " . $conn->error . "</p>";
        } else {
            $stmt = $conn->prepare("INSERT INTO `$table_name` (student_id, student_name, fee_type_category, fee_type_detail, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $student_id, $student['name'], $fee_type_category, $fee_type_detail, $amount);
            if ($stmt->execute()) {
                $message = "<p class='success'>✅ Fee added to receipt and recorded successfully.</p>";
            } else {
                $message = "<p class='notice'>⚠️ Failed to record fee: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Fee Receipt</title>
    <link rel="stylesheet" href="assets/css/admit_card.css">
    <link rel="stylesheet" href="assets/css/fee_receipt.css">
</head>

<body>
    <div class="container-flex">
        <div class="form-container">
            <form method="get" id="feeForm">
                <label>Batch:</label>
                <select name="batch_id" onchange="document.getElementById('feeForm').submit()">
                    <option value="0">-- All Batches --</option>
                    <?php $batches->data_seek(0);
                    while ($batch = $batches->fetch_assoc()): ?>
                        <option value="<?= $batch['id'] ?>" <?= $batch_id == $batch['id'] ? 'selected' : '' ?>><?= htmlspecialchars($batch['name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Class:</label>
                <select name="class_id" onchange="document.getElementById('feeForm').submit()">
                    <option value="0">-- All Classes --</option>
                    <?php $classes->data_seek(0);
                    while ($class = $classes->fetch_assoc()): ?>
                        <option value="<?= $class['id'] ?>" <?= $class_id == $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Student:</label>
                <select name="student_id" required>
                    <option value="0">-- Select Student --</option>
                    <?php if ($students): while ($stu = $students->fetch_assoc()): ?>
                            <option value="<?= $stu['id'] ?>" <?= $student_id == $stu['id'] ? 'selected' : '' ?>><?= htmlspecialchars($stu['name']) ?></option>
                    <?php endwhile;
                    endif; ?>
                </select>

                <label>Fee Type Category:</label>
                <select name="fee_type_category" id="fee_type_category" onchange="showFeeTypeOptions()" required>
                    <option value="">-- Select Type --</option>
                    <option value="Monthly" <?= $fee_type_category == 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="Exam" <?= $fee_type_category == 'Exam' ? 'selected' : '' ?>>Exam</option>
                    <option value="Other" <?= $fee_type_category == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>

                <div id="fee_type_detail_container" style="margin-top:10px;"></div>

                <label>Amount (৳):</label>
                <input style="width: 100%;padding: 12px 14px;margin-top: 8px;margin-bottom: 0px;border: 1px solid #d1d5db;border-radius: 10px;font-size: 15px;background: rgba(255,255,255,0.8);transition: border 0.3s, box-shadow 0.3s;" type="text" name="amount" placeholder="e.g., 1500" value="<?= htmlspecialchars($amount) ?>" required>

                <button type="submit" style="margin-top:15px;">Add to Fee Receipt</button>
                <a href="?reset=1" style="margin-left:10px; color:red;">Reset Receipt</a>
            </form>
            <?= $message ?>
        </div>

        <?php if ($student && !empty($_SESSION['fee_receipt'])): ?>
            <div class="admit-container">
                <div id="feeReceipt">
                    <div class="watermark">FEE RECEIPT</div>
                    <div class="header">
                        <img src="<?= htmlspecialchars($student['photo'] ?? 'student_photo.png') ?>" alt="Student Photo">
                        <div class="header-center">
                            <h2>Apex Model School</h2>
                            <p style="font-size:12px;">Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
                            <p style="font-size:23px;">Fee Receipt</p>
                        </div>
                        <img src="logo.png" alt="School Logo">
                    </div>
                    <hr>
                    <div class="row">
                        <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                        <p><strong>Roll:</strong> <?= htmlspecialchars($student['roll'] ?? '') ?></p>
                    </div>
                    <div class="row">
                        <p><strong>Batch:</strong> <?= htmlspecialchars($student['batch_name']) ?></p>
                        <p><strong>Class:</strong> <?= htmlspecialchars($student['class_name']) ?></p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Sl.</th>
                                <th>Fee Type</th>
                                <th>Amount (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0;
                            $sl = 1;
                            foreach ($_SESSION['fee_receipt'] as $item):
                                if ($item['student_id'] != $student_id) continue;
                                $total += (float)$item['amount'];
                            ?>
                                <tr>
                                    <td><?= $sl++ ?></td>
                                    <td><?= htmlspecialchars($item['fee_type_detail']) ?> (<?= htmlspecialchars($item['fee_type_category']) ?>)</td>
                                    <td><?= htmlspecialchars($item['amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="2" style="text-align:right; font-weight:bold;">Total Amount (৳)</td>
                                <td style="font-weight:bold;"><?= $total ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="text-align:right; margin-top:20px;">
                        <p>________________________</p>
                        <p><strong>Signature</strong></p>
                    </div>
                </div>
                <div style="text-align:center; margin-top:10px;">
                    <button onclick="downloadFeeReceipt()">⬇️ Download Fee Receipt</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function showFeeTypeOptions() {
            const container = document.getElementById('fee_type_detail_container');
            const type = document.getElementById('fee_type_category').value;
            const student_id = document.querySelector('select[name="student_id"]').value;
            container.innerHTML = '';

            if (type === 'Monthly') {
                if (student_id == "0") {
                    container.innerHTML = "<p style='color:red;'>Please select a student first to see unpaid months.</p>";
                    return;
                }
                const batch_id = document.querySelector('select[name="batch_id"]').value;
                const class_id = document.querySelector('select[name="class_id"]').value;
                fetch(`get_unpaid_months.php?student_id=${student_id}&batch_id=${batch_id}&class_id=${class_id}`)
                    .then(response => response.json())
                    .then(months => {
                        if (months.length === 0) {
                            container.innerHTML = "<p style='color:green;'>All months are paid for this student.</p>";
                        } else {
                            let options = months.map(m => `<option value="${m}">${m}</option>`).join('');
                            container.innerHTML = `
                    <label>Select Month:</label>
                    <select name="fee_type_detail" required>
                        <option value="">-- Select Month --</option>
                        ${options}
                    </select>
                `;
                        }
                    });
            } else if (type === 'Exam') {
                container.innerHTML = `
            <label>Select Exam:</label>
            <select name="fee_type_detail" required>
                <option value="">-- Select Exam --</option>
                <option value="Final">Final</option>
                <option value="Half Yearly">Half Yearly</option>
            </select>
        `;
            } else if (type === 'Other') {
                container.innerHTML = `
            <label>Enter Fee Type:</label>
            <input type="text" name="fee_type_detail" placeholder="e.g., Admission Fee" required>
        `;
            }
        }

        function downloadFeeReceipt() {
            var element = document.getElementById('feeReceipt');
            html2pdf().from(element).set({
                margin: 0.5,
                filename: 'fee_receipt.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a4',
                    orientation: 'portrait'
                }
            }).save();
        }

        document.querySelector('select[name="student_id"]').addEventListener('change', function() {
            if (document.getElementById('fee_type_category').value === 'Monthly') {
                showFeeTypeOptions();
            }
        });
        document.getElementById('fee_type_category').addEventListener('change', showFeeTypeOptions);
    </script>
</body>

</html>