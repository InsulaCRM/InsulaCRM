param(
    [string]$Version,
    [string]$NotesFile,
    [switch]$Publish,
    [switch]$SkipComposerInstall,
    [switch]$SkipBuild,
    [switch]$SkipReleaseComposerInstall,
    [switch]$SkipZip,
    [switch]$SkipGitTag,
    [switch]$SkipGithubRelease
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
$versionFile = Join-Path $projectRoot "VERSION"
$releaseRoot = Join-Path $projectRoot "_release"
$releaseDir = Join-Path $releaseRoot "insulacrm"
$artifactsDir = Join-Path $releaseRoot "artifacts"

$excludePatterns = @(
    ".claude",
    ".env",
    ".git",
    ".phpunit.result.cache",
    "_release",
    "node_modules",
    "phpunit.xml",
    "public\\storage",
    "storage\\app\\backups",
    "storage\\app\\updates",
    "storage\\framework\\cache\\data",
    "storage\\framework\\sessions",
    "storage\\framework\\views",
    "storage\\installed.lock",
    "storage\\logs\\laravel.log",
    "test-output.txt",
    "tests"
)

function Run-Step {
    param(
        [string]$Title,
        [scriptblock]$Action
    )

    Write-Host ""
    Write-Host "==> $Title"
    & $Action
}

function Should-Exclude {
    param([string]$RelativePath)

    if ($RelativePath -like '*.md') {
        return $true
    }

    foreach ($pattern in $excludePatterns) {
        if ($RelativePath -eq $pattern -or $RelativePath.StartsWith($pattern + "\\")) {
            return $true
        }
    }

    return $false
}

function Copy-ReleaseItem {
    param(
        [string]$SourcePath,
        [string]$DestinationPath,
        [string]$RelativePath
    )

    $item = Get-Item -LiteralPath $SourcePath -Force

    if (Should-Exclude -RelativePath $RelativePath) {
        return
    }

    if (($item.Attributes -band [IO.FileAttributes]::ReparsePoint) -ne 0) {
        return
    }

    if ($item.PSIsContainer) {
        New-Item -ItemType Directory -Path $DestinationPath -Force | Out-Null

        Get-ChildItem -LiteralPath $SourcePath -Force | ForEach-Object {
            $childRelativePath = if ([string]::IsNullOrEmpty($RelativePath)) {
                $_.Name
            } else {
                "$RelativePath\\$($_.Name)"
            }

            Copy-ReleaseItem `
                -SourcePath $_.FullName `
                -DestinationPath (Join-Path $DestinationPath $_.Name) `
                -RelativePath $childRelativePath
        }

        return
    }

    Copy-Item -LiteralPath $SourcePath -Destination $DestinationPath -Force
}

function Normalize-Version {
    param([string]$Value)

    $trimmed = $Value.Trim()
    if ($trimmed.StartsWith("v")) {
        return $trimmed.Substring(1)
    }

    return $trimmed
}

function Resolve-CommandPath {
    param([string]$Name)

    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($command) {
        return $command.Source
    }

    if ($Name -eq "gh") {
        $fallback = "C:\Program Files\GitHub CLI\gh.exe"
        if (Test-Path $fallback) {
            return $fallback
        }
    }

    throw "Required command '$Name' was not found in PATH."
}

function New-ReleaseZip {
    param(
        [string]$SourceDirectory,
        [string]$DestinationZip,
        [string]$RootFolderName = "insulacrm"
    )

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    if (Test-Path $DestinationZip) {
        Remove-Item $DestinationZip -Force
    }

    $sourceRoot = (Resolve-Path $SourceDirectory).Path
    $rootFolder = $RootFolderName.TrimEnd('/')
    $zip = [System.IO.Compression.ZipFile]::Open($DestinationZip, [System.IO.Compression.ZipArchiveMode]::Create)

    try {
        [void]$zip.CreateEntry($rootFolder + '/')

        $allFiles = Get-ChildItem -LiteralPath $sourceRoot -Force -Recurse -File
        $allDirectories = Get-ChildItem -LiteralPath $sourceRoot -Force -Recurse -Directory
        $nonEmptyDirectories = @{}

        foreach ($file in $allFiles) {
            $relativeDirectory = Split-Path ($file.FullName.Substring($sourceRoot.Length).TrimStart('\')) -Parent
            while (-not [string]::IsNullOrWhiteSpace($relativeDirectory)) {
                $nonEmptyDirectories[$relativeDirectory] = $true
                $relativeDirectory = Split-Path $relativeDirectory -Parent
            }
        }

        foreach ($directory in $allDirectories) {
            $relativePath = $directory.FullName.Substring($sourceRoot.Length).TrimStart('\')
            if ([string]::IsNullOrWhiteSpace($relativePath)) {
                continue
            }

            if ($nonEmptyDirectories.ContainsKey($relativePath)) {
                continue
            }

            $entryName = $rootFolder + '/' + ($relativePath -replace '\\', '/').TrimEnd('/') + '/'
            [void]$zip.CreateEntry($entryName)
        }

        foreach ($file in $allFiles) {
            $relativePath = $file.FullName.Substring($sourceRoot.Length).TrimStart('\')
            if ([string]::IsNullOrWhiteSpace($relativePath)) {
                continue
            }

            $entryName = $rootFolder + '/' + ($relativePath -replace '\\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $zip,
                $file.FullName,
                $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal
            ) | Out-Null
        }
    } finally {
        $zip.Dispose()
    }
}

# ── Read and validate version ────────────────────────────────────────

if (-not $Version) {
    if (-not (Test-Path $versionFile)) {
        throw "VERSION file not found at $versionFile"
    }

    $Version = (Get-Content $versionFile -Raw).Trim()
}

$Version = Normalize-Version -Value $Version

if ([string]::IsNullOrWhiteSpace($Version)) {
    throw "Version must not be empty."
}

if (-not ($Version -match '^\d+\.\d+\.\d+([\-+][0-9A-Za-z\.-]+)?$')) {
    throw "Version '$Version' is not a valid semantic version string."
}

$tagName = "v$Version"
$zipName = "insulacrm-$Version.zip"
$zipPath = Join-Path $artifactsDir $zipName
$resolvedNotesFile = $null

if ($Publish -and [string]::IsNullOrWhiteSpace($NotesFile)) {
    throw "Publishing a release requires -NotesFile with release notes."
}

if ($NotesFile) {
    $resolvedNotesFile = Resolve-Path $NotesFile
}

# ── Pre-flight checks ────────────────────────────────────────────────

Run-Step -Title "Pre-flight validation" -Action {
    # Verify CHANGELOG.md mentions this version
    $changelogPath = Join-Path $projectRoot "CHANGELOG.md"
    if (Test-Path $changelogPath) {
        $changelogContent = Get-Content $changelogPath -Raw
        if ($changelogContent -notmatch [regex]::Escape($Version)) {
            throw "CHANGELOG.md does not mention version $Version. Update the changelog before releasing."
        }
    } else {
        Write-Warning "CHANGELOG.md not found - skipping changelog check."
    }

    # Verify release notes file exists when publishing
    if ($Publish -and $resolvedNotesFile) {
        if (-not (Test-Path $resolvedNotesFile.Path)) {
            throw "Release notes file not found: $($resolvedNotesFile.Path)"
        }

        $notesContent = (Get-Content $resolvedNotesFile.Path -Raw).Trim()
        if ([string]::IsNullOrWhiteSpace($notesContent)) {
            throw "Release notes file is empty: $($resolvedNotesFile.Path)"
        }
    }

    # Verify gh CLI is available when publishing
    if ($Publish -and -not $SkipGithubRelease) {
        $null = Resolve-CommandPath -Name "gh"
        Write-Host "  GitHub CLI: available"
    }

    Write-Host "  Version: $Version"
    Write-Host "  Tag: $tagName"
    Write-Host "  Checks passed."
}

# ── Composer install ──────────────────────────────────────────────────

if (-not $SkipComposerInstall) {
    Run-Step -Title "Installing root Composer dependencies" -Action {
        & composer install
        if ($LASTEXITCODE -ne 0) {
            throw "Root composer install failed."
        }
    }
}

# ── Generate docs and auto-commit if changed ─────────────────

Run-Step -Title "Generating documentation" -Action {
    & php (Join-Path $projectRoot "scripts\generate-docs.php")
    if ($LASTEXITCODE -ne 0) {
        throw "Documentation generation failed."
    }
}

if ($Publish) {
    Run-Step -Title "Auto-committing generated documentation" -Action {
        Push-Location $projectRoot
        try {
            $docChanges = git status --short -- "docs/guide/"
            if ($docChanges) {
                git add "docs/guide/"
                if ($LASTEXITCODE -ne 0) {
                    throw "Failed to stage generated docs."
                }

                git commit -m "Update generated documentation for $tagName"
                if ($LASTEXITCODE -ne 0) {
                    throw "Failed to commit generated docs."
                }

                Write-Host "  Committed generated doc changes."
            } else {
                Write-Host "  No documentation changes to commit."
            }
        } finally {
            Pop-Location
        }
    }
}

# ── Build staged release ──────────────────────────────────────────────

if (-not $SkipBuild) {
    Run-Step -Title "Building staged release" -Action {
        if (Test-Path $releaseDir) {
            Remove-Item $releaseDir -Recurse -Force
        }

        if (-not (Test-Path $releaseRoot)) {
            New-Item -ItemType Directory -Path $releaseRoot -Force | Out-Null
        }

        New-Item -ItemType Directory -Path $releaseDir | Out-Null

        Get-ChildItem -Path $projectRoot -Force | ForEach-Object {
            $name = $_.Name
            if ($name -eq "_release") {
                return
            }

            Copy-ReleaseItem `
                -SourcePath $_.FullName `
                -DestinationPath (Join-Path $releaseDir $name) `
                -RelativePath $name
        }

        $cleanupPaths = @(
            "phpunit.xml",
            "storage\app\backups",
            "storage\app\updates",
            "storage\framework\cache\data",
            "storage\framework\sessions",
            "storage\framework\views",
            "storage\installed.lock",
            "storage\logs\laravel.log",
            "test-output.txt",
            "tests"
        )

        foreach ($relativePath in $cleanupPaths) {
            $target = Join-Path $releaseDir $relativePath
            if (Test-Path $target) {
                Remove-Item $target -Recurse -Force
            }
        }

        $emptyDirectories = @(
            "storage\app\backups",
            "storage\app\updates",
            "storage\framework\cache\data",
            "storage\framework\sessions",
            "storage\framework\views"
        )

        foreach ($relativePath in $emptyDirectories) {
            $target = Join-Path $releaseDir $relativePath
            if (-not (Test-Path $target)) {
                New-Item -ItemType Directory -Path $target -Force | Out-Null
            }
        }
    }
}

# ── Production composer in staged release ─────────────────────────────

if (-not $SkipReleaseComposerInstall) {
    Run-Step -Title "Installing production Composer dependencies in staged release" -Action {
        Push-Location $releaseDir
        try {
            & composer install --no-dev --optimize-autoloader
            if ($LASTEXITCODE -ne 0) {
                throw "Release composer install failed."
            }
        } finally {
            Pop-Location
        }
    }
}

# ── Create ZIP ────────────────────────────────────────────────────────

if (-not $SkipZip) {
    Run-Step -Title "Creating release ZIP" -Action {
        if (-not (Test-Path $artifactsDir)) {
            New-Item -ItemType Directory -Path $artifactsDir -Force | Out-Null
        }

        New-ReleaseZip -SourceDirectory $releaseDir -DestinationZip $zipPath
    }

    # Verify ZIP integrity
    Run-Step -Title "Verifying release ZIP" -Action {
        if (-not (Test-Path $zipPath)) {
            throw "ZIP file was not created at $zipPath"
        }

        $zipInfo = Get-Item $zipPath
        $sizeMB = [math]::Round($zipInfo.Length / 1MB, 2)

        if ($zipInfo.Length -lt 1MB) {
            throw "ZIP file is suspiciously small ($sizeMB MB). Expected at least 1 MB."
        }

        # Count entries in the ZIP
        Add-Type -AssemblyName System.IO.Compression.FileSystem
        $verifyZip = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
        try {
            $entryCount = $verifyZip.Entries.Count
            $fileCount = ($verifyZip.Entries | Where-Object { -not $_.FullName.EndsWith('/') }).Count
        } finally {
            $verifyZip.Dispose()
        }

        if ($fileCount -lt 50) {
            throw "ZIP contains only $fileCount files. Expected at least 50. Possible build failure."
        }

        # Check for files that must NOT be in the package
        # Exact-match files and directory prefixes (trailing /)
        $forbiddenExact = @('.env', 'phpunit.xml')
        $forbiddenDirs = @('.git/', 'tests/')
        $verifyZip2 = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
        try {
            foreach ($entry in $verifyZip2.Entries) {
                $relative = ($entry.FullName -replace '^insulacrm/', '')
                foreach ($exact in $forbiddenExact) {
                    if ($relative -eq $exact) {
                        throw "ZIP contains forbidden file: $($entry.FullName)"
                    }
                }
                foreach ($dir in $forbiddenDirs) {
                    if ($relative -eq $dir -or $relative.StartsWith($dir)) {
                        throw "ZIP contains forbidden path: $($entry.FullName)"
                    }
                }
            }
        } finally {
            $verifyZip2.Dispose()
        }

        # Check for files that MUST be in the package
        $requiredFiles = @(
            'insulacrm/artisan',
            'insulacrm/composer.json',
            'insulacrm/public/index.php',
            'insulacrm/vendor/autoload.php',
            'insulacrm/VERSION'
        )

        $verifyZip3 = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
        try {
            $entryNames = $verifyZip3.Entries | ForEach-Object { $_.FullName }
            foreach ($required in $requiredFiles) {
                if ($required -notin $entryNames) {
                    throw "ZIP is missing required file: $required"
                }
            }

            # Verify VERSION inside ZIP matches expected version
            $versionEntry = $verifyZip3.GetEntry('insulacrm/VERSION')
            if ($versionEntry) {
                $reader = [System.IO.StreamReader]::new($versionEntry.Open())
                try {
                    $zipVersion = $reader.ReadToEnd().Trim()
                } finally {
                    $reader.Dispose()
                }

                if ($zipVersion -ne $Version) {
                    throw "VERSION inside ZIP is '$zipVersion' but expected '$Version'."
                }
            }
        } finally {
            $verifyZip3.Dispose()
        }

        Write-Host "  ZIP: $zipName ($sizeMB MB, $fileCount files)"
        Write-Host "  No forbidden files found."
        Write-Host "  All required files present."
        Write-Host "  VERSION inside ZIP matches: $Version"
    }
}

# ── Publish to GitHub ─────────────────────────────────────────────────

if ($Publish) {
    Run-Step -Title "Verifying git working tree" -Action {
        $status = git status --short
        if ($LASTEXITCODE -ne 0) {
            throw "Failed to read git status."
        }

        $nonLocalChanges = @(
            $status | Where-Object {
                $_ -and
                ($_ -notmatch '^\?\? storage/installed\.lock$')
            }
        )

        if ($nonLocalChanges.Count -gt 0) {
            Write-Host "  Dirty files:"
            $nonLocalChanges | ForEach-Object { Write-Host "    $_" }
            throw "Working tree is not clean. Commit or stash changes before publishing a release."
        }
    }

    if (-not $SkipGitTag) {
        Run-Step -Title "Creating and pushing git tag $tagName" -Action {
            $tagExists = git tag --list $tagName
            if ($LASTEXITCODE -ne 0) {
                throw "Failed to inspect git tags."
            }

            if ($tagExists) {
                Write-Host "  Tag $tagName already exists locally."
            } else {
                git tag $tagName
                if ($LASTEXITCODE -ne 0) {
                    throw "Failed to create git tag $tagName."
                }

                Write-Host "  Created local tag $tagName."
            }

            # Always push the tag to remote - this is what gh release create needs
            git push origin $tagName 2>&1 | ForEach-Object { Write-Host "  $_" }
            if ($LASTEXITCODE -ne 0) {
                throw "Failed to push tag $tagName to origin. Check remote permissions."
            }

            Write-Host "  Tag $tagName pushed to origin."
        }
    }

    if (-not $SkipGithubRelease) {
        Run-Step -Title "Creating GitHub release $tagName" -Action {
            $gh = Resolve-CommandPath -Name "gh"

            # Verify the ZIP exists before attempting upload
            if (-not (Test-Path $zipPath)) {
                throw "Release ZIP not found at $zipPath. Run without -SkipZip first."
            }

            $releaseView = try { & $gh release view $tagName --json tagName 2>$null } catch { $null }
            if ($LASTEXITCODE -eq 0 -and $releaseView) {
                Write-Host "  GitHub release $tagName already exists. Updating notes and uploading asset."
                & $gh release edit $tagName --title "InsulaCRM $Version" --notes-file $resolvedNotesFile.Path
                if ($LASTEXITCODE -ne 0) {
                    throw "Failed to update GitHub release notes for $tagName."
                }

                & $gh release upload $tagName $zipPath --clobber
                if ($LASTEXITCODE -ne 0) {
                    throw "Failed to upload ZIP asset to existing GitHub release."
                }
            } else {
                & $gh release create $tagName $zipPath --title "InsulaCRM $Version" --notes-file $resolvedNotesFile.Path
                if ($LASTEXITCODE -ne 0) {
                    throw "Failed to create GitHub release $tagName."
                }
            }
        }
    }
}

# ── Post-build dev dependency check ───────────────────────────────────

$hasDevDeps = Test-Path (Join-Path $releaseDir "vendor\\phpunit")
if ($hasDevDeps -and $Publish) {
    throw "RELEASE BLOCKED: Staged package contains dev Composer dependencies (phpunit found in vendor/). This happens when -SkipReleaseComposerInstall is used with -Publish. Re-run without -SkipReleaseComposerInstall."
} elseif ($hasDevDeps) {
    Write-Warning "The staged package contains dev Composer dependencies. Run 'composer install --no-dev --optimize-autoloader' inside $releaseDir before publishing."
}

# ── Summary ───────────────────────────────────────────────────────────

Write-Host ""
Write-Host "==> Release complete"
Write-Host "  Version:  $Version"
Write-Host "  Package:  $releaseDir"
if (-not $SkipZip) {
    Write-Host "  ZIP:      $zipPath"
}
if ($Publish) {
    Write-Host "  Tag:      $tagName"
    Write-Host "  Release:  https://github.com/InsulaCRM/InsulaCRM/releases/tag/$tagName"
}
