<?php
ini_set('session.save_path', '/home/mcgkxyz/public_html/temp');
session_start();
date_default_timezone_set("Africa/Cairo");
include 'db.php';
include '../func.php';
include '../checkSession.php';
$dateNow = date('d-M-Y h:i a');
$colorCode = rand_color();

$catID = $_GET['id'];

$select = $con->query("SELECT * FROM categories where id='$catID'");
$row = $select->fetch();

$cat_name = $row['name'];

// Add Tasks

$taskMsg = '';
if (isset($_POST['newTask']) && $_POST['newTask'] == 'Add Task!') {
    $task_name = $_POST['task_name'];

    if (!empty($task_name)) {
        $select = $con->query("SELECT * FROM list where name='$task_name'");

        $taskMsg = '
            Add new task: <b>' . $task_name . '</b> to <b style="text-style:italic;">' . $cat_name . '</b> Category ?
            <br><bR>
            <form action="cat.php?id=' . $_GET['id'] . '" method="post">
            <input type="hidden" name="hiddenTask" value="' . $task_name . '">
            <input type="hidden" name="hiddenCat" value="' . $cat_name . '">
            <input type="submit" name="Yes" value="YES!" class="btn btn-success btn-sm" style="padding-left: 30px;padding-right:30px;">
            <input type="submit" name="Cancel" value="CANCEL!" class="btn btn-danger btn-sm">
            </form>';

    } else {
        $taskMsg = '<font color="red">Enter Task Name!</font>';
        Header('Refresh:1 ' . $_SERVER["REQUEST_URI"]);
    }

}

if (isset($_POST['Yes']) && $_POST['Yes'] == 'YES!') {
    $taskName = $_POST['hiddenTask'];
    $catName = $_POST['hiddenCat'];
    $taskMsg = '<font color="green">Task <b>"' . $taskName . '"</b> Added!</font>';
    $insert = $con->prepare("INSERT INTO list (name, category, added_date, colorCode) VALUES (?, ?, ?, ?)");
    $insert->execute([$taskName, $catName, $dateNow, $colorCode]);

    $updateCats = $con->prepare("UPDATE categories set last_task=? where name=?");
    $updateCats->execute([$dateNow, $catName]);
    Header('Refresh:1 ' . $_SERVER["REQUEST_URI"]);
}

if (isset($_POST['Cancel']) && $_POST['Cancel'] == 'CANCEL!') {
    Header('Refresh:1 ' . $_SERVER["REQUEST_URI"]);
}

?>

<html>
<head>
<title>Planner: <?php echo $cat_name; ?></title>
<meta content="width=device-width, initial-scale=1" name="viewport" />
      <link rel="stylesheet" href="css/bootstrap.min.css"/>
<style>
    html{
    display: table;
    margin: auto;
    }
   body{
    text-align: center;
    display: table-cell;
    vertical-align: middle;
   }
   table{
            border-radius:12px;
            vertical-align:top;
            display:inline-block;
            border: 2px solid #717171;
            overflow: hidden;
            border-spacing: 0px;
            float:left;

        }

        table th{
            border: 2px solid #717171;
            padding: 10px;
            background: #717171;
            color: white;
            border-left: none;
            text-align:center;
            font-size:20px;
        }

</style>
</head>
<body>
<br><a href="index.php" style="font-size:25px;">Back</a><br>

<div id="container">
<div style="border:2px solid #adadad;margin:10px;padding:30px;border-radius:10px;">
<b style="font-style:italic;">Task:</b><form action="cat.php?id=<?php echo $_GET['id']; ?>" method="post">
<input type="text" name="task_name" style="margin-top:10px;">
<input type="submit" name="newTask" value="Add Task!" class="btn btn-primary btn-sm">
</form> <?php echo $taskMsg; ?>
</div>
</div>
<br><br>

<?php

$cats = $con->query("SELECT * FROM categories where name='$cat_name'");
$rof = $cats->fetch();
echo '<table style="border: 2px solid ' . $rof['catColor'] . ' !important;background:#dfdfdf;"><tr><th style="background:' . $rof['catColor'] . ' !important; border: 2px solid ' . $rof['catColor'] . ' !important;"><b id="strokethis"><a href="cat.php?id=' . $_GET['id'] . '" style="color:inherit;text-decoration:none;">' . $cat_name . '</a></b></th></tr><a href="cat.php?id=' . $_GET['id'] . '&fullcontrol" class="btn btn-secondary">Full Control</a>';

