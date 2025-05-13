<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include 'db.php';
include '../func.php';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;

$titleError = '';
$notesError = '';
$addMsg = '';
$note_title = '';
$note_content = '';
$dateNow = date('d M, Y h:i a');
if (isset($_POST['addnew']) && $_POST['addnew'] == 'AddNote') {
    $note_title = $_POST['note_title'];
    $note_content = stripslashes(str_replace('\r\n', '<br/>', $_POST['notes']));
    $sticky = isset($_POST['sticky']) ? 1 : '';
    $sticky_date = $sticky ? $dateNow : '';
    if (empty($_POST['note_title'])) {
        $titleError = '<font color="red">Title is required!</font>';
    } elseif (empty($_POST['notes'])) {
        $notesError = '<font color="red">Content is Empty!</font>';
    } else {
        $insert = $con->prepare("INSERT INTO list (note_title, note_content, status, date_added, sticky, sticky_date) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$note_title, $note_content, 'on', $dateNow, $sticky, $sticky_date]);
        $lastId = $con->lastInsertId();
        $addMsg = '<font color="green"><b> --> <a href="index.php?showID=' . $lastId . '">' . htmlspecialchars($note_title) . '</a> ADDED!</b></font>';
    }
}

if (isset($_POST['yes_delete']) && $_POST['yes_delete'] == 'YES!') {
    $getid = $_POST['hiddenID'];
    $delete = $con->query("DELETE FROM list WHERE id=$getid");
}

$stuckMsg = '';
if (isset($_POST['yes_stick']) && $_POST['yes_stick'] == 'YES!') {
    $getid = $_POST['hiddenID'];
    $stick = $con->query("UPDATE list SET sticky='1' WHERE id=$getid");
   
}

if (isset($_POST['yes_unstick']) && $_POST['yes_unstick'] == 'YES!') {
    $getid = $_POST['hiddenID'];
    $stick = $con->query("UPDATE list SET sticky='' WHERE id=$getid");
    
}

$editMsg = '';
if (isset($_POST['edit']) && $_POST['edit'] == 'Edit!') {
    $getid = $_POST['hiddenID'];
    $note_title = stripslashes(str_replace('\r\n', '<br/>', $_POST['note_title']));
    $note_content = stripslashes(str_replace('\r\n', '<br/>', $_POST['note_content']));
    $update = $con->prepare("UPDATE list SET note_title=?, note_content=?, update_date=? where id=?");
    $update->execute([$note_title, $note_content, $dateNow, $getid]);
    if ($update) {
        $editMsg = '<font color="green">Edited Successfully!</font>';
    }
}

?>


<html>
<head>
<title>Notes</title>

<link rel="stylesheet" href="css/bootstrap.min.css"/>

<script src="js/jquery-3.6.0.min.js"></script>
<style>
    form{
        padding:20px;
    }
    .buttons{
        margin:10px;
    }
    html, body{
        height: 100%;
        margin: 0;
    font-family: Arial, sans-serif;
    }
    html{
    display: table;
    margin: auto;
    }
    body{
        text-align:center;
        display: table-cell;
        padding-bottom: 70px;
    }
    blockquote {
  background: #e3e3e3;
  border-left: 3px solid #bebebe;
  margin: 1.5em 10px 0 0;
  padding: 0.5em 10px;
  text-align: center;
  position: relative;
  min-height: 450px;
  width: 500px; /* adjust the value as needed */
  display: inline-block;
  vertical-align: top;
  position: relative;
}

.note-title {
  background: #AAAAAA;
  padding: 5px;
  color: #212121;
  margin: 0;
  position: absolute;
  top: -15px;
  left: 50%;
  transform: translateX(-50%); /* added */
  text-align: center; /* added */
}


.date_added{
   float:right;
   font-size: 12px;
   font-style: italic;
   padding-top:10px;
}
.date_added_Show{
   position: absolute;
   left:15;
   bottom:0;
   font-size: 12px;
   font-style: italic;
   border: 1px solid black;
   padding: 3px;
}

.icons{
  float:right;
}

.live-container {
    display:flex;
    align-items: center; 
    vertical-align: middle; /* Align vertically in the middle */
    float:left;
    height:40px;
    float:right;
    font-size:11px; important!
}

/* Optional: Adjust spacing between elements */
#LiveRefresh {
    margin-right: 10px; /* Adjust margin as needed */
}

.image-button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .image-button img {
            display: inline-block;
        }
        
        #searchInput {
    width: 50%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

#resultsContainer {
    margin-top: 20px;
}

