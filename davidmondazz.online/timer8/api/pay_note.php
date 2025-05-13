<?php
header('Content-Type: application/json');

require_once 'db.php'; // Assuming db.php handles database connection

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    $user_session_id = $data['user_session_id'] ?? null;

    if ($user_session_id) {
        $conn = getDbConnection();

        if ($conn) {
            try {
                $conn->begin_transaction();

                // 1. Fetch all items from the note for this session
                $stmt = $conn->prepare("SELECT id, item_id, quantity FROM note_items WHERE user_session_id = ?");
                $stmt->bind_param("s", $user_session_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $note_items = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                if (empty($note_items)) {
                    $response = ['status' => 'error', 'message' => 'Your note is empty.'];
                    $conn->rollback();
                } else {
                    $total_cost = 0;
                    $purchased_items_details = [];

                    // Fetch details for items to get prices
                    $item_ids = array_column($note_items, 'item_id');
                    $item_ids_placeholder = implode(',', array_fill(0, count($item_ids), '?'));
                    $stmt_items = $conn->prepare("SELECT id, name, price FROM marketplace_items WHERE id IN ($item_ids_placeholder)");
                    $types = str_repeat('i', count($item_ids));
                    $stmt_items->bind_param($types, ...$item_ids);
                    $stmt_items->execute();
                    $items_result = $stmt_items->get_result();
                    $item_details_map = [];
                    while ($row = $items_result->fetch_assoc()) {
                        $item_details_map[$row['id']] = $row;
                    }
                    $stmt_items->close();

                    // 2. Calculate total cost and prepare purchase records
                    foreach ($note_items as $note_item) {
                        $item_id = $note_item['item_id'];
                        $quantity = $note_item['quantity'] ?? 1; // Default quantity to 1 if not stored

                        if (isset($item_details_map[$item_id])) {
                            $item = $item_details_map[$item_id];
                            $item_price = $item['price'];
                            $subtotal = $item_price * $quantity;
                            $total_cost += $subtotal;

                            // Store details for purchase history
                            $purchased_items_details[] = [
                                'item_id' => $item_id,
                                'name' => $item['name'],
                                'price' => $item_price,
                                'quantity' => $quantity,
                                'subtotal' => $subtotal
                            ];
                        } else {
                            // Log error or handle missing item
                            error_log("Item ID {$item_id} not found in marketplace_items during note payment.");
                        }
                    }

                    // 3. Record the purchase(s) in purchases table
                    // This assumes a purchases table structure that can handle multiple items per transaction
                    // For simplicity here, let's record each item from the note as a separate purchase entry
                    // A more robust solution might involve a separate 'transactions' table and linking items
                    $insert_purchase_stmt = $conn->prepare("INSERT INTO purchases (user_session_id, item_id, item_name, item_price, quantity, purchase_time) VALUES (?, ?, ?, ?, ?, NOW())");

                    foreach ($purchased_items_details as $details) {
                         // Note: This simple implementation records each item individually.
                         // If you need a single transaction record for the whole note,
                         // you'd need a different database structure and insert logic.
                         $insert_purchase_stmt->bind_param("sisdi",
                             $user_session_id,
                             $details['item_id'],
                             $details['name'],
                             $details['price'],
                             $details['quantity']
                         );
                         $insert_purchase_stmt->execute();
                    }
                    $insert_purchase_stmt->close();


                    // 4. Clear the note for this session
                    $delete_note_stmt = $conn->prepare("DELETE FROM note_items WHERE user_session_id = ?");
                    $delete_note_stmt->bind_param("s", $user_session_id);
                    $delete_note_stmt->execute();
                    $delete_note_stmt->close();

                    $conn->commit();

                    // 5. Get updated bank balance (even if not enforced, display might be needed)
                    // Assuming a bank table with user_session_id and balance
                    $updated_balance = 0; // Default if no bank data
                    $stmt_balance = $conn->prepare("SELECT balance FROM bank WHERE user_session_id = ?");
                    $stmt_balance->bind_param("s", $user_session_id);
                    $stmt_balance->execute();
                    $stmt_balance->bind_result($updated_balance);
                    $stmt_balance->fetch();
                    $stmt_balance->close();


                    $response = [
                        'status' => 'success',
                        'message' => 'Note paid successfully!',
                        'total_cost' => $total_cost,
                        'new_balance' => $updated_balance // Provide updated balance
                    ];
                }

            } catch (Exception $e) {
                $conn->rollback();
                $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
                error_log("Pay Note Error: " . $e->getMessage());
            } finally {
                $conn->close();
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Database connection failed.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Invalid request. User session ID is required.'];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method. Only POST is allowed.'];
}

echo json_encode($response);
?>