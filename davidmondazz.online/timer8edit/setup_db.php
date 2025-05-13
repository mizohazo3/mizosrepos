<?php
// setup_db.php (Complete and Updated for Offline Mode + Timer Logs + Fixed Levels Populate)

// Ensure errors are displayed for setup debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Basic formatting for browser output
echo "<pre style='font-family: monospace; background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap; word-wrap: break-word;'>";

// --- Database Configuration ---
$db_host = 'localhost';
$db_name = 'mcgkxyz_timer_app';
$admin_user = 'mcgkxyz_masterpop';
$admin_pass = 'aA0109587045'; // Replace with your MySQL root password if you have one
$db_charset = 'utf8mb4';
// ----------------------------

// --- Level Data ---
// Make sure this array is correct and complete
$levels_data = [
    ['level' => 1, 'hours_required' => 0, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.10],
    ['level' => 2, 'hours_required' => 5, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.11],
    ['level' => 3, 'hours_required' => 10, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.12],
    ['level' => 4, 'hours_required' => 15, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.13],
    ['level' => 5, 'hours_required' => 20, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.14],
    ['level' => 6, 'hours_required' => 26, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.15],
    ['level' => 7, 'hours_required' => 32, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.16],
    ['level' => 8, 'hours_required' => 38, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.17],
    ['level' => 9, 'hours_required' => 44, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.18],
    ['level' => 10, 'hours_required' => 50, 'rank_name' => 'Novice', 'reward_rate_per_hour' => 0.19],
    ['level' => 11, 'hours_required' => 60, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.25],
    ['level' => 12, 'hours_required' => 70, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.27],
    ['level' => 13, 'hours_required' => 80, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.29],
    ['level' => 14, 'hours_required' => 90, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.31],
    ['level' => 15, 'hours_required' => 100, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.33],
    ['level' => 16, 'hours_required' => 120, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.35],
    ['level' => 17, 'hours_required' => 140, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.37],
    ['level' => 18, 'hours_required' => 160, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.39],
    ['level' => 19, 'hours_required' => 180, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.41],
    ['level' => 20, 'hours_required' => 200, 'rank_name' => 'Apprentice', 'reward_rate_per_hour' => 0.43],
    ['level' => 21, 'hours_required' => 220, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.50],
    ['level' => 22, 'hours_required' => 240, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.53],
    ['level' => 23, 'hours_required' => 260, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.56],
    ['level' => 24, 'hours_required' => 280, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.59],
    ['level' => 25, 'hours_required' => 300, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.62],
    ['level' => 26, 'hours_required' => 330, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.65],
    ['level' => 27, 'hours_required' => 360, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.68],
    ['level' => 28, 'hours_required' => 390, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.71],
    ['level' => 29, 'hours_required' => 420, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.74],
    ['level' => 30, 'hours_required' => 450, 'rank_name' => 'Intermediate', 'reward_rate_per_hour' => 0.77],
    ['level' => 31, 'hours_required' => 480, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 0.85],
    ['level' => 32, 'hours_required' => 510, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 0.89],
    ['level' => 33, 'hours_required' => 540, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 0.93],
    ['level' => 34, 'hours_required' => 570, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 0.97],
    ['level' => 35, 'hours_required' => 600, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 1.01],
    ['level' => 36, 'hours_required' => 630, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 1.05],
    ['level' => 37, 'hours_required' => 660, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 1.09],
    ['level' => 38, 'hours_required' => 690, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 1.13],
    ['level' => 39, 'hours_required' => 720, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 1.17],
    ['level' => 40, 'hours_required' => 750, 'rank_name' => 'Advanced', 'reward_rate_per_hour' => 1.21],
    ['level' => 41, 'hours_required' => 780, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.30],
    ['level' => 42, 'hours_required' => 810, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.35],
    ['level' => 43, 'hours_required' => 840, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.40],
    ['level' => 44, 'hours_required' => 870, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.45],
    ['level' => 45, 'hours_required' => 900, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.50],
    ['level' => 46, 'hours_required' => 920, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.55],
    ['level' => 47, 'hours_required' => 940, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.60],
    ['level' => 48, 'hours_required' => 960, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.65],
    ['level' => 49, 'hours_required' => 980, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.70],
    ['level' => 50, 'hours_required' => 1000, 'rank_name' => 'Specialist', 'reward_rate_per_hour' => 1.75],
    ['level' => 51, 'hours_required' => 1030, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 1.85],
    ['level' => 52, 'hours_required' => 1060, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 1.91],
    ['level' => 53, 'hours_required' => 1090, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 1.97],
    ['level' => 54, 'hours_required' => 1120, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 2.03],
    ['level' => 55, 'hours_required' => 1150, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 2.09],
    ['level' => 56, 'hours_required' => 1180, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 2.15],
    ['level' => 57, 'hours_required' => 1210, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 2.21],
    ['level' => 58, 'hours_required' => 1240, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 2.27],
    ['level' => 59, 'hours_required' => 1270, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 2.33],
    ['level' => 60, 'hours_required' => 1300, 'rank_name' => 'Expert', 'reward_rate_per_hour' => 2.39],
    ['level' => 61, 'hours_required' => 1330, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 2.50],
    ['level' => 62, 'hours_required' => 1360, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 2.58],
    ['level' => 63, 'hours_required' => 1390, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 2.66],
    ['level' => 64, 'hours_required' => 1420, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 2.74],
    ['level' => 65, 'hours_required' => 1450, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 2.82],
    ['level' => 66, 'hours_required' => 1480, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 2.90],
    ['level' => 67, 'hours_required' => 1510, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 2.98],
    ['level' => 68, 'hours_required' => 1540, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 3.06],
    ['level' => 69, 'hours_required' => 1570, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 3.14],
    ['level' => 70, 'hours_required' => 1600, 'rank_name' => 'Elite', 'reward_rate_per_hour' => 3.22],
    ['level' => 71, 'hours_required' => 1630, 'rank_name' => 'Master', 'reward_rate_per_hour' => 3.40],
    ['level' => 72, 'hours_required' => 1660, 'rank_name' => 'Master', 'reward_rate_per_hour' => 3.50],
    ['level' => 73, 'hours_required' => 1690, 'rank_name' => 'Master', 'reward_rate_per_hour' => 3.60],
    ['level' => 74, 'hours_required' => 1720, 'rank_name' => 'Master', 'reward_rate_per_hour' => 3.70],
    ['level' => 75, 'hours_required' => 1750, 'rank_name' => 'Master', 'reward_rate_per_hour' => 3.80],
    ['level' => 76, 'hours_required' => 1780, 'rank_name' => 'Master', 'reward_rate_per_hour' => 3.90],
    ['level' => 77, 'hours_required' => 1810, 'rank_name' => 'Master', 'reward_rate_per_hour' => 4.00],
    ['level' => 78, 'hours_required' => 1840, 'rank_name' => 'Master', 'reward_rate_per_hour' => 4.10],
    ['level' => 79, 'hours_required' => 1870, 'rank_name' => 'Master', 'reward_rate_per_hour' => 4.20],
    ['level' => 80, 'hours_required' => 1900, 'rank_name' => 'Master', 'reward_rate_per_hour' => 4.30],
    ['level' => 81, 'hours_required' => 1930, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 4.50],
    ['level' => 82, 'hours_required' => 1960, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 4.65],
    ['level' => 83, 'hours_required' => 1990, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 4.80],
    ['level' => 84, 'hours_required' => 2020, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 4.95],
    ['level' => 85, 'hours_required' => 2050, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 5.10],
    ['level' => 86, 'hours_required' => 2080, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 5.25],
    ['level' => 87, 'hours_required' => 2110, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 5.40],
    ['level' => 88, 'hours_required' => 2140, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 5.55],
    ['level' => 89, 'hours_required' => 2170, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 5.70],
    ['level' => 90, 'hours_required' => 2200, 'rank_name' => 'Grandmaster', 'reward_rate_per_hour' => 5.85],
    ['level' => 91, 'hours_required' => 2240, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 6.10],
    ['level' => 92, 'hours_required' => 2280, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 6.30],
    ['level' => 93, 'hours_required' => 2320, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 6.50],
    ['level' => 94, 'hours_required' => 2360, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 6.70],
    ['level' => 95, 'hours_required' => 2400, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 6.90],
    ['level' => 96, 'hours_required' => 2440, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 7.10],
    ['level' => 97, 'hours_required' => 2480, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 7.30],
    ['level' => 98, 'hours_required' => 2520, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 7.50],
    ['level' => 99, 'hours_required' => 2560, 'rank_name' => 'Legendary', 'reward_rate_per_hour' => 7.70],
    ['level' => 100, 'hours_required' => 2600, 'rank_name' => 'Ultimate', 'reward_rate_per_hour' => 8.00],
];
// -------------------------------------------

