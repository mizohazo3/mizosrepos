
<html>
    <head>
        <style>
            .half-life-link {
            color: blue;
            text-decoration: underline;
            cursor: pointer;
            }
        </style>
    </head>
</html>

<?php
include 'db.php';

if (isset($_POST['medname'])) {
    $medname = $_POST['medname'];

   
        if (strlen($medname) > 0) {
            // Search the medlist table for the name row
            $sql = "SELECT * FROM medlist WHERE name LIKE :medname GROUP BY default_half_life LIMIT 5";
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':medname', '%' . $medname . '%', PDO::PARAM_STR);
            
            // Execute the query and fetch the results
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo "<b style='color:red;'>Suggested Half Lifes:</b><br>";
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if(!empty($row["default_half_life"])){
                        echo '<B style="font-size:20px;"><span class="half-life-value half-life-link">'.htmlspecialchars($row["default_half_life"]) . "</span> hrs</b><br>";
                    }
                }
    } 
}
    
}