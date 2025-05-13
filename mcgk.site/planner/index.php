<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include 'db.php';
include '../func.php';
include '../checkSession.php';
$dateNow = date('d-M-Y h:i a');
$colorCode = rand_color();

$general = $con->query("SELECT * FROM general");
$ro = $general->fetch();

$errorMsg = '';
$taskMsg = '';
if (isset($_POST['newCat']) && $_POST['newCat'] == 'Add Category!') {

    if (!empty($_POST['cat_name'])) {
        $name = $_POST['cat_name'];
        $select = $con->query("SELECT * FROM categories where name='$name'");

        if ($select->rowCount() > 0) {
            $errorMsg = '<font color="red"><b>Category Already Exists!</b></font>';
            Header('Refresh:1 index.php?cats');
        } else {
            $errorMsg = '<br><br><font size="4">Add New Category: <b style="color:' . $colorCode . '">' . $name . '</b> ?</font>
            <br><bR><form action="index.php?cats&addnew" method="post"><input type="hidden" name="hiddenCat" value="' . $name . '"><input type="submit" name="cat_Yes" value="YES!" class="btn btn-success btn-sm" style="padding-left: 30px;padding-right:30px;">
            <a href="index.php?cats" class="btn btn-danger btn-sm">CANCEL!</a></form>';
        }
    } else {
        $errorMsg = '<font color="red"><b>Enter Category Name!</b></font>';
        Header('Refresh:1 index.php?cats');
    }

}

if (isset($_POST['newTask']) && $_POST['newTask'] == 'Add Task!') {
    $task_name = $_POST['task_name'];
    $catsChoose = $_POST['catsChoose'];
    if (empty($_POST['plansSelect'])) {
        $plansSelect = $dateNow;
    } else {
        $plansSelect = $_POST['plansSelect'];
    }
    $planDate = date('d-M-Y', strtotime($plansSelect));
    if (!empty($task_name)) {
        $select = $con->query("SELECT * FROM list where name='$task_name'");

        $taskMsg = '
            <br><br>Add new task: <b>' . $task_name . '</b> to <b style="text-style:italic;">' . $catsChoose . '</b> Category ?
            <br><bR>
            <form action="index.php" method="post">
            <input type="hidden" name="hiddenTask" value="' . $task_name . '">
            <input type="hidden" name="hiddenCat" value="' . $catsChoose . '">
            <input type="hidden" name="hiddenPlan" value="' . $planDate . '">
            <input type="submit" name="Yes" value="YES!" class="btn btn-success btn-sm" style="padding-left: 30px;padding-right:30px;">
            <a href="index.php" class="btn btn-danger btn-sm">CANCEL!</a>
            </form>';

    } else {
        $taskMsg = '<font color="red">Enter Task Name!</font>';
        Header('Refresh:1 index.php');
    }

}

if (isset($_POST['Yes']) && $_POST['Yes'] == 'YES!') {
    $taskName = $_POST['hiddenTask'];
    $catName = $_POST['hiddenCat'];
    $planSelect = $_POST['hiddenPlan'];
    $taskMsg = '<br><br><font color="green">Task <b>"' . $taskName . '"</b> Added!</font>';
    $insert = $con->prepare("INSERT INTO list (name, category, added_date, colorCode, planned) VALUES (?, ?, ?, ?, ?)");
    $insert->execute([$taskName, $catName, $dateNow, $colorCode, $planSelect]);

    $updateCats = $con->prepare("UPDATE categories set last_task=? where name=?");
    $updateCats->execute([$dateNow, $catName]);

}

if (isset($_POST['cat_Yes']) && $_POST['cat_Yes'] == 'YES!') {
    $catName = $_POST['hiddenCat'];
    $taskMsg = '<br><br><font color="green" size="4">Category <b>"' . $catName . '"</b> Added Successfully!</font>';
    $insert = $con->prepare("INSERT INTO categories (name, catColor) VALUES (?, ?)");
    $insert->execute([$catName, $colorCode]);

}

?>

<html>
<head>
<title>Planner</title>
<meta content="width=device-width, initial-scale=1" name="viewport" />
<link rel="stylesheet" href="css/bootstrap.min.css"/>
<?php

////////////// START FONTS

$fonterror = '';
$choosedFont = '';
if (isset($_POST['fontTest'])) {
    $fontfamily = $_POST['newfont'];
    if (!empty($fontfamily)) {
        $choosedFont = $_POST['newfont'];
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=' . $fontfamily . '">';
    } else {
        $fonterror = '<font color="red">Font is Empty!</font>';
        header("Refresh:1 " . $_SERVER["HTTP_REFERER"]);
    }
} elseif (isset($_POST['fontAdd']) && $_POST['fontAdd'] == 'Add!') {
    $fontfamily = $_POST['newfont'];
    if (!empty($fontfamily)) {
        $checkDuplicate = $con->query("SELECT * FROM fonts where name='$fontfamily'");
        if ($checkDuplicate->rowCount() > 0) {
            $fonterror = '<font color="red"> This Font already Exists!</font>';
            header("Refresh:1 " . $_SERVER["HTTP_REFERER"]);
        } else {
            $insertFont = $con->prepare("INSERT INTO fonts (name) VALUES (?)");
            $insertFont->execute([$fontfamily]);
            $fonterror = '<font color="green"> "' . $fontfamily . '" Font Added!</font>';
            header("Refresh:1 " . $_SERVER["HTTP_REFERER"]);
        }

    } else {
        $fonterror = '<font color="red">Font is Empty!</font>';
        header("Refresh:1 " . $_SERVER["HTTP_REFERER"]);
    }
} elseif (isset($_POST['fontsList'])) {
    $fontSelected = $_POST['fontsList'];
    $updateGeneral = $con->prepare("UPDATE general set familyfont=?");
    $updateGeneral->execute([$fontSelected]);
    header("Location: index.php");
} else {
    $fontfamily = $ro['familyfont'];
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=' . $fontfamily . '">';
}

///////////// END FONTS

///////////// START LIST VIEW

if (isset($_POST['viewList'])) {
    $viewList = $_POST['viewList'];
    $update = $con->query("UPDATE general set list_view='$viewList'");
    header("Location: index.php");
}

///////////// END LIST VIEW

