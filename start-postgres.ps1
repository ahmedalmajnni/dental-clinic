# Starts the local PostgreSQL server for the Dental Clinic app.
# Run this once each time you reboot, before starting the Laravel app.
#
# Paths are derived from the current user's profile, so this works on any dev
# machine that keeps the portable PostgreSQL in the default place. Override with
# the -Bin / -Data parameters if yours lives somewhere else.
param(
    [string]$Bin  = "$env:USERPROFILE\pgsql\bin",
    [string]$Data = "$env:USERPROFILE\pgdata",
    [string]$Log  = "$env:USERPROFILE\pg-server.log"
)

if (-not (Test-Path "$Bin\pg_ctl.exe")) {
    Write-Error "pg_ctl.exe not found in '$Bin'. Pass -Bin <path to pgsql\bin>."
    exit 1
}
if (-not (Test-Path $Data)) {
    Write-Error "Data directory '$Data' not found. Pass -Data <path to the cluster>."
    exit 1
}

& "$Bin\pg_ctl.exe" -D $Data -l $Log -o "-p 5432" start
Start-Sleep -Seconds 2
& "$Bin\pg_isready.exe" -h 127.0.0.1 -p 5432