.search-result {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.search-result h3 {
    margin: 0;
    font-size: 18px;
}

.search-result p {
    font-size: 14px;
    color: #666;
}

.pagination {
        margin-top: 50px;   /* Adds space between pagination and notes */
    text-align: center; /* Ensures pagination links are centered */
    display: flex;      /* Use flexbox to center pagination */
    justify-content: center;  /* Centers the pagination items */

}

.pagination a {
        margin: 0 5px;
        padding: 5px 10px;
        text-decoration: none;
        background-color: #f1f1f1;
        border: 1px solid #ddd;
    }
    .pagination a.active {
        background-color: #4CAF50;
        color: white;
    }
    
 @media screen and (-webkit-min-device-pixel-ratio:0) { 
  select:focus,
  textarea:focus,
  input:focus {
    font-size: 16px;
    background: #eee;
  }
}


</style>
</head>

<body>
<Br>
<div>
  <span>Logged as: <b><?=$userLogged;?></b> <a href="../leave.php" class="btn btn-warning btn-sm">Leave!</a> <a href="../index.php" class="btn btn-secondary btn-sm" style="margin:5px;">Main</a><div class="live-container">
    <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
    <span id="LiveNotifications"></span>
</div></span>
  
</div>
<br>


<?php

if(isset($_GET['editID'])){
 $getid=$_GET['showID'];
    echo ' <p><a href="index.php?showID='.$getid.'"><img src="logo.png"></a></p>';
    

}elseif(isset($_GET['showID'])){
   echo ' <p><a href="index.php"><img src="logo.png"></a></p>';
}else{
   echo ' <a href="index.php" style="display: inline-block;"><img src="logo.png"></a>
   <form action="index.php" method="post">
   NoteTitle <input type="text" name="note_title" value="'.$note_title.'" class="buttons"> Sticky? <input type="checkbox" name="sticky" value="1" class="buttons"> '.$titleError.'<br>
   Content: <textarea name="notes" rows="4" cols="50" class="buttons">'.$note_content.'</textarea> '.$notesError.'<br><br>
   <input type="submit" name="addnew" value="AddNote" class="btn btn-info"> '.$addMsg.'
   </form>';
}


echo '<input type="text" id="searchInput" placeholder="Search notes..." onkeyup="searchNotes()" class="search-bar">';
echo '<div id="resultsContainer"></div>';


// Determine the current page number
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$notesPerPage = 10;
$offset = ($currentPage - 1) * $notesPerPage;

// Get the total number of notes in the database
$totalNotes = $con->query("SELECT COUNT(*) AS count FROM list")->fetch()['count'];
$totalPages = ceil($totalNotes / $notesPerPage);


if(!isset($_GET['showID'])){
    echo '<div class="pagination">';

// First Page Button
if ($currentPage > 1) {
    echo '<a href="index.php?page=1">First Page</a>';
}

// Previous Button
if ($currentPage > 1) {
    echo '<a href="index.php?page=' . ($currentPage - 1) . '">Previous</a>';
}

// Page Number Links
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $currentPage) ? 'active' : '';
    echo '<a href="index.php?page=' . $i . '" class="' . $activeClass . '">' . $i . '</a>';
}

// Next Button
if ($currentPage < $totalPages) {
    echo '<a href="index.php?page=' . ($currentPage + 1) . '">Next</a>';
}

// Last Page Button
if ($currentPage < $totalPages) {
    echo '<a href="index.php?page=' . $totalPages . '">Last Page</a>';
}

// Show All Notes Button
echo '<a href="index.php?page=1&showAll=true">Show All Notes</a>';

echo '</div>';
}




if (isset($_GET['showID'])) {
    $showID = $_GET['showID'];
    $selectall = $con->query("SELECT * FROM list WHERE id='$showID'");
} elseif (isset($_GET['showAll']) && $_GET['showAll'] == 'true') {
    // Show all notes without pagination
    $selectall = $con->query("SELECT * FROM list ORDER BY sticky DESC, STR_TO_DATE(sticky_date, '%d %b, %Y %h:%i %p') DESC, id DESC");
} else {
    // Paginated query
    $selectall = $con->query("SELECT * FROM list ORDER BY sticky DESC, STR_TO_DATE(sticky_date, '%d %b, %Y %h:%i %p') DESC, id DESC LIMIT $offset, $notesPerPage");
}



