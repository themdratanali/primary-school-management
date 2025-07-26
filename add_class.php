<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

// Auto-create `classes` table if it does not exist
$conn->query("
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$message = "";

if (isset($_POST['submit_add'])) {
    $name = trim($_POST['class_name']);
    if ($name !== "") {
        $check = $conn->prepare("SELECT id FROM classes WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "Class already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO classes (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $message = "Class added successfully!";
            } else {
                $message = "Error: " . htmlspecialchars($stmt->error);
            }
        }
        $check->close();
    }
}

if (isset($_POST['submit_edit'])) {
    $id = intval($_POST['edit_id']);
    $new_name = trim($_POST['edit_name']);
    if ($new_name !== "") {
        $check = $conn->prepare("SELECT id FROM classes WHERE name = ? AND id != ?");
        $check->bind_param("si", $new_name, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "Class name already exists!";
        } else {
            $stmt = $conn->prepare("UPDATE classes SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $id);
            if ($stmt->execute()) {
                $message = "Class updated successfully!";
            } else {
                $message = "Error: " . htmlspecialchars($stmt->error);
            }
        }
        $check->close();
    }
}

if (isset($_POST['submit_delete'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Class deleted successfully!";
    } else {
        $message = "Error: " . htmlspecialchars($stmt->error);
    }
}

$class_stmt = $conn->prepare("SELECT * FROM classes ORDER BY name ASC");
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class_count = $class_result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Classes</title>
    <link rel="stylesheet" href="assets/css/add_class.css">

    <script>
        function showEdit(id) {
            document.getElementById('name_display_' + id).style.display = 'none';
            document.getElementById('edit_form_' + id).style.display = 'flex';
        }

        function hideEdit(id) {
            document.getElementById('name_display_' + id).style.display = 'flex';
            document.getElementById('edit_form_' + id).style.display = 'none';
        }
    </script>

</head>

<body>

    <div class="dashboard-title">Manage Classes</div>

    <div class="container">
        <div class="left">
            <h2>Add Class</h2>
            <?php if ($message): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="text" name="class_name" placeholder="Enter Class Name (e.g. Class 1)" required>
                <button type="submit" name="submit_add">Add Class</button>
            </form>
        </div>

        <div class="right">
            <h2>Class List (<?= $class_count ?>)</h2>
            <div class="class-list">
                <ul>
                    <?php if ($class_count > 0): ?>
                        <?php while ($row = $class_result->fetch_assoc()): ?>
                            <li>
                                <div id="name_display_<?= $row['id'] ?>" style="flex-grow:1;">
                                    <?= htmlspecialchars($row['name']) ?>
                                </div>
                                <form id="edit_form_<?= $row['id'] ?>" method="post" style="display:none; flex-grow:1;">
                                    <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                    <input type="text" name="edit_name" value="<?= htmlspecialchars($row['name']) ?>" required>
                                    <button type="submit" name="submit_edit">Save</button>
                                    <button type="button" onclick="hideEdit(<?= $row['id'] ?>)">Cancel</button>
                                </form>
                                <button type="button" onclick="showEdit(<?= $row['id'] ?>)">Edit</button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="submit_delete" style="background:#dc3545;">Delete</button>
                                </form>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>No classes found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

</body>

</html>