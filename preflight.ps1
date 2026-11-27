# StockPulse pre-flight (Windows PowerShell). READ-ONLY: changes nothing.
# Run from the project root:  powershell -ExecutionPolicy Bypass -File .\preflight.ps1
function Check($label, $cmd) {
  Write-Host "-- $label"
  try { $o = Invoke-Expression "$cmd 2>&1" | Select-Object -First 3; if ($o) { $o } else { "no output" } }
  catch { "NOT INSTALLED or not on PATH" }
}
Write-Host "== Workspace =="; Get-Location; Get-ChildItem -Force | Select-Object -First 40 | Format-Table Name, Mode, Length -AutoSize
Write-Host "`n== Git =="
if (Get-Command git -ErrorAction SilentlyContinue) {
  git rev-parse --is-inside-work-tree 2>$null
  if ($LASTEXITCODE -eq 0) { git branch --show-current; git status --short | Select-Object -First 30; git log --oneline -5 }
  else { "Not a Git repository" }
} else { "Git not installed" }
Write-Host "`n== Versions =="
Check "php"        "php -v"
Check "composer"   "composer --version"
Check "node"       "node -v"
Check "npm"        "npm -v"
Check "docker"     "docker --version"
Check "compose"    "docker compose version"
Check "psql"       "psql --version"
Check "java"       "java -version"
Write-Host "`n== PHP extensions needed =="
if (Get-Command php -ErrorAction SilentlyContinue) {
  $mods = (php -m) -join "`n"
  foreach ($e in "pdo_pgsql","pgsql","mbstring","intl","bcmath","gd","redis","openssl","zip") {
    if ($mods -match "(?im)^$e$") { "ok      $e" } else { "MISSING $e" }
  }
} else { "PHP not installed, skipped" }
Write-Host "`n== WSL =="
Check "wsl distros" "wsl -l -v"
Write-Host "`n== Existing project files =="
foreach ($f in "docs","composer.json","package.json","artisan") { "{0,-14} {1}" -f $f, (Test-Path $f) }