while ($row = $selectall->fetch()) {
    $note_title = "<a href='index.php?showID=" . $row['id'] . "'>".$row['note_title']."</a>";
    $note_content = nl2br(makeClickableLinks($row['note_content']));

    

  

    $submitButton = '';
    $formStart = '';
    $formEnd = '';
    $refreshICON = '';
    

    
     $deleteICON = "<button onclick='removeButton(" . $row['id'] . ", \"" . addslashes($row['note_title']) . "\")' class='image-button'><img src='img/remove.png' style='text-decoration: none;'></button>";

   



    if (isset($_GET['showID']) && isset($_GET['editID'])) {
        
        $getid = $_GET['showID'];
        $refreshICON = "<a href='index.php?showID=" . $row['id']."'><img src='img/refresh.png' style='float:left;padding-top:6px;'></a>";
        if ($getid == $row['id']) {
            $formStart = '<form action="index.php?showID=' . $getid . '&editID=' . $getid . '" method="post">';
            $note_title = '<input type="text" name="note_title" value="' . $row['note_title'] . '" style="width: 400px;">';
            $note_content = '<textarea name="note_content" style="width: 400px; height: 450px;">' . $row['note_content'] . '</textarea>';
            $submitButton = '<input type="submit" name="edit" value="Edit!" class="btn btn-success">';
            $formEnd = '<input type="hidden" name="hiddenID" value="' . $row['id'] . '"></form>';
        }

        echo "<blockquote class='text-center'><h5 class='bg-light p-2'>" . $formStart . $note_title . $refreshICON." <p class='icons'>  $deleteICON</p><p class='date_added'>Last Updated: " . $row['update_date'] . "<br> Added: " . $row['date_added'] . "</p></h5><p>" . $note_content . "</p>$submitButton $editMsg $formEnd</blockquote>";

    }else{
        if($row['sticky'] == 1){
            $stickStatus = '<button onclick="changeSticky('.$row[id].', 0)" class="image-button"><img src="img/unpinned.png" style="  text-decoration: none; "></button>';

          echo "<blockquote class='text-center'>
    <h5 class='bg-warning p-2'>" . $formStart . '<b>' . $note_title . '</b>' . $refreshICON . " 
        <p class='icons'>
            <a href='index.php?showID=" . $row['id'] . "&editID=" . $row['id'] . "' style='text-decoration: none;'>
                <img src='img/edit.png' style='opacity: 0.5;'>
            </a>
            $stickStatus
            $deleteICON
        </p>
    </h5>
    <p class='note-content' id='noteContent-" . $row['id'] . "' style='text-align:left;margin-left:5px;'>" . $note_content . "</p>
    $submitButton $editMsg $formEnd
    <div id='spacer' style='width: 200px; height: 70px; margin-right:0px;'></div>
    <p class='date_added_Show'>
        <font color='green'>Last Updated: " . $row['update_date'] . "</font><br>
        <font color='red'>Added: " . $row['date_added'] . "</font>
    </p>
</blockquote>";

        }else{
            $stickStatus = '<button onclick="changeSticky('.$row[id].', 1)" class="image-button"><img src="img/sticky.png" style="  text-decoration: none; "></button>';


    

            echo "<blockquote class='text-center'><h5 class='bg-light p-2'>" . $formStart . $note_title . $refreshICON." <p class='icons'> <a href='index.php?showID=" . $row['id'] . "&editID=" . $row['id'] . "' style='  text-decoration: none; '><img src='img/edit.png' style='opacity: 0.5;'> </a> $stickStatus $deleteICON</p></h5><p>" . $note_content . "</p>$submitButton $editMsg $formEnd <div id='spacer' style='width: 200px; height: 70px; margin-right:0px;'></div><p class='date_added_Show'><font color='green'>Last Updated: " . $row['update_date'] . "</font><br><font color='red'>Added: " . $row['date_added'] . "</font></p></blockquote>";


        }

    }



           

        
          
       
      
    

}
if(!isset($_GET['showID'])){
?>


<!-- Pagination Links -->
<div class="pagination">
    <!-- First Page Button -->
    <?php if ($currentPage > 1): ?>
        <a href="index.php?page=1">First Page</a>
    <?php endif; ?>

    <!-- Previous Button -->
    <?php if ($currentPage > 1): ?>
        <a href="index.php?page=<?= $currentPage - 1; ?>">Previous</a>
    <?php endif; ?>

    <!-- Page Number Links -->
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="index.php?page=<?= $i; ?>" 
           class="<?= $i == $currentPage ? 'active' : ''; ?>"><?= $i; ?></a>
    <?php endfor; ?>

    <!-- Next Button -->
    <?php if ($currentPage < $totalPages): ?>
        <a href="index.php?page=<?= $currentPage + 1; ?>">Next</a>
    <?php endif; ?>

    <!-- Last Page Button -->
    <?php if ($currentPage < $totalPages): ?>
        <a href="index.php?page=<?= $totalPages; ?>">Last Page</a>
    <?php endif; ?>

    <!-- Show All Notes Button -->
    <a href="index.php?page=1&showAll=true">Show All Notes</a>
</div>

<?php
};

