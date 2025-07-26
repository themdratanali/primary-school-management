<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
  header('Location: index.php');
  exit;
}

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$classes = $conn->query("SELECT * FROM classes ORDER BY name");
$batches = $conn->query("SELECT * FROM batches ORDER BY name");

$message = '';
$errors = [];

$name = $mother_name = $father_name = $gender = $dob = $birth_cert_no = $blood_group = $religion = $nationality = $nid = '';
$present_address = $permanent_address = $roll = $batch_id = $class_id = $session = '';
$student_email = $father_email = $mother_email = $guardian_email = $signature = '';
$guardian_name = $guardian_profession = $guardian_organization = $guardian_relation = $guardian_mobile = $guardian_address = '';

$checkBase = $conn->query("SHOW TABLES LIKE 'students'");
if ($checkBase->num_rows == 0) {
  $createBaseSQL = "CREATE TABLE `students` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(255) NOT NULL,
      `mother_name` VARCHAR(255) NOT NULL,
      `father_name` VARCHAR(255) NOT NULL,
      `gender` VARCHAR(10) NOT NULL,
      `dob` DATE NOT NULL,
      `birth_cert_no` VARCHAR(100),
      `blood_group` VARCHAR(10),
      `religion` VARCHAR(100),
      `nationality` VARCHAR(100),
      `nid` VARCHAR(100),
      `present_address` TEXT NOT NULL,
      `permanent_address` TEXT NOT NULL,
      `roll` INT,
      `batch_id` INT,
      `class_id` INT,
      `session` VARCHAR(50),
      `student_email` VARCHAR(255) NOT NULL,
      `father_email` VARCHAR(255),
      `mother_email` VARCHAR(255),
      `guardian_email` VARCHAR(255),
      `signature` TEXT,
      `guardian_name` VARCHAR(255),
      `guardian_profession` VARCHAR(255),
      `guardian_organization` VARCHAR(255),
      `guardian_relation` VARCHAR(255),
      `guardian_mobile` VARCHAR(50),
      `guardian_address` TEXT,
      `photo` VARCHAR(255),
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

  if (!$conn->query($createBaseSQL)) {
    die("Error creating base `students` table: " . $conn->error);
  }
}

