<?php
date_default_timezone_set("Africa/Cairo");
include 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add_category') {
        $name = $_POST['name'];
        $color = $_POST['color']; // Add this line to retrieve the color
        echo addCategory($name, $color) ? 'success' : 'error';
        exit;
    }
    if (isset($_POST['add_task'])) {
        $category_id = $_POST['category_id'];
        $task = $_POST['task'];
        addTask($category_id, $task);
    }
    if (isset($_POST['delete_task'])) {
        $task_id = $_POST['task_id'];
        deleteTask($task_id);
    }
    if (isset($_POST['mark_done'])) {
        $task_id = $_POST['task_id'];
        markTaskDone($task_id);
    }
}

$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Todo System</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">

    <script>
$(document).ready(function() {
    // Add new task
    $('.add_task_form').submit(function(event) {
        event.preventDefault();
        var form = $(this);
        var category_id = form.find('input[name="category_id"]').val();
        var task = form.find('input[name="task"]').val();
        $.post('functions.php', {
            action: 'add_task',
            category_id: category_id,
            task: task
        }, function(response) {
            if (response === 'success') {
                // Reload tasks for the category
                loadTasks(category_id);
                form.find('input[name="task"]').val('');
            } else {
                alert('Failed to add task!');
            }
        });
    });

    // Mark task as done
    $('.category-section').on('click', '.task button[name="mark_done"]', function() {
        var task_id = $(this).closest('.task').data('task-id');
        var category_id = $(this).closest('.category-section').find('input[name="category_id"]').val(); // Retrieve category ID
        $.post('functions.php', {
            action: 'mark_done',
            task_id: task_id
        }, function(response) {
            if (response == 'success') {
                // Reload tasks for the category
                loadTasks(category_id); // Pass category ID to loadTasks()
            } else {
                alert('Failed to mark task as done!');
            }
        });
    });

    // Task Unpriority
    $('.category-section').on('click', '.task button[name="unpriority"]', function() {
        var task_id = $(this).closest('.task').data('task-id');
        var category_id = $(this).closest('.category-section').find('input[name="category_id"]').val(); // Retrieve category ID
        $.post('functions.php', {
            action: 'task_unpiority',
            task_id: task_id
        }, function(response) {
            if (response == 'success') {
                // Reload tasks for the category
                loadTasks(category_id); // Pass category ID to loadTasks()
            } else {
                alert('Failed to mark task as done!');
            }
        });
    });
    
// Task Priority
$('.category-section').on('click', '.task button[name="priority_up"]', function() {
    var task_id = $(this).closest('.task').data('task-id');
    var category_id = $(this).closest('.category-section').find('input[name="category_id"]').val(); // Retrieve category ID
    
    // Retrieve all tasks in the category
    var tasks = $('.category-section .task');
    
    // Update priority for the clicked task
    $(this).closest('.task').find('.priority-counter').text('1');

    // Update priorities for other tasks
    var priority = 2; // Start priority from 2 for other tasks
    tasks.each(function() {
        var $task = $(this);
        if ($task.data('task-id') !== task_id) {
            $task.find('.priority-counter').text(priority);
            priority++;
        }
    });

    // Send AJAX request to update priority in the database
    $.post('functions.php', {
        action: 'priority_up',
        task_id: task_id,
        priority: 1 // Set priority to 1 for the clicked task
    }, function(response) {
        if (response == 'success') {
            // Reload tasks for the category
            loadTasks(category_id); // Pass category ID to loadTasks()
        } else {
            alert('Failed to update priority!');
        }
    });
});



    // Handle form submission for adding a new category
   $('#add_category_form').submit(function(event) {
    event.preventDefault(); // Prevent the default form submission
    var form = $(this);
    var nameInput = form.find('input[name="name"]');
    var name = nameInput.val();
    
    // Generate a random color
    var randomColor = getRandomColor();
    
    $.post('functions.php', {
        action: 'add_category',
        name: name,
        color: randomColor
    }, function(response) {
        if (response == 'success') {
            // Clear the input field
            nameInput.val('');
            
            // Focus on the input field after a short delay
            setTimeout(function() {
                nameInput.focus();
            }, 100); // Adjust the delay as needed
            
            // Optionally, you can reload the page or update the UI as needed
            location.reload(); // Reload the page to display the newly added category
        } else {
            alert('Failed to add category!');
        }
    });
});



// Function to generate a random color
function getRandomColor() {
    var letters = '0123456789ABCDEF';
    var color = '#';
    for (var i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}



    // Load tasks for a category
    function loadTasks(category_id) {
        var container = $('#tasks-container-' + category_id);
        $.get('tasks.php?category_id=' + category_id, function(data) {
            container.html(data);
        });
    };
});

$(document).ready(function() {
    // Toggle visibility of add_task_form when toggle button is clicked
    $('.toggle-add-task-form').click(function() {
        // Find the add_task_form within the same category-section and toggle its visibility
        var addTaskForm = $(this).closest('.category-header').find('.add_task_form');
        addTaskForm.toggle();
        // If add_task_form is visible, focus on the input field
        if (addTaskForm.is(':visible')) {
            addTaskForm.find('input[name="task"]').focus();
        }
    });
});


</script>

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
    padding-left:5px;
    font-size:20px;
}

