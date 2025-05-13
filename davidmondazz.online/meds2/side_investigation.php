<?php
date_default_timezone_set("Africa/Cairo");

// Connect to MySQL
$servername = "localhost";
$username = "mcgkxyz_masterpop";
$password = "aA0109587045";
$database = "mcgkxyz_meds2";

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'];
$side_name = $_GET['name'];



  // Select sides 3


     $stmt3 = $conn->prepare("SELECT * FROM side_effects WHERE id = ?");
    $stmt3->bind_param("i", $id); // Assuming $id is an integer
    $stmt3->execute();
    $result4 = $stmt3->get_result();
  
  // Check if the query executed successfully
  if ($result4) {
    $fetch = $result4->fetch_assoc();
    $dose = $fetch["daytime"];
    $st2 = str_replace(',', '', $dose);
    $dayTime = date('d M, Y', strtotime($st2));

    $ended = '';
    if($fetch['ongoing'] == 'no'){
        $ended = ' and ended at '.$fetch['ended'];
    }

    $sidename = $fetch['keyword'];
    $hiddenSideName = $fetch['keyword'];
    if($fetch['feelings'] == 'positive'){
        $sidename = '<font color="green">'.$fetch['keyword'].'</font>';
    }elseif($fetch['feelings'] == 'negative'){
        $sidename = '<font color="red">'.$fetch['keyword'].'</font>';
    }else{
        $sidename = '<font color="blue">'.$fetch['keyword'].'</font>';
    }



      $stmt4 = $conn->prepare("SELECT * FROM side_effects WHERE keyword = ? AND id != ?");
    $stmt4->bind_param("si", $side_name, $id); // Assuming $id is an integer
    $stmt4->execute();
    $result5 = $stmt4->get_result();

    
    $otherSusArray = array(); // Initialize the array

// Function to convert timestamp to time ago format
function time_ago($timestamp) {
    if (empty($timestamp)) return '';
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' ' . ($mins == 1 ? 'minute' : 'minutes') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . ($hours == 1 ? 'hour' : 'hours') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ' . ($days == 1 ? 'day' : 'days') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' ' . ($weeks == 1 ? 'week' : 'weeks') . ' ago';
    } else {
        $months = floor($diff / 2592000);
        return $months . ' ' . ($months == 1 ? 'month' : 'months') . ' ago';
    }
}

// Check if the query executed successfully
if ($result5) {
    while ($rows = $result5->fetch_assoc()) {
        
        $strDate = str_replace(',', '', $rows['daytime']);
        $mySusDate = date('d M, Y', strtotime($strDate));
       
            // Add the value to the array along with id and keyword
            $otherSusArray[] = array(
                'sus' => $rows['my_sus'],
                'id' => $rows['id'],
                'mysusDate' => $mySusDate,
                'keyword' => $rows['keyword'],
                'daytime' => $rows['daytime'],
                'last_checked' => $rows['last_checked']
            );
       
    }
}

$mySus = $fetch['my_sus']; // Store the value of my_sus
$mySusID = $fetch['id'];
$strDate = str_replace(',', '', $fetch['daytime']);
$mySusDate = date('d M, Y', strtotime($strDate));
$otherSus = ''; // Initialize the string variable
$happenedBefore = ''; // Initialize the happenedBefore string