if (!empty($_POST['batch_id']) && !empty($_POST['class_id'])) {
  $batch_id = intval($_POST['batch_id']);
  $class_id = intval($_POST['class_id']);

  $batch_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
  $class_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");

  if ($batch_res && $class_res && $batch_res->num_rows > 0 && $class_res->num_rows > 0) {
    $batch_name = preg_replace('/\s+/', '', $batch_res->fetch_assoc()['name']);
    $class_name = preg_replace('/\s+/', '', $class_res->fetch_assoc()['name']);
    $table_name = "Student_{$batch_name}_{$class_name}";

    $create_sql = "CREATE TABLE IF NOT EXISTS `$table_name` LIKE `students`";
    if ($conn->query($create_sql)) {
      $message = "✅ Table `$table_name` is ready for inserting students.";
    } else {
      $errors[] = "❌ Table creation failed: " . $conn->error;
    }
  } else {
    $errors[] = "❌ Invalid Batch or Class for table creation.";
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $errors[] = "Invalid CSRF token.";
  } else {
    $name = trim($_POST['name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $birth_cert_no = trim($_POST['birth_cert_no'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $present_address = trim($_POST['present_address'] ?? '');
    $permanent_address = trim($_POST['permanent_address'] ?? '');
    $roll = intval($_POST['roll'] ?? 0);
    $batch_id = intval($_POST['batch_id'] ?? 0);
    $class_id = intval($_POST['class_id'] ?? 0);
    $session = trim($_POST['session'] ?? '');
    $student_email = trim($_POST['student_email'] ?? '');
    $father_email = trim($_POST['father_email'] ?? '');
    $mother_email = trim($_POST['mother_email'] ?? '');
    $guardian_email = trim($_POST['guardian_email'] ?? '');
    $signature = trim($_POST['signature'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_profession = trim($_POST['guardian_profession'] ?? '');
    $guardian_organization = trim($_POST['guardian_organization'] ?? '');
    $guardian_relation = trim($_POST['guardian_relation'] ?? '');
    $guardian_mobile = trim($_POST['guardian_mobile'] ?? '');
    $guardian_address = trim($_POST['guardian_address'] ?? '');

    if (!filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Invalid student email format.";
    }
    foreach (['father_email', 'mother_email', 'guardian_email'] as $field) {
      if (!empty($$field) && !filter_var($$field, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid format for {$field}.";
      }
    }

    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['jpg', 'jpeg', 'png'];
      $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed)) {
        $errors[] = "Invalid photo format.";
      } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        $errors[] = "Photo exceeds size limit.";
      } else {
        if (!is_dir('uploads/students')) mkdir('uploads/students', 0777, true);
        $photo = 'uploads/students/' . uniqid('student_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo)) {
          $errors[] = "Photo upload failed.";
          $photo = '';
        }
      }
    }

    if (empty($errors)) {
      $batch_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
      $class_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");

      if ($batch_res && $class_res && $batch_res->num_rows > 0 && $class_res->num_rows > 0) {
        $batch_name = preg_replace('/\s+/', '', $batch_res->fetch_assoc()['name']);
        $class_name = preg_replace('/\s+/', '', $class_res->fetch_assoc()['name']);
        $table_name = "Student_{$batch_name}_{$class_name}";

        $stmt = $conn->prepare("INSERT INTO `$table_name` (name, mother_name, father_name, gender, dob, birth_cert_no, blood_group, religion, nationality, nid, present_address, permanent_address, roll, batch_id, class_id, session, student_email, father_email, mother_email, guardian_email, signature, guardian_name, guardian_profession, guardian_organization, guardian_relation, guardian_mobile, guardian_address, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
          $stmt->bind_param(
            "ssssssssssssiiisssssssssssss",
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
            $session,
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
            $photo
          );

          if ($stmt->execute()) {
            $message = "✅ Student added to table `$table_name` successfully.";

            $excelFile = 'uploads/excel/' . $table_name . '.xlsx';
            if (!is_dir('uploads/excel')) mkdir('uploads/excel', 0777, true);

            if (file_exists($excelFile)) {
              $spreadsheet = IOFactory::load($excelFile);
              $sheet = $spreadsheet->getActiveSheet();
              $row = $sheet->getHighestRow() + 1;
            } else {
              $spreadsheet = new Spreadsheet();
              $sheet = $spreadsheet->getActiveSheet();
              $headers = ['Name', 'Mother Name', 'Father Name', 'Gender', 'DOB', 'Birth Cert No', 'Blood Group', 'Religion', 'Nationality', 'NID', 'Present Address', 'Permanent Address', 'Roll', 'Batch ID', 'Class ID', 'Session', 'Student Email', 'Father Email', 'Mother Email', 'Guardian Email', 'Signature', 'Guardian Name', 'Guardian Profession', 'Guardian Organization', 'Guardian Relation', 'Guardian Mobile', 'Guardian Address', 'Photo', 'Added At'];
              $sheet->fromArray($headers, NULL, 'A1');
              $row = 2;
            }

            $data = [
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
              $session,
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
              date('Y-m-d H:i:s')
            ];
            $sheet->fromArray($data, NULL, 'A' . $row);
            $writer = new Xlsx($spreadsheet);
            $writer->save($excelFile);
          } else {
            $errors[] = "Database error: " . $stmt->error;
          }
          $stmt->close();
        } else {
          $errors[] = "Statement prepare error: " . $conn->error;
        }
      } else {
        $errors[] = "Invalid batch or class selection.";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Add Student</title>
  <link rel="stylesheet" href="assets/css/add_student.css">
</head>

<body>
  <div class="container">
    <h2>Student Registration Form</h2>

    <?php if ($message): ?>
      <p class="message-success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="message-error">
        <?php foreach ($errors as $err): ?>
          <div><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <fieldset>
        <legend>Personal Information</legend>
        <div class="row">
          <div><label for="name">Name *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
          </div>
          <div><label for="gender">Gender *</label>
            <select id="gender" name="gender" required>
              <option value="">Select Gender</option>
              <option value="Male" <?= ($gender ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= ($gender ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
              <option value="Other" <?= ($gender ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
          </div>
        </div>

        <div class="row">
          <div><label for="mother_name">Mother's Name *</label>
            <input type="text" id="mother_name" name="mother_name" value="<?= htmlspecialchars($mother_name ?? '') ?>" required>
          </div>
          <div><label for="father_name">Father's Name *</label>
            <input type="text" id="father_name" name="father_name" value="<?= htmlspecialchars($father_name ?? '') ?>" required>
          </div>
        </div>

        <div class="row">
          <div><label for="dob">Date of Birth *</label>
            <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($dob ?? '') ?>" required>
          </div>
          <div><label for="birth_cert_no">Birth Certificate No.</label>
            <input type="text" id="birth_cert_no" name="birth_cert_no" value="<?= htmlspecialchars($birth_cert_no ?? '') ?>">
          </div>
        </div>

        <div class="row">
          <div><label for="blood_group">Blood Group</label>
            <input type="text" id="blood_group" name="blood_group" value="<?= htmlspecialchars($blood_group ?? '') ?>">
          </div>
          <div><label for="religion">Religion</label>
            <input type="text" id="religion" name="religion" value="<?= htmlspecialchars($religion ?? '') ?>">
          </div>
        </div>

        <div class="row">
          <div><label for="nationality">Nationality</label>
            <input type="text" id="nationality" name="nationality" value="<?= htmlspecialchars($nationality ?? '') ?>">
          </div>
          <div><label for="nid">NID</label>
            <input type="text" id="nid" name="nid" value="<?= htmlspecialchars($nid ?? '') ?>">
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Address</legend>
        <label for="present_address">Present Address *</label>
        <textarea id="present_address" name="present_address" required><?= htmlspecialchars($present_address ?? '') ?></textarea>

        <label for="permanent_address">Permanent Address *</label>
        <textarea id="permanent_address" name="permanent_address" required><?= htmlspecialchars($permanent_address ?? '') ?></textarea>
      </fieldset>

      <fieldset>
        <legend>Academic Information</legend>
        <label>Batch:</label>
        <select name="batch_id" id="batch_id" required>
          <option value="">Select Batch</option>
          <?php
          $batches->data_seek(0);
          while ($b = $batches->fetch_assoc()):
          ?>
            <option value="<?= $b['id'] ?>" <?= ($batch_id ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endwhile; ?>
        </select>

        <label>Class:</label>
        <select name="class_id" id="class_id" required>
          <option value="">Select Class</option>
          <?php
          $classes->data_seek(0);
          while ($c = $classes->fetch_assoc()):
          ?>
            <option value="<?= $c['id'] ?>" <?= ($class_id ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endwhile; ?>
        </select>

        <label>Roll:</label>
        <input type="number" name="roll" id="roll" readonly placeholder="Auto-generated" value="<?= htmlspecialchars($roll ?? '') ?>">
      </fieldset>

      <fieldset>
        <legend>Contact Information</legend>
        <label for="student_email">Student Email *</label>
        <input type="email" id="student_email" name="student_email" value="<?= htmlspecialchars($student_email ?? '') ?>" required />

        <label for="father_email">Father Email</label>
        <input type="email" id="father_email" name="father_email" value="<?= htmlspecialchars($father_email ?? '') ?>" />

        <label for="mother_email">Mother Email</label>
        <input type="email" id="mother_email" name="mother_email" value="<?= htmlspecialchars($mother_email ?? '') ?>" />

        <label for="guardian_email">Guardian Email</label>
        <input type="email" id="guardian_email" name="guardian_email" value="<?= htmlspecialchars($guardian_email ?? '') ?>" />

        <label for="signature">Signature</label>
        <textarea id="signature" name="signature"><?= htmlspecialchars($signature ?? '') ?></textarea>
      </fieldset>

      <fieldset>
        <legend>Local Guardian</legend>
        <label for="guardian_name">Guardian Name</label>
        <input type="text" id="guardian_name" name="guardian_name" value="<?= htmlspecialchars($guardian_name ?? '') ?>" />

        <label for="guardian_profession">Guardian Profession</label>
        <input type="text" id="guardian_profession" name="guardian_profession" value="<?= htmlspecialchars($guardian_profession ?? '') ?>" />

        <label for="guardian_organization">Guardian Organization</label>
        <input type="text" id="guardian_organization" name="guardian_organization" value="<?= htmlspecialchars($guardian_organization ?? '') ?>" />

        <label for="guardian_relation">Guardian Relation</label>
        <input type="text" id="guardian_relation" name="guardian_relation" value="<?= htmlspecialchars($guardian_relation ?? '') ?>" />

        <label for="guardian_mobile">Guardian Mobile</label>
        <input type="text" id="guardian_mobile" name="guardian_mobile" value="<?= htmlspecialchars($guardian_mobile ?? '') ?>" />

        <label for="guardian_address">Guardian Address</label>
        <textarea id="guardian_address" name="guardian_address"><?= htmlspecialchars($guardian_address ?? '') ?></textarea>
      </fieldset>

      <fieldset>
        <legend>Photo</legend>
        <label for="photo">Upload Photo</label>
        <input type="file" id="photo" name="photo" accept="image/*" />
      </fieldset>

      <button class="generatebutton" type="submit" name="submit">Add Student</button>
    </form>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function() {
      function fetchNextRoll() {
        let batchId = $('#batch_id').val();
        let classId = $('#class_id').val();

        if (batchId && classId) {
          $.ajax({
            url: 'get_next_roll.php',
            type: 'POST',
            data: {
              batch_id: batchId,
              class_id: classId
            },
            success: function(response) {
              $('#roll').val(response);
            }
          });
        } else {
          $('#roll').val('');
        }
      }
      $('#batch_id, #class_id').on('change', fetchNextRoll);
      fetchNextRoll();
    });
  </script>

</body>

</html>