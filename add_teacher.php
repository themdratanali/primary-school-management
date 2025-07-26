<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

// Auto-create teachers table if not exists
$createTableSql = "
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    mother_name VARCHAR(255),
    father_name VARCHAR(255),
    gender ENUM('Male','Female','Other') DEFAULT NULL,
    dob DATE DEFAULT NULL,
    birth_cert_no VARCHAR(100) DEFAULT NULL,
    blood_group VARCHAR(10) DEFAULT NULL,
    religion VARCHAR(100) DEFAULT NULL,
    nationality VARCHAR(100) DEFAULT NULL,
    nid VARCHAR(100) DEFAULT NULL,
    present_address TEXT,
    permanent_address TEXT,
    education TEXT,
    experience TEXT,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject_id INT NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($createTableSql);
$subjects = $conn->query("SELECT id, name FROM subjects ORDER BY name");
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $mother_name = trim($_POST['mother_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $gender = $_POST['gender'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $birth_cert_no = trim($_POST['birth_cert_no'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $present_address = trim($_POST['present_address'] ?? '');
    $permanent_address = trim($_POST['permanent_address'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $subject_id = intval($_POST['subject_id']);

    $photo = null;

    if (empty($name) || empty($phone) || empty($email) || empty($subject_id) || empty($gender)) {
        $message = "Please fill all required fields (Name, Gender, Phone, Email, Subject).";
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_ext)) {
                if ($_FILES['photo']['size'] <= 2 * 1024 * 1024) {
                    if (!is_dir('uploads/teachers')) {
                        mkdir('uploads/teachers', 0777, true);
                    }
                    $photo = 'uploads/teachers/' . uniqid('teacher_', true) . '.' . $file_ext;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo)) {
                        $message = "Failed to upload photo.";
                    }
                } else {
                    $message = "Photo must be under 2MB.";
                }
            } else {
                $message = "Invalid photo format. Allowed: jpg, jpeg, png, gif.";
            }
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO teachers (
            name, mother_name, father_name, gender, dob, birth_cert_no, blood_group, religion,
            nationality, nid, present_address, permanent_address, education, experience,
            phone, email, subject_id, photo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            $message = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param(
                "ssssssssssssssssss",
                $name,
                $mother_name,
                $father_name,
                $gender,
                $dob,
                $birth_cert_no,
                $blood_group,
                $religion,
                $nationality,
                $nid,
                $present_address,
                $permanent_address,
                $education,
                $experience,
                $phone,
                $email,
                $subject_id,
                $photo
            );
            if ($stmt->execute()) {
                $message = "✅ Teacher added successfully!";
            } else {
                $message = "❌ Execute failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Add Teacher - Clean 3D</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/add_teacher.css">
</head>

<body>

    <div class="container">
        <h2>Teacher Registration Form</h2>

        <?php if (!empty($message)): ?>
            <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="Full name" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="gender" class="form-label">Gender *</label>
                    <select class="form-control" id="gender" name="gender" required>
                        <option value="" disabled <?= empty($gender) ? 'selected' : '' ?>>Select gender</option>
                        <option <?= (isset($gender) && $gender === 'Male') ? 'selected' : '' ?>>Male</option>
                        <option <?= (isset($gender) && $gender === 'Female') ? 'selected' : '' ?>>Female</option>
                        <option <?= (isset($gender) && $gender === 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="mother_name" class="form-label">Mother's Name</label>
                    <input type="text" class="form-control" id="mother_name" name="mother_name" placeholder="Mother's name" value="<?= isset($mother_name) ? htmlspecialchars($mother_name) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="father_name" class="form-label">Father's Name</label>
                    <input type="text" class="form-control" id="father_name" name="father_name" placeholder="Father's name" value="<?= isset($father_name) ? htmlspecialchars($father_name) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="dob" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" id="dob" name="dob" value="<?= isset($dob) ? htmlspecialchars($dob) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="birth_cert_no" class="form-label">Birth Certificate No.</label>
                    <input type="text" class="form-control" id="birth_cert_no" name="birth_cert_no" placeholder="Certificate number" value="<?= isset($birth_cert_no) ? htmlspecialchars($birth_cert_no) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="blood_group" class="form-label">Blood Group</label>
                    <input type="text" class="form-control" id="blood_group" name="blood_group" placeholder="e.g. A+, O-" value="<?= isset($blood_group) ? htmlspecialchars($blood_group) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="religion" class="form-label">Religion</label>
                    <input type="text" class="form-control" id="religion" name="religion" placeholder="Religion" value="<?= isset($religion) ? htmlspecialchars($religion) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="nationality" class="form-label">Nationality</label>
                    <input type="text" class="form-control" id="nationality" name="nationality" placeholder="Nationality" value="<?= isset($nationality) ? htmlspecialchars($nationality) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="nid" class="form-label">NID</label>
                    <input type="text" class="form-control" id="nid" name="nid" placeholder="National ID" value="<?= isset($nid) ? htmlspecialchars($nid) : '' ?>">
                </div>
                <div class="col-12">
                    <label for="present_address" class="form-label">Present Address</label>
                    <textarea class="form-control" id="present_address" name="present_address" placeholder="Current address" rows="2"><?= isset($present_address) ? htmlspecialchars($present_address) : '' ?></textarea>
                </div>
                <div class="col-12">
                    <label for="permanent_address" class="form-label">Permanent Address</label>
                    <textarea class="form-control" id="permanent_address" name="permanent_address" placeholder="Permanent address" rows="2"><?= isset($permanent_address) ? htmlspecialchars($permanent_address) : '' ?></textarea>
                </div>
                <div class="col-12">
                    <label for="education" class="form-label">Education Details</label>
                    <textarea class="form-control" id="education" name="education" placeholder="Educational qualifications" rows="2"><?= isset($education) ? htmlspecialchars($education) : '' ?></textarea>
                </div>
                <div class="col-12">
                    <label for="experience" class="form-label">Experience</label>
                    <textarea class="form-control" id="experience" name="experience" placeholder="Work experience" rows="2"><?= isset($experience) ? htmlspecialchars($experience) : '' ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone *</label>
                    <input type="text" class="form-control" id="phone" name="phone" required placeholder="Phone number" value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Email address" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label for="subject_id" class="form-label">Subject *</label>
                    <select class="form-control" id="subject_id" name="subject_id" required>
                        <option value="" disabled <?= empty($subject_id) ? 'selected' : '' ?>>Select Subject</option>
                        <?php while ($row = $subjects->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= (isset($subject_id) && $subject_id == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="photo" class="form-label">Photo (jpg, jpeg, png, gif, max 2MB)</label>
                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-4">➕ Add Teacher</button>
        </form>
    </div>

</body>

</html>