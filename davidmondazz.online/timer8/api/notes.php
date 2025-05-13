<?php

include_once '../timezone_config.php';
// api/notes.php - API endpoint for the notes feature

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Required files
require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Default response
$response = [
    'status' => 'error',
    'message' => 'An unexpected error occurred.'
];

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
}

// Get request body for POST/DELETE
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

try {
    // GET request - get all noted items
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get the most recent active note
        $stmt = $pdo->query("
            SELECT * FROM on_the_note
            WHERE is_paid = 0
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $noteRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $notes = [];
        
        if ($noteRow) {
            // Parse the JSON items list
            $itemsList = json_decode($noteRow['items_list'], true);
            if (is_array($itemsList)) {
                $notes = $itemsList;
            }
        }
        
        $response = [
            'status' => 'success',
            'notes' => $notes,
            'count' => count($notes)
        ];
    }
    
    // POST request - add item to note
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($input['item_id'])) {
            $response['message'] = 'Missing item_id parameter.';
            http_response_code(400);
            echo json_encode($response);
        }
        
        $itemId = intval($input['item_id']);
        
        // Get item details from marketplace_items
        $itemStmt = $pdo->prepare("
            SELECT id, name, price, image_url 
            FROM marketplace_items 
            WHERE id = ? AND is_active = 1
        ");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            $response['message'] = 'Item not found or inactive.';
            http_response_code(404);
            echo json_encode($response);
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Get the most recent active note
        $noteStmt = $pdo->query("
            SELECT * FROM on_the_note
            WHERE is_paid = 0
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $noteRow = $noteStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($noteRow) {
            // Update existing note
            $itemsList = json_decode($noteRow['items_list'], true);
            if (!is_array($itemsList)) {
                $itemsList = [];
            }
            
            // Check if item already exists in the note
            $itemExists = false;
            foreach ($itemsList as $existingItem) {
                if (isset($existingItem['item_id']) && $existingItem['item_id'] == $itemId) {
                    $itemExists = true;
                    break;
                }
            }
            
            if ($itemExists) {
                $response['message'] = 'Item already in your note.';
                $response['status'] = 'success'; // Not really an error
                $pdo->commit();
                echo json_encode($response);
            }
            
            // Add item to the list
            $itemsList[] = [
                'item_id' => $itemId,
                'name' => $item['name'],
                'price' => floatval($item['price']),
                'image_url' => $item['image_url']
            ];
            
            // Calculate new total
            $newTotal = 0;
            foreach ($itemsList as $listItem) {
                $newTotal += floatval($listItem['price']);
            }
            
            // Update the note
            $updateStmt = $pdo->prepare("
                UPDATE on_the_note 
                SET items_list = ?, 
                    total_amount = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([
                json_encode($itemsList),
                $newTotal,
                $noteRow['id']
            ]);
        } else {
            // Create new note
            $itemsList = [[
                'item_id' => $itemId,
                'name' => $item['name'],
                'price' => floatval($item['price']),
                'image_url' => $item['image_url']
            ]];
            
            // Use PHP's DateTime for consistent timestamp generation (matches timer)
            $now_dt = new DateTime();
            $now_str = $now_dt->format('Y-m-d H:i:s');

            $insertStmt = $pdo->prepare("
                INSERT INTO on_the_note
                (items_list, total_amount, is_paid, created_at)
                VALUES (?, ?, 0, ?)
            ");
            $insertStmt->execute([
                json_encode($itemsList),
                floatval($item['price']),
                $now_str // Pass the timestamp generated by PHP
            ]);
        }
        
        $pdo->commit();
        
        $response = [
            'status' => 'success',
            'message' => 'Item added to note.',
            'item_id' => $itemId,
            'item_name' => $item['name']
        ];
    }

    // POST request - mark note as paid
    else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action']) && $input['action'] === 'mark_paid') {
        // Start transaction
        $pdo->beginTransaction();

        // Get the most recent active note
        $noteStmt = $pdo->query("
            SELECT id FROM on_the_note
            WHERE is_paid = 0
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $noteRow = $noteStmt->fetch(PDO::FETCH_ASSOC);

        if (!$noteRow) {
            $response['message'] = 'No active note found to mark as paid.';
            $response['status'] = 'success'; // Not really an error
            $pdo->commit();
            echo json_encode($response);
        }

        // Mark the note as paid using PHP's DateTime for consistent timestamp
        $now_dt = new DateTime();
        $now_str = $now_dt->format('Y-m-d H:i:s');

        $updateStmt = $pdo->prepare("
            UPDATE on_the_note
            SET is_paid = 1,
                paid_at = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$now_str, $noteRow['id']]);

        $pdo->commit();

        $response = [
            'status' => 'success',
            'message' => 'Note marked as paid.',
            'note_id' => $noteRow['id']
        ];
    }

    // DELETE request - remove item from note or clear all
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get the most recent active note
        $noteStmt = $pdo->query("
            SELECT * FROM on_the_note
            WHERE is_paid = 0
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $noteRow = $noteStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$noteRow) {
            $response['message'] = 'No active note found.';
            $response['status'] = 'success'; // Not really an error
            echo json_encode($response);
        }
        
        // If item_id is provided, remove just that item
        if (isset($input['item_id'])) {
            $itemId = intval($input['item_id']);
            $itemsList = json_decode($noteRow['items_list'], true);
            
            if (!is_array($itemsList)) {
                $itemsList = [];
            }
            
            // Find and remove the item
            $removedItem = null;
            $newItemsList = [];
            foreach ($itemsList as $item) {
                if (isset($item['item_id']) && $item['item_id'] == $itemId) {
                    $removedItem = $item;
                } else {
                    $newItemsList[] = $item;
                }
            }
            
            if (!$removedItem) {
                $response['message'] = 'Item not found in your note.';
                $pdo->commit();
                echo json_encode($response);
            }
            
            // Calculate new total
            $newTotal = 0;
            foreach ($newItemsList as $item) {
                $newTotal += floatval($item['price']);
            }
            
            // If no items left, delete the note
            if (empty($newItemsList)) {
                $deleteStmt = $pdo->prepare("DELETE FROM on_the_note WHERE id = ?");
                $deleteStmt->execute([$noteRow['id']]);
            } else {
                // Update the note
                $updateStmt = $pdo->prepare("
                    UPDATE on_the_note 
                    SET items_list = ?, 
                        total_amount = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    json_encode($newItemsList),
                    $newTotal,
                    $noteRow['id']
                ]);
            }
            
            $pdo->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'Item removed from note.',
                'removed_item' => $removedItem['name']
            ];
        } 
        // If no item_id provided, clear all notes
        else {
            $deleteStmt = $pdo->prepare("DELETE FROM on_the_note WHERE id = ?");
            $deleteStmt->execute([$noteRow['id']]);
            
            $pdo->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'All notes cleared.'
            ];
        }
    }
    
    // Other request methods
    else {
        $response['message'] = 'Unsupported request method.';
        http_response_code(405);
    }

} catch (PDOException $e) {
    // Rollback transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $response['message'] = 'Database error: ' . $e->getMessage();
    http_response_code(500);
} catch (Exception $e) {
    // Rollback transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
}

// Return JSON response
echo json_encode($response);