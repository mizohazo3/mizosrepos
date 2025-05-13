<?php
date_default_timezone_set("Africa/Cairo");

// Function to get the current USDEGP exchange rate from database
function getUSDEGPRate() {
    try {
        // Connect to mcgkxyz_timer_app database
        $timer_db = new PDO("mysql:host=localhost;dbname=mcgkxyz_timer_app", "mcgkxyz_masterpop", "aA0109587045");
        $timer_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Query to get USDEGP value
        $query = $timer_db->prepare("SELECT USDEGP FROM user_progress WHERE id = 1");
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        // Return the exchange rate or default value
        return $result && isset($result['USDEGP']) ? (float)$result['USDEGP'] : 50.59;
    } catch (PDOException $e) {
        // Log error for debugging
        error_log("Error fetching USDEGP rate: " . $e->getMessage());
        // Return default value if there's an error
        return 50.59;
    }
}

/**
 * Get medication price per dose from med_prices table
 * @param int $medId - Medication ID
 * @param object $con - Database connection
 * @param bool $inUSD - Whether to return the price in USD (true) or EGP (false)
 * @return float - Price per dose
 */
function getMedicationPricePerDose($medId, $con, $inUSD = false) {
    $priceQuery = $con->prepare("SELECT price_per_dose, custom_price, dollar_dose_price FROM med_prices WHERE med_id = ? ORDER BY update_date DESC LIMIT 1");
    $priceQuery->execute([$medId]);
    $priceData = $priceQuery->fetch(PDO::FETCH_ASSOC);
    
    if ($priceData) {
        if ($inUSD) {
            // Always use dollar_dose_price for USD prices if available
            if (isset($priceData['dollar_dose_price']) && $priceData['dollar_dose_price'] > 0) {
                return $priceData['dollar_dose_price'];
            }
            
            // If dollar_dose_price is not available, convert EGP to USD
            $egpPrice = !empty($priceData['custom_price']) ? $priceData['custom_price'] : $priceData['price_per_dose'];
            require_once 'BankConfig.php';
            BankConfig::initialize();
            return BankConfig::convertToUSD($egpPrice);
        }
        
        // For EGP prices, use custom_price or price_per_dose as before
        return !empty($priceData['custom_price']) ? $priceData['custom_price'] : $priceData['price_per_dose'];
    }
    
    // Fallback to calculating from medlist table if not in med_prices
    $priceQuery = $con->prepare("SELECT price, doses_per_package FROM medlist WHERE id = ?");
    $priceQuery->execute([$medId]);
    $priceData = $priceQuery->fetch(PDO::FETCH_ASSOC);
    
    // Calculate price per dose if data is available
    if ($priceData && isset($priceData['price']) && isset($priceData['doses_per_package']) && $priceData['doses_per_package'] > 0) {
        $pricePerDose = $priceData['price'] / $priceData['doses_per_package'];
        
        if ($inUSD) {
            // Convert to USD if requested
            require_once 'BankConfig.php';
            BankConfig::initialize();
            return BankConfig::convertToUSD($pricePerDose);
        }
        
        return round($pricePerDose, 2); // Round to 2 decimal places
    }
    
    // Default to 0 if no price data is available
    return 0;
}

