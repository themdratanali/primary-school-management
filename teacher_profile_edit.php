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

$sql = "SELECT * FROM teachers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Teacher not found.");
}

$photo = (!empty($data['photo']) && file_exists($data['photo'])) ? $data['photo'] : 'uploads/teachers/default-photo.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $birth_cert_no = $_POST['birth_cert_no'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';
    $religion = $_POST['religion'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $nid = $_POST['nid'] ?? '';
    $present_address = $_POST['present_address'] ?? '';
    $permanent_address = $_POST['permanent_address'] ?? '';
    $education = $_POST['education'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject_id = $_POST['subject_id'] ?? null;

    if (!empty($_FILES['photo']['name'])) {
        $photoName = 'uploads/teachers/' . time() . '_' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoName);
    } else {
        $photoName = $data['photo'];
    }

    $update_sql = "UPDATE teachers SET name=?, gender=?, mother_name=?, father_name=?, dob=?, birth_cert_no=?, blood_group=?, religion=?, nationality=?, nid=?, present_address=?, permanent_address=?, education=?, experience=?, phone=?, email=?, subject_id=?, photo=? WHERE id=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssssssssssssssssi", $name, $gender, $mother_name, $father_name, $dob, $birth_cert_no, $blood_group, $religion, $nationality, $nid, $present_address, $permanent_address, $education, $experience, $phone, $email, $subject_id, $photoName, $id);

    if ($update_stmt->execute()) {
        header("Location: teacher_profile.php?id=$id");
        exit;
    } else {
        echo "Error updating teacher profile.";
    }
}

$subjects_result = $conn->query("SELECT id, name FROM subjects ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Teacher Profile - <?= htmlspecialchars($data['name']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f0f0;
            margin: 0;
        }

        .container {
            max-width: 700px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }

        .container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        form label {
            font-weight: bold;
        }

        form input[type="text"],
        form input[type="date"],
        form input[type="email"],
        form textarea,
        form select {
            width: 100%;
            padding: 8px;
            margin: 5px 0 12px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        form textarea {
            resize: vertical;
        }

        form button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 16px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        form button:hover {
            background: #0056b3;
        }

        .photo-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            display: block;
            margin: 0 auto 15px auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Edit Teacher Profile</h2>
        <img src="<?= htmlspecialchars($photo) ?>" alt="Teacher Photo" class="photo-preview">
        <form method="post" enctype="multipart/form-data">
            <label>Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" required>

            <label>Gender:</label>
            <input type="text" name="gender" value="<?= htmlspecialchars($data['gender']) ?>">

            <label>Mother's Name:</label>
            <input type="text" name="mother_name" value="<?= htmlspecialchars($data['mother_name']) ?>">

            <label>Father's Name:</label>
            <input type="text" name="father_name" value="<?= htmlspecialchars($data['father_name']) ?>">

            <label>Date of Birth:</label>
            <input type="date" name="dob" value="<?= htmlspecialchars($data['dob']) ?>">

            <label>Birth Certificate No:</label>
            <input type="text" name="birth_cert_no" value="<?= htmlspecialchars($data['birth_cert_no']) ?>">

            <label>Blood Group:</label>
            <input type="text" name="blood_group" value="<?= htmlspecialchars($data['blood_group']) ?>">

            <label>Religion:</label>
            <input type="text" name="religion" value="<?= htmlspecialchars($data['religion']) ?>">

            <label>Nationality:</label>
            <input type="text" name="nationality" value="<?= htmlspecialchars($data['nationality']) ?>">

            <label>NID:</label>
            <input type="text" name="nid" value="<?= htmlspecialchars($data['nid']) ?>">

            <label>Present Address:</label>
            <textarea name="present_address" rows="2"><?= htmlspecialchars($data['present_address']) ?></textarea>

            <label>Permanent Address:</label>
            <textarea name="permanent_address" rows="2"><?= htmlspecialchars($data['permanent_address']) ?></textarea>

            <label>Education:</label>
            <textarea name="education" rows="2"><?= htmlspecialchars($data['education']) ?></textarea>

            <label>Experience:</label>
            <textarea name="experience" rows="2"><?= htmlspecialchars($data['experience']) ?></textarea>

            <label>Phone:</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($data['phone']) ?>">

            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>">

            <label>Subject:</label>
            <select name="subject_id">
                <option value="">Select Subject</option>
                <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                    <option value="<?= $subject['id'] ?>" <?= ($subject['id'] == $data['subject_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Change Photo (optional):</label>
            <input type="file" name="photo" accept="image/*">

            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>

</html>