$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = "C:\Users\a\OneDrive\Desktop\MY CRAT WEB\CyberLumin"
$watcher.IncludeSubdirectories = $true
$watcher.EnableRaisingEvents = $true

$action = {
    $path = $Event.SourceEventArgs.FullPath
    $changeType = $Event.SourceEventArgs.ChangeType
    $time = Get-Date -Format "HH:mm:ss"
    Write-Host "[$time] $changeType : $path"
}

Register-ObjectEvent $watcher "Created" -Action $action | Out-Null
Register-ObjectEvent $watcher "Changed" -Action $action | Out-Null
Register-ObjectEvent $watcher "Deleted" -Action $action | Out-Null
Register-ObjectEvent $watcher "Renamed" -Action $action | Out-Null

Write-Host "Watching: C:\Users\a\OneDrive\Desktop\MY CRAT WEB\CyberLumin"
Write-Host "Press Ctrl+C to stop.`n"

while ($true) {
    Start-Sleep -Seconds 1
}