echo "Database Setup Script Started...\n";

$pdo = null;
$pdo_init = null;

try {
    // 1. Connect to MySQL server
    $dsn_init = "mysql:host=$db_host;charset=$db_charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    echo "Attempting connection to MySQL server ($admin_user@$db_host)... ";
    $pdo_init = new PDO($dsn_init, $admin_user, $admin_pass, $options);
    echo "Connected successfully.\n";

    // 2. Create the database
    echo "Attempting to create database '$db_name' if it doesn't exist... ";
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET $db_charset COLLATE {$db_charset}_unicode_ci");
    echo "Database check/creation complete.\n";
    $pdo_init = null;

    // 3. Connect to the specific database
    echo "Connecting to the '$db_name' database... ";
    $dsn_db = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
    $pdo = new PDO($dsn_db, $admin_user, $admin_pass, $options);
    echo "Connected successfully.\n";

    // --- Disable Foreign Keys ---
    echo "Disabling foreign key checks for setup... ";
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    echo "Done.\n";

    // --- Drop existing tables ---
    echo "Dropping existing tables (if they exist) to ensure clean setup...\n";
    $pdo->exec("DROP TABLE IF EXISTS purchase_logs;");
    $pdo->exec("DROP TABLE IF EXISTS timer_logs;");
    $pdo->exec("DROP TABLE IF EXISTS user_progress;");
    $pdo->exec("DROP TABLE IF EXISTS timers;");
    $pdo->exec("DROP TABLE IF EXISTS levels;");
    $pdo->exec("DROP TABLE IF EXISTS settings;");
    $pdo->exec("DROP TABLE IF EXISTS marketplace_items;");
    echo "Dropping complete.\n";

    // --- Table Definitions ---
    // levels table
    $sql_levels = <<<SQL
CREATE TABLE levels (
    level INT UNSIGNED PRIMARY KEY,
    hours_required DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    rank_name VARCHAR(50) NOT NULL,
    reward_rate_per_hour DECIMAL(10, 4) DEFAULT 0.0000 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=$db_charset COLLATE={$db_charset}_unicode_ci;
SQL;
    // timers table
    $sql_timers = <<<SQL
CREATE TABLE timers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    accumulated_seconds BIGINT UNSIGNED DEFAULT 0,
    start_time DATETIME NULL,
    is_running TINYINT(1) DEFAULT 0 NOT NULL,
    current_level INT UNSIGNED DEFAULT 1 NOT NULL,
    notified_level INT UNSIGNED DEFAULT 1 NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (is_running)
) ENGINE=InnoDB DEFAULT CHARSET=$db_charset COLLATE={$db_charset}_unicode_ci;
SQL;
    // user_progress table
    $sql_user_progress = <<<SQL
CREATE TABLE user_progress (
    id INT UNSIGNED PRIMARY KEY DEFAULT 1,
    bank_balance DECIMAL(15, 4) DEFAULT 0.0000 NOT NULL,
    CONSTRAINT id_must_be_1 CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=$db_charset COLLATE={$db_charset}_unicode_ci;
SQL;
    // settings table
    $sql_settings = <<<SQL
CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=$db_charset COLLATE={$db_charset}_unicode_ci;
SQL;
    // timer_logs table
    $sql_timer_logs = <<<SQL
CREATE TABLE timer_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timer_id INT UNSIGNED NOT NULL,
    session_start_time DATETIME NOT NULL,
    session_end_time DATETIME NOT NULL,
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    earned_amount DECIMAL(15, 6) DEFAULT 0.000000 NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (timer_id),
    FOREIGN KEY (timer_id) REFERENCES timers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=$db_charset COLLATE={$db_charset}_unicode_ci;
SQL;

    // --- NEW: marketplace_items table ---
    $sql_marketplace_items = <<<SQL
CREATE TABLE marketplace_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(15, 4) NOT NULL DEFAULT 0.0000,
    image_url VARCHAR(512) NULL,
    stock INT DEFAULT -1,
    is_active TINYINT(1) DEFAULT 1 NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (is_active, price)
) ENGINE=InnoDB DEFAULT CHARSET=$db_charset COLLATE={$db_charset}_unicode_ci;
SQL;

    // --- NEW: purchase_logs table ---
    $sql_purchase_logs = <<<SQL
CREATE TABLE purchase_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    item_name_snapshot VARCHAR(100) NOT NULL,
    price_paid DECIMAL(15, 4) NOT NULL,
    purchase_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES marketplace_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=$db_charset COLLATE={$db_charset}_unicode_ci;
SQL;

    // --- Execute Table Creation ---
    echo "Creating table 'levels'... "; $pdo->exec($sql_levels); echo "Done.\n";
    echo "Creating table 'timers'... "; $pdo->exec($sql_timers); echo "Done.\n";
    echo "Creating table 'user_progress'... "; $pdo->exec($sql_user_progress); echo "Done.\n";
    echo "Creating table 'settings'... "; $pdo->exec($sql_settings); echo "Done.\n";
    echo "Creating table 'marketplace_items'... "; $pdo->exec($sql_marketplace_items); echo "Done.\n";
    echo "Creating table 'timer_logs'... "; $pdo->exec($sql_timer_logs); echo "Done.\n";
    echo "Creating table 'purchase_logs'... "; $pdo->exec($sql_purchase_logs); echo "Done.\n";

    // --- Add Missing Foreign Key Constraint Separately ---
    try {
        echo "Attempting to add Foreign Key timers(current_level) -> levels(level)...";
        // Drop constraint if it exists first (for idempotency)
        try { $pdo->exec("ALTER TABLE timers DROP FOREIGN KEY fk_timers_level;"); } catch (PDOException $dropFkEx) { /* Ignore error if constraint doesn't exist */ }
        $pdo->exec("ALTER TABLE timers ADD CONSTRAINT fk_timers_level FOREIGN KEY (current_level) REFERENCES levels(level) ON DELETE RESTRICT ON UPDATE CASCADE;");
        echo "Done.\n";
    } catch (PDOException $fkError) {
        echo "\n!!! WARNING: Could not add FK constraint timers(current_level) -> levels(level). Error: " . $fkError->getMessage() . "!!!\n";
    }


    // --- Populate Levels Table ---
    echo "Populating 'levels' table...\n";
    $pdo->beginTransaction();
    try {
        $sql_insert_level = "INSERT INTO levels (level, hours_required, rank_name, reward_rate_per_hour) VALUES (:level, :hours, :rank, :rate)";
        $stmt_insert = $pdo->prepare($sql_insert_level);
        $inserted_count = 0;

        if (!empty($levels_data) && is_array($levels_data)) {
            foreach ($levels_data as $level_data) {
                if (isset($level_data['level'], $level_data['hours_required'], $level_data['rank_name'], $level_data['reward_rate_per_hour'])) {
                    // *** This is the corrected part ***
                    $stmt_insert->execute([
                        ':level' => $level_data['level'],
                        ':hours' => $level_data['hours_required'],
                        ':rank'  => $level_data['rank_name'],
                        ':rate'  => $level_data['reward_rate_per_hour']
                    ]);
                    // *** End corrected part ***
                    $inserted_count++;
                } else {
                    error_log("Setup DB Warning: Skipping invalid level data entry in \$levels_data array: " . print_r($level_data, true));
                    echo "\n!!! WARNING: Skipping invalid level data entry: " . print_r($level_data, true) . "!!!\n";
                }
            }
        } else {
             error_log("Setup DB Error: \$levels_data array is empty or not an array. Cannot populate levels.");
             echo "\n!!! ERROR: \$levels_data array is empty or not an array. No levels will be inserted. !!!\n";
        }

        if ($inserted_count > 0) {
            $pdo->commit();
            echo "  Successfully inserted " . $inserted_count . " levels.\n";
        } else {
            error_log("Setup DB Warning: No valid levels were inserted. Transaction not committed (or already rolled back).");
             echo "\n!!! WARNING: No valid levels were inserted. Check \$levels_data array and previous warnings. !!!\n";
            if ($pdo->inTransaction()) {
                 $pdo->rollBack();
                 echo "Transaction rolled back as no levels were inserted.\n";
            }
        }

    } catch (PDOException $e) { // More specific catch for PDO errors during population
        error_log("Setup DB Error during levels population: " . $e->getMessage() . ". SQLSTATE: " . $e->getCode() . ". ErrorInfo: " . print_r($e->errorInfo, true));
        if ($pdo && $pdo->inTransaction()) {
             $pdo->rollBack();
             echo "\nLevel population transaction rolled back due to PDO error.\n";
        }
        echo "\n!!! PDO ERROR POPULATING LEVELS TABLE: " . $e->getMessage() . " !!!\n";
        throw $e; // Re-throw to stop the script
    } catch (Exception $e) { // Generic catch
        error_log("Setup DB Error during levels population: " . $e->getMessage());
        if ($pdo && $pdo->inTransaction()) {
             $pdo->rollBack();
             echo "\nLevel population transaction rolled back due to error.\n";
        }
        echo "\n!!! ERROR POPULATING LEVELS TABLE: " . $e->getMessage() . " !!!\n";
        throw $e; // Re-throw to stop the script
    }


    // --- Initialize User Progress ---
    echo "Initializing 'user_progress' table (ID=1)... ";
    $sql_init_progress = "INSERT IGNORE INTO user_progress (id, bank_balance) VALUES (1, 0.0000)";
    $pdo->exec($sql_init_progress);
    echo "Done.\n";

    // --- Initialize settings table ---
    echo "Initializing 'settings' table (difficulty_multiplier=1.0)... ";
    $sql_init_settings = "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('difficulty_multiplier', '1.0')";
    $pdo->exec($sql_init_settings);
    echo "Done.\n";

    // --- NEW: Populate Marketplace with Sample Items ---
    echo "Populating 'marketplace_items' table with sample data...\n";
    $pdo->beginTransaction();
    try {
        $sample_items = [
            ['name' => 'Bronze Time Booster (1hr)', 'description' => 'Instantly adds 1 hour of time to a chosen timer (feature not implemented).', 'price' => 5.00, 'image_url' => 'https://via.placeholder.com/100/CD7F32/FFFFFF?text=1hr'],
            ['name' => 'Silver Time Booster (5hr)', 'description' => 'Instantly adds 5 hours of time to a chosen timer (feature not implemented).', 'price' => 20.00, 'image_url' => 'https://via.placeholder.com/100/C0C0C0/FFFFFF?text=5hr'],
            ['name' => 'Cosmetic: Blue Timer Skin', 'description' => 'Changes the visual appearance of a timer (feature not implemented).', 'price' => 50.00, 'image_url' => 'https://via.placeholder.com/100/61dafb/FFFFFF?text=Skin'],
            ['name' => 'Experience Potion (Small)', 'description' => 'Grants a small amount of bonus experience towards the next level (feature not implemented).', 'price' => 15.50, 'image_url' => 'https://via.placeholder.com/100/90EE90/000000?text=XP+S'],
        ];

        $sql_insert_item = "INSERT INTO marketplace_items (name, description, price, image_url, stock, is_active) VALUES (:name, :desc, :price, :img, -1, 1)";
        $stmt_insert = $pdo->prepare($sql_insert_item);
        $inserted_items = 0;
        foreach ($sample_items as $item) {
            $stmt_insert->execute([
                ':name' => $item['name'],
                ':desc' => $item['description'],
                ':price' => $item['price'],
                ':img' => $item['image_url']
            ]);
            $inserted_items++;
        }
        $pdo->commit();
        echo "  Successfully inserted " . $inserted_items . " sample items.\n";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "\n!!! ERROR POPULATING MARKETPLACE ITEMS: " . $e->getMessage() . " !!!\n";
        throw $e;
    }

    // --- Re-enable Foreign Keys ---
    echo "Re-enabling foreign key checks... ";
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    echo "Done.\n";

    echo "\n-------------------------------------\n";
    echo "DATABASE SETUP SCRIPT COMPLETED SUCCESSFULLY!\n";
    echo "Database '$db_name' is ready with marketplace and purchase logging functionality.\n";
    echo "-------------------------------------\n";

} catch (Exception $e) {
    // --- ERROR HANDLING ---
    echo "\n\n----- DATABASE SETUP FAILED! -----\n";
    echo "Error: " . $e->getMessage() . "\n";
    if ($e instanceof PDOException) {
        echo "SQLSTATE: " . $e->getCode() . "\n";
        echo "PDO Error Info: " . print_r($e->errorInfo, true) . "\n";
    }
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";

    // Attempt to re-enable FK checks even after failure
    if ($pdo) {
        try {
            echo "Attempting to re-enable foreign key checks after error... ";
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            echo "Done.\n";
        } catch(Exception $ex) {
             echo "Failed to re-enable FK checks after error: " . $ex->getMessage() . "\n";
        }
    } else if ($pdo_init) { // If error happened before main $pdo connection
        try {
             echo "Attempting to re-enable foreign key checks via pdo_init after error... ";
             $pdo_init->exec("SET FOREIGN_KEY_CHECKS=1;");
             echo "Done.\n";
         } catch(Exception $ex) {
             echo "Failed to re-enable FK checks after error: " . $ex->getMessage() . "\n";
         }
    } else {
         echo "Could not attempt to re-enable foreign keys (no database connection established).\n";
    }

    exit(1); // Exit with error code

} finally {
    // Clean up connections
    $pdo = null;
    $pdo_init = null;
    echo "</pre>"; // Close preformatted block
}

?>