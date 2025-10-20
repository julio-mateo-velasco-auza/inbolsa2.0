# tools/packager/PostBuild-Rebase.ps1
param(
  [string]$DistPath = "dist",
  # ⬇ Ajusta a la subcarpeta real en iPage (ej: "/inbolsaNeo/")
  [string]$BaseSubfolder = "/inbolsaNeo/",
  # ⬇ Dejamos CORS tal cual (NO reemplazamos URLs de API)
  [string[]]$ApiFind = @(),
  [string]$ApiReplaceWith = "/inbolsa-api/api",
  [switch]$EnableApiReplace = $false,
  # Páginas donde inyectar el bridge
  [string[]]$InjectBridgeIn = @(
    "index.html",
    "productos/index.html",
    "qr/index.html"
  )
)

if (!(Test-Path $DistPath)) {
  Write-Error "No existe $DistPath. Genera la build primero."
  exit 1
}

$BASE = ($BaseSubfolder.TrimEnd('/') + "/")

# 1) Copiar bridge-privado.js al dist
$bridgeSrc = "tools/packager/bridge-privado.js"
if (!(Test-Path $bridgeSrc)) { Write-Error "Falta $bridgeSrc"; exit 1 }
$bridgeDst = Join-Path $DistPath "bridge-privado.js"
Copy-Item $bridgeSrc $bridgeDst -Force

# 2) Reescribir archivos (rutas base y, opcionalmente, URLs API)
Get-ChildItem -Path $DistPath -Recurse -File | Where-Object {
  $_.Extension -in ".html", ".css", ".js", ".mjs"
} | ForEach-Object {
  $content = Get-Content $_.FullName -Raw

  if ($EnableApiReplace.IsPresent -and $ApiFind.Count -gt 0) {
    foreach ($needle in $ApiFind) {
      if ($content -like "*$needle*") {
        $content = $content -replace [Regex]::Escape($needle), [Regex]::Escape($ApiReplaceWith)
      }
    }
  }

  # Prepend BASE a rutas absolutas "/..." (sin tocar //, http(s), data:, mailto:, tel:)
  $pattern = '(["''(=\s])\/(?!\/|(?:(?:https?:)?\/\/)|(?:data:)|(?:mailto:)|(?:tel:))'
  $content = [regex]::Replace($content, $pattern, "`$1$BASE")

  # Inyectar <base href="..."> si no existe
  if ($_.Extension -eq ".html" -and $content -notmatch "<base ") {
    $content = $content -replace "<head(.*?)>", "<head`$1>`r`n  <base href=""$BASE"" />"
  }

  Set-Content -Path $_.FullName -Value $content -Encoding UTF8
}

# 3) Inyectar script bridge en páginas clave
foreach ($rel in $InjectBridgeIn) {
  $file = Join-Path $DistPath $rel
  if (Test-Path $file) {
    $html = Get-Content $file -Raw
    if ($html -notmatch "bridge-privado.js") {
      $tag = "  <script src=""$($BaseSubfolder.TrimEnd('/'))/bridge-privado.js""></script>`n</head>"
      $html = $html -replace "</head>", $tag
      Set-Content -Path $file -Value $html -Encoding UTF8
    }
  }
}

Write-Host "✅ Post-build listo para inbolsa-landing (BASE=$BASE)."
