<?php
require_once 'config.php';
session_start();

// Check if user has admin privileges (implement your auth logic here)
// For now, we'll keep it simple for development

// Database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submissions
$message = "";
$error = "";

// Reset timer
if (isset($_POST['reset_timer'])) {
    $timer_id = $_POST['timer_id'];
    $sql = "UPDATE timers SET total_time = 0, status = 'idle' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $timer_id);
    
    if ($stmt->execute()) {
        $message = "Timer reset successfully";
    } else {
        $error = "Error resetting timer: " . $conn->error;
    }
    $stmt->close();
}

// Delete timer
if (isset($_POST['delete_timer'])) {
    $timer_id = $_POST['timer_id'];
    $sql = "DELETE FROM timers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $timer_id);
    
    if ($stmt->execute()) {
        $message = "Timer deleted successfully";
    } else {
        $error = "Error deleting timer: " . $conn->error;
    }
    $stmt->close();
}

// Lock timer
if (isset($_POST['lock_timer'])) {
    $timer_id = $_POST['timer_id'];
    $lock_type = $_POST['lock_type'];
    
    $sql = "UPDATE timers SET manage_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $lock_type, $timer_id);
    
    if ($stmt->execute()) {
        $message = "Timer locked successfully";
    } else {
        $error = "Error locking timer: " . $conn->error;
    }
    $stmt->close();
}

// Unlock timer
if (isset($_POST['unlock_timer'])) {
    $timer_id = $_POST['timer_id'];
    
    $sql = "UPDATE timers SET manage_status = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $timer_id);
    
    if ($stmt->execute()) {
        $message = "Timer unlocked successfully";
    } else {
        $error = "Error unlocking timer: " . $conn->error;
    }
    $stmt->close();
}

// Add category
if (isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];
    $sql = "INSERT INTO categories (name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category_name);
    
    if ($stmt->execute()) {
        $message = "Category added successfully";
    } else {
        $error = "Error adding category: " . $conn->error;
    }
    $stmt->close();
}

// Delete category
if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    
    if ($stmt->execute()) {
        $message = "Category deleted successfully";
    } else {
        $error = "Error deleting category: " . $conn->error;
    }
    $stmt->close();
}

// Get all timers
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'total_time';
$order_direction = isset($_GET['direction']) ? $_GET['direction'] : 'DESC';

// Validate order parameters to prevent SQL injection
$allowed_columns = ['total_time', 'name', 'status'];
if (!in_array($order_by, $allowed_columns)) {
    $order_by = 'total_time';
}

$allowed_directions = ['ASC', 'DESC'];
if (!in_array($order_direction, $allowed_directions)) {
    $order_direction = 'DESC';
}

// Custom ordering for status
if ($order_by == 'status') {
    if ($order_direction == 'DESC') {
        $timers_sql = "SELECT t.*, c.name as category_name 
                      FROM timers t 
                      LEFT JOIN categories c ON t.category_id = c.id 
                      ORDER BY 
                      CASE 
                          WHEN t.manage_status = 'lock&special' THEN 1 
                          WHEN t.manage_status = 'lock' THEN 2
                          WHEN t.status = 'idle' THEN 5
                          WHEN t.status = 'paused' THEN 4
                          WHEN t.status = 'running' THEN 3
                          ELSE 6
                      END";
    } else {
        $timers_sql = "SELECT t.*, c.name as category_name 
                      FROM timers t 
                      LEFT JOIN categories c ON t.category_id = c.id 
                      ORDER BY 
                      CASE 
                          WHEN t.manage_status = 'lock&special' THEN 6 
                          WHEN t.manage_status = 'lock' THEN 5
                          WHEN t.status = 'idle' THEN 2
                          WHEN t.status = 'paused' THEN 3
                          WHEN t.status = 'running' THEN 4
                          ELSE 1
                      END";
    }
} else {
    $timers_sql = "SELECT t.*, c.name as category_name 
                  FROM timers t 
                  LEFT JOIN categories c ON t.category_id = c.id 
                  ORDER BY t.$order_by $order_direction";
}
$timers_result = $conn->query($timers_sql);

