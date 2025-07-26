<?php
session_start();
if (!isset($_SESSION['student_email'])) {
    header('Location: index.php');
    exit;
}
include 'db.php';

$email = $_SESSION['student_email'];

$stmt = $conn->prepare("SELECT s.* FROM students s JOIN student_users su ON s.id = su.student_id WHERE su.email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch student results
$result_stmt = $conn->prepare("SELECT sub.name AS subject_name, r.marks FROM results r JOIN subjects sub ON r.subject_id = sub.id WHERE r.student_id = ?");
$result_stmt->bind_param("i", $student['id']);
$result_stmt->execute();
$results = $result_stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Student Dashboard</title>
</head>

<body>
    <h2>Welcome, <?= htmlspecialchars($student['name']) ?></h2>
    <h3>Your Profile</h3>
    <table border="1" cellpadding="5">
        <tr>
            <td>Name</td>
            <td><?= htmlspecialchars($student['name']) ?></td>
        </tr>
        <tr>
            <td>Mother's Name</td>
            <td><?= htmlspecialchars($student['mother_name']) ?></td>
        </tr>
        <tr>
            <td>Father's Name</td>
            <td><?= htmlspecialchars($student['father_name']) ?></td>
        </tr>
        <tr>
            <td>Gender</td>
            <td><?= htmlspecialchars($student['gender']) ?></td>
        </tr>
        <tr>
            <td>DOB</td>
            <td><?= htmlspecialchars($student['dob']) ?></td>
        </tr>
        <tr>
            <td>Roll</td>
            <td><?= htmlspecialchars($student['roll']) ?></td>
        </tr>
        <tr>
            <td>Session</td>
            <td><?= htmlspecialchars($student['session']) ?></td>
        </tr>
        <tr>
            <td>Present Address</td>
            <td><?= htmlspecialchars($student['present_address']) ?></td>
        </tr>
        <tr>
            <td>Permanent Address</td>
            <td><?= htmlspecialchars($student['permanent_address']) ?></td>
        </tr>
    </table>

    <h3>Your Results</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>Subject</th>
            <th>Marks</th>
        </tr>
        <?php while ($r = $results->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($r['subject_name']) ?></td>
                <td><?= htmlspecialchars($r['marks']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <p><a href="student_logout.php">Logout</a></p>
</body>

</html>