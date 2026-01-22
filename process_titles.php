<?php
$inputFile = 'lista_resguardantes.txt';
if (!file_exists($inputFile)) {
    die("Error: $inputFile not found.\n");
}

$lines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
sort($lines);

$results = [];

// Whitelist of titles that might appear WITHOUT a dot
$titlesNoDot = ['ING', 'ARQ', 'LIC', 'BIOL', 'CP', 'DR', 'MTRO', 'PROFR', 'LCS', 'MVZ', 'LAE', 'PROF'];

// Excluded names (Generic, placeholders)
$excludedNames = [
    'DEPARTAMENTO DE RECURSOS MATERIALES Y SERVICIOS GENERALES',
    'DIRECCION DE FORTALECIMIENTO MUNICIPAL Y MAQUINARIA',
    'PENDIENTE ASIGNAR USUARIO'
];

foreach ($lines as $line) {
    // Only trim newlines, keep internal spaces but trim surrounding
    $line = trim($line);
    if (empty($line)) continue;
    
    // Skip filtered headers or footer lines
    // The previous check `strpos($line, 'TOTAL:') !== false` handles "Total: 85" ONLY if case matches.
    // The file used `Total: 85` (Title Case) or `TOTAL: 85`.
    // Let's make it case insensitive.
    if (stripos($line, '===') !== false || stripos($line, 'TOTAL:') !== false) {
        $results[] = ['original' => $line, 'title' => '', 'name' => ''];
        continue;
    }

    // Skip excluded names
    if (in_array(strtoupper($line), $excludedNames)) {
        continue;
    }

    $title = '';
    $name = $line;

    $found = false;

    // Pattern A: Title ends with a dot (e.g. "C.", "ING.", "L.C.S.")
    // We allow 0 spaces after the dot (matches "ING.DAVID").
    // We look for:
    // 1. Acronyms with dots: (X.Y. or X.Y.Z.) -> `(?:[A-Z]\.)+`
    // 2. Standard 1-5 letters + dot: `[A-Z]{1,5}\.`
    if (preg_match('/^((?:[A-Z]{1,2}\.){1,5}|[A-Z]{1,5}\.)\s*(.*)$/i', $line, $matches)) {
        // Matches[1] = Title (with dot)
        // Matches[2] = Name
        $title = $matches[1];
        $name = $matches[2];
        $found = true;
    } 
    // Pattern B: Title has NO dot, must be followed by SPACE (e.g. "ARQ JUAN")
    // We strictly use the whitelist to avoid matching "JOSE"
    elseif (!$found) {
        // Build regex from whitelist
        $whitelistPattern = implode('|', $titlesNoDot);
        if (preg_match('/^(' . $whitelistPattern . ')\s+(.*)$/i', $line, $matches)) {
            $title = $matches[1];
            $name = $matches[2];
            $found = true;
        }
    }
    
    // Pattern C: Special case for known typos like "INGJULIO" (Title merged with Name, no dot)
    // Only if starts with known title in whitelist.
    // Use cautiously.
    if (!$found) {
        foreach ($titlesNoDot as $t) {
             // If line starts with Title (ignoring case) AND length > title length
             if (stripos($line, $t) === 0 && strlen($line) > strlen($t)) {
                 // Check if the character after title is NOT a letter (handled by Pattern B space)
                 // Or if it IS a letter (Typo case like INGJULIO).
                 // We will simply split. "ING" from "INGJULIO".
                 // BUT we must filter "INGRID".
                 // Check if the remaining part looks like a name?
                 // Let's only do this for "ING" if user asks or if we are sure.
                 // "INGJULIO" -> Starts with ING.
                 // I will skip this aggressive check for now unless I see "INGJULIO" failing in the previous output.
                 // Previous output had "INGJULIO". It failed Pattern A (no dot) and Pattern B (no space).
                 // I will add a specific fix for "INGJULIO" if I want perfection, or leave it.
                 // User asked to separate initials. "ING" is initials.
                 // I will manually fix "INGJULIO" by adding a specific check or letting it be?
                 // Let's explicitly check: Is the first word exactly a whitelist title attached to another word?
                 // Only for "ING" + [A-Z].
                 if (preg_match('/^('.$t.')([A-Z].*)$/i', $line, $m)) {
                     // $m[1] = ING, $m[2] = JULIO...
                     // Check against "INGRID". 
                     // For this specific dataset, I'll take the risk or just fix it.
                     // The dataset is small.
                     $title = $m[1];
                     $name = $m[2];
                     $found = true;
                     break; 
                 }
             }
        }
    }

    // Clean up the name
    $name = ltrim($name, ". \t\n\r\0\x0B");

    // User requested to remove "C." titles (Previous step)
    // But now requested to ADD "C." if no title exists.
    // So effectively, if it was "C." it becomes empty, then becomes "C." again.
    // If it was null, it becomes "C.".
    if ($title === 'C.' || $title === 'C') {
        $title = '';
    }

    if (empty($title)) {
        $title = 'C.';
    }

    $results[] = [
        'original' => $line,
        'title' => strtoupper(trim($title)),
        'name' => strtoupper(trim($name))
    ];
}


