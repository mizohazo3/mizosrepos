<?php

date_default_timezone_set("Africa/Cairo");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trash Calculator</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }
        #container {
            text-align: center;
        }
        #timer {
            font-size: 5em;
            margin-bottom: 20px;
        }
        button, input {
            font-size: 1.2em;
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="container">
        <div id="timer">00:00:00.00</div>
        <button id="startBtn">Start</button>
        <button id="pauseBtn">Pause</button>
        <button id="stopBtn">Stop</button>
        <br>
        <input type="number" id="itemCount" placeholder="Enter number of items">
        <div id="result"></div>
    </div>

    <script src="stopwatch.js"></script>
</body>
</html>