function output_date_data($date, $con, $counter) {
    if(!isset($_GET['showNoMore'])){
$datenow = date("d M, Y h:i a");
    $formatted_date = date("d M Y", strtotime($date));
    $select = $con->query("SELECT m.*, l.id as med_id FROM medtrack m LEFT JOIN medlist l ON m.medname = l.name WHERE STR_TO_DATE(m.dose_date, '%d %b, %Y') = '$date' ORDER BY m.id DESC");

// Check if there are any mednames data for the current date
$has_mednames_data = $select->rowCount() > 0;

// Calculate the total cost for this day
$dayTotalCost = 0;
if ($has_mednames_data) {
    $costSelect = $con->query("SELECT m.*, l.id as med_id FROM medtrack m LEFT JOIN medlist l ON m.medname = l.name WHERE STR_TO_DATE(m.dose_date, '%d %b, %Y') = '$date'");
    while ($costRow = $costSelect->fetch()) {
        if (isset($costRow['med_id'])) {
            // Get USD price directly using dollar_dose_price
            $pricePerDose = getMedicationPricePerDose($costRow['med_id'], $con, true);
            $dayTotalCost += $pricePerDose;
        }
    }
}

// Apply the 'highlight' class if there's no mednames data, otherwise use 'normal-date'
$date_class = !$has_mednames_data ? 'highlight' : 'normal-date';


        $selectSides = $con->query("SELECT * FROM side_effects WHERE STR_TO_DATE(daytime, '%d %b, %Y') = '$date' order by id desc");

        $all_sides = '';
        $positives = 0;
        $negatives = 0;
        while($fetch = $selectSides->fetch()){
            $keyword = $fetch['keyword'];
            $sides_status = '';
            if($fetch['ongoing'] == 'yes'){
                $sides_status = '<span data-id="' . $fetch['id'] . '" data-keyword="' . $fetch['keyword'] . '"><a href="#" class="buttonLink" id="ongoingOFF"><img src="img/stop.png"></a></span>';

            }

            if($fetch['feelings'] == 'positive'){
                $keyword = '<font color="green">'.$fetch['keyword'].'</font>';
                $positives++;
            }elseif($fetch['feelings'] == 'negative'){
                $keyword = '<font color="red">'.$fetch['keyword'].'</font>';
                $negatives++;
            }
        
            
        
            $all_sides .= $sides_status.' <a href="side_investigation.php?id='.$fetch['id'].'&name='.$fetch['keyword'].'"><b>'.$keyword.'</b></a> | ';
        }

        
        $howyoufeel = '';
        if($date == date('Y-m-d')){
            $howyoufeel = '<input type="text" name="sidesText" id="sidesText" onkeyup="getResults(this.value)" placeholder="How do you feel?">
            <div id="results" class="results"></div>
          
            
            <span name="' . $datenow . '">
     
         
            <div id="feelings" style="display:inline;">
            <input type="radio" id="positive" name="feelings" value="positive" checked>
            <label for="positive"><font color="green">Positive</font></label>
            <input type="radio" id="neutral" name="feelings" value="neutral">
            <label for="neutral"><font color="blue">Neutral</font></label>
            <input type="radio" id="negative" name="feelings" value="negative">
            <label for="negative"><font color="red">Negative</font></label></div>
            <input type="text" name="susText" id="susText" onkeyup="get_sus_Results(this.value)" placeholder="My Sus..." style="width:180px;">
            <div id="sus_results" class="results"></div>
              <button class="button2" id="sidesButton">Submit</button>
            </span>';
        }else{
           
            $input_id = "sidesText" . $counter;
            $results_id = "results" . $counter;
          
         if(isset($_GET['how_did_you_feel'])){
            $howyoufeel = '<input type="text" name="' . $input_id . '" id="' . $input_id . '" onkeyup="getResults2(this.value, ' . $counter . ')" placeholder="How did you feel?"> <div id="' . $results_id . '" class="results"></div>';
            $startTime = strtotime($date); // Convert your date to a timestamp
            $howyoufeel .= '<select name="feeltime1' . $counter . '" id="feeltime1' . $counter . '" class="feeltime1">';
            for ($i = 0; $i < 24; $i++) { // Loop for 24 hours
                $time = date("h:i a", $startTime + $i * 60 * 60);
                $dateOption1 = date("d M, Y", $startTime + $i * 60 * 60); // Use a different variable name for date option 1
                $dateTime = $dateOption1 . ' ' . $time;
                $howyoufeel .= "<option value='$dateTime'>$dateTime</option>";
            }
            $howyoufeel .= '</select> to ';
            $howyoufeel .= '<select name="feeltime2' . $counter . '" id="feeltime2' . $counter . '" class="feeltime2"><option value=""></option>';
            for ($i = 0; $i < 48; $i++) { // Loop for 48 hours
                $time = date("h:i a", $startTime + $i * 60 * 60);
                $dateOption2 = date("d M, Y", $startTime + $i * 60 * 60); // Use a different variable name for date option 2
                $dateTime = $dateOption2 . ' ' . $time;
                $howyoufeel .= "<option value='$dateTime'>$dateTime</option>";
            }
            
            $howyoufeel .= '</select>';

            $howyoufeel .= ' <label for="feelings' . $counter . '">Feelings</label>';
            $howyoufeel .= '<select name="feelings' . $counter . '" id="feelings' . $counter . '">';
            $howyoufeel .= '<option value="positive" class="positive">Positive</option>';
            $howyoufeel .= '<option value="neutral" class="neutral">Neutral</option>';
            $howyoufeel .= '<option value="negative" class="negative">Negative</option>';
            $howyoufeel .= '</select> ';
            
            $howyoufeel .= '<label>Ongoing <input type="checkbox" name="ongoing' . $counter . '" id="ongoing' . $counter . '" class="ongoing"></label> ';
            $howyoufeel .= 'my Sus:<input type="text" name="my_sus' . $counter . '" id="my_sus' . $counter . '" style="width: 130px;"> ';
            
            $howyoufeel .= '<button type="button" class="button2 sidesButtons" data-counter="' . $counter . '">Submit</button>';
            
            
         }
        
        
       

        }

    if ($select->rowCount() > 0) {

        if(empty($all_sides)){
            $all_sides = $all_sides;
        }else{
            $all_sides = 'positives: '.$positives.', negatives: '.$negatives.' / '.$all_sides;
        }


        // Add day cost to the date header
        $dayCostDisplay = $dayTotalCost > 0 ? ' <span style="color:red;font-weight:bold;">-$' . number_format($dayTotalCost, 2) . '</span>' : '';
        
        if(isset($_GET['how_did_you_feel'])) {
            // Use the new mood form template for a more modern look
            $date = $formatted_date;
            $amount = $dayTotalCost > 0 ? '-$' . number_format($dayTotalCost, 2) : '';
            include 'mood-form-template.php';
        } else {
            echo '<table class="table-wrapper"><tr><td class="' . $date_class . '"><b class="dayTitle" style="color:#AC2F20;"><a href="daypage.php?date='.$formatted_date.'" style="text-decoration: none;color: inherit; /* Remove color */">' . $formatted_date . ':</a> </b>' . $dayCostDisplay . '
            '.$howyoufeel.''.$all_sides.'</td></tr></table>';
        }
        while ($fetch = $select->fetch()) {


            $dose_date = $fetch['dose_date'];
            $str = str_replace(',', '', $dose_date);
            $day = date('d M Y', strtotime($str));
            $timeonly = date('h:i a', strtotime($str));
    
            $st1 = str_replace(',', '', $datenow);
            $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));
    
            $dateStarted = $dose_date;
            $st2 = str_replace(',', '', $dateStarted);
            $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));
    
            $timeFirst = strtotime('' . $dateStarted2 . '');
            $timeSecond = strtotime('' . $dateNow2 . '');
            $differenceInSeconds2 = ($timeSecond - $timeFirst);
            
            $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
            $currentDate = new DateTime();
            $difference = $currentDate->diff($startDate);
            $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;
    
            $timespent = '';
            $daystohrs = '';
    
            if ($differenceInSeconds <= 59) {
                $time_spent = '<font color="red" style="font-weight:bold;">'.$differenceInSeconds . ' sec ago</font>';
            } elseif ($differenceInSeconds < 3600) {
                $time_spent = '<font color="red"><b>'.round(($differenceInSeconds / 60), 2) . ' mins ago</b></font>';
            } elseif ($differenceInSeconds < 86400) {
                $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs ago';
            } elseif ($differenceInSeconds <= 2592000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days ago';
                $daystohrs = round($differenceInSeconds / 3600, 2) . ' hrs = ';
            } elseif ($differenceInSeconds >= 2592000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days = ';
                $time_spent .= round($differenceInSeconds / 2592000, 2) . ' month ';
                if ($differenceInSeconds >= 31104000) {
                    $time_spent .= ' =';
                    $time_spent .= round($differenceInSeconds / 31104000, 2) . ' yrs ago';
                }
            }
    
            if (!empty($fetch['details'])) {
        
                if($fetch['medname'] != 'Mast'){
                $details = '';
                }else{
                $details = ', <b>[ ' . $fetch['details'] . ' ]</b>';
                }
                
            } else {
                $details = '';
            }


               $words = $fetch['medname'];
                $firstWord = preg_replace('/\s*\d+\D*$/', '', $words);

           


            // Meds Records in Main Page
          // Get price per dose for this medication
          $pricePerDose = 0;
          if (isset($fetch['med_id'])) {
              // Get price in EGP for display
              $pricePerDoseEGP = getMedicationPricePerDose($fetch['med_id'], $con);
              // Get price in USD using dollar_dose_price
              $pricePerDose = getMedicationPricePerDose($fetch['med_id'], $con, true);
          }
          
          // Format price display - show both EGP and USD
          $priceDisplay = $pricePerDose > 0 ? ' <span style="color:#007bff;font-weight:bold;">($' . number_format($pricePerDose, 2) . ')</span>' : '';
          
          if(isset($_GET['show_delete_buttons'])){
                echo '<div id="med-details">';
                // Make sure we're explicitly outputting the numeric ID as integer
                $recordId = (int)$fetch['id'];
                echo (!empty($fetch['medname']) ? '- <font class="medTitle"><b><a href="possible_sides.php?name=' . urlencode($firstWord) . '" style="text-decoration: none;color: inherit;">' . $fetch['medname'] . '</a></b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ')' . $details . $priceDisplay . ' <button class="deleteRecord btn btn-danger btn-sm" data-id="' . $recordId . '" style="margin-left: 5px; font-size: 10px; padding: 1px 5px;">Delete</button>' .' <br>' : 'N/A');
                echo '</div>';
           
        }else{
                echo '<div id="med-details">';
          echo (!empty($fetch['medname']) ? '- <font class="medTitle"><b><a href="possible_sides.php?name=' . urlencode($firstWord) . '" style="text-decoration: none;color: inherit;">' . $fetch['medname'] . '</a></b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ')' . $details . $priceDisplay .' <br>' : 'N/A');
          echo '</div>';
        }
          }
    } else {

        if(empty($all_sides)){
            $all_sides = $all_sides;
        }else{
            $all_sides = 'positives: '.$positives.', negatives: '.$negatives.' / '.$all_sides;
        }

        // Add day cost to the date header (will be empty for dates with no meds, but still maintain consistent layout)
        $dayCostDisplay = $dayTotalCost > 0 ? ' <span style="color:red;font-weight:bold;">-$' . number_format($dayTotalCost, 2) . '</span>' : '';
        
        echo '<table class="table-wrapper"><tr><td class="' . $date_class . '"><b class="dayTitle" style="color:#1E93C1;"><a href="daypage.php?date='.$formatted_date.'" style="text-decoration: none;color: inherit; /* Remove color */">' . $formatted_date . ':</a> </b>' . $dayCostDisplay . ''.$howyoufeel.''.$all_sides.'<br>';
        echo "- <i style='color:#757A6F;'><b>N/A</b></i><br></td></tr></table>";
    }
    echo '<br>';
    }else{
        

 $select = $con->query("SELECT * FROM medlist WHERE nomore='yesFirst' ORDER BY STR_TO_DATE(lastdose, '%d %b, %Y %h:%i %p') DESC");
while ($fetch = $select->fetch()) {


            $dose_date = $fetch['lastdose'];
            $str = str_replace(',', '', $dose_date);
            $day = date('d M Y', strtotime($str));
            $timeonly = date('h:i a', strtotime($str));
    
            $datenow = date("d M, Y h:i a");
            $st1 = str_replace(',', '', $datenow);
            $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));
    
            $dateStarted = $dose_date;
            $st2 = str_replace(',', '', $dateStarted);
            $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));
    
            $timeFirst = strtotime('' . $dateStarted2 . '');
            $timeSecond = strtotime('' . $dateNow2 . '');
            $differenceInSeconds2 = ($timeSecond - $timeFirst);
            
            $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
            $currentDate = new DateTime();
            $difference = $currentDate->diff($startDate);
            $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;
    
            $timespent = '';
            $daystohrs = '';
    
            if ($differenceInSeconds <= 59) {
                $time_spent = $differenceInSeconds . ' sec';
            } elseif ($differenceInSeconds < 3600) {
                $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
            } elseif ($differenceInSeconds < 86400) {
                $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds <= 2592000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
                $daystohrs = round($differenceInSeconds / 3600, 2) . ' hrs = ';
            } elseif ($differenceInSeconds >= 2592000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days = ';
                $time_spent .= round($differenceInSeconds / 2592000, 2) . ' month';
                if ($differenceInSeconds >= 31104000) {
                    $time_spent .= ' =';
                    $time_spent .= round($differenceInSeconds / 31104000, 2) . ' yrs';
                }
            }
    
            if (!empty($fetch['details'])) {
                $details = ', <b>[ ' . $fetch['details'] . ' ]</b>';
            } else {
                $details = '';
            }
    


            // Get price per dose for this medication if available
            $pricePerDose = 0;
            if (isset($fetch['id'])) {
                // Get price in USD using dollar_dose_price
                $pricePerDose = getMedicationPricePerDose($fetch['id'], $con, true);
            }
            
            // Format price display
            $priceDisplay = $pricePerDose > 0 ? ' <span style="color:#007bff;font-weight:bold;">($' . number_format($pricePerDose, 2) . ')</span>' : '';
            
            echo '- <font class="medTitle"><b>' . $fetch['name'] . '</b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ' ago)' . $details . $priceDisplay . ' <br>';
        }
    }

}

