<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
  header('Location: index.php');
  exit;
}

$classes = $conn->query("SELECT * FROM classes ORDER BY name");
$batches = $conn->query("SELECT * FROM batches ORDER BY name");

$students = [];
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $batch_id = intval($_POST['batch_id'] ?? 0);
  $class_id = intval($_POST['class_id'] ?? 0);

  if ($batch_id && $class_id) {
    $batch_res = $conn->query("SELECT name FROM batches WHERE id = $batch_id");
    $class_res = $conn->query("SELECT name FROM classes WHERE id = $class_id");

    if ($batch_res && $class_res && $batch_res->num_rows > 0 && $class_res->num_rows > 0) {
      $batch_name = preg_replace('/\s+/', '', $batch_res->fetch_assoc()['name']);
      $class_name = preg_replace('/\s+/', '', $class_res->fetch_assoc()['name']);
      $table_name = "Student_{$batch_name}_{$class_name}";

      $checkTable = $conn->query("SHOW TABLES LIKE '$table_name'");
      if ($checkTable->num_rows > 0) {
        $students_res = $conn->query("SELECT id, name, roll, photo FROM `$table_name` ORDER BY roll ASC");
        if ($students_res) {
          while ($row = $students_res->fetch_assoc()) {
            $students[] = $row + ['table_name' => $table_name];
          }
          $message = "Showing students for Batch: <strong>$batch_name</strong>, Class: <strong>$class_name</strong>";
        } else {
          $errors[] = "Error fetching students: " . $conn->error;
        }
      } else {
        $errors[] = "No student table found for the selected batch and class.";
      }
    } else {
      $errors[] = "Invalid Batch or Class selection.";
    }
  } else {
    $errors[] = "Please select both Batch and Class.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>View Student Profiles</title>
  <link rel="stylesheet" href="assets/css/view_students.css">
</head>

<body>
  <div class="container">
    <h2>View Student Profiles</h2>

    <?php if ($message): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <?php foreach ($errors as $error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>
    <?php endif; ?>

    <form method="post">
      <select name="batch_id" required>
        <option value="">-- Select Batch --</option>
        <?php $batches->data_seek(0);
        while ($b = $batches->fetch_assoc()): ?>
          <option value="<?= $b['id'] ?>" <?= isset($batch_id) && $batch_id == $b['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($b['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <select name="class_id" required>
        <option value="">-- Select Class --</option>
        <?php $classes->data_seek(0);
        while ($c = $classes->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>" <?= isset($class_id) && $class_id == $c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <button type="submit">Show Students</button>
    </form>

    <?php if (!empty($students)): ?>
      <table>
        <thead>
          <tr>
            <th>Roll</th>
            <th>Name</th>
            <th>Photo</th>
            <th>Profile</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $stu): ?>
            <tr>
              <td><?= htmlspecialchars($stu['roll']) ?></td>
              <td>
                <?php
                $photoPath = (!empty($stu['photo']) && file_exists($stu['photo'])) ? $stu['photo'] : 'uploads/students/default-photo.jpg';
                ?>
                <img src="<?= htmlspecialchars($photoPath) ?>" alt="Photo" class="photo-thumb" />
              </td>
              <td><?= htmlspecialchars($stu['name']) ?></td>
              <td>
                <a href="student_profile.php?table=<?= urlencode($stu['table_name']) ?>&id=<?= urlencode($stu['id']) ?>" class="view-profile-link">View Profile</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div id="profileModal">
    <div id="profileModalContent">
      <span id="closeModal">&times;</span>
      <iframe id="modalIframe" src=""></iframe>
    </div>
  </div>

  <script>
    document.querySelectorAll('.view-profile-btn').forEach(button => {
      button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const table = this.getAttribute('data-table');
        const modal = document.getElementById('profileModal');
        const iframe = document.getElementById('modalIframe');
        iframe.src = `student_profile.php?table=${encodeURIComponent(table)}&id=${encodeURIComponent(id)}`;
        modal.style.display = 'flex';
      });
    });

    document.getElementById('closeModal').addEventListener('click', function() {
      const modal = document.getElementById('profileModal');
      const iframe = document.getElementById('modalIframe');
      iframe.src = "";
      modal.style.display = 'none';
    });

    window.addEventListener('keydown', function(e) {
      if (e.key === "Escape") {
        const modal = document.getElementById('profileModal');
        const iframe = document.getElementById('modalIframe');
        iframe.src = "";
        modal.style.display = 'none';
      }
    });
  </script>
</body>

</html>