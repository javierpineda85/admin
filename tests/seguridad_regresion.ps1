$ErrorActionPreference = 'Stop'
$OutputEncoding = [System.Text.UTF8Encoding]::new()
[Console]::OutputEncoding = $OutputEncoding
$raizProyecto = Split-Path -Parent $PSScriptRoot
$xdebugLog = Join-Path $env:TEMP 'campus-xdebug-seguridad.log'
Push-Location $raizProyecto

try {
    $archivosPhp = & rg --files -g '*.php'
    foreach ($archivoPhp in $archivosPhp) {
        & php -d "xdebug.log=$xdebugLog" -l $archivoPhp | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "Falló php -l para $archivoPhp" }
    }
    Write-Output "OK: sintaxis PHP valida en $($archivosPhp.Count) archivos"

    $pruebas = @(
        'tests/seguridad_fase1.php',
        'tests/seguridad_auth.php',
        'tests/seguridad_fase3.php',
        'tests/seguridad_fase4.php',
        'tests/seguridad_fase5.php'
    )
    foreach ($prueba in $pruebas) {
        & php -d "xdebug.log=$xdebugLog" $prueba
        if ($LASTEXITCODE -ne 0) { throw "Falló $prueba" }
    }

    $apache = Get-ChildItem 'C:\wamp64\bin\apache' -Filter httpd.exe -Recurse -ErrorAction SilentlyContinue |
        Sort-Object FullName -Descending |
        Select-Object -First 1
    if ($apache) {
        $confApache = Join-Path (Split-Path -Parent (Split-Path -Parent $apache.FullName)) 'conf\httpd.conf'
        & $apache.FullName -t -f $confApache
        if ($LASTEXITCODE -ne 0) { throw 'La configuración de Apache no es válida.' }
    }

    & git diff --check
    if ($LASTEXITCODE -ne 0) { throw 'git diff --check encontró errores.' }
    Write-Output 'OK: regresion automatizada de seguridad completa'
} finally {
    Pop-Location
}
