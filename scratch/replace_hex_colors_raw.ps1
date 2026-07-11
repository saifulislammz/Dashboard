$files = Get-ChildItem -Path "d:\00 Php Project\Dashboard\views", "d:\00 Php Project\Dashboard\public", "d:\00 Php Project\Dashboard\src" -Filter "*.php" -Recurse

$replacements = @{
    '#7c3aed' = 'var(--color-primary-green)'
    '#6d28d9' = 'var(--color-primary-green)'
    '#ddd6fe' = 'var(--color-primary-green)'
    '#f5f3ff' = 'var(--color-primary-green)'
    '#ede9fe' = 'var(--color-primary-green)'
    '#f3e8ff' = 'var(--color-primary-green)'
    '#e9d5ff' = 'var(--color-primary-green)'
    '#4f46e5' = 'var(--color-primary-green)'
}

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $originalContent = $content
    
    foreach ($pattern in $replacements.Keys) {
        $content = [System.Text.RegularExpressions.Regex]::Replace($content, $pattern, $replacements[$pattern], [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    }
    
    if ($content -cne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        Write-Output "Updated $($file.FullName)"
    }
}
