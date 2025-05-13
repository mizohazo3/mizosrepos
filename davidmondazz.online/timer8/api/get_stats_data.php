<?php

include_once '../timezone_config.php';
// api/get_stats_data.php
ini_set('display_errors', 0); // Recommended for production
ini_set('display_startup_errors', 0); // Recommended for production
ini_set('log_errors', 1); // Keep logging enabled
error_reporting(E_ALL);

// Logging function
function log_stats_action($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    // Ensure directory exists and is writable (basic check)
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    if (is_writable($log_dir)) {
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    } else {
    }
}

// Create logs directory if it doesn't exist (redundant check, but safe)
$logs_dir = __DIR__ . '/../logs';
if (!file_exists($logs_dir)) {
    @mkdir($logs_dir, 0755, true);
}

$log_file = $logs_dir . '/stats_api.log';
log_stats_action("Stats API request received", $log_file);

require_once 'db.php';
header('Content-Type: application/json');

// Set CORS headers (adjust origin if needed for security)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS'); // Limit methods if possible
header('Access-Control-Allow-Headers: Content-Type');

// Handle potential OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
}


$response = ['status' => 'error', 'message' => 'Failed to retrieve statistics data.'];

try {
    log_stats_action("Fetching statistics data...", $log_file);

    // Get period from request (default: day)
    $period = isset($_GET['period']) ? $_GET['period'] : 'day';

    // Set parameters based on period
    switch ($period) {
        case 'day':
            $days = 1;
            $useHourly = true;
            $startDate = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $sqlDateFormat = '%Y-%m-%d %H:00:00';
            $dateFormat = 'Y-m-d H:00:00';
            $interval = new DateInterval('PT1H');
            $sqlDateCondition = 'session_end_time >= :start_date';
            $sqlPurchaseDateCondition = 'purchase_time >= :start_date';
            break;
        case 'week':
            $days = 7;
            $useHourly = false;
            $startDate = date('Y-m-d', strtotime("-{$days} days +1 day"));
            $sqlDateFormat = '%Y-%m-%d';
            $dateFormat = 'Y-m-d';
            $interval = new DateInterval('P1D');
            $sqlDateCondition = 'DATE(session_end_time) >= :start_date';
            $sqlPurchaseDateCondition = 'DATE(purchase_time) >= :start_date';
            break;
        case 'month':
            $days = 30;
            $useHourly = false;
            $startDate = date('Y-m-d', strtotime("-{$days} days +1 day"));
            $sqlDateFormat = '%Y-%m-%d';
            $dateFormat = 'Y-m-d';
            $interval = new DateInterval('P1D');
            $sqlDateCondition = 'DATE(session_end_time) >= :start_date';
            $sqlPurchaseDateCondition = 'DATE(purchase_time) >= :start_date';
            break;
        case 'year_monthly':
            $days = 365;
            $useHourly = false;
            $startDate = date('Y-m-01', strtotime('-11 months'));
            $sqlDateFormat = '%Y-%m';
            $dateFormat = 'Y-m';
            $interval = new DateInterval('P1M');
            $sqlDateCondition = 'session_end_time >= :start_date';
            $sqlPurchaseDateCondition = 'purchase_time >= :start_date';
            break;
        default:
            $days = 1;
            $period = 'day';
            $useHourly = true;
            $startDate = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $sqlDateFormat = '%Y-%m-%d %H:00:00';
            $dateFormat = 'Y-m-d H:00:00';
            $interval = new DateInterval('PT1H');
            $sqlDateCondition = 'session_end_time >= :start_date';
            $sqlPurchaseDateCondition = 'purchase_time >= :start_date';
            break;
    }

    log_stats_action("Period: $period, Days: $days, Start Date: $startDate, Hourly: " . ($useHourly ? 'Yes' : 'No'), $log_file);

    // --- Get Aggregated Earnings & Hours ---
    $sql_daily_earnings = "
        SELECT
            DATE_FORMAT(session_end_time, '$sqlDateFormat') as date,
            SUM(earned_amount) as total_earned,
            SUM(duration_seconds)/3600.0 as total_hours
        FROM timer_logs
        WHERE $sqlDateCondition
        GROUP BY DATE_FORMAT(session_end_time, '$sqlDateFormat')
        ORDER BY date ASC
    ";

    try {
        $stmt_earnings = $pdo->prepare($sql_daily_earnings);
        $stmt_earnings->execute([':start_date' => $startDate]);
        $daily_earnings = $stmt_earnings->fetchAll(PDO::FETCH_ASSOC);
        log_stats_action("Fetched " . count($daily_earnings) . " periods of earnings/hours data.", $log_file);
    } catch (PDOException $e) {
        log_stats_action("ERROR in earnings query: " . $e->getMessage(), $log_file);
        throw $e;
    }

    // --- Get Aggregated Spending ---
    $sql_daily_spending = "
        SELECT
            DATE_FORMAT(purchase_time, '$sqlDateFormat') as date,
            SUM(price_paid) as total_spent
        FROM purchase_logs
        WHERE $sqlPurchaseDateCondition
        GROUP BY DATE_FORMAT(purchase_time, '$sqlDateFormat')
        ORDER BY date ASC
    ";

    try {
        $stmt_spending = $pdo->prepare($sql_daily_spending);
        $stmt_spending->execute([':start_date' => $startDate]);
        $daily_spending = $stmt_spending->fetchAll(PDO::FETCH_ASSOC);
        log_stats_action("Fetched " . count($daily_spending) . " periods of spending data.", $log_file);
    } catch (PDOException $e) {
        log_stats_action("ERROR in spending query: " . $e->getMessage(), $log_file);
        throw $e;
    }

    // --- Calculate Totals for the Summary ---
    $sql_totals = "
        SELECT
            COALESCE(SUM(tl.earned_amount), 0) as total_earned,
            COALESCE(SUM(tl.duration_seconds)/3600.0, 0) as total_hours
        FROM timer_logs tl
        WHERE $sqlDateCondition
    ";

    try {
        $stmt_totals = $pdo->prepare($sql_totals);
        $stmt_totals->execute([':start_date' => $startDate]);
        $totals = $stmt_totals->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_stats_action("ERROR in totals query: " . $e->getMessage(), $log_file);
        throw $e;
    }

    $sql_total_spent = "
        SELECT
            COALESCE(SUM(pl.price_paid), 0) as total_spent
        FROM purchase_logs pl
        WHERE $sqlPurchaseDateCondition
    ";

    try {
        $stmt_total_spent = $pdo->prepare($sql_total_spent);
        $stmt_total_spent->execute([':start_date' => $startDate]);
        $total_spent_result = $stmt_total_spent->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_stats_action("ERROR in total spent query: " . $e->getMessage(), $log_file);
        throw $e;
    }

    // Assign fetched totals
    $total_earned = (float) ($totals['total_earned'] ?? 0);
    $total_hours = (float) ($totals['total_hours'] ?? 0);
    $total_spent = (float) ($total_spent_result['total_spent'] ?? 0);

    // Calculate average daily hours
    $avg_hours = 0;
    // Use the actual number of days in the range for average calculation
    $effective_days = $days;
    if ($total_hours > 0 && $effective_days > 0) {
        $avg_hours = $total_hours / $effective_days;
    }

    // --- Fill missing dates/hours in the date range ---
    $filled_data = [];
    // Use DateTimeImmutable for safer date manipulation
    $current_dt = new DateTimeImmutable($startDate);
    // Ensure end date is inclusive and considers the current time for hourly
    $end_dt = new DateTimeImmutable();

    // Create lookup arrays for faster access
    $earnings_by_date = [];
    foreach ($daily_earnings as $earning) {
        $earnings_by_date[$earning['date']] = $earning;
    }

    $spending_by_date = [];
    foreach ($daily_spending as $spending) {
        $spending_by_date[$spending['date']] = $spending;
    }

    // Loop through the date/time range and fill data
    while ($current_dt <= $end_dt) {
        $date_str = $current_dt->format($dateFormat);

        $day_data = [
            'date' => $date_str,
            'total_earned' => isset($earnings_by_date[$date_str]) ? (float) $earnings_by_date[$date_str]['total_earned'] : 0.0,
            'total_hours' => isset($earnings_by_date[$date_str]) ? (float) $earnings_by_date[$date_str]['total_hours'] : 0.0,
            'total_spent' => isset($spending_by_date[$date_str]) ? (float) $spending_by_date[$date_str]['total_spent'] : 0.0
        ];

        $filled_data[] = $day_data;
        $current_dt = $current_dt->add($interval);

        // Safety break for potential infinite loops (adjust limit if needed)
        if (count($filled_data) > ($days * 24 + 10)) { // Allow some buffer
             log_stats_action("WARN: Exceeded expected data points limit, breaking loop.", $log_file);
             break;
        }
    }

    // --- Prepare Final Response ---
    $response = [
        'status' => 'success',
        'period' => $period,
        'days' => $days, // Keep for reference if needed
        'daily_data' => $filled_data,
        'totals' => [
            'total_earned' => $total_earned,
            'total_hours' => $total_hours,
            'total_spent' => $total_spent,
            'avg_hours' => (float) $avg_hours // Ensure avg is float
        ]
    ];

    log_stats_action("Successfully generated stats response. Data points: " . count($filled_data), $log_file);

} catch (PDOException $e) {
    log_stats_action("ERROR (PDO): " . $e->getMessage() . " | SQL State: " . $e->getCode(), $log_file);
    $response = [
        'status' => 'error',
        'message' => 'Database error occurred while retrieving statistics.' // User-friendly message
    ];
    http_response_code(500);
} catch (Exception $e) {
    log_stats_action("ERROR (General): " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine(), $log_file);
    $response = [
        'status' => 'error',
        'message' => 'An unexpected error occurred while retrieving statistics.' // User-friendly message
    ];
    http_response_code(500);
}

// Ensure numeric values are properly encoded even if they are 0
echo json_encode($response, JSON_PRESERVE_ZERO_FRACTION | JSON_NUMERIC_CHECK);
exit;
?>