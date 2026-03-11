param(
    [Parameter(Position = 0)]
    [string]$Message,

    [string]$Branch = "main",

    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

function Invoke-Git {
    param(
        [Parameter(Mandatory = $true)]
        [string[]]$Arguments
    )

    & git @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "git $($Arguments -join ' ') gagal."
    }
}

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    throw "Git tidak ditemukan di sistem."
}

$statusLines = git status --short

if ($LASTEXITCODE -ne 0) {
    throw "Gagal membaca git status."
}

if (-not $statusLines) {
    Write-Host "Tidak ada perubahan untuk di-commit."
    exit 0
}

if ([string]::IsNullOrWhiteSpace($Message)) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $Message = "Update $timestamp"
}

if ($DryRun) {
    Write-Host "Dry run aktif."
    Write-Host "Branch  : $Branch"
    Write-Host "Message : $Message"
    Write-Host "Perubahan:"
    $statusLines | ForEach-Object { Write-Host "  $_" }
    exit 0
}

Invoke-Git -Arguments @("add", "-A")
Invoke-Git -Arguments @("commit", "-m", $Message)
Invoke-Git -Arguments @("push", "origin", $Branch)

Write-Host "Selesai push ke origin/$Branch dengan message: $Message"
