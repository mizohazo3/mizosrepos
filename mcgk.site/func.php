<?php

if (isset($_GET['id'])) {
    $showId = $_GET['id'];
}

function TimerNow($startDate)
{
    $dateNow = date('d M, Y h:i:s a');
    $st1 = str_replace(',', '', $dateNow);
    $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));

    $dateStarted = $startDate;
    $st2 = str_replace(',', '', $dateStarted);
    $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

    $timeFirst = strtotime('' . $dateStarted2 . '');
    $timeSecond = strtotime('' . $dateNow2 . '');
    $differenceInSeconds = ($timeSecond - $timeFirst);

    return $differenceInSeconds;
}

function vitDAmount($TimeSpent)
{

    $amount = ($TimeSpent / 60) * 500;

    return $amount;

}

function SunLevels($TimeSpent)
{

    $uiCount = ($TimeSpent / 60) * 500;

    if ($uiCount < 20000) {
        $lvl = 0;
    } elseif ($uiCount <= 40000) {
        $lvl = 1;
    } elseif ($uiCount <= 60000) {
        $lvl = 2;
    } elseif ($uiCount <= 100000) {
        $lvl = 3;
    } elseif ($uiCount <= 140000) {
        $lvl = 4;
    } elseif ($uiCount <= 200000) {
        $lvl = 5;
    } elseif ($uiCount <= 240000) {
        $lvl = 6;
    } elseif ($uiCount <= 280000) {
        $lvl = 7;
    } elseif ($uiCount <= 300000) {
        $lvl = 8;
    } elseif ($uiCount <= 340000) {
        $lvl = 9;
    } elseif ($uiCount <= 380000) {
        $lvl = 10;
    } elseif ($uiCount <= 400000) {
        $lvl = 11;
    } elseif ($uiCount <= 450000) {
        $lvl = 12;
    } elseif ($uiCount <= 500000) {
        $lvl = 13;
    } elseif ($uiCount <= 560000) {
        $lvl = 14;
    }

    return $lvl;

}

function ConvertSeconds($timeinSeconds)
{
    $time_spent = '';
    if (!empty($timeinSeconds)) {
        if ($timeinSeconds <= 59) {
            $time_spent = $timeinSeconds . ' sec';
        } elseif ($timeinSeconds < 3600) {
            $time_spent = round(($timeinSeconds / 60), 2) . ' mins';
        } elseif ($timeinSeconds >= 3600) {
            $time_spent = round($timeinSeconds / 3600, 2) . ' hrs';
        }
    } else {
        $time_spent = '';
    }
    return $time_spent;
}

function convertTime($getTime)
{
    $TimeConv = '';
    if ($getTime <= 59) {
        $TimeConv = '(' . $getTime . ' sec)';
        if (empty($getTime)) {
            $TimeConv = '';
        }
    } elseif ($getTime < 3600) {
        $TimeConv = '(' . round(($getTime / 60), 2) . ' mins)';
    } elseif ($getTime >= 3600) {
        $TimeConv = '(' . round(($getTime / 3600), 2) . ' hrs)';
    }
    return $TimeConv;
}

function detailTime($total_seconds)
{
    $seconds = $total_seconds;
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@" . (int) $seconds);
    $diff = $dtF->diff($dtT);

    $total_hours = $diff->days * 24 + $diff->h;
    $total_minutes = $diff->i;

    if ($total_seconds == '') {
        $total = '';
    } else {
        $total = '<span style="font-size:15px; color: #2e7d32; background-color: #e8f5e9; border: 1px solid #81c784; border-radius: 1em; padding: 0.2em 0.5em; display: inline-block;"><b>' . $total_hours . ' hrs</b>, ' . $total_minutes . ' ms</span>';
    }
    return $total;
}

function rand_color()
{
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

function levels($theExp, $next = null)
{

    $maxlevel = 50;

    $arr = array();
    array_unshift($arr, "");
    unset($arr[0]);

    $exp = 0;
    for ($i = 1; $i <= $maxlevel; $i++) {

        $exp += $i * 1000 * 1.7;

        $arr[] = $exp;
        $arr[$i] = $exp;

    }

    foreach ($arr as $level => $exp) {

        if ($exp > $theExp) {
            $currentLevel = $level - 1;

            $currentLVL = "LVL $currentLevel";

            if (!isset($arr[$currentLevel])) {
                $baseLevelExp = '';
                $progress = '';
                $remain = '';
            } else {
                $baseLevelExp = $exp - $arr[$currentLevel];
                $remain = ($exp - $theExp);
                $progress = round(100 - ($remain * 100 / $baseLevelExp), 1);
            }

            $progress = ' &nbsp;<div class="w3-light-grey w3-round-xlarge" style="width:20%;display:inline-block;">
                    <div class="w3-container w3-blue w3-round-xlarge" style="width:' . $progress . '%;"><b style="color:#1d1f20;">' . $progress . '%</b></div>
                </div>';

            $finalPrint = $currentLVL . ' - ' . ($exp - $theExp) . ' xp to reach level ' . $level . ' ' . $progress;

            return array($currentLevel, $finalPrint);

        }
    }

    return 'MAX LEVEL';
}

function diffinTime($startDate, $endDate)
{
    $st1 = str_replace(',', '', $endDate);
    $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));

    $st2 = str_replace(',', '', $startDate);
    $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

    $timeFirst = strtotime('' . $dateStarted2 . '');
    $timeSecond = strtotime('' . $dateNow2 . '');
    $differenceInSeconds = ($timeSecond - $timeFirst);
    $differenceInMinutes = round($differenceInSeconds / 60, 2);
    $differenceInHours = round($differenceInMinutes / 60, 2);
    $differenceInDays = round($differenceInHours / 24, 2);

    return array($differenceInSeconds, $differenceInMinutes, $differenceInHours, $differenceInDays);
}

function secondsToTime($seconds)
{
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes');
}

function makeClickableLinks($s)
{
    $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
    return preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $s);

}



function TimeLeft($startDate, $endDate){

    $st1 = str_replace(',', '', $endDate);
    $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));

    $st2 = str_replace(',', '', $startDate);
    $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

    $timeFirst = strtotime('' . $dateStarted2 . '');
    $timeSecond = strtotime('' . $dateNow2 . '');
    $differenceInSeconds = ($timeSecond - $timeFirst);

    if ($differenceInSeconds <= 59) {
        $time_spent = $differenceInSeconds . ' sec';
    } elseif ($differenceInSeconds < 3600) {
        $time_spent = round(($differenceInSeconds / 60), 1) . ' mins';
    } elseif ($differenceInSeconds < 86400) {
        $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
    } elseif ($differenceInSeconds <= 31104000) {
        $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
    } elseif ($differenceInSeconds >= 31104000) {
        $time_spent = round($differenceInSeconds / 31104000, 2) . ' yrs';
    }
    return $time_spent;
}