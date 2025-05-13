<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include 'db.php';
include '../func.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $sticky = $_POST['sticky'];
    if ($sticky == '1'){
        $dateNow = date('d M, Y h:i a');
        
    }else{
       $dateNow = '';
    }
    

    try {
        // Update query using PDO
        $sql = "UPDATE list SET sticky = :sticky, sticky_date = :sticky_date WHERE id = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':sticky', $sticky, PDO::PARAM_INT);
        $stmt->bindParam(':sticky_date', $dateNow, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->errorInfo()]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