// Iterate through the array to create the string
foreach ($otherSusArray as $susData) {
    $sus = $susData['sus'];
    $ids = $susData['id'];
    $susDates = $susData['mysusDate'];
    $keyword = $susData['keyword'];
    $daytime = $susData['daytime'];
    $lastChecked = isset($susData['last_checked']) ? $susData['last_checked'] : '';
    
    $lastCheckedText = !empty($lastChecked) ? ' <small style="color:gray;">[Last checked: ' . time_ago($lastChecked) . ']</small>' : '';

   if(empty($sus)){
     // Concatenate happenedBefore with the message
     $happenedBefore .= 'Happened before at: ('.$susDates.')<a href="side_investigation.php?id='.$ids.'&name='.$keyword.'" class="track-sus-click" data-id="'.$ids.'">' . $daytime . '</a>'.$lastCheckedText.'<br>';
   }

    // If the value is different from my_sus, include it in the "Other Sus" section
  
       if(!empty($sus)){
     
            // Create the link with parameters id and name
            $otherSus .= '<a href="side_investigation.php?id=' . $ids . '&name=' . $keyword . '" class="track-sus-click" data-id="'.$ids.'" style="border-radius: 10px; padding: 5px 10px; display: inline-block; border: 2px solid black;">(' . $susDates . ') ' . $sus . '</a>'.$lastCheckedText.', ';
       
       }
   
}
// Print the removeButton
echo '<div class="inline-container">';
echo '<button onclick="goBack()" style="margin-right: 10px;vertical-align: middle;"><img src="img/back.png" width="30px;" height="30px;"></button>';
echo '<a href="index.php"><button style="margin-right: 10px;vertical-align: middle;"><img src="img/home.png" width="30px;" height="30px;"></button></a>';
echo '<input type="text" name="sidesText" id="sidesText" onkeyup="getResults(this.value)" placeholder="Search sides..." style="vertical-align: middle;"> ';
echo '<input type="text" name="searchMySus" id="searchMySus" onkeyup="getmySusResults(this.value)" placeholder="Search My_Sus ..." style="vertical-align: middle;">';
echo '</div>';
echo '<div id="results" class="results"></div>';
// Separating the containers to ensure they are siblings in the HTML structure
echo '<div id="my_sus_results" class="results"></div>';

echo '<br><Br>';


// Set $otherText based on whether otherSus is empty or not
$otherText = empty($otherSus) ? '' : ', other Sus: ' . $otherSus;

// Convert the date format to a format strtotime can understand
echo '{<b style="font-size:20px;"><span id="editSideName">' . $sidename . '</span></b>}';
echo '<button id="editMySide">Edit</button>';
echo '<input type="text" id="mySideInput" style="display: none;">';
echo '<input type="hidden" id="thisSideName" value="' . $hiddenSideName . '">';
echo '<div id="radioOptions" style="display: inline;">';
if($fetch['feelings'] == 'positive'){
echo '  <input type="radio" id="positive" name="choice" value="positive" checked>';
echo '  <label for="positive"><font color="green">Positive</font></label>';
echo '  <input type="radio" id="negative" name="choice" value="negative">';
echo '  <label for="negative"><font color="red">Negative</font></label> ';
}else{
echo '  <input type="radio" id="positive" name="choice" value="positive">';
echo '  <label for="positive"><font color="green">Positive</font></label>';
echo '  <input type="radio" id="negative" name="choice" value="negative" checked>';
echo '  <label for="negative"><font color="red">Negative</font></label> ';
}
echo '</div>';
echo '<button id="saveMySide" style="display: none;">Save</button>';
echo ' at ';
echo $dose;
echo $ended;
echo '<br>';


// Escape special characters in the search query
$escapedSideName = $conn->real_escape_string($side_name);

// Prepare the search query
$searchQuery = "+" . str_replace(" ", " +", $escapedSideName);

// Perform a full-text search query
$sql = "SELECT id, keyword, feelings, my_sus, daytime
FROM side_effects 
WHERE MATCH(keyword) AGAINST('$searchQuery' IN NATURAL LANGUAGE MODE) and feelings ='".$fetch['feelings']."'
GROUP BY id, keyword, feelings, my_sus, daytime 
ORDER BY 
    CASE feelings
        WHEN 'positive' THEN 1
        WHEN 'negative' THEN 2
        ELSE 3
    END asc, my_sus DESC Limit 10";
$result = $conn->query($sql);

if ($result === false) {
    die("Error executing query: " . $conn->error);
}
$similarKeywords = [];

