<?php
session_start();
include 'db.php';

// Auto-create tables if they do not exist
$conn->query("
    CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
    CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(100) NOT NULL,
        total_mark INT NOT NULL,
        class_id INT NOT NULL,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_subject_per_class (name, class_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$message = "";
$selected_class = isset($_POST['class_id']) ? $_POST['class_id'] : '';

$classes = $conn->query("SELECT * FROM classes");

if (isset($_POST['submit_add'])) {
    $class_id = $_POST['class_id'];
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $total_mark = trim($_POST['total_mark']);

    if ($name != "" && $class_id != "" && $code != "" && $total_mark != "") {
        $check = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND class_id = ?");
        $check->bind_param("si", $name, $class_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "Subject already exists in this class!";
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (name, code, total_mark, class_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $name, $code, $total_mark, $class_id);
            if ($stmt->execute()) {
                $message = "Subject added successfully!";
            } else {
                $message = "Error: " . $stmt->error;
            }
        }
        $check->close();
    } else {
        $message = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Subject</title>
    <link rel="stylesheet" href="assets/css/add_subject.css">
</head>

<body>

    <div class="dashboard-title">Add Subject</div>

    <div class="container">
        <div class="left">
            <h2>Add Subject</h2>
            <?php if ($message): ?>
                <p class="message"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>
            <form method="post">
                <select name="class_id" class="classSelect" id="classSelect" onchange="loadSubjects()" required>
                    <option value="">Select Class</option>
                    <?php $classes->data_seek(0);
                    while ($row = $classes->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($row['id'] == $selected_class) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="name" placeholder="Enter Subject Name" required />
                <input type="text" name="code" placeholder="Enter Subject Code" required />
                <input type="number" name="total_mark" placeholder="Enter Total Mark" required />
                <button type="submit" name="submit_add">Add Subject</button>
            </form>
        </div>

        <div class="right">
            <div class="batch-count" id="subjectCount">Select a class to view subjects</div>
            <div class="batch-list">
                <ul id="subjectList"></ul>
            </div>
        </div>
    </div>

    <script>
        function loadSubjects() {
            const classId = document.getElementById("classSelect").value;
            const countBox = document.getElementById("subjectCount");
            const listBox = document.getElementById("subjectList");

            if (classId === "") {
                countBox.textContent = "Select a class to view subjects";
                listBox.innerHTML = "";
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "get_subjects_class.php?class_id=" + classId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        countBox.textContent = "Total Subjects: " + response.count;
                        listBox.innerHTML = "";
                        if (response.count > 0) {
                            response.subjects.forEach(function(sub) {
                                const li = document.createElement("li");
                                li.innerHTML = `
                            <div id="display_${sub.id}">
                                ${sub.name} | Code: ${sub.code} | Mark: ${sub.total_mark}
                                <button class="edit-btn" onclick="editSubject(${sub.id}, '${sub.name}', '${sub.code}', ${sub.total_mark})">Edit</button>
                                <button class="cancel-btn" onclick="deleteSubject(${sub.id})" style="margin-left:10px;">Delete</button>
                            </div>
                            <div id="edit_${sub.id}" style="display:none;">
                                <input type="text" id="name_${sub.id}" class="inline-input" value="${sub.name}" placeholder="Name">
                                <input type="text" id="code_${sub.id}" class="inline-input" value="${sub.code}" placeholder="Code">
                                <input type="number" id="mark_${sub.id}" class="inline-input" value="${sub.total_mark}" placeholder="Mark">
                                <button class="save-btn" onclick="saveSubject(${sub.id})">Save</button>
                                <button class="cancel-btn" onclick="cancelEdit(${sub.id})">Cancel</button>
                            </div>
                        `;
                                listBox.appendChild(li);
                            });
                        } else {
                            listBox.innerHTML = "<li>No subjects found.</li>";
                        }
                    } catch (e) {
                        countBox.textContent = "Error loading subjects";
                        listBox.innerHTML = "";
                    }
                }
            };
            xhr.send();
        }

        function editSubject(id) {
            document.getElementById(`display_${id}`).style.display = "none";
            document.getElementById(`edit_${id}`).style.display = "block";
        }

        function cancelEdit(id) {
            document.getElementById(`edit_${id}`).style.display = "none";
            document.getElementById(`display_${id}`).style.display = "block";
        }

        function saveSubject(id) {
            const name = document.getElementById(`name_${id}`).value.trim();
            const code = document.getElementById(`code_${id}`).value.trim();
            const total_mark = document.getElementById(`mark_${id}`).value.trim();

            if (name === "" || code === "" || total_mark === "") {
                alert("All fields are required.");
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "update_subject.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        alert(response.message);
                        if (response.success) {
                            loadSubjects();
                        }
                    } catch (e) {
                        alert("Error updating subject.");
                    }
                }
            };
            xhr.send(`id=${id}&name=${encodeURIComponent(name)}&code=${encodeURIComponent(code)}&total_mark=${encodeURIComponent(total_mark)}`);
        }

        function deleteSubject(id) {
            if (!confirm("Are you sure you want to delete this subject? This action cannot be undone.")) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "delete_subject.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        alert(response.message);
                        if (response.success) {
                            loadSubjects();
                        }
                    } catch (e) {
                        alert("Error deleting subject.");
                    }
                }
            };
            xhr.send(`id=${id}`);
        }

        window.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById("classSelect").value !== "") {
                loadSubjects();
            }
        });
    </script>

</body>

</html>