function output_med_functions_scripts_and_html() {
    echo <<<HTML
    <div id="confirmationModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <p id="confirmationMessage"></p>
        <input type="text" id="MysusText" placeholder="Sus?...">
        <button id="confirmButton">Confirm</button>
      </div>
    </div>
HTML;

    echo <<<'JS'
<script>

$(document).ready(function(){
  $("#sidesText").focus(function(){
    $(this).attr("placeholder", "");
  });
  $("#sidesText").blur(function(){
    if($(this).val() == ""){
      $(this).attr("placeholder", "How do you feel?");
    }
  });
});

$(document).on('click', "#sidesButton",function(){
        var daytime = $(this).parents("span").attr("name");
        var sidesText = $("input[type='text'][name='sidesText']").val();
        var susText = $("input[type='text'][name='susText']").val();
        var selectedFeeling = $("input[name='feelings']:checked").val(); // Get the value of the selected radio option


       
            $.ajax({
               url: 'sides.php',
               type: 'GET',
               data: {daytime: daytime, sidesText: sidesText, susText: susText, selectedFeeling: selectedFeeling},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        

    });

   


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
                        document.getElementById("sidesText").value = this.innerHTML;
                        document.getElementById("results").style.display = "none";
                    }
                    resultsDiv.appendChild(resultDiv);
                }
            }
        };
        xmlhttp.open("GET", "search.php?q=" + value, true);
        xmlhttp.send();
    }
}




