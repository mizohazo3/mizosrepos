<?php

function getReasonDropdownList($con)
{
    $selectReason = $con->query("SELECT reason, COUNT(*) as count
           FROM transactions
           WHERE reason IN (
               SELECT reason
               FROM transactions where type='take'
               GROUP BY reason
               HAVING COUNT(*) > 1
           )
           GROUP BY reason
           ORDER BY count DESC;");

    $output = "";
    if ($selectReason->rowCount() > 0) {
        $output .= "<select name='reasonDroplist' style='width:90%;'>";
        $output .= "<option value=''></option>";
        while ($row = $selectReason->fetch()) {
            $output .= "<option value='" . $row["reason"] . "'>" . $row["reason"] . " </option>";
        }
        $output .= "</select>";
    } else {
        $output .= "No data found.";
    }
    return $output;
}
