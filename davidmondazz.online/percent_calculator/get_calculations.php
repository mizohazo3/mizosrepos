<?php
// Database connection setup
$db_host = 'localhost';
$db_user = 'mcgkxyz_masterpop';  // Change to your MySQL username
$db_pass = 'aA0109587045';      // Change to your MySQL password
$db_name = 'mcgkxyz_percent_calculator';  // Database name

$conn = null;
$all_calculations = [];

try {
    // Connect to MySQL database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get recent calculations
    if ($conn) {
        $stmt = $conn->query("SELECT * FROM calculations ORDER BY timestamp DESC LIMIT 5");
        $all_calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    // Silently handle errors
}
?>

<div class="details-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <h3 style="margin: 0;">Your Calculations</h3>
    <?php if (!empty($all_calculations)): ?>
        <button type="button" id="clear-history-button" class="btn-danger">Clear History</button>
    <?php endif; ?>
</div>

<?php if (empty($all_calculations)): ?>
    <div class="no-data">No calculations yet</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Rate</th>
                <th>Likes</th>
                <th>Views</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_calculations as $calc): ?>
                <tr>
                    <td><?php echo number_format($calc['percentage'], 2) . '%'; ?></td>
                    <td><?php echo number_format($calc['likes']); ?></td>
                    <td><?php echo number_format($calc['views']); ?></td>
                    <td><?php echo date('M j, g:i a', $calc['timestamp']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?> 