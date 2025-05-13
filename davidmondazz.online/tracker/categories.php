<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include 'db.php';
include $_SERVER['DOCUMENT_ROOT'] . '/func.php';
// Get the path of the current script
$scriptPath = $_SERVER['SCRIPT_NAME']; // e.g., /trackerOLD/show.php

// Get the directory name of the script path
$baseDirectory = dirname($scriptPath); // e.g., /trackerOLD

// Ensure the base directory ends with a slash if it's not the root
// And handle the case where the script is in the root directory
if ($baseDirectory === '/' || $baseDirectory === '\\') {
    // If script is in the root, base directory is just '/'
    $baseDirectory = '/';
} elseif (substr($baseDirectory, -1) !== '/') {
    // Otherwise, ensure it ends with a slash
    $baseDirectory .= '/'; // e.g., /trackerOLD/
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

// Construct the base URL including the directory
$baseURL = $protocol . "://" . $host . $baseDirectory; // e.g., https://davidmondazz.online/trackerOLD/

?>

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;
$baseImgPath = $mainDomainURL . $baseDirectory;

// Helper function to fix image paths
function imgPath($imgName) {
    global $baseImgPath;
    return $baseImgPath . 'img/' . $imgName;
}

// Replace all instances of src="img/ with src="' . imgPath(' in the output buffer

$errMsg = '';
if (isset($_POST['addnew']) && $_POST['addnew'] == 'Add!') {
    $catName = $_POST['cat_name'];
    $check = $con->query("SELECT * FROM categories where name='$catName'");
    $checkRows = $check->rowCount();

    if (empty($_POST['cat_name'])) {
        $errMsg = '<font color="red">Enter Category Name!</font>';
        header("Refresh:1; url=categories.php");
    } elseif ($checkRows > 0) {
        $errMsg = '<font color="red">This Category is already Exist!</font>';
        header("Refresh:1; url=categories.php");
    } else {
        $cat_name = $_POST['cat_name'];
        $insert = $con->prepare("INSERT INTO categories (name, total_time) VALUES (?, ?) ");
        $insert->execute([$cat_name, '0']);
        $errMsg = '<font color="green">Category Added Successfully!</font>';
        header("Refresh:2; url=categories.php");
    }
}

?>

 <!DOCTYPE html>
 <html>
 <head>
 	<title>Tracker Catogries</title>
 	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<script src="js/jquery-3.6.0.min.js"></script>
 	<style type="text/css">
 		form{
			padding: 20px;
		}
 		.cat_list{
			font-size: 20px;
			padding: 20px;
		}
		.path{
			padding: 20px;
		}

        .stroked-text {
  -webkit-text-stroke: 0.3px black; /* For Safari */
  text-stroke: 0.3px #C6C6C6; /* For other browsers */
}
 	</style>
 </head>
 <body>

<div class="path"><img src="<?php echo $baseImgPath; ?>/img/home.png"><a href="index.php">Home</a> | Categories:</div>

 <form action="categories.php" method="post" style="display:inline">
 	New Category: <input type="text" name="cat_name">
 	<input type="submit" name="addnew" value="Add!" class="btn btn-warning"> <?php echo $errMsg; ?>
 </form> <a href="categories.php?manage" class="btn btn-info">Manage</a> <a href="categories.php"><img src="<?php echo $baseImgPath; ?>/img/refresh.png" style="padding-left:20px;"></a>

  <div class="cat_list">
 	<?php
$showCat = $con->query("SELECT SUM(d.total_time) as totTime, c.name as cat_name, c.colorCode
FROM details d
JOIN categories c ON c.name = d.cat_name
GROUP BY c.name
ORDER BY totTime DESC;");
$showEmptyCats = $con->query("SELECT * from categories where total_time = '0'");
if ($showCat->rowCount() > 0) {
    $countall = 0;
    while ($row = $showCat->fetch(PDO::FETCH_ASSOC)) {
        $catname = '<font color="'.$row['colorCode'].'" class="stroked-text">'.$row['cat_name'].'</font>';
        $arr[$catname][] = $row['totTime'];
        $countall += $row['totTime'];
    }
    $totalTime = round(($countall / 60) / 60, 2);
    echo '<font color="green"><b>Total Time Tracked: </b></font><b>'. $totalTime.' Hrs</b> <span style="font-size:12px;"><b><i>= '.round($totalTime / 24).' days = '.round(($totalTime / 24) / 30, 2).' months.</b></i></span><bR><br>';

    foreach ($arr as $key => $value) {
        if (isset($_GET['manage'])) {
            $deleteOption = ' <span name="' . $key . '">
			<button class="btn btn-danger btn-sm remove">Delete</button>
							</span>';
        } else {
            $deleteOption = '';
        }

        echo '<b>*' . $key . '</b> ';

        foreach ($value as $item) {

            $total_time = '';
            if (!empty($item)) {
                if ($item <= 59) {
                    $total_time = '<font color="red">(' . $item . ' sec)</font>';
                } elseif ($item < 3600) {
                    $total_time = '<font color="red">(' . round(($item / 60), 2) . ' min)</font>';
                } elseif ($item > 3600) {
                    $total_time = '<font color="red">(' . round(($item / 3600), 2) . ' hrs)</font>';
                }

            }
            echo $total_time . $deleteOption . '<br>';

        }

    }

    while ($showEmpty = $showEmptyCats->fetch()) {
        if (isset($_GET['manage'])) {
            $deleteOption = ' <span name="' . $showEmpty['name'] . '">
			<button class="btn btn-danger btn-sm remove">Delete</button>
							</span>';
        } else {
            $deleteOption = '';
        }

        echo '<b>*' . $showEmpty['name'] . $deleteOption . '</b><br> ';
    }

} else {
    echo 'There are no Categories!';
}

?>
 </div>

 <script type="text/javascript">

    $(".remove").click(function(){

        var name = $(this).parents("span").attr("name");


        if(confirm('Are you sure to remove '+ name +' ?'))

        {

            $.ajax({

               url: 'cat_delete.php',

               type: 'GET',

               data: {name: name},

               error: function() {

                  alert('Something is wrong');

               },

               success: function(data) {
                    alert(name +" removed successfully");
					window.location.reload();

               }

            });

        }

    });


</script>

<?php
// End the output buffer to apply the image path fix
?>
 </body>
 </html>