// Get all categories
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel - Timer Tracking System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            padding-top: 56px;
            padding-bottom: 20px;
        }
        
        .navbar {
            background-color: #212529 !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            padding: 15px 0;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .table th {
            font-weight: 600;
            color: #495057;
        }
        
        .btn-admin {
            padding: 8px 15px;
            font-weight: 500;
            border-radius: 5px;
        }
        
        .stats-card {
            background: linear-gradient(to right, #4e73df, #224abe);
            color: white;
        }
        
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stats-number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        /* Sort styles */
        th a {
            color: #495057;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        th a:hover {
            color: #0d6efd;
        }
        
        th a i {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Timer Tracking</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_control.php"><i class="fas fa-cog"></i> Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="debug.php"><i class="fas fa-bug"></i> Debug</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-cogs me-2"></i>Admin Control Panel</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <!-- Timer Stats -->
            <div class="col-md-4">
                <div class="card stats-card text-center p-3">
                    <div class="stats-icon"><i class="fas fa-stopwatch"></i></div>
                    <div class="stats-title">Total Timers</div>
                    <div class="stats-number"><?php echo $timers_result->num_rows; ?></div>
                </div>
            </div>
            
            <!-- Categories Stats -->
            <div class="col-md-4">
                <div class="card stats-card text-center p-3">
                    <div class="stats-icon"><i class="fas fa-tags"></i></div>
                    <div class="stats-title">Categories</div>
                    <div class="stats-number"><?php echo $categories_result->num_rows; ?></div>
                </div>
            </div>
            
            <!-- Active Timers Stats -->
            <div class="col-md-4">
                <div class="card stats-card text-center p-3">
                    <div class="stats-icon"><i class="fas fa-play-circle"></i></div>
                    <div class="stats-title">Active Timers</div>
                    <?php
                    $active_sql = "SELECT COUNT(*) as active_count FROM timers WHERE status = 'running'";
                    $active_result = $conn->query($active_sql);
                    $active_count = $active_result->fetch_assoc()['active_count'];
                    ?>
                    <div class="stats-number"><?php echo $active_count; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Manage Timers -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-stopwatch me-2"></i>Manage Timers</h5>
                <div class="input-group" style="max-width: 300px;">
                    <input type="text" id="timer-search" class="form-control" placeholder="Search timers...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="timers-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>
                                    <a href="?order_by=name&direction=<?php echo ($order_by == 'name' && $order_direction == 'ASC') ? 'DESC' : 'ASC'; ?>">
                                        Name
                                        <?php if ($order_by == 'name'): ?>
                                            <i class="fas fa-sort-<?php echo $order_direction == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Category</th>
                                <th>
                                    <a href="?order_by=status&direction=<?php echo ($order_by == 'status' && $order_direction == 'ASC') ? 'DESC' : 'ASC'; ?>">
                                        Status
                                        <?php if ($order_by == 'status'): ?>
                                            <i class="fas fa-sort-<?php echo $order_direction == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?order_by=total_time&direction=<?php echo ($order_by == 'total_time' && $order_direction == 'ASC') ? 'DESC' : 'ASC'; ?>">
                                        Duration
                                        <?php if ($order_by == 'total_time'): ?>
                                            <i class="fas fa-sort-<?php echo $order_direction == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($timers_result->num_rows > 0): ?>
                                <?php while ($timer = $timers_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $timer['id']; ?></td>
                                        <td><?php echo htmlspecialchars($timer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($timer['category_name'] ?? 'None'); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                if ($timer['manage_status'] === 'lock' || $timer['manage_status'] === 'lock&special') {
                                                    echo 'bg-danger';
                                                } else if ($timer['status'] == 'running') {
                                                    echo 'bg-success';
                                                } else if ($timer['status'] == 'paused') {
                                                    echo 'bg-warning';
                                                } else {
                                                    echo 'bg-secondary';
                                                }
                                            ?>">
                                                <?php 
                                                    if ($timer['manage_status'] === 'lock') {
                                                        echo 'Locked';
                                                    } else if ($timer['manage_status'] === 'lock&special') {
                                                        echo 'Lock&Special';
                                                    } else {
                                                        echo ucfirst($timer['status']);
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $total_time = $timer['total_time'];
                                            $hours = floor($total_time / 3600);
                                            $minutes = floor(($total_time % 3600) / 60);
                                            $secs = $total_time % 60;
                                            echo sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="timer_details.php?id=<?php echo $timer['id']; ?>" class="btn btn-sm btn-info btn-admin me-1">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (empty($timer['manage_status'])): ?>
                                                <div class="dropdown d-inline-block me-1">
                                                    <button class="btn btn-sm btn-secondary btn-admin dropdown-toggle" type="button" id="lockDropdown<?php echo $timer['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="lockDropdown<?php echo $timer['id']; ?>">
                                                        <li>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="timer_id" value="<?php echo $timer['id']; ?>">
                                                                <input type="hidden" name="lock_type" value="lock">
                                                                <button type="submit" name="lock_timer" class="dropdown-item">
                                                                    <i class="fas fa-lock me-2"></i>Lock
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="timer_id" value="<?php echo $timer['id']; ?>">
                                                                <input type="hidden" name="lock_type" value="lock&special">
                                                                <button type="submit" name="lock_timer" class="dropdown-item">
                                                                    <i class="fas fa-user-lock me-2"></i>Lock & Special
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <?php else: ?>
                                                <form method="post" class="d-inline me-1" onsubmit="return confirm('Are you sure you want to unlock this timer?');">
                                                    <input type="hidden" name="timer_id" value="<?php echo $timer['id']; ?>">
                                                    <button type="submit" name="unlock_timer" class="btn btn-sm btn-success btn-admin">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this timer?');">
                                                    <input type="hidden" name="timer_id" value="<?php echo $timer['id']; ?>">
                                                    <button type="submit" name="delete_timer" class="btn btn-sm btn-danger btn-admin">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No timers found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Manage Categories -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Manage Categories</h5>
                <div class="d-flex">
                    <div class="input-group me-2" style="max-width: 250px;">
                        <input type="text" id="category-search" class="form-control" placeholder="Search categories...">
                        <button class="btn btn-outline-secondary" type="button" id="clear-category-search">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="categories-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Timers Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categories_result->num_rows > 0): ?>
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td>
                                            <?php
                                            $cat_id = $category['id'];
                                            $count_sql = "SELECT COUNT(*) as timer_count FROM timers WHERE category_id = $cat_id";
                                            $count_result = $conn->query($count_sql);
                                            $timer_count = $count_result->fetch_assoc()['timer_count'];
                                            echo $timer_count;
                                            ?>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category? All associated timers will be set to no category.');">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger btn-admin">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No categories found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Format times
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }
        
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Timer search
            const searchInput = document.getElementById('timer-search');
            const clearButton = document.getElementById('clear-search');
            const table = document.getElementById('timers-table');
            const rows = table.querySelectorAll('tbody tr');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                rows.forEach(row => {
                    const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const category = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    
                    if (name.includes(searchTerm) || category.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                rows.forEach(row => {
                    row.style.display = '';
                });
            });
            
            // Category search
            const categorySearchInput = document.getElementById('category-search');
            const clearCategoryButton = document.getElementById('clear-category-search');
            const categoryTable = document.getElementById('categories-table');
            const categoryRows = categoryTable.querySelectorAll('tbody tr');
            
            categorySearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                categoryRows.forEach(row => {
                    const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    
                    if (name.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            clearCategoryButton.addEventListener('click', function() {
                categorySearchInput.value = '';
                categoryRows.forEach(row => {
                    row.style.display = '';
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?> 