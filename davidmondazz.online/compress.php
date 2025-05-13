<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Compression Progress</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        #progress-container { width: 80%; margin: auto; border: 1px solid #ccc; padding: 10px; border-radius: 5px; }
        #progress-bar { height: 25px; width: 0%; background-color: #4CAF50; text-align: center; color: white; line-height: 25px; }
        #status { margin-top: 10px; font-size: 18px; }
    </style>
</head>
<body>

    <h2>Image Compression Progress</h2>
    <button onclick="startCompression()">Start Compression</button>

    <div id="progress-container">
        <div id="progress-bar">0%</div>
    </div>
    <p id="status">Waiting to start...</p>

    <script>
        function startCompression() {
            document.getElementById("progress-bar").style.width = "0%";
            document.getElementById("progress-bar").innerText = "0%";
            document.getElementById("status").innerText = "Processing images...";

            let xhr = new XMLHttpRequest();
            xhr.open("GET", "compress_images.php", true);
            xhr.send();

            let progressInterval = setInterval(() => {
                fetch("progress.txt?cache=" + new Date().getTime()) // Get progress updates
                    .then(response => response.text())
                    .then(progress => {
                        let percentage = parseInt(progress.trim());
                        if (!isNaN(percentage)) {
                            document.getElementById("progress-bar").style.width = percentage + "%";
                            document.getElementById("progress-bar").innerText = percentage + "%";

                            if (percentage >= 100) {
                                clearInterval(progressInterval);
                                document.getElementById("status").innerText = "✅ Compression Completed!";
                            }
                        }
                    });
            }, 500); // Check progress every 500ms
        }
    </script>

</body>
</html>
