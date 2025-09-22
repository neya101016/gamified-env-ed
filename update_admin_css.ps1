#!/bin/powershell

# This script adds the admin.css stylesheet to all admin PHP files

$adminPages = Get-ChildItem -Path "D:\Software\Server\htdocs\Green\admin\*.php" -Recurse -File

foreach ($file in $adminPages) {
    $content = Get-Content -Path $file.FullName -Raw
    
    # Only update files that have HTML structure and don't already include admin.css
    if ($content -match '<link.*bootstrap\.min\.css' -and $content -match '<link.*styles\.css' -and $content -notmatch '<link.*admin\.css') {
        Write-Host "Updating $($file.Name)..."
        
        $newContent = $content -replace '(<link.*styles\.css">\s*)', '$1<link rel="stylesheet" href="../assets/css/admin.css">' + "`n    "
        Set-Content -Path $file.FullName -Value $newContent
    }
}

Write-Host "All admin pages updated!"