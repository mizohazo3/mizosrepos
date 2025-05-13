<?php
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
    error_log('Database Connection Error in db.php: ' . $e->getMessage());

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
// --- NO WHITESPACE OR ANYTHING AFTER THIS CLOSING TAG ---
?>