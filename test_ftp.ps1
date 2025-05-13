$ftpHost = "66.23.203.210"
$ftpUser = "mcgkxyz"
$ftpPass = "MSre58dW3GeU1"
$port = 21

# Create FTP request
$ftp = [System.Net.FtpWebRequest]::Create("ftp://${ftpHost}:${port}/")
$ftp.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
$ftp.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
$ftp.UseBinary = $true
$ftp.KeepAlive = $false
$ftp.Timeout = 10000  # 10 seconds timeout

try {
    Write-Host "Attempting to connect to FTP server at $ftpHost..." -ForegroundColor Yellow
    $response = $ftp.GetResponse()
    $stream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    
    Write-Host "Connection successful! Directory listing:" -ForegroundColor Green
    $listing = $reader.ReadToEnd()
    Write-Output $listing
    
    $reader.Close()
    $stream.Close()
    $response.Close()
}
catch {
    Write-Host "Error connecting to FTP server:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}
