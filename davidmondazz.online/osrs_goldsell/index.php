<?php
// Database connection
$host = 'localhost';
$db = 'mcgkxyz_osrs_sellgold';
$user = 'mcgkxyz_masterpop';
$pass = 'aA0109587045';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch EGP to USD rate from database
$result = $conn->query("SELECT egp_to_usd FROM settings LIMIT 1");
$row = $result->fetch_assoc();
$egp_to_usd = $row['egp_to_usd'];

// Initialize variables
$price = $amount = $fees_percent = $fixed_fees = $total_sell = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['price'];
    $amount = $_POST['amount'];
    $fees_percent = isset($_POST['fees_percent']) && is_numeric($_POST['fees_percent']) ? $_POST['fees_percent'] : 0; // Default to 0
    $fixed_fees = isset($_POST['fixed_fees']) && is_numeric($_POST['fixed_fees']) ? $_POST['fixed_fees'] : 0;       // Default to 0

    // Calculate total sell
    $total_fees = ($price * $amount * ($fees_percent / 100)) + $fixed_fees;
    $total_sell = ($price * $amount) - $total_fees;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSRS Gold Selling Calculator</title>
    <style>
       a {
        color: inhert;
        text-decoration: none; /* no underline */
        }
    </style>
</head>
<body>
    <h1><a href="index.php" style="">OSRS Gold Selling Calculator</a></h1>
    <form method="post">
        <label>Amount (million): <input type="number" name="amount" value="<?= htmlspecialchars($amount) ?>" required></label><br><br>
        <label>Price ($ per million): <input type="number" step="0.001" name="price" value="<?= htmlspecialchars($price) ?>" required></label><br><br>
        <label>Fees (%): <input type="number" step="0.01" name="fees_percent" value="<?= htmlspecialchars($fees_percent) ?>"></label><br><br>
<label>Fixed Fees ($): <input type="number" step="0.01" name="fixed_fees" value="<?= htmlspecialchars($fixed_fees) ?>"></label><br><br>
        <p>EGP to USD Exchange Rate: <?= $egp_to_usd ?> <a href="egpusd.php" target="_blank">(Change)</a></p>
        <button type="submit">Calculate</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <h2>Results</h2><br>, for <?php echo $_POST['amount'];?> m gp<br>
        <p>Total Sell: <b style="font-size:20px;color:green;"><?= number_format($total_sell, 2, '.', ',') ?></b> USD = <b style="font-size:20px;color:red;"><?= number_format($total_sell * $egp_to_usd, 2, '.', ',') ?></b> EGP <br><br></p>

    <?php endif; ?>
</body>
</html>
