<?php

include_once '../timezone_config.php';
// api/delete_item.php

ini_set('display_errors', 0); // Production should hide errors
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
header('Content-Type: application/json');

$log_file = __DIR__ . '/../logs/delete_item.log'; // Optional logging
function log_delete($message, $log_file) { file_put_contents($log_file, date('[Y-m-d H:i:s] ').$message."\n", FILE_APPEND); }

$response = ['status' => 'error', 'message' => 'Invalid request.'];

// Expecting item ID via POST data (usually sent via JS fetch)
$input = json_decode(file_get_contents('php://input'), true);
$item_id = isset($input['item_id']) ? filter_var($input['item_id'], FILTER_VALIDATE_INT) : null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$item_id) {
    log_delete("Invalid request: Method={$_SERVER['REQUEST_METHOD']} ID={$item_id}", $log_file);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method or missing item ID.']);
    exit;
}

log_delete("Attempting delete for item ID: $item_id", $log_file);
$pdo->beginTransaction(); // Use transaction in case we need multiple steps (like deleting image file)

try {
    // 1. Optional: Get the image URL before deleting to remove the file later
    $stmt_get = $pdo->prepare("SELECT name, image_url FROM marketplace_items WHERE id = :id");
    $stmt_get->execute([':id' => $item_id]);
    $item_data = $stmt_get->fetch();

    if (!$item_data) {
        throw new Exception("Item not found.", 404);
    }
    $item_name = $item_data['name'];
    $image_url = $item_data['image_url'];
    log_delete("Found item '{$item_name}'. Image URL: '{$image_url}'", $log_file);

    // 2. Attempt to delete the item from the database
    $stmt_delete = $pdo->prepare("DELETE FROM marketplace_items WHERE id = :id");
    $stmt_delete->execute([':id' => $item_id]);

    $rowCount = $stmt_delete->rowCount();

    if ($rowCount > 0) {
        log_delete("Successfully deleted item ID: $item_id from DB. Rows affected: $rowCount", $log_file);

        // 3. Optional & Important: Delete the associated image file
        if ($image_url) {
            $filePath = __DIR__ . '/../' . $image_url; // Construct path relative to this script
            $filePath = realpath($filePath); // Get absolute path to be safe

            // Basic security check: Ensure the path is within your uploads directory
            $allowedDir = realpath(__DIR__ . '/../uploads/market_images');
            if ($filePath && $allowedDir && strpos($filePath, $allowedDir) === 0 && file_exists($filePath)) {
                if (unlink($filePath)) {
                    log_delete("Successfully deleted image file: $filePath", $log_file);
                } else {
                    // Log failure but maybe don't fail the whole request? Or do? Depends on requirements.
                     $response['warning'] = "Item deleted from database, but failed to delete image file.";
                }
            } else {
                log_delete("Image file not found or outside allowed directory: {$filePath} (resolved) from {$image_url} ", $log_file);
            }
        }

        $pdo->commit(); // Commit only if DB delete succeeded
        $response = ['status' => 'success', 'message' => "Item '{$item_name}' deleted successfully."];
        if (isset($response['warning'])) {
            $response['status'] = 'success_with_warning'; // Custom status
        }

    } else {
         // Item might have been deleted already, or ID was invalid, but didn't throw error above?
         log_delete("Item ID: $item_id not found during delete execution, or already deleted.", $log_file);
         throw new Exception("Item not found or could not be deleted.", 404); // Treat as not found
    }

} catch (PDOException $e) {
    $pdo->rollBack();

    // Check for foreign key constraint violation (23000/1451)
    if ($e->getCode() == 23000) {
        http_response_code(409); // Conflict
        $response['message'] = 'Cannot delete item: It has been purchased previously. Consider hiding it instead.';
    } else {
        http_response_code(500); // Internal Server Error
        $response['message'] = 'Database error during deletion.';
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(isset($e->getCode) && $e->getCode() >= 400 ? $e->getCode() : 500); // Use exception code if applicable
    $response['message'] = 'Failed to delete item: ' . $e->getMessage();
}

echo json_encode($response);
?>