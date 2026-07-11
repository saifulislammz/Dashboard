$files = Get-ChildItem -Path "d:\00 Php Project\Dashboard\views", "d:\00 Php Project\Dashboard\public", "d:\00 Php Project\Dashboard\src" -Filter "*.php" -Recurse

$replacements = @{
    # Blues/Teals to Green
    '\b(text|bg|border|ring|shadow)-blue-(\d+|[a-z]+)\b' = '$1-green-$2'
    '\b(text|bg|border|ring|shadow)-indigo-(\d+|[a-z]+)\b' = '$1-green-$2'
    '\b(text|bg|border|ring|shadow)-cyan-(\d+|[a-z]+)\b' = '$1-green-$2'
    '\b(text|bg|border|ring|shadow)-sky-(\d+|[a-z]+)\b' = '$1-gray-$2'
    '\b(text|bg|border|ring|shadow)-teal-(\d+|[a-z]+)\b' = '$1-green-$2'
    '\b(text|bg|border|ring|shadow)-emerald-(\d+|[a-z]+)\b' = '$1-green-$2'
    
    # Oranges/Ambers to Yellow
    '\b(text|bg|border|ring|shadow)-orange-(\d+|[a-z]+)\b' = '$1-yellow-$2'
    '\b(text|bg|border|ring|shadow)-amber-(\d+|[a-z]+)\b' = '$1-yellow-$2'
    
    # Purples/Pinks to Red
    '\b(text|bg|border|ring|shadow)-purple-(\d+|[a-z]+)\b' = '$1-red-$2'
    '\b(text|bg|border|ring|shadow)-pink-(\d+|[a-z]+)\b' = '$1-red-$2'
    '\b(text|bg|border|ring|shadow)-rose-(\d+|[a-z]+)\b' = '$1-red-$2'
    '\b(text|bg|border|ring|shadow)-fuchsia-(\d+|[a-z]+)\b' = '$1-red-$2'
    
    # Other grays to standard gray
    '\b(text|bg|border|ring|shadow)-slate-(\d+|[a-z]+)\b' = '$1-gray-$2'
    '\b(text|bg|border|ring|shadow)-zinc-(\d+|[a-z]+)\b' = '$1-gray-$2'
    '\b(text|bg|border|ring|shadow)-stone-(\d+|[a-z]+)\b' = '$1-gray-$2'
    '\b(text|bg|border|ring|shadow)-neutral-(\d+|[a-z]+)\b' = '$1-gray-$2'
    
    # Custom config colors
    '\biconBgTeal\b' = 'iconBgGreen'
    '\biconBgBlue\b' = 'iconBgGreen'
    '\biconBgOrange\b' = 'iconBgYellow'
    '\biconBgPurple\b' = 'iconBgRed'
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