// Display the search results
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $similarKeywords[] = array(
            'keyword' => $row["keyword"],
            'feelings' => $row["feelings"],
            'my_sus' => $row["my_sus"],
            'id' => $row["id"],
            'daytime' => $row["daytime"]
        );
    }

  
    foreach ($similarKeywords as $keywordData) {
        if($keywordData['keyword'] != $side_name){
            if($keywordData['feelings'] == 'positive'){
                $feelingColor= 'green';
            }elseif($keywordData['feelings'] == 'negative'){
                $feelingColor= 'red';
            }else{
                $feelingColor= 'blue';
            }
            
                $st2 = str_replace(',', '', $keywordData['daytime']);
               $dayOnly = date("Y-m-d", strtotime($st2));
               
            echo "<br>(".$dayOnly.")<a href='side_investigation.php?id=".$keywordData['id'] . "&name=".urlencode($keywordData['keyword']) . "' style='color: inherit;'><font color='".$feelingColor."'>".$keywordData['keyword'] . "</font>(".$keywordData['my_sus'].")</a>, ";
        }
    }
} 
echo '<br>';
echo '<br>';
echo '<b>My Sus: </b>('.$mySusDate.')<span id="mySusText" style="border-radius: 10px; padding: 5px 10px; display: inline-block; border: 2px solid black;">' . htmlspecialchars($mySus) . '</span>';
echo ' <button id="editMySus">Edit</button>';
echo '<input type="text" id="mySusInput" style="display: none;width:50%;">';
echo '<button id="saveMySus" style="display: none;">Save</button>';
echo $otherText;
echo '<br>';
echo $happenedBefore;



  }

  // end of select sides 3





  // Select sides 2
    $stmt2 = $conn->prepare("SELECT * FROM side_effects WHERE keyword = ?");
    $stmt2->bind_param("s", $side_name); // Assuming $side_name is a string
    $stmt2->execute();
    $result3 = $stmt2->get_result();

  
  // Check if the query executed successfully
  if ($result3) {

      
   
     // Fetch all medications from medtrack table
  $sql = "SELECT * FROM medtrack ORDER BY id DESC";
  $result_medtrack = $conn->query($sql);
  
  // Create an associative array to store medication occurrences
  $medications_occurrences = array();
  
  // Loop through each row of the result from side_effects
  while ($fetch = $result3->fetch_assoc()) {
      $dose = $fetch["daytime"];
      $st2 = str_replace(',', '', $dose);
  
      // Convert the date format to a format strtotime can understand
      $dose_date = date('Y-m-d h:i:s a', strtotime($st2));
     
  
      // Loop through each row of the result from medtrack
      while ($row = $result_medtrack->fetch_assoc()) {
          // Calculate date after 5 half-lives
          $half_life_hours = $row["default_half_life"] * 60 * 60; // Convert half-life to seconds
          $dose2 = $row["dose_date"];
          $st3 = str_replace(',', '', $dose2);
          $dose_date2 = date('Y-m-d h:i:s a', strtotime($st3));
          $date_after_5_half_lives = date("Y-m-d h:i:s a", strtotime($st3) + (5 * $half_life_hours));
  
          if ($dose_date2 <= $dose_date && $date_after_5_half_lives >= $dose_date) {
              $med_name = $row["medname"];
              // Increment occurrence count if medication exists in the array, otherwise initialize it to 1
              if (array_key_exists($med_name, $medications_occurrences)) {
                  $medications_occurrences[$med_name]++;
              } else {
                  $medications_occurrences[$med_name] = 1;
              }
           
          }
      }
      // Move the internal pointer back to the beginning of the result set for medtrack
      $result_medtrack->data_seek(0);
     
  }
  
  // Sort medications_occurrences array by value in descending order
  arsort($medications_occurrences);
  

  
  // Output unique medications that caused the side effect and occurred repeatedly
/*

  echo "<br><b>Suspected Meds:</b><br>";
  foreach ($medications_occurrences as $med_name => $occurrences) {
      if ($occurrences > 1) {
      
         $words = $med_name;
         $firstWord = preg_replace('/\s*\d+\D*$/', '', $words);
          echo "<a href='possible_sides.php?name=".$firstWord."' style='text-decoration: none;color: inherit;'>$med_name</a> ($occurrences occurrences)<br>";
      }
  }
*/
  
  // Add medication combinations based on page ID
  echo "<br><b>Medication Combinations:</b><br>";
  
  // Get the specific side effect record using the page ID parameter
  $sql_side = "SELECT * FROM side_effects WHERE id = ?";
  $stmt_side = $conn->prepare($sql_side);
  $stmt_side->bind_param("i", $id); // Use the $id from URL parameter
  $stmt_side->execute();
  $result_side = $stmt_side->get_result();
  
  if ($row_side = $result_side->fetch_assoc()) {
      // Get the side effect date from this specific record
      $side_date_str = str_replace(',', '', $row_side["daytime"]);
      $sideEffectDate = date('Y-m-d h:i:s a', strtotime($side_date_str));
      
      // Get all medications that were in half-life at the time of side effect
      $sql = "SELECT * FROM medtrack ORDER BY id DESC";
      $result_halflife = $conn->query($sql);
      
      $medsInHalfLife = array();
      
      while ($row = $result_halflife->fetch_assoc()) {
          $half_life_hours = $row["default_half_life"] * 60 * 60; // Convert half-life to seconds
          $dose_date = $row["dose_date"];
          $st3 = str_replace(',', '', $dose_date);
          $med_date = date('Y-m-d h:i:s a', strtotime($st3));
          
          // If medication was taken before the side effect
          if (strtotime($med_date) <= strtotime($sideEffectDate)) {
              // Calculate time difference in hours
              $time_diff_hours = (strtotime($sideEffectDate) - strtotime($med_date)) / 3600;
              
              // Check if medication is still in its half-life (not past it)
              if ($time_diff_hours <= $row["default_half_life"]) {
                  // Extract medication name with dose
                  $med_name = $row["medname"];
                  
                  // Extract base name and dose separately
                  preg_match('/^(.*?)(\d+\.?\d*)(.*)$/i', $med_name, $matches);
                  if (count($matches) >= 3) {
                      $base_name = trim($matches[1]);
                      $dose = $matches[2]; // This captures the decimal value
                      $unit = trim($matches[3]);
                      
                      // Format the medication with dose
                      $med_with_dose = $base_name . ' ' . $dose . $unit;
                      
                      // Add to array if not already present with this exact dose
                      if (!in_array($med_with_dose, $medsInHalfLife)) {
                          $medsInHalfLife[] = $med_with_dose;
                      }
                  } else {
                      // If the regex didn't match, just use the full name
                      if (!in_array($med_name, $medsInHalfLife)) {
                          $medsInHalfLife[] = $med_name;
                      }
                  }
              }
          }
      }
      
      // Sort the medications alphabetically
      sort($medsInHalfLife);
      
      // Display the combination if there are multiple medications
      if (count($medsInHalfLife) > 1) {
          echo implode(" + ", $medsInHalfLife);
      } elseif (count($medsInHalfLife) == 1) {
          echo $medsInHalfLife[0] . " (single medication)";
      } else {
          echo "No medications were in half-life at the time of side effect";
      }
  } else {
      echo "Side effect record not found";
  }
  echo "<br>";
  
  }
  
  // end of Select sides 2