function get_sus_Results(value) {
    if (value.length == 0) {
        document.getElementById("sus_results").style.display = "none";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var results = JSON.parse(this.responseText);
                var resultsDiv = document.getElementById("sus_results");
                resultsDiv.innerHTML = "";
                if (results.length > 0) {
                    resultsDiv.style.display = "block";
                    for (var i = 0; i < results.length; i++) {
                        var resultDiv = document.createElement("div");
                        resultDiv.dataset.mySus = results[i].my_sus;
                        resultDiv.dataset.keyword = results[i].keyword;
                        resultDiv.innerHTML = results[i].my_sus;
                        resultDiv.onclick = function() {
                            document.getElementById("susText").value = this.dataset.mySus;
                            document.getElementById("sus_results").style.display = "none";
                        };
                        resultsDiv.appendChild(resultDiv);
                    }
                } else {
                    resultsDiv.style.display = "none";
                }
            }
        };
        xmlhttp.open("GET", "search_mySus_main.php?q=" + encodeURIComponent(value), true);
        xmlhttp.send();
    }
}






$(document).ready(function() {
    $(".submitButton").click(function() {
        var \$form = $(this).closest('.myForm');
        var feeltime1 = \$form.find(".feeltime1").val();
        var feeltime2 = \$form.find(".feeltime2").val();
        var sidesText2 = \$form.find("input[name='sidesText2']").val();
        var ongoing = \$form.find('input[name="ongoing"]').prop('checked');

        if (confirm('You felt "' + sidesText2 + '" from "' + feeltime1 + '" to "' + feeltime2 + '" ?')) {
            $.ajax({
                url: 'sides2.php', // Specify the URL of the PHP script to handle the form submission
                type: 'POST', // Use POST method for form submission
                data: { feeltime1: feeltime1, feeltime2: feeltime2, sidesText2: sidesText2, ongoing: ongoing },
                success: function(data) {
                    // Handle success response
                    alert('Form submitted successfully');
                    window.location = 'index.php?how_did_you_feel';
                },
                error: function() {
                    // Handle error
                    alert('Something went wrong');
                }
            });
        } else {
            // User clicked "Cancel" in the confirmation dialog
            // You can add any additional action here if needed
        }
    });
});





