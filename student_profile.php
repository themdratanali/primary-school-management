<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$table = $_GET['table'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$table || !$id) {
    die("Invalid request.");
}

$checkTable = $conn->query("SHOW TABLES LIKE '$table'");
if ($checkTable->num_rows == 0) {
    die("Table does not exist.");
}

$stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Student not found.");
}

$student = $result->fetch_assoc();
$stmt->close();

$batch_name = '';
$class_name = '';

if (!empty($student['batch_id'])) {
    $stmtBatch = $conn->prepare("SELECT name FROM batches WHERE id = ?");
    $stmtBatch->bind_param("i", $student['batch_id']);
    $stmtBatch->execute();
    $resBatch = $stmtBatch->get_result();
    if ($resBatch->num_rows > 0) {
        $batch_name = $resBatch->fetch_assoc()['name'];
    }
    $stmtBatch->close();
}

if (!empty($student['class_id'])) {
    $stmtClass = $conn->prepare("SELECT name FROM classes WHERE id = ?");
    $stmtClass->bind_param("i", $student['class_id']);
    $stmtClass->execute();
    $resClass = $stmtClass->get_result();
    if ($resClass->num_rows > 0) {
        $class_name = $resClass->fetch_assoc()['name'];
    }
    $stmtClass->close();
}

$photoPath = __DIR__ . '/' . ($student['photo'] ?? '');
$photo = (!empty($student['photo']) && file_exists($photoPath)) ? $student['photo'] : 'uploads/students/default-photo.jpg';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Student Profile - <?= htmlspecialchars($student['name']) ?> </title>
    <link rel="stylesheet" href="assets/css/student_profile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        function downloadProfile() {
            const element = document.getElementById('admitCard');
            const opt = {
                margin: [10, 0, 0, 0], // Top margin 10px
                filename: 'student_profile_<?= $id ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 3,
                    useCORS: true,
                    scrollY: 0
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                },
                pagebreak: {
                    mode: ['avoid-all']
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</head>

<body>
    <div class="container-flex">
        <div class="admit-container">
            <div id="admitCard">
                <div class="watermark">
                    <img src="logo.png" alt="Watermark">
                </div>
                <div class="card-border">
                    <div class="header">
                        <img src="<?= htmlspecialchars($photo) ?>" alt="Student Photo">
                        <div class="header-center">
                            <h2>Apex Model School</h2>
                            <p style="font-size: 12px;margin-top: 2px;">Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
                        </div>
                        <img src="logo.png" alt="School Logo">
                    </div>
                    <hr>

                    <div class="section-title">Personal Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Name : </strong> <?= htmlspecialchars($student['name']) ?></td>
                            <td><strong>Gender: </strong> <?= htmlspecialchars($student['gender']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mother's Name : </strong> <?= htmlspecialchars($student['mother_name']) ?></td>
                            <td><strong>Father's Name : </strong> <?= htmlspecialchars($student['father_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Date of Birth : </strong> <?= htmlspecialchars($student['dob']) ?></td>
                            <td><strong>Birth Certificate No. : </strong> <?= htmlspecialchars($student['birth_cert_no']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Blood Group : </strong> <?= htmlspecialchars($student['blood_group']) ?></td>
                            <td><strong>Religion : </strong> <?= htmlspecialchars($student['religion']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Nationality : </strong> <?= htmlspecialchars($student['nationality']) ?></td>
                            <td><strong>NID : </strong> <?= htmlspecialchars($student['nid']) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Address</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Present Address : </strong> <?= nl2br(htmlspecialchars($student['present_address'])) ?></td>
                            <td><strong>Permanent Address : </strong> <?= nl2br(htmlspecialchars($student['permanent_address'])) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Academic Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Batch :</strong> <?= htmlspecialchars($batch_name) ?></td>
                            <td><strong>Class:</strong> <?= htmlspecialchars($class_name) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Roll :</strong> <?= htmlspecialchars($student['roll']) ?></td>
                            <td></td>
                        </tr>
                    </table>

                    <div class="section-title">Contact Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Student Email : </strong> <?= htmlspecialchars($student['student_email']) ?></td>
                            <td><strong>Father Email : </strong> <?= htmlspecialchars($student['father_email']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mother Email : </strong> <?= htmlspecialchars($student['mother_email']) ?></td>
                            <td><strong>Guardian Email : </strong> <?= htmlspecialchars($student['guardian_email']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Signature : </strong> <?= htmlspecialchars($student['signature']) ?></td>
                            <td></td>
                        </tr>
                    </table>

                    <div class="section-title">Local Guardian</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Guardian Name : </strong> <?= htmlspecialchars($student['guardian_name']) ?></td>
                            <td><strong>Guardian Profession : </strong> <?= htmlspecialchars($student['guardian_profession']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Guardian Organization : </strong> <?= htmlspecialchars($student['guardian_organization']) ?></td>
                            <td><strong>Guardian Relation : </strong> <?= htmlspecialchars($student['guardian_relation']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Guardian Mobile : </strong> <?= htmlspecialchars($student['guardian_mobile']) ?></td>
                            <td><strong>Guardian Address : </strong> <?= nl2br(htmlspecialchars($student['guardian_address'])) ?></td>
                        </tr>
                    </table>
                    <div class="signature">
                        <p style="font-size: 12px; text-align: left; font-weight: bold; text-decoration: underline; width: 50%;"> Student/Parents Signature</p>
                        <p style="width: 50%; font-size: 12px; text-align: right; margin-right: 50px; font-weight: bold; text-decoration: underline;">Signature</p>
                    </div>
                </div>
            </div>
            <div style="text-align: center;">
                <button class="download-btn" onclick="downloadProfile()">⬇️ Download Profile</button>
                <a href="student_profile_edit.php?table=<?= $table ?>&id=<?= $id ?>" class="download-btn" style="background: #28a745; margin-left: 10px;">✏️ Edit Profile</a>
            </div>
        </div>
    </div>
</body>

</html>