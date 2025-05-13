$ftpHost = "66.23.203.210"
$ftpUser = "mcgkxyz"
$ftpPass = "MSre58dW3GeU1"
$port = 21

# Test various paths to find the correct one
$pathsToTest = @(
    "davidmondazz.online/meds2",
    "davidmondazz.online/public_html/meds2", 
    "public_html/meds2", 
    "public_html/davidmondazz.online/meds2",
    "davidmondazz.online"
)

foreach ($path in $pathsToTest) {
    try {
        Write-Host "`n[Testing path: $path]" -ForegroundColor Yellow
        
        # Create FTP request
        $ftp = [System.Net.FtpWebRequest]::Create("ftp://${ftpHost}:${port}/$path/")
        $ftp.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $ftp.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $ftp.UseBinary = $true
        $ftp.KeepAlive = $false
        $ftp.Timeout = 5000  # 5 seconds timeout
        
        $response = $ftp.GetResponse()
        $stream = $response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        
        Write-Host "SUCCESS: Path '$path' exists. Contents:" -ForegroundColor Green
        $listing = $reader.ReadToEnd()
        Write-Output $listing
        
        $reader.Close()
        $stream.Close()
        $response.Close()
        
        Write-Host "This is the path you should use in your config: '$path'" -ForegroundColor Cyan
    }
    catch {
        Write-Host "FAILED: Path '$path' - Error: $($_.Exception.Message)" -ForegroundColor Red
    }
}