.tasks-container{
font-size:20px;
}
.task {
    margin-bottom: 5px; /* Adjust the value to increase or decrease the distance between tasks */
}
</style>

</head>
<body>
    <h1><a href="index.php" style="text-decoration: none;color: inherit;">New Category</a></h1>
    <form id="add_category_form" method="post">
        <input type="text" name="name" placeholder="Category Name">
        <button type="submit" name="add_category">Add!</button>
    </form>
    <br><br>
   

    <?php foreach ($categories as $category): ?>
        <div class="category-section">
    <div class="category-header">
        <h2 class="category-name">/<font color="#<?= $category['color'] ?>"><?= $category['name'] ?></font>::</h2>
        <button class="toggle-add-task-form btn btn-secondary btn-sm" style="width: 18px; height: 18px; padding: 0; font-size: 12px;"><i class="bi bi-plus bi-lg"></i></button>
        <form class="add_task_form" method="post" style="display: none;margin-left:10px;">
            <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
            <input type="text" name="task" placeholder="Add new task">
            <button type="submit">Add new task</button>
        </form>
    </div>
    <div class="tasks-container" id="tasks-container-<?= $category['id'] ?>">
       <?php
$tasks = getTasks($category['id']); // Assuming getTasks function retrieves tasks
$counter = 1; // Initialize counter variable
foreach ($tasks as $task):
?>
<div class="task" data-task-id="<?= $task['id'] ?>">
    <span style="padding-left:10px;">
        <div style="border: 1px solid black; padding:5px; text-align: center; display:inline;"><?= $counter ?></div>
        <?php if (!empty($task['priority'])): ?>
            <span class="task-priority" style="background-color: yellow;"><?= $task['task'] ?></span>
        <?php else: ?>
            <?= $task['task'] ?>
        <?php endif; ?>
    </span>
    <?php
   
        echo '<button type="button" name="mark_done" class="btn btn-success btn-sm" style="width: 18px; height: 18px; padding: 0; font-size: 14px;">
        <i class="bi bi-check2"></i>
        </button> - ';
        
          echo '<button type="button" name="priority_up" class="btn btn-primary btn-sm" style="width: 18px; height: 18px; padding: 0; font-size: 14px;">
        <i class="bi bi-arrow-up"></i>
        </button>';

        echo ' <button type="button" name="unpriority" class="btn btn-dark btn-sm" style="width: 18px; height: 18px; padding: 0; font-size: 14px;">
        <i class="bi bi-arrow-down"></i>
        </button>';
 
    ?>
</div>
<?php 
$counter++; // Increment counter
endforeach; ?>
<br>
</div>
</div>






    <?php endforeach; ?>
</body>


</html>

