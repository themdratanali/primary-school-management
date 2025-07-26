<?php
session_start();
include 'db.php';
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Apex Model School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>

    <div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
    </div>

    <div class="sidebar" id="sidebar">
        <h4>Admin Login</h4>

        <a href="dashboard_home.php" target="mainFrame" class="active">📊 Overview</a>

        <div class="menu-item">
            <a>📚 Academic <span class="toggle-icon">▶</span></a>
            <div class="submenu">
                <a href="add_batch.php" target="mainFrame">Add Batch</a>
                <a href="add_class.php" target="mainFrame">Add Class</a>
                <a href="add_subject.php" target="mainFrame">Add Subject</a>
            </div>
        </div>

        <div class="menu-item">
            <a>👨‍🏫 Teacher <span class="toggle-icon">▶</span></a>
            <div class="submenu">
                <a href="add_teacher.php" target="mainFrame">Add Teacher</a>
                <a href="teachers_list.php" target="mainFrame">Teacher List</a>
            </div>
        </div>

        <div class="menu-item">
            <a>👩‍🎓 Student <span class="toggle-icon">▶</span></a>
            <div class="submenu">
                <a href="add_student.php" target="mainFrame">Add Student</a>
                <a href="view_students.php" target="mainFrame">Student List</a>
            </div>
        </div>

        <a href="admit_card.php" target="mainFrame">🎫 Admit Card</a>

        <div class="menu-item">
            <a>📄 Manage Results <span class="toggle-icon">▶</span></a>
            <div class="submenu">
                <a href="manage_results.php" target="mainFrame">Manage Results</a>
                <a href="marksheet.php" target="mainFrame">Mark Sheet</a>
            </div>
        </div>

        <div class="menu-item">
            <a>💰 Fee Management <span class="toggle-icon">▶</span></a>
            <div class="submenu">
                <a href="fee_receipt.php" target="mainFrame">Fee Management</a>
                <a href="fee_see.php" target="mainFrame">Fee See</a>
            </div>
        </div>

        <a href="promote_students.php" target="mainFrame">🎓 Promote Students</a>

        <div class="logout-link">
            <a href="logout.php">Logout</a>
        </div>

        <div class="footer-credit">
            Developed & Maintained by<br>
            <img src="deppol.png" alt="Deppol">
        </div>
    </div>

    <div class="main-content">
        <iframe name="mainFrame" src="dashboard_home.php"></iframe>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
        }

        document.querySelectorAll('.menu-item > a').forEach(menuToggle => {
            menuToggle.addEventListener('click', () => {
                const menuItem = menuToggle.parentElement;
                menuItem.classList.toggle('open');
                const submenu = menuItem.querySelector('.submenu');
                if (submenu.style.display === "flex") {
                    submenu.style.display = "none";
                } else {
                    submenu.style.display = "flex";
                }
            });
        });

        const links = document.querySelectorAll('.sidebar a[target="mainFrame"]');
        links.forEach(link => {
            link.addEventListener('click', () => {
                links.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });
    </script>

</body>

</html>