<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$table = $_GET['table'] ?? 'students';

if (!$id) {
    die("Invalid request.");
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
$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $mother_name = $_POST['mother_name'];
    $father_name = $_POST['father_name'];
    $dob = $_POST['dob'];
    $birth_cert_no = $_POST['birth_cert_no'];
    $blood_group = $_POST['blood_group'];
    $religion = $_POST['religion'];
    $nationality = $_POST['nationality'];
    $nid = $_POST['nid'];
    $present_address = $_POST['present_address'];
    $permanent_address = $_POST['permanent_address'];
    $batch_id = intval($_POST['batch_id']);
    $class_id = intval($_POST['class_id']);
    $roll = $_POST['roll'];
    $student_email = $_POST['student_email'];
    $father_email = $_POST['father_email'];
    $mother_email = $_POST['mother_email'];
    $guardian_email = $_POST['guardian_email'];
    $signature = $_POST['signature'];
    $guardian_name = $_POST['guardian_name'];
    $guardian_profession = $_POST['guardian_profession'];
    $guardian_organization = $_POST['guardian_organization'];
    $guardian_relation = $_POST['guardian_relation'];
    $guardian_mobile = $_POST['guardian_mobile'];
    $guardian_address = $_POST['guardian_address'];

    $photo = $student['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/students/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $newPhotoName = uniqid('student_', true) . '.' . $ext;
        $uploadPath = $uploadDir . $newPhotoName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            if (!empty($student['photo']) && file_exists($student['photo']) && $student['photo'] != 'uploads/students/default-photo.jpg') {
                unlink($student['photo']);
            }
            $photo = $uploadPath;
        }
    }

    $stmt = $conn->prepare("UPDATE `$table` SET 
        name = ?, gender = ?, mother_name = ?, father_name = ?, dob = ?, birth_cert_no = ?,
        blood_group = ?, religion = ?, nationality = ?, nid = ?, present_address = ?, permanent_address = ?,
        batch_id = ?, class_id = ?, roll = ?, student_email = ?, father_email = ?, mother_email = ?, 
        guardian_email = ?, signature = ?, guardian_name = ?, guardian_profession = ?, 
        guardian_organization = ?, guardian_relation = ?, guardian_mobile = ?, guardian_address = ?, photo = ?
        WHERE id = ?");
    $stmt->bind_param(
        "ssssssssssssiisssssssssssssi",
        $name,
        $gender,
        $mother_name,
        $father_name,
        $dob,
        $birth_cert_no,
        $blood_group,
        $religion,
        $nationality,
        $nid,
        $present_address,
        $permanent_address,
        $batch_id,
        $class_id,
        $roll,
        $student_email,
        $father_email,
        $mother_email,
        $guardian_email,
        $signature,
        $guardian_name,
        $guardian_profession,
        $guardian_organization,
        $guardian_relation,
        $guardian_mobile,
        $guardian_address,
        $photo,
        $id
    );

    if ($stmt->execute()) {
        header("Location: student_profile.php?table=$table&id=$id");
        exit;
    } else {
        echo "Update failed: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Student Profile</title>
    <link rel="stylesheet" href="assets/css/student_profile_edit.css">
</head>

<body>
    <div class="container">
        <h2>Edit Student Profile</h2>
        <form method="post" enctype="multipart/form-data">
            <label>Photo:
                <input type="file" name="photo" accept="image/*">
                <?php if (!empty($student['photo'])): ?>
                    <img src="<?= htmlspecialchars($student['photo']) ?>" alt="Photo" class="photo-preview">
                <?php endif; ?>
            </label>
            <label>Name:<input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required></label>
            <label>Gender:
                <select name="gender" required>
                    <option value="Male" <?= ($student['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($student['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= ($student['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </label>
            <label>Mother's Name:<input type="text" name="mother_name" value="<?= htmlspecialchars($student['mother_name']) ?>"></label>
            <label>Father's Name:<input type="text" name="father_name" value="<?= htmlspecialchars($student['father_name']) ?>"></label>
            <label>Date of Birth:<input type="date" name="dob" value="<?= htmlspecialchars($student['dob']) ?>"></label>
            <label>Birth Certificate No:<input type="text" name="birth_cert_no" value="<?= htmlspecialchars($student['birth_cert_no']) ?>"></label>
            <label>Blood Group:<input type="text" name="blood_group" value="<?= htmlspecialchars($student['blood_group']) ?>"></label>
            <label>Religion:<input type="text" name="religion" value="<?= htmlspecialchars($student['religion']) ?>"></label>
            <label>Nationality:<input type="text" name="nationality" value="<?= htmlspecialchars($student['nationality']) ?>"></label>
            <label>NID:<input type="text" name="nid" value="<?= htmlspecialchars($student['nid']) ?>"></label>
            <label>Present Address:<textarea name="present_address"><?= htmlspecialchars($student['present_address']) ?></textarea></label>
            <label>Permanent Address:<textarea name="permanent_address"><?= htmlspecialchars($student['permanent_address']) ?></textarea></label>
            <label>Batch:
                <select name="batch_id">
                    <option value="">Select Batch</option>
                    <?php while ($row = $batches->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($student['batch_id'] == $row['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
            <label>Class:
                <select name="class_id">
                    <option value="">Select Class</option>
                    <?php while ($row = $classes->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($student['class_id'] == $row['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
            <label>Roll:<input type="text" name="roll" value="<?= htmlspecialchars($student['roll']) ?>"></label>
            <label>Student Email:<input type="email" name="student_email" value="<?= htmlspecialchars($student['student_email']) ?>"></label>
            <label>Father Email:<input type="email" name="father_email" value="<?= htmlspecialchars($student['father_email']) ?>"></label>
            <label>Mother Email:<input type="email" name="mother_email" value="<?= htmlspecialchars($student['mother_email']) ?>"></label>
            <label>Guardian Email:<input type="email" name="guardian_email" value="<?= htmlspecialchars($student['guardian_email']) ?>"></label>
            <label>Signature:<input type="text" name="signature" value="<?= htmlspecialchars($student['signature']) ?>"></label>
            <label>Guardian Name:<input type="text" name="guardian_name" value="<?= htmlspecialchars($student['guardian_name']) ?>"></label>
            <label>Guardian Profession:<input type="text" name="guardian_profession" value="<?= htmlspecialchars($student['guardian_profession']) ?>"></label>
            <label>Guardian Organization:<input type="text" name="guardian_organization" value="<?= htmlspecialchars($student['guardian_organization']) ?>"></label>
            <label>Guardian Relation:<input type="text" name="guardian_relation" value="<?= htmlspecialchars($student['guardian_relation']) ?>"></label>
            <label>Guardian Mobile:<input type="text" name="guardian_mobile" value="<?= htmlspecialchars($student['guardian_mobile']) ?>"></label>
            <label>Guardian Address:<textarea name="guardian_address"><?= htmlspecialchars($student['guardian_address']) ?></textarea></label>
            <button type="submit">✅ Update Profile</button>
        </form>
    </div>
</body>

</html>