$(document).on('click', "#ongoingOFF", function() {
    var \$span = $(this).closest("span");
    var id = \$span.data("id");
    var keyword = \$span.data("keyword");

    // Set the confirmation message
    var confirmationMessage = 'Are you sure "' + keyword + '" side has ended?';
    $('#confirmationMessage').text(confirmationMessage);

    // Display the modal
    $('#confirmationModal').css('display', 'block');

    // Close the modal if the user clicks on (x) button
    $('.close').click(function() {
        $('#confirmationModal').css('display', 'none');
    });

    // Handle confirmation button click
    $('#confirmButton').click(function() {
        // Retrieve the value of the textbox
        var susText = $('#MysusText').val();

        // Close the modal
        $('#confirmationModal').css('display', 'none');

        // Perform AJAX request with id, keyword, and susText
        $.ajax({
            url: 'ongoingOff.php',
            type: 'GET',
            data: { id: id, keyword: keyword, susText: susText },
            error: function() {
                alert('Something is wrong');
            },
            success: function(data) {
                window.location = 'index.php';
            }
        });
    });
});


$(document).ready(function() {
    // Apply color to the selected option initially
    applyColorToSelectedOption();

    // Apply color to the selected option when it changes
    $('#feelings').change(function() {
        applyColorToSelectedOption();
    });

    function applyColorToSelectedOption() {
        var selectedValue = $('#feelings').val();
        $('#feelings').css('color', getColorForOption(selectedValue));
    }

    function getColorForOption(value) {
        // Return color based on the option value
        switch (value) {
            case 'positive':
                return 'green';
            case 'neutral':
                return 'grey';
            case 'negative':
                return 'red';
            default:
                return ''; // Default color
        }
    }
});


