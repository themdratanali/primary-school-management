<?php
session_start();
include 'db.php';
if (!isset($_SESSION['admin'])) {
  header('Location: index.php');
  exit;
}

$sql = "SELECT t.id, t.name, t.email, t.phone, t.photo, s.name AS subject_name 
        FROM teachers t
        LEFT JOIN subjects s ON t.subject_id = s.id
        ORDER BY t.name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Teacher List</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: #f4f6f9;
      min-height: 100vh;
      padding: 30px 15px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .dashboard-title {
      font-size: 28px;
      color: #333;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 600;
    }

    .container {
      width: 95%;
      max-width: 1100px;
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 30px;
      overflow-x: auto;
    }

    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 25px;
      font-weight: 600;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
    }

    th,
    td {
      padding: 14px 18px;
      text-align: left;
      color: #333;
      font-size: 15px;
    }

    th {
      background: #f0f2f5;
      font-weight: 600;
    }

    tr {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    tr:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      background: #f9fafc;
    }

    a {
      color: #4a6ee0;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    a:hover {
      color: #2743b3;
    }

    .photo-thumb {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #ddd;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .photo-thumb:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 600px) {

      th,
      td {
        padding: 10px 12px;
        font-size: 14px;
      }

      .photo-thumb {
        width: 45px;
        height: 45px;
      }
    }
  </style>

</head>

<body>
  <div class="container">
    <h2>All Teachers</h2>
    <table>
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Subject</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()):
            $photo = (!empty($row['photo']) && file_exists($row['photo'])) ? $row['photo'] : 'uploads/teachers/default-photo.jpg';
          ?>
            <tr>
              <td><img src="<?= htmlspecialchars($photo) ?>" alt="Photo" class="photo-thumb"></td>
              <td><a href="teacher_profile.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['phone']) ?></td>
              <td><?= htmlspecialchars($row['subject_name'] ?? 'N/A') ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center;">No teachers found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</body>

</html>