$ErrorActionPreference = 'Stop'

$phpCommand = Get-Command php -ErrorAction Stop
$phpPath = $phpCommand.Source
$cleanupScript = Join-Path $PSScriptRoot 'cleanup_old_reports.php'
$taskName = 'Servitory Report Retention Cleanup'

if (-not (Test-Path -LiteralPath $cleanupScript -PathType Leaf)) {
    throw "Cleanup script not found: $cleanupScript"
}

$action = New-ScheduledTaskAction -Execute $phpPath -Argument ('"{0}"' -f $cleanupScript)
$trigger = New-ScheduledTaskTrigger -Daily -At '3:00AM'
$principal = New-ScheduledTaskPrincipal `
    -UserId ([Security.Principal.WindowsIdentity]::GetCurrent().Name) `
    -LogonType Interactive `
    -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 10)

Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Description 'Deletes Servitory reports older than 30 days; user accounts are preserved.' `
    -Force | Out-Null

Write-Output "Registered '$taskName' to run daily at 3:00 AM for the current Windows user."