function getResults2(value, counter) {
    if (value.length == 0) {
        document.getElementById("results" + counter).style.display = "none";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var results = JSON.parse(this.responseText);
                var resultsDiv = document.getElementById("results" + counter);
                resultsDiv.innerHTML = "";
                resultsDiv.style.display = "block";
                for (var i = 0; i < results.length; i++) {
                    var resultDiv = document.createElement("div");
                    resultDiv.innerHTML = results[i];
                    resultDiv.onclick = function() {
                        document.getElementById("sidesText" + counter).value = this.innerHTML;
                        document.getElementById("results" + counter).style.display = "none";
                    }
                    resultsDiv.appendChild(resultDiv);
                }
            }
        };
        xmlhttp.open("GET", "search.php?q=" + value, true);
        xmlhttp.send();
    }
}

// Add event listener to the document body
document.body.addEventListener('click', function(event) {
    var resultsDiv = document.getElementById("results");
    // Check if results element exists and the click event target is not within the results area
    if (resultsDiv && !resultsDiv.contains(event.target)) {
        // If click is outside of results, hide the results
        resultsDiv.style.display = "none";
    }
});

// Add event listener to the document body
document.body.addEventListener('click', function(event) {
    var resultsDiv = document.getElementById("sus_results");
    // Check if sus_results element exists and the click event target is not within the results area
    if (resultsDiv && !resultsDiv.contains(event.target)) {
        // If click is outside of results, hide the results
        resultsDiv.style.display = "none";
    }
});

$(document).on('click', '.sidesButtons', function() {
    var counter = $(this).data('counter');
    var sidesText = $('#sidesText' + counter).val();
    var feeltime1 = $('#feeltime1' + counter).val(); // Assuming you have IDs like 'feeltime1' + counter
    var feeltime2 = $('#feeltime2' + counter).val(); // Assuming you have IDs like 'feeltime2' + counter
    var ongoing = $('#ongoing' + counter).prop('checked');
    var selectedFeeling = $('#feelings' + counter).val();
    var my_sus_value = $('#my_sus' + counter).val();



    if (confirm('You felt "' + sidesText + '" back then?')) {
        $.ajax({
            url: 'didfeelsides.php',
            type: 'GET',
            data: {
                sidesText: sidesText,
                selectedFeeling: selectedFeeling,
                feeltime1:feeltime1,
                feeltime2:feeltime2,
                ongoing: ongoing,
                my_sus_value:my_sus_value
            },
            error: function() {
                alert('Something went wrong');
            },
            success: function(data) {
                window.location = window.location;
            }
        });
    }
});


 // JavaScript to handle AJAX for updating the rating
function updateMedRating(medId, newRating) {
    $.ajax({
        url: '/update_med_rating', // Your backend endpoint
        method: 'POST',
        data: {
            med_id: medId,
            rating: newRating
        },
        success: function(response) {
            alert('Rating updated successfully!');
        },
        error: function(xhr, status, error) {
            alert('Failed to update the rating: ' + error);
        }
    });
}

// Example usage on button click or other event
$('#updateRatingButton').on('click', function() {
    const medId = $('#medIdInput').val(); // Get med_id from input
    const newRating = $('#ratingInput').val(); // Get rating from input
    updateMedRating(medId, newRating);
});

</script>
JS;
}


