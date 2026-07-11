$files = Get-ChildItem -Path "d:\00 Php Project\Dashboard\views", "d:\00 Php Project\Dashboard\public", "d:\00 Php Project\Dashboard\src" -Filter "*.php" -Recurse

$replacements = @{
    '\[#7c3aed\]' = 'primary'
    '\[#6d28d9\]' = 'green-700'
    '\[#ddd6fe\]' = 'green-200'
    '\[#f5f3ff\]' = 'green-50'
    '\[#ede9fe\]' = 'green-100'
    '\[#f3e8ff\]' = 'green-50'
    '\[#e9d5ff\]' = 'green-200'
    '\[#4f46e5\]' = 'primary'
}

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $originalContent = $content
    
    foreach ($pattern in $replacements.Keys) {
        $content = [System.Text.RegularExpressions.Regex]::Replace($content, $pattern, $replacements[$pattern])
    }
    
    if ($content -cne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        Write-Output "Updated $($file.FullName)"
    }
}
