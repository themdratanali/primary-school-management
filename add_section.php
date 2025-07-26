<?php
session_start();
include 'db.php';
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$classes = $conn->query("SELECT * FROM classes");
$batches = $conn->query("SELECT * FROM batches");

if (isset($_POST['submit'])) {
    $class_id = $_POST['class_id'];
    $batch_id = $_POST['batch_id'];
    $name = $_POST['name'];

    $stmt = $conn->prepare("INSERT INTO sections (class_id, batch_id, name) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $class_id, $batch_id, $name);
    $stmt->execute();
    $message = "Section added successfully";
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Section</title>
</head>

<body>
    <h2>Add Section</h2>
    <form method="post">
        Class:<br>
        <select name="class_id" required>
            <option value="">Select Class</option>
            <?php while ($row = $classes->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        Batch:<br>
        <select name="batch_id" required>
            <option value="">Select Batch</option>
            <?php while ($row = $batches->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        Section Name:<br>
        <input type="text" name="name" required><br><br>

        <button type="submit" name="submit">Add Section</button>
    </form>
    <?php if (isset($message)) echo "<p style='color:green;'>$message</p>"; ?>
    <p><a href="admin_dashboard.php">Back to Dashboard</a></p>
</body>

</html>