?>

<script>

  $(document).delegate(".FastStart", "click", function(){
    var name = $(this).parents("span").attr("name");
    var id = $(this).parents("span").attr("id");

    // Directly call the AJAX request without showing the confirmation
   $.ajax({
    type: 'GET',
    url: '<?php echo $mainDomainURL; ?>/tracker/faststart.php', // Absolute URL based on main domain
    data: { name: name, id: id },
    beforeSend: function () {},
    success: function (response) {
        location.reload();
    }
});
});

 $(document).delegate(".FastStop", "click", function(){
    var name = $(this).parents("span").attr("name");
    var id = $(this).parents("span").attr("id");

    // Directly call the AJAX request without showing the confirmation
   $.ajax({
    type: 'GET',
    url: '<?php echo $mainDomainURL; ?>/tracker/stop.php', // Absolute URL based on main domain
    data: { name: name, id: id },
    beforeSend: function () {},
    success: function (response) {
        location.reload();
    }
});
});

    document.addEventListener("DOMContentLoaded", function(event) {
            var scrollpos = localStorage.getItem('scrollpos');
            if (scrollpos) window.scrollTo(0, scrollpos);
        });

        window.onbeforeunload = function(e) {
            localStorage.setItem('scrollpos', window.scrollY);
        };
        
    // Define the AJAX function
function loadContent() {
    $.ajax({
        url: '../checkWorking.php', // URL of your PHP script
        type: 'GET',
        success: function(data) {
            // Update only the specific parts of your page
            $('#LiveRefresh').html(data);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX request failed: ' + textStatus);
        }
    });
}

// Call the function immediately when the page loads
loadContent();

// Then call the function every second
setInterval(loadContent, 1000);


var lastData = null; // Variable to store the last received data

function loadNotif() {
    $.ajax({
        url: '../notifications.php',
        type: 'GET',
        success: function(data) {
            // Only update the content if the data has changed
            if (data !== lastData) {
                $('#LiveNotifications').html(data);
                lastData = data; // Update the last received data
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX request failed: ' + textStatus);
        }
    });
}

// Call the function immediately when the page loads
loadNotif();

// Then call the function every second
setInterval(loadNotif, 1000);

 function changeSticky(id, sticky) {
            $.ajax({
                url: 'update_sticky.php',
                type: 'POST',
                data: {
                    id: id,
                    sticky: sticky
                },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('Failed to update sticky status: ' + result.error);
                    }
                },
                error: function() {
                    alert('An error occurred while updating the sticky status.');
                }
            });
        }        
        
        function removeButton(id, noteTitle) {
    // Show a confirmation message with the note title
    var confirmDelete = confirm("Are you sure you want to delete the note: '" + noteTitle + "'?");

    if (confirmDelete) {
        // Proceed with the deletion (AJAX request or DOM manipulation)

        // Example: Making an AJAX request to delete the item
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "quick_delete.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                 location.href = 'index.php';
            } else {
                alert("Failed to delete the note.");
            }
        };
        xhr.send("id=" + id); // Sending the ID of the item to delete

    }
}

function searchNotes() {
    var query = document.getElementById('searchInput').value;

    if (query.trim() === '') {
        document.getElementById('resultsContainer').innerHTML = ''; // Clear results if search is empty
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'search.php?q=' + encodeURIComponent(query), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById('resultsContainer').innerHTML = xhr.responseText;
            var hr = document.createElement('hr');
            document.getElementById('resultsContainer').appendChild(hr);
        }
    };
    xhr.send();
}

function setTextDirection(noteId) {
        const noteContentElement = document.getElementById(noteId);
        const text = noteContentElement.textContent || noteContentElement.innerText;

        // Regular expression to detect Arabic characters
        const arabicPattern = /[\u0600-\u06FF]/;

        if (arabicPattern.test(text)) {
            noteContentElement.style.direction = 'rtl';
            noteContentElement.style.textAlign = 'right';
        } else {
            noteContentElement.style.direction = 'ltr';
            noteContentElement.style.textAlign = 'left';
        }
    }

    // Apply text direction for each note content
    window.onload = function() {
        const noteContents = document.querySelectorAll('.note-content');
        noteContents.forEach(noteContent => {
            setTextDirection(noteContent.id);
        });
    };
    
    

</script>

</body>
</html>