if (isset($_GET['styled']) && $_GET['styled'] == 'ok') {
    $updateGeneral = $con->prepare("UPDATE general set styled=?");
    $updateGeneral->execute(['ok']);
    header("Location: " . $_SERVER["HTTP_REFERER"]);
} elseif (isset($_GET['styled']) && $_GET['styled'] == 'removed') {
    $updateGeneral = $con->prepare("UPDATE general set styled=?");
    $updateGeneral->execute(['removed']);
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}

if (isset($_GET['showCats']) && $_GET['showCats'] == 'show') {
    $updateGeneral = $con->prepare("UPDATE general set showCats=?");
    $updateGeneral->execute(['show']);
    header("Location: " . $_SERVER["HTTP_REFERER"]);
} elseif (isset($_GET['showCats']) && $_GET['showCats'] == 'hide') {
    $updateGeneral = $con->prepare("UPDATE general set showCats=?");
    $updateGeneral->execute(['hide']);
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}

if (isset($_GET['showAll']) && $_GET['showAll'] == 'yes') {
    $updateGeneral = $con->prepare("UPDATE general set showAll=?");
    $updateGeneral->execute(['yes']);
    header("Location: " . $_SERVER["HTTP_REFERER"]);
} elseif (isset($_GET['showAll']) && $_GET['showAll'] == 'no') {
    $updateGeneral = $con->prepare("UPDATE general set showAll=?");
    $updateGeneral->execute(['no']);
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}

if (isset($_POST['plansChange'])) {
    $GetPlanID = $_POST['hiddenPlanID'];
    $planChange = $_POST['plansChange'];
    $changeDate = date("d-M-Y", strtotime($planChange));
    $updateList = $con->prepare("UPDATE list set planned=? where id='$GetPlanID'");
    $updateList->execute([$changeDate]);

    header("Location: index.php");

}

?>

    <style>
        form{
            display:inline;
        }
   #gridTable {
            border-radius:12px;
            vertical-align:top;
            display:inline-block;
            border: 2px solid #717171;
            margin: 10px;
            overflow: hidden;
            border-spacing: 0px;

        }

        #gridTable th{
            border: 2px solid #717171;
            padding: 10px;
            background: #717171;
            color: white;
            border-left: none;
            text-align:center;
            font-size:20px;
        }



        #priorityTable td, #priorityTable th {
        border: 1px solid #ddd;
        padding: 8px;
        }

        #priorityTable tr:nth-child(even){background-color: #f2f2f2;}

        #priorityTable tr:hover {background-color: #ddd;}

        #priorityTable th {
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: left;
        background-color: #04AA6D;
        color: white;
        }


        #PlannedTable td{
         border: 2px solid #ddd;
         padding:15px;
        }


        #PlannedTable th {
        border: 1px solid #ddd;
        padding: 8px;
        }

        #PlannedTable tr:nth-child(even){background-color: #f2f2f2;}

        #PlannedTable tr:hover {background-color: #ddd;}

        #PlannedTable th {
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: center;
        background-color: #04AA6D;
        color: white;
        }



      a:link {
        text-decoration: none;
      }

      a:hover {
        color: #ad4c21;
      }
        div{
            clear:both;

        }

        #strokethis{
            text-shadow: 1px 1px 0 #818181;
            letter-spacing: 2px;
        }
        body{
            background: url(bg.png) no-repeat center center fixed;
            background-size: cover;
            height: 100%;
            size:100%;
            font-family: "<?php echo $fontfamily; ?>", sans-serif;
        }
        .circle_active{
		    background: #219026;
		    width: 8px;
		    height: 8px;
		    border-radius: 50%;
		    display:inline-block;
		}
        .circle_done{
		    background: #5b5b5b;
		    width: 8px;
		    height: 8px;
		    border-radius: 50%;
		    display:inline-block;
		}

        /* Mobile Screen */
        @media (max-width: 768px){
            #cat_name{
                margin-left:10px;
            }
            #task_style{
                margin-left:20px;
                font-size:15px !important;
            }
            #task_style del{
                background:#c1c1c1;
            }

            #task_style_noCats{
                margin-left:10px;
                font-weight:bold;

            }
            #task_style_noCats del{
                background:#c1c1c1;
                font-style:italic;
            }
            #detailed_time{
                display: inline-block;
                padding-left:30px;
                padding-top:10px;
            }
            #stats{
            margin-top:10px;
            margin-bottom:50px;
            background:#c4b555;
            padding:30px;
            border-radius:5px;
            text-align:center;
        }
        #container{
            margin: 0 auto;
            width: 400px;
        }
        #container input, select{
           padding:10px;
        }

        #priorityTable {
        border-collapse: collapse;
        margin-left: auto;
        margin-right: auto;
        font-size:10px;
        }

        #PlannedTable {
        border-collapse: collapse;
        margin-left: auto;
        margin-right: auto;
        font-size:13px;
        width: 360px;
        }

        #PlannedTable td > #plans{
        font-size:13px;
        }

        #PlannedTable td > #catnames{
        font-size:11px;
        }

        }


        /* Big Screen */
        @media (min-width: 768px){
            #cat_name{
                margin-left:120px;
            }
            #task_style{
                padding-left:190px;
                font-size:20px;
            }
            #task_style del{
                background:#c1c1c1;
            }
            #task_style_noCats{
                padding-left:90px;
                font-size:20px;
                font-weight:bold;
            }
            #task_style_noCats del{
                background:#c1c1c1;
            }
            #stats{
            margin-top:10px;
            margin-bottom:50px;
            background:#c4b555;
            padding:20px;
            border-radius:5px;
        }

        #container{
            margin: 0 auto;
            width: 700px;
        }
        #container input, select{
           padding:10px;
        }
        #priorityTable {
        border-collapse: collapse;
        margin-left: auto;
        margin-right: auto;
        font-size:18px;
        }

        #PlannedTable {
        border-collapse: collapse;
        margin-left: auto;
        margin-right: auto;
        font-size:18px;
        width: 800px;
        }

        #PlannedTable td > #plans{
        font-size:20px;
        }

        }



    </style>
</head>
<body>





<?php

