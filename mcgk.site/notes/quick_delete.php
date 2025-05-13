<?php
// Include your database connection
include 'db.php'; // Adjust the path if necessary

// Check if the ID is passed via POST
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        // Prepare and execute the delete query
        $sql = "DELETE FROM list WHERE id = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // If deletion is successful, return a success response
            echo json_encode(['success' => true]);
        } else {
            // If deletion fails, return an error response
            echo json_encode(['success' => false, 'error' => 'Failed to delete the item.']);
        }
    } catch (PDOException $e) {
        // If there’s an exception, return an error message
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // If no ID is provided, return an error response
    echo json_encode(['success' => false, 'error' => 'No ID provided.']);
}
?>