// Select sides 1
$sql = "SELECT * FROM side_effects WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result2 = $stmt->get_result();

// Check if the query executed successfully
if ($result2) {
    $fetch = $result2->fetch_assoc();
    $dose = $fetch["daytime"];
    $st2 = str_replace(',', '', $dose);

    // Convert the date format to a format strtotime can understand
  
     $side_date = date('Y-m-d h:i:s a', strtotime($st2)); // sides date
    echo '<br><bR>';

    // Select all medications
    $sql = "SELECT * FROM medtrack ORDER BY id DESC";
    $result = $conn->query($sql);

    function remainingDrugAmount($initialAmount, $hours, $halfLife) {
        $remainingAmount = $initialAmount * pow(0.5, ($hours / $halfLife));
        return $remainingAmount;
    }

        echo "<b>Medications were still in the system:</b><br>";

       // Loop through each row of the result
        $remainingAmounts = [];
        while ($row = $result->fetch_assoc()) {
            // Calculate date after 5 half-lives
            $half_life_hours = $row["default_half_life"] * 60 * 60; // Convert half-life to seconds
            $dose2 = $row["dose_date"];
            $st3 = str_replace(',', '', $dose2);
            $dose_date = date('Y-m-d h:i:s a', strtotime($st3)); // meds date
            $date_after_5_half_lives = date("Y-m-d h:i:s a", strtotime($st3) + (5 * $half_life_hours));
            

          if(strtotime($dose_date) <= strtotime($side_date)){
              // Compare date after 5 half-lives with current date
              if (strtotime($date_after_5_half_lives) >= strtotime($side_date)) {
         $startDate = DateTime::createFromFormat('d M, Y h:i a', $dose2);
        $currentDate = DateTime::createFromFormat('d M, Y h:i a', $dose); // Assuming $side_date is your current date
        $difference = $currentDate->diff($startDate);
        $time_difference_hours = $difference->days * 24 + $difference->h + ($difference->i / 60);
                
                $timeDiff = '';
                if(round($time_difference_hours, 2) <= $row["default_half_life"]){
                    $timeDiff = '<font color="green">'.round($time_difference_hours, 2).'</font>';
                }else{
                    $timeDiff = '<font color="red">'.round($time_difference_hours, 2).'</font>';
                }

                preg_match('/\d+(\.\d+)?/', $row["medname"], $matches);

                $errorMsg = '';
                if (!empty($matches)) {
                    $initialAmount = floatval($matches[0]);
                    // Your code to handle $initialAmount goes here
                    $remainingAmount = remainingDrugAmount($initialAmount, $time_difference_hours, $row["default_half_life"]);
                 
                    // Get the drug name
             
                $words = $row["medname"];
                $drugName = preg_replace('/\s*\d+\D*$/', '', $words);
                

                // Add remaining amount to the array for this drug
                if (!isset($remainingAmounts[$drugName])) {
                    $remainingAmounts[$drugName] = 0;
                }
                $remainingAmounts[$drugName] += $remainingAmount;
                $remain = '(Remain: '.round($remainingAmount,2).' mg)';

                } else {
                    // Handle the case where no matches were found
                    $errorMsg = "<font color='red'>(No Med Dose Identified!)</font>";
                    $remainingAmount = '';
                    $remain = '';
                }

             
                
               $words = $row["medname"];
                $firstWord = preg_replace('/\s*\d+\D*$/', '', $words);

                
                echo '<a href="possible_sides.php?name='.$firstWord.'" style="text-decoration: none;color: inherit;">'.$row["medname"] .'</a> = '.$timeDiff. " hrs (Half: ".floatval($row["default_half_life"]).") $remain ".$errorMsg."<br>"; // Output medication name
             
            }
          }
      
        }
        echo '<br><br>';

        
        asort($remainingAmounts);
        // Output grouped remaining amounts for each drug
        foreach ($remainingAmounts as $drugName => $totalRemainingAmount) {
            echo '<a href="possible_sides.php?name='.$drugName.'" style="text-decoration: none;color: inherit;">'.$drugName . "</a> (Total Remain: " . round($totalRemainingAmount,2) . " mg)<br>";
        }
        
        

        echo '<br><br>';

   // end of select sides 1

// meds that just passed half life
$sql2 = "SELECT * FROM medtrack ORDER BY id DESC";
$result2 = $conn->query($sql2);


    echo "<b>Medications that just passed half life:</b><br>";

    // Loop through each row of the result
    $remainingAmounts = [];
    while ($row = $result2->fetch_assoc()) {
        // Calculate date after 5 half-lives
        $half_life_hours = $row["default_half_life"] * 60 * 60; // Convert half-life to seconds
        $dose2 = $row["dose_date"];
        $st3 = str_replace(',', '', $dose2);
        $dose_date = date('Y-m-d h:i:s a', strtotime($st3)); // meds date
        $date_after_5_half_lives = date("Y-m-d h:i:s a", strtotime($st3) + (5 * $half_life_hours));
        
        if ($row["default_half_life"] > 0) {
        if (strtotime($dose_date) <= strtotime($side_date)) {
            // Compare dose timestamp with side timestamp
           // Convert dose date and side date to timestamps
        $dose_timestamp = strtotime($dose_date);
        $side_timestamp = strtotime($side_date);

        // Calculate time difference in hours
        $time_difference_hours = abs($side_timestamp - $dose_timestamp) / (60 * 60);

            // Check if the time difference is within the range of half-life ± 1 hour
        if ($time_difference_hours >= $row["default_half_life"] && $time_difference_hours <= ($row["default_half_life"] + 1)) {
            // Display medication name
          
            echo $row["medname"] . "<br>";
        }
                    }
                }
  
    }
  
// end of meds that just passed half life

echo '<br><br>';


    } 

 