if (isset($_GET['fonts'])) {
    $fontChoose = '<div style="border:2px solid #8f8f8f;display:inline;padding:10px;margin-left:15px;position: relative;top: 20%;transform: translateY(-10%);"><font color="white">NewFont:</font> <form action="index.php?fonts" method="post" style="display:inline;">
    <input type="text" name="newfont" value="' . $choosedFont . '" style="width:150px;">
    <input type="submit" name="fontTest" value="Test">
    <input type="submit" name="fontAdd" value="Add!">
    </form> ' . $fonterror . '</div>';
} else {
    $fontChoose = '<a href="index.php?fonts" class="btn btn-primary" style="margin-left:20px;">Fonts</a>';
    $fontChoose .= ' <form action="index.php" method="post" style="display:inline"><select name="fontsList"  style="font-family:Arial, Helvetica, sans-serif;" onchange="this.form.submit()">
    <option>' . $ro['familyfont'] . '</option>';

    $getAllFonts = $con->query("SELECT * FROM fonts");
    while ($row = $getAllFonts->fetch()) {
        if ($row['name'] == $ro['familyfont']) {

        } else {
            $fontChoose .= '<option>' . $row['name'] . '</option>';
        }

    }

    $fontChoose .= '</select></form>';
}

$styled = '';
$catshow = '';
$list_view = '';

if ($ro['showAll'] == 'no') {

    $showall = '<a href="index.php?showAll=yes" class="btn btn-primary btn-light" style="margin-left:20px;">ShowAll</a> <font color="#DC583C">Mode:Default</font>';

    if ($ro['styled'] == 'ok') {
        $styled = '<a href="index.php?styled=removed" class="btn btn-primary" style="margin-left:20px;">RemoveStyle</a>';
    } else {
        $styled = '<a href="index.php?styled=ok" class="btn btn-primary" style="margin-left:20px;">Styled</a>';
    }

    if (isset($ro['showCats']) && $ro['showCats'] == 'show') {
        $catshow = '<a href="index.php?showCats=hide" class="btn btn-primary" style="margin-left:20px;">HideCategories</a>';
    } else {
        $catshow = '<a href="index.php?showCats=show" class="btn btn-primary" style="margin-left:20px;">ShowCategories</a>';
    }

} else {
    $showall = '<a href="index.php?showAll=no" class="btn btn-primary btn btn-dark" style="margin-left:20px;">Default</a> <font color="#DC583C">Mode:ViewAll</font>';
    $list_view = '<font color="white">View: </font><form action="index.php" method="post" style="display:inline"><select name="viewList" onchange="this.form.submit()">';

    if ($ro['list_view'] == 'GRID') {
        $list_view .= '<option>GRID</option><option>Priority</option><option>Planned</option>';
    } elseif ($ro['list_view'] == 'Priority') {
        $list_view .= '<option>Priority</option><option>GRID</option><option>Planned</option>';
    } elseif ($ro['list_view'] == 'Planned') {
        $list_view .= '<option>Planned</option><option>Priority</option><option>GRID</option>';
    }

    $list_view .= '</select></form>';

}

?>


</div>
</div>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand" href="index.php"><img src="logo.png"></a> <font color="white">Planner v1.0</font>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse w-100 order-3" id="navbarNav">
    <ul class="navbar-nav ms-auto">
    <li class="nav-item pt-4 ms-4 p-lg-1">
     <?php

$selectdone = $con->query("SELECT * FROM list where status='done'");
$selectcanceled = $con->query("SELECT * FROM list where status='canceled'");
echo '<div style="position: relative;top: 50%;transform: translateY(-50%);"><font color="green">Done:</font> <font color="white">' . $selectdone->rowCount() . '</font>, <font color="red">Canceled:</font> <font color="white">' . $selectcanceled->rowCount() . '</font></div>';

?>
      </li>
    <li class="nav-item p-2 p-lg-1">
      <?php echo $catshow; ?>
      </li>
      <li class="nav-item p-2 p-lg-1">
      <?php echo $fontChoose; ?>
      </li>
      <li class="nav-item p-2 p-lg-1">
       <?php echo $list_view; ?>
      </li>
      <li class="nav-item p-2 p-lg-1">
      <?php echo $styled; ?>
      </li>
      <li class="nav-item p-2 p-lg-1">
      <?php echo $showall; ?>
      </li>
      <li class="nav-item p-2 p-lg-1">
      <a href="index.php?cats" class="btn btn-warning" style="margin-left:20px;">Categories</a>
      </li>
    </ul>
  </div>




</nav>



<?php

