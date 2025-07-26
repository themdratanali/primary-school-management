<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
  header('Location: index.php');
  exit;
}

$sql = "SELECT name, photo FROM teachers WHERE photo IS NOT NULL AND photo != '' ORDER BY name";
$result = $conn->query($sql);

$uploadsDir = __DIR__ . '/uploads/teachers/';
$uploadsUrl = 'uploads/teachers/';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Teacher Photos</title>
</head>

<body>

  <h2>Teacher Photos</h2>

  <div>
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div>
          <?php
          $photoFilename = basename($row['photo']);
          $fullPhotoPath = $uploadsDir . $photoFilename;
          $urlPhotoPath = $uploadsUrl . $photoFilename;

          if (!empty($row['photo']) && file_exists($fullPhotoPath)) {
            echo '<img src="' . htmlspecialchars($urlPhotoPath) . '" alt="' . htmlspecialchars($row['name']) . '" style="width:150px;height:150px;object-fit:cover;">';
          } else {
            echo '<img src="uploads/teachers/default-photo.png" alt="No photo available" style="width:150px;height:150px;object-fit:cover;">';
          }
          ?>
          <div><?= htmlspecialchars($row['name']) ?></div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No teacher photos available.</p>
    <?php endif; ?>
  </div>

  <div>
    <a href="admin_dashboard.php">← Back to Dashboard</a>
  </div>

</body>

</html>