// Close MySQL connection
$conn->close();




?>

<title>Side: (<?php echo $dayTime;?>)<?php echo $side_name;?></title>

<br><br><br><br><br>

<style>
    #sidesText {
    width: 250px; /* Adjust the width as needed */
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
    background-color: #6cc380;
  }
  
    #searchMySus {
    width: 250px; /* Adjust the width as needed */
    padding: 10px;
    margin-left:10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
    background-color: #c6b164;
  }
  ::placeholder {
        color: black;
    }
#results {
    border: 1px solid #ccc;
    display: none;
    background-color: white; /* Set the background color to white */
    max-width: 300px; /* Adjust the max-width according to your preference */
}

#results div {
    padding: 10px;
    cursor: pointer;
    color: black; /* Set the font color to black */
    font-weight: bold; /* Make the font bold */
}

#results div:hover {
    background-color: yellow;
}

#my_sus_results {
    border: 1px solid #ccc;
    display: none;
    background-color: white; /* Set the background color to white */
    max-width: 300px; /* Adjust the max-width according to your preference */
}

#my_sus_results div {
    padding: 10px;
    cursor: pointer;
    color: black; /* Set the font color to black */
    font-weight: bold; /* Make the font bold */
}

#my_sus_results div:hover {
    background-color: yellow;
}