if (isset($_GET['cats'])) {

    echo '<div id="container">

    <div style="border:2px solid #adadad;margin:10px;padding:30px;border-radius:10px;text-align:center;">
    <b style="font-style:italic;">Category:</b><form action="index.php?cats" method="post">
    <input type="text" name="cat_name" style="margin-top:10px;"> ';
    echo '<input type="submit" name="newCat" value="Add Category!" class="btn btn-info btn-sm">
    </form>';

    echo $errorMsg;

    echo '</div>
    </div>
    <center><a href="index.php?cats&delete" class="btn btn-danger">Delete</a></center><br><br>';

    echo '<div class="container">';
    $selectCats = $con->query("SELECT * FROM categories order by STR_TO_DATE(last_task, '%d-%M-%Y %h:%i') desc");
    while ($rows = $selectCats->fetch()) {

        $deleteCats = '';
        if (isset($_GET['cats']) && isset($_GET['delete'])) {
            $deleteCats = '<span name="' . $rows['name'] . '" id="' . $rows['id'] . '"><button id="deleteCats" style="border: none !important;
            "><img src="remove.png"></button></span>';
        }

        echo '<div style="padding:10px;display:inline;line-height:60px;"><a href="cat.php?id=' . $rows['id'] . '" style="background-color:' . $rows['catColor'] . ';border-color:' . $rows['catColor'] . '" class="btn btn-primary">' . $rows['name'] . '</a> ' . $deleteCats . '</div>';
    }
    echo '</div>';

} else {

    if (isset($_POST['task_name'])) {
        $taskPost = 'value="' . $_POST['task_name'] . '"';
    } else {
        $taskPost = '';
    }

    echo '<div id="container">
<div style="border:2px solid #adadad;margin:10px;padding:30px;border-radius:10px;text-align:center;">
<b style="font-style:italic;">Task:</b><form action="index.php" method="post">
<input type="text" name="task_name" style="margin-top:10px;" ' . $taskPost . '>
<select name="catsChoose">';
    $selectCats = $con->query("SELECT * FROM categories order by STR_TO_DATE(last_task, '%d-%M-%Y %h:%i') desc");
    while ($rows = $selectCats->fetch()) {
        if (isset($_POST['catsChoose'])) {
            if ($_POST['catsChoose'] == $rows['name']) {
                echo '<option value="' . $rows['name'] . '" selected>' . $rows['name'] . '</option>';
            } else {
                echo '<option value="' . $rows['name'] . '">' . $rows['name'] . '</option>';
            }
        } else {
            echo '<option value="' . $rows['name'] . '">' . $rows['name'] . '</option>';
        }
    }
    echo '</select>';

    if ($ro['list_view'] == 'Planned') {
        $plansBox = '<select name="plansSelect">';
        for ($i = 0; $i < 30; $i++) {
            $dayPlan = date("Y-m-d", strtotime("+$i days"));
            $selectCount = $con->query("SELECT COUNT(*) as totalNum FROM list where STR_TO_DATE(planned, '%d-%M-%Y')='$dayPlan' and status = ''");
            $row = $selectCount->fetch();

            if (!empty($row['totalNum'])) {
                $totalNum = '(' . $row['totalNum'] . ' Tasks)';
            } else {
                $totalNum = '';
            }
            $dateToday = date('Y-m-d');
            $timeDiffs = diffinTime($dateToday, $dayPlan);
            $diffText = ' (' . $timeDiffs[3] . ' days)';

            if ($dayPlan == $dateToday) {
                $diffText = ' (Today)';
            }

            if (isset($_POST['plansSelect'])) {
                if ($_POST['plansSelect'] == $dayPlan) {
                    $plansBox .= '<option value="' . $dayPlan . '" selected>' . $dayPlan . $diffText . ' ' . $totalNum . ' </option>';
                } else {
                    $plansBox .= '<option value="' . $dayPlan . '">' . $dayPlan . $diffText . ' ' . $totalNum . ' </option>';
                }
            } else {
                $plansBox .= '<option value="' . $dayPlan . '">' . $dayPlan . $diffText . ' ' . $totalNum . ' </option>';
            }

        }
        $plansBox .= '</select> ';
        echo $plansBox;
    }

    echo '<input type="submit" name="newTask" value="Add Task!" class="btn btn-primary btn-sm">
</form>';

    echo $taskMsg;

    echo '</div>
</div>
<br><br>';

    if ($ro['showAll'] == 'yes') {
        echo '<div style="text-align:center;">';
        if ($ro['list_view'] == 'GRID') {

            $arr = array();
            $selectall = $con->query("SELECT * FROM categories order by STR_TO_DATE(last_task, '%d-%M-%Y %h:%i %p') desc");
            while ($row = $selectall->fetch()) {
                $arr[$row['name']][] = ['catColor' => $row['catColor'], 'CatID' => $row['id']];
            }

            foreach ($arr as $key => $value) {

                foreach ($value as $item) {
                    $check = $con->query("SELECT * FROM list where category='$key' and status=''");
                    if ($check->rowCount() > 0) {
                        $cat_name = $key;
                        echo '<table id="gridTable" style="border: 2px solid ' . $item['catColor'] . ' !important;background:#dfdfdf;"><tr><th style="background:' . $item['catColor'] . ' !important; border: 2px solid ' . $item['catColor'] . ' !important;"><b id="strokethis"><a href="cat.php?id=' . $item['CatID'] . '" style="color: inherit;">' . $key . '</a></b></th></tr>';
                    }

                }

                $cats = $con->query("SELECT * FROM list where category='$key' and status != 'canceled' and status='' order by STR_TO_DATE(added_date, '%d-%M-%Y %h:%i') desc");
                while ($ros = $cats->fetch()) {

                    $doneOption = '<span name="' . $ros['name'] . '" id="' . $ros['id'] . '"><button id="doneButton" class="btn btn-success btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Done</button></span>';

                    $removeOption = '<span name="' . $ros['name'] . '" id="' . $ros['id'] . '"><button id="removeButton" class="btn btn-danger btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Remove</button></span>';

                    $cancelOption = '<a href="index.php"><button class="btn btn-info btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Cancel</button></a>';

                    $editOption = '<span name="' . $ros['name'] . '" id="' . $ros['id'] . '"><button id="editButton" class="btn btn-warning btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Edit</button></span>';

                    if (isset($_GET['manage'])) {
                        if ($_GET['manage'] == $ros['id']) {
                            echo '<tr><td style="padding:10px;font-size:18px;min-width:323px;">- <span style="vertical-align: middle;">' . $ros['name'] . ' </span> ' . $doneOption . ' ' . $removeOption . ' ' . $cancelOption . ' <br>';
                        } else {
                            echo '<tr><td style="padding:10px;font-size:18px;min-width:323px;">- <span style="vertical-align: middle;">' . $ros['name'] . ' </span><a href="index.php?manage=' . $ros['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a>  <a href="index.php?edit=' . $ros['id'] . '"><img src="edit.png" style="vertical-align: middle;"></a> <br>';
                        }
                    } elseif (isset($_GET['edit'])) {
                        if ($_GET['edit'] == $ros['id']) {
                            echo '<tr><td style="padding:10px;font-size:18px;min-width:323px;">- <input type="text" name="editText" id="editText" value="' . $ros['name'] . '" > ' . $editOption . '  <br>';
                        } else {
                            echo '<tr><td style="padding:10px;font-size:18px;min-width:323px;">- <span style="vertical-align: middle;">' . $ros['name'] . ' </span><a href="index.php?manage=' . $ros['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a> <a href="index.php?edit=' . $ros['id'] . '"><img src="edit.png" style="vertical-align: middle;"></a> <br>';
                        }
                    } else {
                        echo '<tr><td style="padding:10px;font-size:18px;min-width:323px;">- <span style="vertical-align: middle;">' . $ros['name'] . ' </span> <a href="index.php?manage=' . $ros['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a> <a href="index.php?edit=' . $ros['id'] . '"><img src="edit.png" style="vertical-align: middle;"></a><br>';
                    }

                }

                echo '</td></tr></table>';
            }

        } elseif ($ro['list_view'] == 'Priority') {
            /////////// Start Priority LIST////////////

            echo '<a href="index.php" class="btn btn-info">Refresh</a> <a href="index.php?quick_position" class="btn btn-primary">QuickPosition</a> <a href="index.php?edit" class="btn btn-secondary">Edit</a> <a href="index.php?showDates" class="btn btn-light">ShowDates</a><br><br>';

            //// Start ReArrange Position
            $selects = $con->query("SELECT * FROM list where status='' order by cast(position as unsigned) asc");
            $counter = 1;
            while ($fetch = $selects->fetch()) {
                $array[$fetch['id']][] = ['name' => $fetch['name'], 'count' => $counter++, 'position' => $fetch['position']];
            }

            foreach ($array as $key => $value) {

                foreach ($value as $item) {
                    $update = $con->query("UPDATE list set position='$item[count]' where id='$key'");
                }

            }

            ///// End ReArrange Position

            ///// Start of UP & Down Buttons

            if (isset($_POST['downButton']) && $_POST['downButton'] == 'Down') {
                $currentPosition = $_POST['currentPosition'];
                $currentID = $_POST['currentID'];

                // Next Position
                $prevPosition = $_POST['currentPosition'] + 1;
                $selectNext = $con->query("SELECT * FROM list where position='$prevPosition' and status=''");
                $fetch = $selectNext->fetch();
                $nextID = $fetch['id'];

                // Down Current & UP Next Position
                $down = $con->query("UPDATE list set position=position+1 where id='$currentID'");
                $upNext = $con->query("UPDATE list set position=position-1 where id='$nextID'");

            }

            if (isset($_POST['upButton']) && $_POST['upButton'] == 'UP') {
                $currentPosition = $_POST['currentPosition'];
                $currentID = $_POST['currentID'];

                // Next Position
                $prevPosition = $_POST['currentPosition'] - 1;
                $selectNext = $con->query("SELECT * FROM list where position='$prevPosition' and status=''");
                $fetch = $selectNext->fetch();
                $prevID = $fetch['id'];

                // UP Current & Down Prev Position
                $up = $con->query("UPDATE list set position=position-1 where id='$currentID'");
                $downPrev = $con->query("UPDATE list set position=position+1 where id='$prevID'");

            }

            ///// End of UP & Down Buttons

            ///// Start of Quick Position

            if (isset($_POST['goButton']) && $_POST['goButton'] == 'GO') {
                $editPosition = $_POST['editPosition'];
                $currentID = $_POST['currentID'];
                $position_name = $_POST['positionName'];
                $updates = $con->query("UPDATE list set position='$editPosition' where id='$currentID'");
            }

            ///// End of Quick Position

            if (isset($_GET['showDates']) && isset($_GET['orderByDate'])) {
                $show = $con->query("SELECT * FROM list where status='' order by STR_TO_DATE(added_date, '%d-%M-%Y %h:%i %p') desc");
            } else {
                $show = $con->query("SELECT * FROM list where status='' order by cast(position as unsigned) asc");
            }
            $count = $show->rowCount();

            $editTh = '';
            if (isset($_GET['edit'])) {
                $editTh = '<th>Edit</th>';
            }
            $showdates = '';
            if (isset($_GET['showDates'])) {
                $showdates = '<th><a href="index.php?showDates&orderByDate" style="color:inherit;">Date_Created</a></th>';
            }

            echo '<table id="priorityTable"><tr><th>Position</th><th>Tasks</th><th>Priority</th><th>Category</th><th>Action</th>' . $editTh . '' . $showdates . '</tr>';
            while ($row = $show->fetch()) {
                $getCatID = $con->query("SELECT * FROM categories where name='$row[category]'");
                $cat = $getCatID->fetch();
                $upButton = '';
                $downButton = '';
                if ($row['position'] < $count) {
                    $downButton = '<form action="index.php?down=' . $row['name'] . '" method="post">
                <input type="hidden" name="currentPosition" value="' . $row['position'] . '">
                <input type="hidden" name="currentID" value="' . $row['id'] . '">
                <input type="submit" name="downButton" value="Down"></form>';
                }
                if ($row['position'] != 1) {
                    $upButton = '<form action="index.php?up=' . $row['name'] . '" method="post">
                <input type="hidden" name="currentPosition" value="' . $row['position'] . '">
                <input type="hidden" name="currentID" value="' . $row['id'] . '">
                <input type="submit" name="upButton" value="UP"></form>';
                }
                if (isset($_GET['quick_position'])) {
                    $positionBox = '<form action="index.php?quick_position=' . $row['name'] . '" method="post">
                    <input type="hidden" name="positionName" value="' . $row['name'] . '">
                    <input type="text" name="editPosition" value="' . $row['position'] . '" style="width:50px;">
                    <input type="hidden" name="currentID" value="' . $row['id'] . '">
                    <input type="submit" name="goButton" value="GO"></form>';
                } else {
                    $positionBox = '(' . $row['position'] . ')';
                }
                $name = $row['name'];
                if (isset($_GET['down'])) {
                    if ($_GET['down'] == $row['name']) {
                        $name = '<font color="red"><b>' . $row['name'] . '</b></font>';
                    }
                }
                if (isset($_GET['up'])) {
                    if ($_GET['up'] == $row['name']) {
                        $name = '<font color="green"><b>' . $row['name'] . '</b></font>';
                    }
                }
                if (isset($_GET['quick_position'])) {
                    if ($_GET['quick_position'] == $row['name']) {
                        $name = '<font color="blue"><b>' . $row['name'] . '</b></font>';
                    }
                }
                if (isset($_GET['edit'])) {
                    if ($_GET['edit'] == $row['name']) {
                        $name = '<input type="text" name="editText" id="editText" value="' . $row['name'] . '">';
                    }
                }
                if (isset($_GET['showDates'])) {
                    $dates = '<td>' . $row['added_date'] . '</td>';
                } else {
                    $dates = '';
                }

                $doneOption = '<span name="' . $row['name'] . '" id="' . $row['id'] . '"><button id="doneButton" class="btn btn-light btn-sm"><img src="right.png"></button></span>';

                $removeOption = '<span name="' . $row['name'] . '" id="' . $row['id'] . '"><button id="removeButton" class="btn btn-light btn-sm"><img src="remove.png"></button></span>';

                $editOption = '';
                if (isset($_GET['edit'])) {
                    if ($_GET['edit'] == '') {
                        $editOption = '<td><a href="index.php?edit=' . $row['name'] . '"><img src="edit.png"></a></td>';
                    } else {
                        if ($_GET['edit'] == $row['name']) {
                            $editOption = '<td><span name="' . $row['name'] . '" id="' . $row['id'] . '"><button id="editButton" class="btn btn-warning btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Edit</button></span></td>';
                        } else {
                            $editOption = '<td style="text-align:center;"><a href="index.php?edit=' . $row['name'] . '"><img src="edit.png"></a></td>';
                        }
                    }
                }

                echo '<tr><td>' . $positionBox . '</td>' . '<td> ' . $name . '</td><td>' . $upButton . $downButton . '</td><td><a href="cat.php?id=' . $cat['id'] . '">' . $row['category'] . '</a></td><td>' . $doneOption . ' ' . $removeOption . '</td>' . $editOption . '' . $dates . '</tr>';
            }
            echo '</table><br><br>';

        } else {

            // Start Planned function
            $dateToday = date('Y-m-d');
            echo '<a href="index.php" class="btn btn-info">Refresh</a> <a href="index.php?cancel_plans" class="btn btn-secondary">CancelPlan</a> <a href="index.php?editTasks" class="btn btn-dark">EditTasks</a><br><bR>';
            $shows = $con->query("SELECT * FROM list where status='' group by STR_TO_DATE(planned, '%d-%M-%Y') order by STR_TO_DATE(planned, '%d-%M-%Y')='$dateToday' desc ,STR_TO_DATE(planned, '%d-%M-%Y') asc");

            $arrs = array();
            while ($rows = $shows->fetch()) {
                $dateFormat = date('Y-m-d', strtotime($rows['planned']));
                $arrs[$dateFormat][] = ['planId' => $rows['id']];
            }

            foreach ($arrs as $key => $value) {

                $timeDiff = diffinTime($dateNow, $key);

                if ($key == $dateToday) {
                    $planIcon = '222.png';
                    echo '<table id="PlannedTable"><tr><th style="background-color:#BD2F2F !important;border-radius:20px 20px 0px 0px;" colspan="3"><b><font size="2">Today <img src="notif.png"></font> <br> ' . $key . '</b></th></tr>';
                } else {

                    if ($key > $dateToday) {
                        $planIcon = 'arrowcircle.png';
                        echo '<table id="PlannedTable"><tr><th style="background-color:#3273CA !important;" colspan="2"><b><font size="3"><img src="upcoming.png">Upcoming (in ' . $timeDiff[3] . ' days!)</font> <br> ' . $key . '</b></th></tr>';
                    } else {
                        $planIcon = 'stop.png';
                        echo '<table id="PlannedTable"><tr><th style="background-color:#E0C632 !important;" colspan="2"><b>' . $key . ' <img src="late.png"></b></th></tr>';
                    }

                }

                foreach ($value as $item) {

                    $selectAll = $con->query("SELECT * FROM list where status='' and STR_TO_DATE(planned, '%d-%M-%Y')='$key' order by category desc");
                    while ($rws = $selectAll->fetch()) {
                        $selectCat = $con->query("SELECT * FROM categories where name='$rws[category]'");
                        $fetch = $selectCat->fetch();

                        $plansBox = '';
                        $plansCss = '';

                        if (isset($_GET['plans']) && $_GET['id'] == $rws['id']) {

                            if ($key == $dateToday) {
                                $plansBox = '<form action="index.php" method="post"><select name="plansChange" onchange="this.form.submit()">';
                                $plansBox .= '<option value=""></option>';
                                for ($i = 1; $i < 30; $i++) {
                                    $dayPlan = date("Y-m-d", strtotime("+$i days"));
                                    $selectCount = $con->query("SELECT COUNT(*) as totalNum FROM list where STR_TO_DATE(planned, '%d-%M-%Y')='$dayPlan' and status = ''");
                                    $row = $selectCount->fetch();

                                    if (!empty($row['totalNum'])) {
                                        $totalNum = '(' . $row['totalNum'] . ' Tasks)';
                                    } else {
                                        $totalNum = '';
                                    }
                                    $timeDiffs = diffinTime($dateToday, $dayPlan);
                                    $diffText = ' (' . $timeDiffs[3] . ' days)';
                                    $plansBox .= '<option value="' . $dayPlan . '">' . $dayPlan . $diffText . ' ' . $totalNum . '</option>';
                                }

                                $plansBox .= '</select><input type="hidden" name="hiddenPlanID" value="' . $rws['id'] . '"></form>';
                            } elseif ($key < $dateToday) {
                                $plansBox = '<form action="index.php" method="post"><select name="plansChange" onchange="this.form.submit()">';
                                $plansBox .= '<option value=""></option>';
                                for ($i = 0; $i < 30; $i++) {
                                    $dayPlan = date("Y-m-d", strtotime("+$i days"));
                                    $selectCount = $con->query("SELECT COUNT(*) as totalNum FROM list where STR_TO_DATE(planned, '%d-%M-%Y')='$dayPlan' and status = ''");
                                    $row = $selectCount->fetch();

                                    if (!empty($row['totalNum'])) {
                                        $totalNum = '(' . $row['totalNum'] . ' Tasks)';
                                    } else {
                                        $totalNum = '';
                                    }
                                    $timeDiffs = diffinTime($dateToday, $dayPlan);
                                    $diffText = ' (' . $timeDiffs[3] . ' days)';
                                    if ($dayPlan == $dateToday) {
                                        $diffText = ' (Today)';
                                    }
                                    $plansBox .= '<option value="' . $dayPlan . '">' . $dayPlan . $diffText . ' ' . $totalNum . ' </option>';
                                }

                                $plansBox .= '</select><input type="hidden" name="hiddenPlanID" value="' . $rws['id'] . '"></form>';
                            } else {

                                $plansBox = '<form action="index.php" method="post"><select name="plansChange" onchange="this.form.submit()">';
                                $plansBox .= '<option value=""></option>';
                                for ($i = 0; $i < 30; $i++) {
                                    $dayPlan = date("Y-m-d", strtotime("+$i days"));
                                    $selectCount = $con->query("SELECT COUNT(*) as totalNum FROM list where STR_TO_DATE(planned, '%d-%M-%Y')='$dayPlan' and status = ''");
                                    $row = $selectCount->fetch();

                                    if (!empty($row['totalNum'])) {
                                        $totalNum = '(' . $row['totalNum'] . ' Tasks)';
                                    } else {
                                        $totalNum = '';
                                    }
                                    $timeDiffs = diffinTime($dateToday, $dayPlan);
                                    $diffText = ' (' . $timeDiffs[3] . ' days)';
                                    if ($dayPlan == $dateToday) {
                                        $diffText = ' (Today)';
                                    }
                                    if ($dayPlan == $key) {
                                        $plansBox .= '<option value="' . $key . '" selected>' . $key . ' </option>';
                                    } else {
                                        $plansBox .= '<option value="' . $dayPlan . '">' . $dayPlan . $diffText . ' ' . $totalNum . ' </option>';
                                    }
                                }

                                $plansBox .= '</select><input type="hidden" name="hiddenPlanID" value="' . $rws['id'] . '"></form>';

                            }

                            $plansCss = '<br><img src="down_arrow.png"> ' . $plansBox . '';

                        }

                        $doneOption = '<span name="' . $rws['name'] . '" id="' . $rws['id'] . '"><button id="doneButton" class="btn btn-light btn-sm"  style="display:inline;margin-left:5px;"><img src="right.png"></button></span>';

                        $removeOption = '';
                        if (isset($_GET['cancel_plans'])) {
                            $removeOption = '<span name="' . $rws['name'] . '" id="' . $rws['id'] . '"><button id="removeButton" class="btn btn-light btn-sm" style="display:inline;margin:5px;"><img src="remove.png"></button></span>';
                        }

                        $editOption = '';
                        $tasks = $rws['name'];
                        if (isset($_GET['editTasks'])) {
                            if (isset($_GET['id']) && $_GET['id'] == $rws['id']) {
                                $tasks = '<textarea name="editText" id="editText">' . $rws['name'] . '</textarea><span name="' . $rws['name'] . '" id="' . $rws['id'] . '"><button id="editButton" class="btn btn-warning btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Edit</button></span>';
                            } else {
                                $editOption = '<button class="btn btn-light btn-sm" style="display:inline;margin:5px;"><a href="index.php?editTasks&id=' . $rws['id'] . '"><img src="edit.png"></a></button>';
                            }
                        }

                        echo '<tr>
                        <td> <img src="' . $planIcon . '"><span id="plans" style="padding:10px;"><b>' . $tasks . '</b></span>' . $plansCss . '<span id="catnames" style="text-shadow:1px 1px 10px #fff, 1px 1px 10px;-webkit-text-stroke: 0.03em #8F8F8F;float:right;border:2px solid #8F8F8F;padding:3px 8px 3px 8px;border-radius:10px;color:' . $fetch['catColor'] . ';"><a href="cat.php?id=' . $fetch['id'] . '" style="color:inherit;">' . $rws['category'] . '</a></span></td>

                        <td style="width:17%;text-align:center;"><button class="btn btn-light btn-sm" style="display:inline;margin-bottom:5px;"><a href="index.php?plans&id=' . $rws['id'] . '"><img src="change.png"></a></button>' . $doneOption . $removeOption . $editOption . '</td>
                        </tr>';
                    }
                    echo '</table>';
                }
                echo '<br>';
            }

            // End Planned function
        }
        echo '</div>';
        /////////// END Priority LIST/////////////
    } else {
        echo '<div style="float:left;">';
        $selectAll = $con->query("SELECT * FROM list where status !='canceled' group by category, STR_TO_DATE(added_date, '%d-%M-%Y') order by id desc");
        while ($fetch = $selectAll->fetch()) {
            $dateAdded = $fetch['added_date'];
            $dateOnly = date('Y-m-d', strtotime($dateAdded));
            $timeOnly = date('h:i', strtotime($dateAdded));

            $array[$dateOnly][] = ['cat_name' => $fetch['category']];
        }

        foreach ($array as $key => $value) {
            $dateFormat = date('d-M-Y', strtotime($key));
            echo '<font color="#5a97c2" style="font-size:24px;border:2px solid #bebebe;border-radius:10px;padding:5px;background:#d1d3d5;font-weight:bold;">' . $dateFormat . ':</font> ';

            if ($ro['showCats'] == 'show') {
                foreach ($value as $item) {
                    $cat_names = $item['cat_name'];
                    $cats = $con->query("SELECT * FROM categories where name='$item[cat_name]'");
                    $ros = $cats->fetch();

                    if ($ro['styled'] == 'ok') {
                        echo '<br><br><span id="cat_name" style="border:2px solid #737272;border-radius:10px;padding:5px;font-style:italic;"><b> <a href="cat.php?id=' . $ros['id'] . '" style="color:inherit;">' . $item['cat_name'] . '</a></b></span><br>';
                    } else {
                        echo '<br><br><span id="cat_name" style="border:2px solid #737272;border-radius:10px;padding:5px;font-style:italic;color:' . $ros['catColor'] . '"><b id="strokethis"> <a href="cat.php?id=' . $ros['id'] . '" style="color:inherit;">' . $item['cat_name'] . '</a></b></span><br>';
                    }

                    $selectAll = $con->query("SELECT * FROM list where category='$item[cat_name]' and STR_TO_DATE(added_date, '%d-%M-%Y')='$key' order by status asc");

                    while ($rws = $selectAll->fetch()) {
                        if ($rws['status'] == 'done') {
                            $diff = diffinTime($rws['added_date'], $rws['done_date']);
                            $detailedTime = secondsToTime($diff[0]);
                            echo '<br><span> <font color="#706d6d" style="font-size:20px;font-weight:bold;font-style:italic;" id="task_style"><img src="down_arrow.png"> <del>' . $rws['name'] . '</del></font></span> <img src="done.png"> <span id="detailed_time" style="font-size:12px;font-style:italic;">@' . $rws['done_date'] . ' (Took: ' . $detailedTime . ')</span><br>';
                        } elseif ($rws['status'] == 'canceled') {

                        } else {

                            $doneOption = '<span name="' . $rws['name'] . '" id="' . $rws['id'] . '"><button id="doneButton" class="btn btn-success btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Done</button></span>';

                            $removeOption = '<span name="' . $rws['name'] . '" id="' . $rws['id'] . '"><button id="removeButton" class="btn btn-danger btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Remove</button></span>';

                            $cancelOption = '<a href="index.php"><button class="btn btn-info btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Cancel</button></a>';

                            if (isset($_GET['manage'])) {
                                if ($_GET['manage'] == $rws['id']) {
                                    echo '<br><span id="task_style"> <font color="' . $rws['colorCode'] . '" id="strokethis"><img src="down_arrow.png"> ' . $rws['name'] . '</font> ' . $doneOption . $removeOption . ' ' . $cancelOption . ' </span><br>';
                                } else {
                                    if ($ro['styled'] == 'ok') {
                                        echo '<br><span id="task_style"> <font color="' . $rws['colorCode'] . '" id="strokethis"><img src="down_arrow.png"> <span style="vertical-align: middle;">' . $rws['name'] . '</span></font> <a href="index.php?manage=' . $rws['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a></span><br>';
                                    } else {
                                        echo '<br><span> <font style="font-size:20px;font-weight:bold;" id="task_style"><img src="down_arrow.png"> <span style="vertical-align: middle;">' . $rws['name'] . '</span></font> <a href="index.php?manage=' . $rws['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a> </span><br>';
                                    }
                                }
                            } else {
                                if ($ro['styled'] == 'ok') {
                                    echo '<br><span id="task_style"> <font color="' . $rws['colorCode'] . '" id="strokethis"><img src="down_arrow.png"> <span style="vertical-align: middle;">' . $rws['name'] . '</span></font> <a href="index.php?manage=' . $rws['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a></span><br>';
                                } else {
                                    echo '<br><span> <font style="font-size:20px;font-weight:bold;" id="task_style"><img src="down_arrow.png"> <span style="vertical-align: middle;">' . $rws['name'] . '</span></font> <a href="index.php?manage=' . $rws['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a> </span><br>';
                                }
                            }
                        }
                    }

                }
            } else {
                $selectAll = $con->query("SELECT * FROM list where STR_TO_DATE(added_date, '%d-%M-%Y')='$key' order by status asc");

                echo '<br>';
                while ($rws = $selectAll->fetch()) {
                    if ($rws['status'] == 'done') {
                        $diff = diffinTime($rws['added_date'], $rws['done_date']);
                        $detailedTime = secondsToTime($diff[0]);
                        echo '<br><span> <font color="#706d6d" style="font-weight:bold;font-size:20px;" id="task_style_noCats"><img src="down_arrow.png"> <del>' . $rws['name'] . '</del></font></span> <img src="done.png"> <span id="detailed_time" style="font-size:12px;font-style:italic;">@' . $rws['done_date'] . ' (Took: ' . $detailedTime . ')</span><br>';

                    } elseif ($rws['status'] == 'canceled') {

                    } else {

                        $doneOption = '<span name="' . $rws['name'] . '" id="' . $rws['id'] . '"><button id="doneButton" class="btn btn-success btn-sm" style="border-radius:10px;margin-left:10px;padding-left:25px;padding-right:25px;">Done</button></span>';

                        $removeOption = '<span name="' . $rws['name'] . '" id="' . $rws['id'] . '"><button id="removeButton" class="btn btn-danger btn-sm" style="border-radius:10px;margin-left:10px;padding-left:10px;padding-right:10px;">Remove</button></span>';

                        $cancelOption = '<a href="index.php"><button class="btn btn-info btn-sm" style="border-radius:10px;margin-left:10px;padding-left:20px;padding-right:20px;">Cancel</button></a>';

                        if (isset($_GET['manage'])) {
                            if ($_GET['manage'] == $rws['id']) {
                                echo '<br><span id="task_style_noCats"><img src="down_arrow.png"> <font color="' . $rws['colorCode'] . '" id="strokethis">' . $rws['name'] . '</font> ' . $doneOption . $removeOption . ' ' . $cancelOption . '</span><br>';
                            } else {
                                if ($ro['styled'] == 'ok') {
                                    echo '<br><span id="task_style_noCats"><img src="down_arrow.png"> <font color="' . $rws['colorCode'] . '" id="strokethis"><span style="vertical-align: middle;">' . $rws['name'] . '</span></font> <a href="index.php?manage=' . $rws['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a></span><br>';
                                } else {
                                    echo '<br><span id="task_style_noCats"><img src="down_arrow.png"> <span style="vertical-align: middle;">' . $rws['name'] . '</span> <a href="index.php?manage=' . $rws['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a></span><br>';
                                }
                            }
                        } else {
                            if ($ro['styled'] == 'ok') {
                                echo '<br><span id="task_style_noCats"><img src="down_arrow.png"> <font color="' . $rws['colorCode'] . '" id="strokethis"><span style="vertical-align: middle;">' . $rws['name'] . '</span></font> <a href="index.php?manage=' . $rws['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a></span><br>';
                            } else {
                                echo '<br><span id="task_style_noCats"><img src="down_arrow.png"> <span style="vertical-align: middle;">' . $rws['name'] . '</span> <a href="index.php?manage=' . $rws['id'] . '"><img src="manage.png" style="vertical-align: middle;"></a></span><br>';
                            }
                        }
                    }
                }
            }

            echo '<br><br><br>';

        }
        echo '</div>';
    }
}

?>
</div>
</div>

<script src="js/jquery-3.6.0.min.js"></script>
<script src="js/bootstrap.min.js"></script>
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



$(document).delegate("#removeButton", "click", function(){
            var name = $(this).parents("span").attr("name");
            var id = $(this).parents("span").attr("id");

            Swal.fire({
            title: '<font size="4">Do you want to remove <b style="color:red;">'+ name +'</b> ?</font>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: "Yes, Remove!"
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



$(document).delegate("#editButton", "click", function(){
            var id = $(this).parents("span").attr("id");
            var name = document.getElementById("editText").value;

            Swal.fire({
            title: '<font size="4">Do you want to Edit this to <b style="color:blue;">'+ name +'</b> ?</font>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0E931C',
            cancelButtonColor: '#d33',
            confirmButtonText: "Yes, Edit!"
            }).then((result) => {

                     if (result.isConfirmed) {

                    $.ajax({
                    type: "POST",
                    url: "edit.php",
                    data: {name: name, id:id},
                beforeSend: function () {},
                success: function (response) {

                    Swal.fire(
                    "Success!",
                    "The Task Changed <b style='color:blue;'>"+ name +"</b> Successfully!",
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


$(document).delegate("#deleteCats", "click", function(){
            var name = $(this).parents("span").attr("name");
            var id = $(this).parents("span").attr("id");

            Swal.fire({
            title: '<font size="4">Are you sure you want to delete category: <b style="color:red;">'+ name +'</b> ?</font>',
            text: "Note: it's Permenant and all tasks related will be removed",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: "Yes, Delete Permenant!"
            }).then((result) => {

                     if (result.isConfirmed) {

                    $.ajax({
                    type: "POST",
                    url: "deletecats.php",
                    data: {name: name, id:id},
                beforeSend: function () {},
                success: function (response) {

                    Swal.fire(
                    "Success!",
                    "The Category <b style='color:red;'>"+ name +"</b> has been Deleted!",
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

        $("#editText").keyup(function(event) {
    if (event.keyCode === 13) {
        $("#editButton").click();
    }
    });


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
if (isset($_POST['cat_Yes']) && $_POST['cat_Yes'] == 'YES!') {
    $task = '<font size="5">Category <b style="color:green">' . $catName . '</b> has been added!</font>';
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