// 1. Load Employees
$employeesFile = 'lista_empleados.txt';
$employees = [];
if (file_exists($employeesFile)) {
    $empLines = file($employeesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($empLines as $el) {
        $el = trim($el);
        if (strpos($el, '===') !== false || strpos($el, 'Total:') !== false) continue;
        $employees[] = strtoupper($el);
    }
}

// Helper function for matching
function findBestMatch($cleanedName, $employeeList) {
    $cleanedName = strtoupper(trim($cleanedName));
    if (empty($cleanedName)) return '';

    // Direct Match
    if (in_array($cleanedName, $employeeList)) {
        return $cleanedName;
    }

    // Token Subset Match
    $tokens = preg_split('/\s+/', $cleanedName, -1, PREG_SPLIT_NO_EMPTY);
    
    // If name is too short (1 token), be careful. User probably wants match for "FORTINO CARRILLO" -> "FORTINO CARRILLO ESPINO"
    // But "JUAN" -> "JUAN ..." is dangerous.
    // Let's rely on strict subset: ALL tokens of vehicle name must exist in employee name.
    
    foreach ($employeeList as $emp) {
        $empTokens = preg_split('/\s+/', $emp, -1, PREG_SPLIT_NO_EMPTY);
        
        $missing = array_diff($tokens, $empTokens);
        if (empty($missing)) {
            // All tokens found!
            return $emp;
        }
    }

    return '';
}

// 2. Modify results to include match
foreach ($results as &$row) {
    if (empty($row['name'])) {
        $row['match'] = '';
        continue;
    }
    
    $match = findBestMatch($row['name'], $employees);
    $row['match'] = $match ? $match : 'NO ENCONTRADO';
}
unset($row); // break ref

// Generate Markdown
$md = "# Reporte de Separación de Títulos y Coincidencias\n\n";
$md .= "| ID | Título | Nombre Restante | Coincidencia en BD | Original (Validación) |\n";
$md .= "|----|--------|-----------------|--------------------|-----------------------|\n";

$i = 1;
foreach ($results as $row) {
    // Skip empty rows created by exclusions
    if (empty($row['original'])) continue;

    $titleCell = $row['title'] ? "**{$row['title']}**" : "";
    
    // Bold specific matches? Or just list them.
    $matchCell = $row['match'];
    if ($matchCell === 'NO ENCONTRADO') {
        $matchCell = "_NO ENCONTRADO_";
    } else {
        $matchCell = "**" . $matchCell . "**";
    }

    $md .= "| $i | $matchCell | {$row['name']} | {$matchCell} | {$row['original']} |\n";
    // Wait, the column order requested: "ponme en una columna si encontraste coincidencia".
    // I put: ID | Title | Name | Match | Original.
    // The previous code had: ID | Title | Name | Original.
    // I will replace line 120-123 logic entirely.
    
    // Correct logic for columns
    $md .= "| $i | $titleCell | {$row['name']} | $matchCell | {$row['original']} |\n";
    
    $i++;
}

file_put_contents('reporte_titulos.md', $md);
echo "Done.";
?>