.inline-container {
    display: inline-flex; /* Display elements inline */
    align-items: center; /* Align items vertically */
}

</style>

<br><br>

<?php

echo $removeButton = '<span id="' . $_GET['id'] . '" name="' . $_GET['name'] . '"><button class="button3" id="removeButton" style="padding: 3px 15px;">REMOVE "' . $_GET['name'] . '"!</button></span><br><br>';

?>

<script src="js/jquery-3.6.0.min.js"></script>
<script>

// JavaScript function to convert timestamps to time ago format
function timeAgo(timestamp) {
    if (!timestamp) return '';
    
    var now = Math.floor(Date.now() / 1000);
    var diff = now - timestamp;
    
    if (diff < 60) {
        return diff + ' seconds ago';
    } else if (diff < 3600) {
        var mins = Math.floor(diff / 60);
        return mins + ' ' + (mins === 1 ? 'minute' : 'minutes') + ' ago';
    } else if (diff < 86400) {
        var hours = Math.floor(diff / 3600);
        return hours + ' ' + (hours === 1 ? 'hour' : 'hours') + ' ago';
    } else if (diff < 604800) {
        var days = Math.floor(diff / 86400);
        return days + ' ' + (days === 1 ? 'day' : 'days') + ' ago';
    } else if (diff < 2592000) {
        var weeks = Math.floor(diff / 604800);
        return weeks + ' ' + (weeks === 1 ? 'week' : 'weeks') + ' ago';
    } else {
        var months = Math.floor(diff / 2592000);
        return months + ' ' + (months === 1 ? 'month' : 'months') + ' ago';
    }
}

$(document).on('click', "#removeButton",function(){
        var id = $(this).parents("span").attr("id");
        var name = $(this).parents("span").attr("name");

        if(confirm('Are you sure you want to remove "'+ name +'" ?'))
        {
            $.ajax({
               url: 'remove_side.php',
               type: 'GET',
               data: {id: id, name: name},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location = 'index.php';
               }
            });
        }
    });


     // Store the scroll position before navigating away
     function storeScrollPosition() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        }

        // Restore the scroll position when navigating back
        function restoreScrollPosition() {
            var scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition !== null) {
                window.scrollTo(0, scrollPosition);
                sessionStorage.removeItem('scrollPosition');
            }
        }

        // Function to navigate back
        function goBack() {
            restoreScrollPosition(); // Restore the scroll position
            window.history.back(); // Navigate back
        }

        $(document).ready(function() {
    $("#editMySus").click(function() {
        $("#mySusText").hide(); // Hide the text
        $("#mySusInput").val($("#mySusText").text()).show().focus(); // Set input value to current text, show input field, and focus
        $("#editMySus").hide(); // Hide the "Edit" button
        $("#saveMySus").show(); // Show the "Save" button
    });

    $("#saveMySus").click(function() {
        var newSus = $("#mySusInput").val();
        var id = <?php echo $_GET['id']; ?>; // Get the id from PHP
        $.ajax({
            type: "POST",
            url: "update_my_sus.php",
            data: { newSus: newSus, id: id }, // Pass both newSus and id
            success: function(response) {
                $("#mySusText").text(response).show(); // Update the displayed text
                $("#mySusInput").hide(); // Hide the input field
                $("#saveMySus").hide(); // Hide the "Save" button
                $("#editMySus").show(); // Show the "Edit" button
            }
        });
    });
});


 $(document).ready(function() {
     $("#radioOptions").hide();
    $("#editMySide").click(function() {
        $("#mySideInput").val($("#editSideName").text()).show().focus(); // Set input value to current text, show input field, and focus
        $("#editMySide").hide(); // Hide the "Edit" button
        $("#saveMySide").show(); // Show the "Save" button
        $("#radioOptions").show(); // Show the "Save" button
        
    });

    $("#saveMySide").click(function() {
        var newSide = $("#mySideInput").val();
        var thisSideName = $("#thisSideName").val();
        var id = <?php echo $_GET['id']; ?>; // Get the id from PHP
        var selectedOption = $("input[name='choice']:checked").val(); // Get the value of the selected radio option
        $.ajax({
            type: "POST",
            url: "update_my_side.php",
            data: { newSide: newSide, thisSideName:thisSideName, id:id, selectedOption: selectedOption},
            success: function(response) {
                $("#editSideName").text(response).show(); // Update the displayed text
                $("#mySideInput").hide(); // Hide the input field
                $("#saveMySide").hide(); // Hide the "Save" button
                $("#editMySide").show(); // Show the "Edit" button
                window.location.href = "side_investigation.php?id=" + id + "&name=" + newSide;
            }
        });
    });
});


