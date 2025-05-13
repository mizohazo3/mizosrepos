<?php
date_default_timezone_set("Africa/Cairo");
$dateNow = date('d M, Y h:i:s a');
include 'db.php';
include '../db.php';


if (isset($_POST['action']) && $_POST['action'] == 'add_category') {
    $name = $_POST['name'];
    $color = $_POST['color']; // Add this line to retrieve the color
    echo addCategory($name, $color) ? 'success' : 'error';
    exit;
}

function addCategory($name, $color) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO categories (name, color) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $color);
    
    return $stmt->execute();
}

function getCategories() {
    global $conn;
    $result = $conn->query("SELECT * FROM categories");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function addTask($category_id, $task) {
    global $conn;
    $datetime = date('Y-m-d H:i:s'); // Get current date and time in MySQL format
    $stmt = $conn->prepare("INSERT INTO tasks (category_id, task, datetime) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $category_id, $task, $datetime);
    return $stmt->execute();
}

function getTasks($category_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE category_id = ? AND completed = 0 ORDER BY CASE WHEN priority = '' THEN 9999 ELSE CAST(priority AS SIGNED INTEGER) END ASC, id desc");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function deleteTask($task_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    return $stmt->execute();
}

function markTaskDone($task_id) {
    global $conn;
    
      $stmt_fetch_task_name = $conn->prepare("SELECT task FROM tasks WHERE id = ?");
    $stmt_fetch_task_name->bind_param("i", $task_id);
    $stmt_fetch_task_name->execute();
    $result = $stmt_fetch_task_name->get_result();
    $task_row = $result->fetch_assoc();
    $task_name = $task_row['task'];
    
    
    $stmt_mark_task_done = $conn->prepare("UPDATE tasks SET completed = 1, test = ? WHERE id = ?");
    $stmt_mark_task_done->bind_param("si", $task_name, $task_id);
    
         
      if ($stmt_mark_task_done->execute()) {
        return 'success';
    } else {
        // Log or display error
        error_log("Error marking task as done: " . $stmt_mark_task_done->error);
        return 'error';
    }
    
    
    
}


function taskUnpriority($task_id) {
    global $conn;
    
    // Retrieve task name
    $stmt_fetch_task_name = $conn->prepare("SELECT task FROM tasks WHERE id = ?");
    $stmt_fetch_task_name->bind_param("i", $task_id);
    $stmt_fetch_task_name->execute();
    $result = $stmt_fetch_task_name->get_result();
    $task_row = $result->fetch_assoc();
    $task_name = $task_row['task'];
    
    // Update task priority to NULL
    $stmt_mark_task_done = $conn->prepare("UPDATE tasks SET priority = NULL WHERE id = ?");
    $stmt_mark_task_done->bind_param("i", $task_id);
    
    if ($stmt_mark_task_done->execute()) {
        return 'success';
    } else {
        // Log or display error
        error_log("Error marking task as done: " . $stmt_mark_task_done->error);
        return 'error';
    }
}



function PriorityUP($task_id, $priority) {
    global $conn;
    
      $stmt_fetch_task_name = $conn->prepare("SELECT task FROM tasks WHERE id = ?");
    $stmt_fetch_task_name->bind_param("i", $task_id);
    $stmt_fetch_task_name->execute();
    $result = $stmt_fetch_task_name->get_result();
    $task_row = $result->fetch_assoc();
    $task_name = $task_row['task'];
    
    
    if (!empty($priority)) {
        // If $priority is not empty, bind it to the prepared statement
        $stmt_mark_task_done = $conn->prepare("UPDATE tasks SET priority = ?, test = ? WHERE id = ?");
        $stmt_mark_task_done->bind_param("isi", $priority, $task_name, $task_id);
    } else {
        // If $priority is empty, set it to NULL
        $priority = NULL;
        $stmt_mark_task_done = $conn->prepare("UPDATE tasks SET priority = ?, test = ? WHERE id = ?");
        $stmt_mark_task_done->bind_param("ssi", $priority, $task_name, $task_id);
    }
    
    if ($stmt_mark_task_done->execute()) {
        return 'success';
    } else {
        // Log or display error
        error_log("Error marking task as done: " . $stmt_mark_task_done->error);
        return 'error';
    }
    
    
    
}

// Check if AJAX request for adding task
if (isset($_POST['action']) && $_POST['action'] == 'add_task') {
    $category_id = $_POST['category_id'];
    $task = $_POST['task'];
    echo addTask($category_id, $task) ? 'success' : 'error';
    exit;
}

// Check if AJAX request for marking task as done
if (isset($_POST['action']) && $_POST['action'] == 'mark_done') {
    $task_id = $_POST['task_id'];
    echo markTaskDone($task_id);
    exit;
}

// Check if AJAX request for Priority Task
if (isset($_POST['action']) && $_POST['action'] == 'priority_up') {
    $task_id = $_POST['task_id'];
    $priority = $_POST['priority'];

    echo PriorityUP($task_id, $priority);
    exit;
}

// Check if AJAX request for UnPriority Task
if (isset($_POST['action']) && $_POST['action'] == 'task_unpiority') {
    $task_id = $_POST['task_id'];

    echo taskUnpriority($task_id);
    exit;
}

?>
