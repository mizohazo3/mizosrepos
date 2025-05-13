<?php

include_once '../timezone_config.php';
// --- Database Configuration ---
$host = 'localhost';      // Or your DB host
$dbname = 'mcgkxyz_timer_app';    // Your database name
$username = 'mcgkxyz_masterpop';       // Your DB username
$password = 'aA0109587045';           // Your DB password
$db_charset = 'utf8mb4';
// ----------------------------

try {
    // Establish PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$db_charset",
        $username,
        $password,
        [ // PDO options
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
        ]
    );
    // --- NO ECHO OR PRINT STATEMENTS HERE ---

} catch (PDOException $e) {
    // Log the error server-side (important for security)

    // Option 1: Re-throw the exception to be caught by the calling script
    // This is generally preferred if this file is only included.
    throw new PDOException('Database connection failed. Check server logs.', (int)$e->getCode(), $e);

    // Option 2: Output a JSON error and exit (Use if this script might be called directly, less ideal)
    /*
    http_response_code(500); // Internal Server Error
    // Ensure header is set *before* output
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed. Please check server configuration.'
    ]);
    exit; // Stop script execution
    */
}

/**
 * Calculates the dynamic bank balance based on timer earnings, purchases, and paid notes.
 *
 * @param PDO $pdo The database connection object.
 * @return float The calculated bank balance.
 */
function calculateDynamicBalance(PDO $pdo): float {
    try {
        // 1. Calculate Total Income from Timers
        $stmt_income = $pdo->query("SELECT SUM(earned_amount) as total_income FROM timer_logs");
        $income_row = $stmt_income->fetch();
        $total_income = $income_row ? (float)$income_row['total_income'] : 0.0;

        // 2. Calculate Total Purchases
        $stmt_purchases = $pdo->query("SELECT SUM(price_paid) as total_purchases FROM purchase_logs");
        $purchases_row = $stmt_purchases->fetch();
        $total_purchases = $purchases_row ? (float)$purchases_row['total_purchases'] : 0.0;

        // 3. Calculate Total Paid Notes
        $stmt_notes = $pdo->query("SELECT SUM(total_amount) as total_notes FROM on_the_note WHERE is_paid = 1");
        $notes_row = $stmt_notes->fetch();
        $total_paid_notes = $notes_row ? (float)$notes_row['total_notes'] : 0.0;

        // 4. Calculate Current Balance
        $current_balance = $total_income - $total_purchases - $total_paid_notes;

        return $current_balance;

    } catch (PDOException $e) {
        // Log the error and return 0 or re-throw if critical
        // Depending on requirements, you might want to handle this differently
        // For now, returning 0 to avoid breaking flows that expect a number.
        return 0.0;
    }
}

// --- NO WHITESPACE OR ANYTHING AFTER THIS CLOSING TAG ---
?>