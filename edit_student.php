<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
  header('Location: index.php');
  exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("Student ID missing or invalid");
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
  die("Student not found");
}

$classes = $conn->query("SELECT * FROM classes");
$batches = $conn->query("SELECT * FROM batches");

$message = '';

if (isset($_POST['submit'])) {
  $name = $_POST['name'];
  $mother_name = $_POST['mother_name'];
  $father_name = $_POST['father_name'];
  $gender = $_POST['gender'];
  $dob = $_POST['dob'];
  $birth_cert_no = $_POST['birth_cert_no'];
  $blood_group = $_POST['blood_group'];
  $religion = $_POST['religion'];
  $nationality = $_POST['nationality'];
  $nid = $_POST['nid'];
  $present_address = $_POST['present_address'];
  $permanent_address = $_POST['permanent_address'];
  $roll = $_POST['roll'];
  $batch_id = $_POST['batch_id'];
  $class_id = $_POST['class_id'];
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
  if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $newPhoto = 'uploads/students/' . uniqid() . '.' . $ext;
    if (!is_dir('uploads/students')) {
      mkdir('uploads/students', 0777, true);
    }
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $newPhoto)) {
      if (!empty($photo) && file_exists($photo) && $photo !== 'uploads/students/default-photo.jpg') {
        unlink($photo);
      }
      $photo = $newPhoto;
    }
  }

  $stmt = $conn->prepare("UPDATE students SET 
        name=?, mother_name=?, father_name=?, gender=?, dob=?, birth_cert_no=?, blood_group=?, religion=?, nationality=?, nid=?,
        present_address=?, permanent_address=?, roll=?, batch_id=?, class_id=?, student_email=?, father_email=?, mother_email=?, guardian_email=?,
        signature=?, guardian_name=?, guardian_profession=?, guardian_organization=?, guardian_relation=?, guardian_mobile=?, guardian_address=?, photo=?
        WHERE id=?");

  $stmt->bind_param(
    "ssssssssssssiiisssssssssssi",
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
    $roll,
    $batch_id,
    $class_id,
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
    $message = "Student information updated successfully.";
    header("Location: edit_student.php?id=$id&updated=1");
    exit;
  } else {
    $message = "Error updating student: " . $stmt->error;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Edit Student</title>
</head>

<body>

  <h1>Edit Student</h1>

  <?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">

    <label>Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required><br>

    <label>Mother's Name:</label>
    <input type="text" name="mother_name" value="<?= htmlspecialchars($student['mother_name']) ?>" required><br>

    <label>Father's Name:</label>
    <input type="text" name="father_name" value="<?= htmlspecialchars($student['father_name']) ?>" required><br>

    <label>Gender:</label>
    <select name="gender" required>
      <option value="">Select</option>
      <option value="Male" <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
      <option value="Female" <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
      <option value="Other" <?= $student['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
    </select><br>

    <label>Date of Birth:</label>
    <input type="date" name="dob" value="<?= htmlspecialchars($student['dob']) ?>" required><br>

    <label>Birth Certificate No.:</label>
    <input type="text" name="birth_cert_no" value="<?= htmlspecialchars($student['birth_cert_no']) ?>"><br>

    <label>Blood Group:</label>
    <input type="text" name="blood_group" value="<?= htmlspecialchars($student['blood_group']) ?>"><br>

    <label>Religion:</label>
    <input type="text" name="religion" value="<?= htmlspecialchars($student['religion']) ?>"><br>

    <label>Nationality:</label>
    <input type="text" name="nationality" value="<?= htmlspecialchars($student['nationality']) ?>"><br>

    <label>NID:</label>
    <input type="text" name="nid" value="<?= htmlspecialchars($student['nid']) ?>"><br>

    <label>Present Address:</label>
    <textarea name="present_address" required><?= htmlspecialchars($student['present_address']) ?></textarea><br>

    <label>Permanent Address:</label>
    <textarea name="permanent_address" required><?= htmlspecialchars($student['permanent_address']) ?></textarea><br>

    <label>Roll:</label>
    <input type="text" name="roll" value="<?= htmlspecialchars($student['roll']) ?>" required><br>

    <label>Batch:</label>
    <select name="batch_id" required>
      <option value="">Select Batch</option>
      <?php while ($batch = $batches->fetch_assoc()): ?>
        <option value="<?= $batch['id'] ?>" <?= $student['batch_id'] == $batch['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($batch['name']) ?>
        </option>
      <?php endwhile; ?>
    </select><br>

    <label>Class:</label>
    <select name="class_id" required>
      <option value="">Select Class</option>
      <?php while ($class = $classes->fetch_assoc()): ?>
        <option value="<?= $class['id'] ?>" <?= $student['class_id'] == $class['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($class['name']) ?>
        </option>
      <?php endwhile; ?>
    </select><br>

    <label>Student Email:</label>
    <input type="email" name="student_email" value="<?= htmlspecialchars($student['student_email']) ?>" required><br>

    <label>Father Email:</label>
    <input type="email" name="father_email" value="<?= htmlspecialchars($student['father_email']) ?>"><br>

    <label>Mother Email:</label>
    <input type="email" name="mother_email" value="<?= htmlspecialchars($student['mother_email']) ?>"><br>

    <label>Guardian Email:</label>
    <input type="email" name="guardian_email" value="<?= htmlspecialchars($student['guardian_email']) ?>"><br>

    <label>Signature:</label>
    <textarea name="signature"><?= htmlspecialchars($student['signature']) ?></textarea><br>

    <h3>Local Guardian Details</h3>

    <label>Guardian Name:</label>
    <input type="text" name="guardian_name" value="<?= htmlspecialchars($student['guardian_name']) ?>"><br>

    <label>Guardian Profession:</label>
    <input type="text" name="guardian_profession" value="<?= htmlspecialchars($student['guardian_profession']) ?>"><br>

    <label>Guardian Organization:</label>
    <input type="text" name="guardian_organization" value="<?= htmlspecialchars($student['guardian_organization']) ?>"><br>

    <label>Guardian Relation:</label>
    <input type="text" name="guardian_relation" value="<?= htmlspecialchars($student['guardian_relation']) ?>"><br>

    <label>Guardian Mobile:</label>
    <input type="text" name="guardian_mobile" value="<?= htmlspecialchars($student['guardian_mobile']) ?>"><br>

    <label>Guardian Address:</label>
    <textarea name="guardian_address"><?= htmlspecialchars($student['guardian_address']) ?></textarea><br>

    <label>Photo:</label><br>
    <?php
    $photo = (!empty($student['photo']) && file_exists($student['photo'])) ? $student['photo'] : 'uploads/students/default-photo.jpg';
    ?>
    <img src="<?= htmlspecialchars($photo) ?>" alt="Photo" width="150" style="border:1px solid #ccc; padding:5px;"><br>
    <input type="file" name="photo" accept="image/*"><br><br>

    <button type="submit" name="submit">Update Student</button>
  </form>

  <p><a href="student_profile.php?id=<?= $student['id'] ?>">← Back to Profile</a></p>

</body>

</html>