// Function to handle getting results and redirecting
function getResults(value) {
    if (value.length == 0) { 
        document.getElementById("results").style.display = "none";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var results = JSON.parse(this.responseText);
                var resultsDiv = document.getElementById("results");
                resultsDiv.innerHTML = "";
                resultsDiv.style.display = "block";
                for (var i = 0; i < results.length; i++) {
                    var resultDiv = document.createElement("div");
                    resultDiv.innerHTML = results[i];
                    resultDiv.onclick = function() {
                        var resultName = this.innerHTML;
                        // AJAX request to fetch latest ID for the result name
                        var xhr = new XMLHttpRequest();
                        xhr.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                                var latestId = JSON.parse(this.responseText);
                                // Redirect to side_investigation.php with latest ID and result name
                                window.location.href = "side_investigation.php?id=" + latestId + "&name=" + encodeURIComponent(resultName);
                            }
                        };
                        xhr.open("GET", "get_latest_id.php?name=" + encodeURIComponent(resultName), true);
                        xhr.send();
                    }
                    resultsDiv.appendChild(resultDiv);
                }
            }
        };
        xmlhttp.open("GET", "search.php?q=" + value, true);
        xmlhttp.send();
    }
}

function getmySusResults(value) {
    if (value.length == 0) {
        document.getElementById("my_sus_results").style.display = "none";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var results = JSON.parse(this.responseText);
                var resultsDiv = document.getElementById("my_sus_results");
                resultsDiv.innerHTML = "";
                resultsDiv.style.display = "block";
                for (var i = 0; i < results.length; i++) {
                    var resultDiv = document.createElement("div");
                    resultDiv.dataset.mySus = results[i].my_sus;
                    resultDiv.dataset.id = results[i].id;
                    resultDiv.dataset.keyword = results[i].keyword;
                    resultDiv.style.color = results[i].color; // Apply color style
                    resultDiv.innerHTML = results[i].my_sus + " = " + "<span style='color: " + results[i].color + "'>" + results[i].keyword + "</span>";
                    resultDiv.onclick = function() {
                        var resultId = this.dataset.id;
                        var resultKeyword = this.dataset.keyword;
                        // Redirect to side_investigation.php with the ID and keyword
                        window.location.href = "side_investigation.php?id=" + resultId + "&name=" + encodeURIComponent(resultKeyword);
                    };
                    resultsDiv.appendChild(resultDiv);
                }
            }
        };
        xmlhttp.open("GET", "search_mySus.php?q=" + value, true);
        xmlhttp.send();
    }
}








// Add event listener to the document body
document.body.addEventListener('click', function(event) {
    var resultsDiv = document.getElementById("results");
    // Check if the click event target is not within the results area
    if (!resultsDiv.contains(event.target)) {
        // If click is outside of results, hide the results
        resultsDiv.style.display = "none";
    }
});

// Track clicks on "Other Sus" links and update last_checked timestamp
$(document).on('click', ".track-sus-click", function(e) {
    var id = $(this).data('id');
    var $link = $(this);
    
    // Make AJAX call to update the last_checked timestamp
    $.ajax({
        url: 'update_sus.php',
        type: 'GET',
        data: {id: id},
        error: function() {
            console.log('Error updating last checked timestamp');
        },
        success: function(timestamp) {
            // Look for existing timestamp element (a small tag right after the link)
            var $nextElement = $link.next('small');
            
            if ($nextElement.length > 0) {
                // Update existing timestamp
                $nextElement.html('[Last checked: ' + timeAgo(timestamp) + ']');
            } else {
                // Add new timestamp after the link
                $link.after(' <small style="color:gray;">[Last checked: ' + timeAgo(timestamp) + ']</small>');
            }
        }
    });
});




</script>


<br><br><br><br><br>


