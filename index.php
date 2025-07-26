<?php
session_start();
include 'db.php';

$conn->query("
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    number VARCHAR(20),
    photo VARCHAR(255) DEFAULT 'default.png',
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$check_admin = $conn->query("SELECT COUNT(*) as total FROM admins");
$total = $check_admin->fetch_assoc()['total'];

if ($total == 0) {
    $default_name = 'Admin';
    $default_email = 'admin@yourdomain.com';
    $default_number = '0123456789';
    $default_photo = 'default.png';
    $default_username = 'admin';
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admins (name, email, number, photo, username, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $default_name, $default_email, $default_number, $default_photo, $default_username, $default_password);
    $stmt->execute();
}

if (isset($_POST['login'])) {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin'] = true;
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_number'] = $admin['number'];
            $_SESSION['admin_photo'] = $admin['photo'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login - Apex Model School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
    <div class="login-container">
        <img src="assets/img/logo.png" alt="Admin Logo" class="logo">

        <?php if (isset($error)) echo "<div class='error'>" . htmlspecialchars($error) . "</div>"; ?>

        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <div class="footer"> Developed & Maintained by<br> <img src="assets/img/deppol.png" alt="Deppol Logo">
        </div>
    </div>

</body>

</html>