if (isset($_GET['fullcontrol'])) {
    $cats = $con->query("SELECT * FROM list where category='$cat_name' order by status ='' desc, status='done' desc, STR_TO_DATE(done_date, '%d-%M-%Y %h:%i') desc, STR_TO_DATE(added_date, '%d-%M-%Y %h:%i') desc");
} else {
    $cats = $con->query("SELECT * FROM list where category='$cat_name' and status != 'canceled' order by status asc, STR_TO_DATE(done_date, '%d-%M-%Y %h:%i') desc, STR_TO_DATE(added_date, '%d-%M-%Y %h:%i') desc");
}
while ($ros = $cats->fetch()) {

    $deleteButton = '';
    if (isset($_GET['fullcontrol'])) {
        $deleteButton = '<span name="' . $ros['name'] . '" id="' . $ros['id'] . '"><button id="deleteButton" class="btn btn-warning btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">PermaDelete</button></span>';
    }

    $doneOption = '<span name="' . $ros['name'] . '" id="' . $ros['id'] . '"><button id="doneButton" class="btn btn-success btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Done</button></span>';

    $cancelOption = '<span name="' . $ros['name'] . '" id="' . $ros['id'] . '"><button id="cancelButton" class="btn btn-danger btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Cancel</button></span>';

    echo '<table class="table table-bordered"><tbody>';
    if ($ros['status'] == 'done') {
        $diff = diffinTime($ros['added_date'], $ros['done_date']);
        $detailedTime = secondsToTime($diff[0]);
        echo '<tr><td style="padding:10px;background:#818181;font-size:20px;min-width:500px;font-weight:bold;"><img src="done.png"> <del>' . $ros['name'] . '</del> <span style="font-size:12px;font-style:italic;">@' . $ros['done_date'] . ' (Took: ' . $detailedTime . ')</span>' . $deleteButton . '</td></tr><br>';
    } elseif ($ros['status'] == 'canceled') {
        echo '<tr><td style="padding:10px;background:#818181;font-size:20px;min-width:500px;font-weight:bold;"><img src="remove.png"> <del>' . $ros['name'] . '</del>' . $deleteButton . '</td></tr>  <br>';
    } else {
        echo '<tr><td style="padding:10px;font-size:20px;min-width:500px;font-weight:bold;"><img src="arrow.png"> ' . $ros['name'] . ' ' . $doneOption . ' ' . $cancelOption . $deleteButton . '</td></tr><br>';
    }
    echo '</tbody></table>';
}
echo '</table>';

?>

<script src="js/jquery-3.6.0.min.js"></script>
<script src="js/sweetalert2.all.min.js"></script>
<script>
        $(document).delegate("#doneButton", "click", function(){
            var name = $(this).parents("span").attr("name");
            var id = $(this).parents("span").attr("id");

            Swal.fire({
            title: '<font size="4">Do you want to make <b style="color:green;">'+ name +'</b> Done ?</font>',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "Yes, it's Done!"
            }).then((result) => {

                     if (result.isConfirmed) {
                    $.ajax({
                    type: "POST",
                    url: "done.php",
                    data: {name: name, id:id},
                beforeSend: function () {},
                success: function (response) {
                    Swal.fire(
                    "Success!",
                    "Your Task <b style='color:green;'>"+ name +"</b> has been Done!",
                    "success"
                    ).then((okay) => {
                        if(okay.isConfirmed){
                            location.reload();
                        }
                    });
                }
                });
                    } else if (result.isDenied) {
                    Swal.fire('Changes are not saved', '', 'info')
                }

                     });

});

    $(document).delegate("#cancelButton", "click", function(){
            var name = $(this).parents("span").attr("name");
            var id = $(this).parents("span").attr("id");

            Swal.fire({
            title: '<font size="4">Do you want to Cancel <b style="color:red;">'+ name +'</b> ?</font>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: "Yes, Cancel!"
            }).then((result) => {

                     if (result.isConfirmed) {

                    $.ajax({
                    type: "POST",
                    url: "remove.php",
                    data: {name: name, id:id},
                beforeSend: function () {},
                success: function (response) {

                    Swal.fire(
                    "Success!",
                    "The Task <b style='color:red;'>"+ name +"</b> has been Removed!",
                    "success"
                    ).then((okay) => {
                        if(okay.isConfirmed){
                            location.reload();
                        }
                    });


                }
                });
                    } else if (result.isDenied) {
                    Swal.fire('Changes are not saved', '', 'info')
                }

                     });

});

$(document).delegate("#deleteButton", "click", function(){
            var name = $(this).parents("span").attr("name");
            var id = $(this).parents("span").attr("id");

            Swal.fire({
            title: '<font size="4">Do you want to Delete <b style="color:red;">'+ name +'</b> Permenantly ?!</font>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: "Yes, Permenant Delete!"
            }).then((result) => {

                     if (result.isConfirmed) {

                    $.ajax({
                    type: "POST",
                    url: "delete.php",
                    data: {name: name, id:id},
                beforeSend: function () {},
                success: function (response) {

                    Swal.fire(
                    "Success!",
                    "The Task <b style='color:red;'>"+ name +"</b> has been Deleted Permenantly!",
                    "success"
                    ).then((okay) => {
                        if(okay.isConfirmed){
                            location.reload();
                        }
                    });


                }
                });
                    } else if (result.isDenied) {
                    Swal.fire('Changes are not saved', '', 'info')
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

$(document).ready(function() {

        <?php

if (isset($_POST['Yes']) && $_POST['Yes'] == 'YES!') {
    $task = '<font size="5">Task <b style="color:green">' . $taskName . '</b> has been added!</font>';
    echo " Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: '" . $task . "',
        showConfirmButton: false,
        timer: 2000
        })";
}

?>


});
</script>
</body>

</html>
