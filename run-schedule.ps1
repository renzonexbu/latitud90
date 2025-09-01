# Script PowerShell para ejecutar Laravel Schedule permanentemente
# Ejecutar como: powershell -ExecutionPolicy Bypass -File run-schedule.ps1

param(
    [int]$SleepSeconds = 60
)

# Configuración
$ProjectPath = "C:\laragon\www\latitud90"
$PhpPath = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
$LogFile = "$ProjectPath\storage\logs\schedule-forever.log"

# Función para escribir logs
function Write-Log {
    param($Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage
    Add-Content -Path $LogFile -Value $logMessage
}

# Cambiar al directorio del proyecto
Set-Location $ProjectPath

Write-Log "🔄 Iniciando ejecución permanente del Laravel Schedule..."
Write-Log "📁 Directorio: $ProjectPath"
Write-Log "⏰ Intervalo: $SleepSeconds segundos"
Write-Log "📝 Log: $LogFile"
Write-Log "🛑 Presiona Ctrl+C para detener"
Write-Log ""

$executionCount = 0

try {
    while ($true) {
        $executionCount++
        $now = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        
        Write-Log "🔄 Ejecución #$executionCount - $now"
        
        # Ejecutar el schedule
        $result = & $PhpPath artisan schedule:run 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-Log "✅ Ejecución #$executionCount completada exitosamente"
        } else {
            Write-Log "⚠️ Ejecución #$executionCount completada con advertencias"
            Write-Log "Output: $result"
        }
        
        Write-Log "⏳ Esperando $SleepSeconds segundos..."
        Write-Log ""
        
        # Esperar antes de la siguiente ejecución
        Start-Sleep -Seconds $SleepSeconds
    }
} catch {
    Write-Log "❌ Error crítico: $($_.Exception.Message)"
    Write-Log "Stack trace: $($_.ScriptStackTrace)"
} finally {
    Write-Log "🛑 Proceso detenido"
}
