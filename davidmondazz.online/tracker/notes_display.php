<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Notes</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* Theme Colors - Dark Mode (Default) */
            --bg-color: #121212;
            --text-color: #e9ecef;
            --card-bg: #1e1e2d;
            --card-border: #2c2c3a;
            --timer-display-bg: #000;
            --timer-display-text: #0ff;
            --navbar-bg: #0d0d17;
            --navbar-text: #ffffff;
            --primary-color: #4d7cfe;
            --secondary-color: #6c757d;
            --success-color: #2fb344;
            --warning-color: #f7b924;
            --danger-color: #e63757;
            --info-color: #39afd1;
            --accent-color: #9776e0;
            --boxshadow-color: rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--boxshadow-color);
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: rgba(240, 245, 255, 0.05);
            border-bottom: 1px solid var(--card-border);
            padding: 15px 20px;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .notes-container {
            font-family: monospace;
            margin: 20px;
        }

        .notes-block {
            background-color: #121212;
            color: #e9ecef;
            padding: 20px;
            border-radius: 5px;
            white-space: pre;
            overflow-x: auto;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .timer-name {
            color: #4d7cfe;
            font-weight: bold;
        }

        .start-time {
            color: #2fb344;
        }

        .end-time {
            color: #f7b924;
        }

        .note-text {
            color: #e9ecef;
        }

        .back-button {
            margin: 20px;
            padding: 10px 20px;
            color: var(--text-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .back-button:hover {
            color: var(--primary-color);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Timers
        </a>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-sticky-note me-2"></i> Timer Notes
                </h4>
            </div>
            <div class="card-body">
                <div id="loading" class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading notes...</p>
                </div>
                
                <div id="notes-container" class="notes-container" style="display: none;">
                    <pre id="notes-block" class="notes-block"></pre>
                </div>
                
                <div id="no-notes-message" class="text-center py-3" style="display: none;">
                    <i class="fas fa-info-circle me-2"></i> No notes found.
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Load notes
            loadNotes();
        });
        
        function loadNotes() {
            // Show loading indicator
            document.getElementById('loading').style.display = 'block';
            document.getElementById('notes-container').style.display = 'none';
            document.getElementById('no-notes-message').style.display = 'none';
            
            fetch('api/get_all_notes.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Hide loading indicator
                    document.getElementById('loading').style.display = 'none';
                    
                    if (data.success && data.notes && data.notes.length > 0) {
                        // Show notes container
                        document.getElementById('notes-container').style.display = 'block';
                        
                        // Format and display notes
                        const notesBlock = document.getElementById('notes-block');
                        let notesText = "ID    | Timer Name          | Start Time           | End Time             | Note\n";
                        notesText += "-".repeat(100) + "\n";
                        
                        data.notes.forEach(note => {
                            const id = String(note.id).padEnd(5);
                            const timerName = note.timer_name.substring(0, 20).padEnd(20);
                            const startTime = note.start_time_formatted.padEnd(20);
                            const endTime = (note.stop_time_formatted || "In progress").padEnd(20);
                            
                            notesText += `${id}| ${timerName}| ${startTime}| ${endTime}| ${note.note}\n`;
                        });
                        
                        notesBlock.textContent = notesText;
                    } else {
                        // Show no notes message
                        document.getElementById('no-notes-message').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading notes:', error);
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('no-notes-message').style.display = 'block';
                    document.getElementById('no-notes-message').innerHTML = `<div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> 
                        Error loading notes: ${error.message}. Please try again.
                    </div>`;
                });
        }
    </script>
</body>
</html> 