Param(
  [string]$BaseSubfolder = "/inbolsaNeo/",
  [string]$DistPath = "dist"
)

$ErrorActionPreference = "Stop"

# Directorio del script, robusto (sirve en PS 5/7 y evita $PSScriptRoot vacío)
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot  = Join-Path $ScriptDir ".."
$PostBuild = Join-Path $ScriptDir "packager\PostBuild-Rebase.ps1"

Write-Host "→ 1) Forzar prerender=true en .astro con 'false'…"
Get-ChildItem (Join-Path $RepoRoot "src") -Recurse -Include *.astro,*.md,*.mdx -File | ForEach-Object {
  $c = Get-Content $_.FullName -Raw
  $n = $c -replace 'export\s+const\s+prerender\s*=\s*false\s*;', 'export const prerender = true;'
  if ($n -ne $c) {
    Set-Content $_.FullName $n -Encoding UTF8
    Write-Host "   → cambiado: $($_.FullName)"
  }
}

Write-Host "→ 2) Compilar Astro (estático)…"
Push-Location $RepoRoot
npm run build
$code = $LASTEXITCODE
Pop-Location
if ($code -ne 0) { throw "❌ El build falló." }

# Asegurar bridge-privado.js por si no existe
$bridgeUp = Join-Path $ScriptDir "packager\bridge-privado.js"
if (!(Test-Path $bridgeUp)) {
  Write-Host "→ Creando tools/packager/bridge-privado.js (mínimo)…"
@'
(() => {
  // Bridge mínimo para iPage/XAMPP (no-SSR).
  console.debug("[bridge-privado] cargado");
})();
'@ | Set-Content $bridgeUp -Encoding UTF8
}

Write-Host "→ 3) Post-build (rebase de rutas + inyección bridge)…"
powershell -ExecutionPolicy Bypass -File $PostBuild -DistPath $DistPath -BaseSubfolder $BaseSubfolder

Write-Host "✅ Listo: sube 'dist/' a $BaseSubfolder en iPage/XAMPP y coloca el .htaccess allí."
