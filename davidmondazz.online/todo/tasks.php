
<?php
date_default_timezone_set("Africa/Cairo");
include 'functions.php';

if (isset($_GET['category_id'])) {
    $category_id = $_GET['category_id'];
    $tasks = getTasks($category_id);
    $counter = 1; // Initialize counter variable
    foreach ($tasks as $task) {
        echo '<div class="task" data-task-id="' . $task['id'] . '"><li data-task-id="' . $task['id'] . '"><span style="padding-left:10px;">';
        ?>
        
        <div style="border: 1px solid black; padding:5px; text-align: center; display:inline;"><?= $counter ?></div>
        <?php if (!empty($task['priority'])): ?>
            <span class="task-priority" style="background-color: yellow;"><?= $task['task'] ?></span>
        <?php else: ?>
            <?= $task['task'] ?>
        <?php endif; ?>
        
        <?php
        echo '</span> <button type="button" name="mark_done" class="btn btn-success btn-sm" style="width: 18px; height: 18px; padding: 0; font-size: 14px;"><i class="bi bi-check2"></i></button> - ';
        
        echo '<button type="button" name="priority_up" class="btn btn-primary btn-sm" style="width: 18px; height: 18px; padding: 0; font-size: 14px;display:inline;">
        <i class="bi bi-arrow-up"></i>
        </button>';
       
        echo ' <button type="button" name="unpriority" class="btn btn-dark btn-sm" style="width: 18px; height: 18px; padding: 0; font-size: 14px;">
        <i class="bi bi-arrow-down"></i>
        </button></li></div>'; // Closing tags adjusted
        
        $counter++; // Increment counter for each task
    }
    echo '<Br>';
}


?>
 </div>
<style>
.category-section {
    display: flex;
    flex-direction: column;
}

.category-header {
    display: flex;
    align-items: center;
}

.category-name {
    margin-right: 10px;
}

.tasks-container{
font-size:20px;
}
.task {
    margin-bottom: 5px; /* Adjust the value to increase or decrease the distance between tasks */
}
</style>
