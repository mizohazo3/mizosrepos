<?php
date_default_timezone_set("Africa/Cairo");
$dateNow = date('d M, Y h:i:s a');

function showgamesUpdates($link)
{

    if (empty($link)) {
        return "";
    } else {
        // Use file_get_contents() to retrieve the contents of the link as a string
        $text = file_get_contents($link);

// Use preg_match() to find the date in the string after the "Release Date" text
        if (preg_match('/Release Date: (\d{4}-\d{2}-\d{2})/', $text, $matches)) {
            // Convert the date string to a DateTime object
            $date = new DateTime($matches[1]);

            // Calculate the difference between the date and today as a DateInterval object
            $interval = $date->diff(new DateTime());

            // Get the number of days from the DateInterval object
            $days_left = $interval->days;

            // Format the date as "1 Mar, 2023" using the DateTime::format() method
            $formatted_date = $date->format('j M, Y');

            // $formatted_date now contains the date in the format "1 Mar, 2023"
            // $days_left now contains the number of days left until today
            return "$formatted_date: <span style='color: grey'>since $days_left days</span>";

        } else {
            // If the "Release Date" text is not found, output nothing
            return "";
        }
    }

}
