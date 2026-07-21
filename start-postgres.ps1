# Starts the local PostgreSQL server for the Dental Clinic app.
# Run this once each time you reboot, before starting the Laravel app.
$bin  = "C:\Users\ASUS I7\pgsql\bin"
$data = "C:\Users\ASUS I7\pgdata"
$log  = "C:\Users\ASUS I7\pg-server.log"

& "$bin\pg_ctl.exe" -D $data -l $log -o "-p 5432" start
Start-Sleep -Seconds 2
& "$bin\pg_isready.exe" -h 127.0.0.1 -p 5432
