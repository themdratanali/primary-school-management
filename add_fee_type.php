<?php
session_start();
include 'db.php';
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$batches = $conn->query("SELECT id, name FROM batches ORDER BY name");
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

$fee_type_options = [
    'Exam Fee',
    'Admission Fee',
    'Monthly Fee',
    'Travel Fee',
    'Other Fee'
];

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batch_id = intval($_POST['batch_id']);
    $class_id = intval($_POST['class_id']);
    $fee_type = $_POST['fee_type'] ?? '';
    $amount = floatval($_POST['amount']);
    if ($batch_id && $class_id && $fee_type && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO fee_types (batch_id, class_id, name, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisd", $batch_id, $class_id, $fee_type, $amount);
        if ($stmt->execute()) {
            $message = "✅ Fee type added successfully.";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "❌ Please fill all fields with valid data.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Add Fee Type</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="assets/css/add_fee_type.css">

    <script>
        $(function() {
            const feeAmounts = {
                "Exam Fee": 500,
                "Admission Fee": 1000,
                "Monthly Fee": 300,
                "Travel Fee": 200,
                "Other Fee": 0
            };
            $('select[name="fee_type"]').on('change', function() {
                let selected = $(this).val();
                let amount = feeAmounts[selected] ?? 0;
                $('input[name="amount"]').val(amount);
            });
        });
    </script>

</head>

<body>
    <h2>Add Fee Type</h2>
    <?php if ($message) echo "<p>$message</p>"; ?>
    <form method="post">
        <label>Select Batch:</label>
        <select name="batch_id" required>
            <option value="">-- Select Batch --</option>
            <?php while ($b = $batches->fetch_assoc()): ?>
                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Select Class:</label>
        <select name="class_id" required>
            <option value="">-- Select Class --</option>
            <?php while ($c = $classes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Select Fee Type:</label>
        <select name="fee_type" required>
            <option value="">-- Select Fee Type --</option>
            <?php foreach ($fee_type_options as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Amount (Tk):</label>
        <input type="number" name="amount" step="0.01" required>

        <button type="submit">Add Fee Type</button>
    </form>
</body>

</html>