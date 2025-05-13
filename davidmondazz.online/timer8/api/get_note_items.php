<?php

include_once '../timezone_config.php';
header('Content-Type: application/json');

// This is a temporary file for debugging the frontend rendering.
// It returns dummy data in the expected format.

$response = [
    'status' => 'success',
    'items' => [
        [
            'id' => 1, // Database ID for the note entry
            'item_id' => 1, // ID of the marketplace item
            'name' => 'Dummy Item 1',
            'price' => '10.00',
            'image_url' => 'https://via.placeholder.com/50/FF0000/FFFFFF?text=Dummy1',
            'quantity' => 1 // Assuming quantity is handled by grouping in frontend
        ],
        [
            'id' => 2,
            'item_id' => 2,
            'name' => 'Dummy Item 2',
            'price' => '5.50',
            'image_url' => 'https://via.placeholder.com/50/00FF00/FFFFFF?text=Dummy2',
            'quantity' => 1
        ],
         [
            'id' => 3,
            'item_id' => 1, // Same item_id as Dummy Item 1 to test grouping
            'name' => 'Dummy Item 1',
            'price' => '10.00',
            'image_url' => 'https://via.placeholder.com/50/FF0000/FFFFFF?text=Dummy1',
            'quantity' => 1
        ]
    ],
    'current_balance' => 100.00 // Dummy balance
];

echo json_encode($response);
?>