<?php
session_start();
include 'db.php';
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Teacher ID missing or invalid");
}

$id = (int)$_GET['id'];

$sql = "SELECT teachers.*, subjects.name AS subject_name 
        FROM teachers 
        LEFT JOIN subjects ON teachers.subject_id = subjects.id 
        WHERE teachers.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Teacher not found");
}

$photo = (!empty($data['photo']) && file_exists($data['photo'])) ? $data['photo'] : 'uploads/teachers/default-photo.jpg';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Teacher Profile - <?= htmlspecialchars($data['name']) ?></title>
    <link rel="stylesheet" href="assets/css/teacher_profile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadProfile() {
            const element = document.getElementById('admitCard');
            const opt = {
                margin: [10, 0, 0, 0],
                filename: 'teacher_profile_<?= $id ?>.pdf',
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
                        <img src="<?= htmlspecialchars($photo) ?>" alt="Teacher Photo">
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
                            <td><strong>Name : </strong> <?= htmlspecialchars($data['name']) ?></td>
                            <td><strong>Gender : </strong> <?= htmlspecialchars($data['gender']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mother's Name : </strong> <?= htmlspecialchars($data['mother_name']) ?></td>
                            <td><strong>Father's Name : </strong> <?= htmlspecialchars($data['father_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Date of Birth : </strong> <?= htmlspecialchars($data['dob']) ?></td>
                            <td><strong>Birth Cert. No. : </strong> <?= htmlspecialchars($data['birth_cert_no']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Blood Group : </strong> <?= htmlspecialchars($data['blood_group']) ?></td>
                            <td><strong>Religion : </strong> <?= htmlspecialchars($data['religion']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Nationality : </strong> <?= htmlspecialchars($data['nationality']) ?></td>
                            <td><strong>NID : </strong> <?= htmlspecialchars($data['nid']) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Address</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Present Address : </strong> <?= nl2br(htmlspecialchars($data['present_address'])) ?></td>
                            <td><strong>Permanent Address : </strong> <?= nl2br(htmlspecialchars($data['permanent_address'])) ?></td>
                        </tr>
                    </table>

                    <div class="section-title">Professional Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Education : </strong> <?= nl2br(htmlspecialchars($data['education'])) ?></td>
                            <td><strong>Experience : </strong> <?= nl2br(htmlspecialchars($data['experience'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Subject : </strong> <?= htmlspecialchars($data['subject_name']) ?></td>
                            <td></td>
                        </tr>
                    </table>

                    <div class="section-title">Contact Information</div>
                    <table class="info-table">
                        <tr>
                            <td><strong>Phone : </strong> <?= htmlspecialchars($data['phone']) ?></td>
                            <td><strong>Email : </strong> <?= htmlspecialchars($data['email']) ?></td>
                        </tr>
                    </table>

                    <div class="signature">
                        <p style="font-size: 12px; text-align: left; font-weight: bold; text-decoration: underline; width: 50%;">Teacher Signature</p>
                        <p style="width: 50%; font-size: 12px; text-align: right; margin-right: 50px; font-weight: bold; text-decoration: underline;">Authority Signature</p>
                    </div>
                </div>
            </div>
            <div style="text-align: center;">
                <button class="download-btn" onclick="downloadProfile()">⬇️ Download Profile</button>
                <a href="teacher_profile_edit.php?id=<?= $id ?>" class="download-btn" style="background: #28a745; margin-left: 10px;">✏️ Edit Profile</a>
            </div>
        </div>
    </div>
</body>

</html>