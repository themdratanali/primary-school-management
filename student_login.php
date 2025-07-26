<?php
session_start();
include 'db.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM student_users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['student_email'] = $email;
            header('Location: student_dashboard.php');
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Invalid email";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Student Login</title>
</head>

<body>
    <h2>Student Login</h2>
    <form method="post">
        Email:<br>
        <input type="email" name="email" required><br><br>
        Password:<br>
        <input type="password" name="password" required><br><br>
        <button type="submit" name="login">Login</button>
    </form>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>

</html>