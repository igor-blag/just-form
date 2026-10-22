$ErrorActionPreference = 'Stop'

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$mainFile = Join-Path $repoRoot 'just-form.php'
$mainSource = Get-Content -LiteralPath $mainFile -Raw

if ($mainSource -notmatch '(?m)^\s*\*\s*Version:\s*(?<version>[0-9]+(?:\.[0-9]+){1,3})\s*$') {
    throw 'Could not read a numeric plugin version from just-form.php.'
}

$version = $Matches.version
$distDir = Join-Path $repoRoot 'dist'
$archivePath = Join-Path $distDir "just-form-$version.zip"
$files = @(
    'just-form.php',
    'block.json',
    'admin/class-admin.php',
    'assets/block.js',
    'assets/form.css',
    'assets/form.js',
    'includes/class-block.php',
    'includes/class-handler.php',
    'includes/class-smtp.php',
    'includes/class-spam-protection.php',
    'includes/class-updater.php'
)

foreach ($relativePath in $files) {
    if (-not (Test-Path -LiteralPath (Join-Path $repoRoot $relativePath) -PathType Leaf)) {
        throw "Required release file is missing: $relativePath"
    }
}

New-Item -ItemType Directory -Path $distDir -Force | Out-Null
if (Test-Path -LiteralPath $archivePath) {
    Remove-Item -LiteralPath $archivePath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::Open(
    $archivePath,
    [System.IO.Compression.ZipArchiveMode]::Create
)

try {
    foreach ($relativePath in $files) {
        $sourcePath = Join-Path $repoRoot $relativePath
        $entryName = 'just-form/' + ($relativePath -replace '\\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive,
            $sourcePath,
            $entryName,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null
    }
}
finally {
    $archive.Dispose()
}

Write-Output "Created $archivePath"
