<?php
// ver_reporte_titulos.php
// Script to generate an HTML view of the custodian analysis

$inputFile = 'lista_resguardantes.txt';
if (!file_exists($inputFile)) {
    die("Error: $inputFile not found.\n");
}

$lines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
sort($lines);

// Configuration
$titlesNoDot = ['ING', 'ARQ', 'LIC', 'BIOL', 'CP', 'DR', 'MTRO', 'PROFR', 'LCS', 'MVZ', 'LAE', 'PROF'];
$excludedNames = [
    'DEPARTAMENTO DE RECURSOS MATERIALES Y SERVICIOS GENERALES',
    'DIRECCION DE FORTALECIMIENTO MUNICIPAL Y MAQUINARIA',
    'PENDIENTE ASIGNAR USUARIO'
];

$results = [];

// --- TITLE EXTRACTION LOGIC (Copied from process_titles.php) ---
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    // Skip filters
    if (stripos($line, '===') !== false || stripos($line, 'TOTAL:') !== false) {
        // Skip entirely from visual reporting? Or keep as raw?
        // User asked to exclude them.
        continue;
    }

    if (in_array(strtoupper($line), $excludedNames)) {
        continue;
    }

    $title = '';
    $name = $line;
    $found = false;

    // Pattern A: Title ends with a dot
    if (preg_match('/^((?:[A-Z]{1,2}\.){1,5}|[A-Z]{1,5}\.)\s*(.*)$/i', $line, $matches)) {
        $title = $matches[1];
        $name = $matches[2];
        $found = true;
    } 
    // Pattern B: Title from whitelist (no dot, followed by space)
    elseif (!$found) {
        $whitelistPattern = implode('|', $titlesNoDot);
        if (preg_match('/^(' . $whitelistPattern . ')\s+(.*)$/i', $line, $matches)) {
            $title = $matches[1];
            $name = $matches[2];
            $found = true;
        }
    }
    
    // Pattern C: Typo case (INGJULIO) - Simplistic check if improved previously
    if (!$found) {
        foreach ($titlesNoDot as $t) {
             if (stripos($line, $t) === 0 && strlen($line) > strlen($t)) {
                 if (preg_match('/^('.$t.')([A-Z].*)$/i', $line, $m)) {
                     $title = $m[1];
                     $name = $m[2];
                     $found = true;
                     break; 
                 }
             }
        }
    }

    $name = ltrim($name, ". \t\n\r\0\x0B");

    // Remove "C." or "C" to re-assign later
    if ($title === 'C.' || $title === 'C') {
        $title = '';
    }

    // Default to "C." if empty
    if (empty($title)) {
        $title = 'C.';
    }

    $results[] = [
        'original' => $line,
        'title' => strtoupper(trim($title)),
        'name' => strtoupper(trim($name)),
        'match' => '' // Placeholder
    ];
}

// --- EMPLOYEE MATCHING LOGIC ---
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

function findBestMatch($cleanedName, $employeeList) {
    $cleanedName = strtoupper(trim($cleanedName));
    if (empty($cleanedName)) return '';

    if (in_array($cleanedName, $employeeList)) {
        return $cleanedName;
    }

    $tokens = preg_split('/\s+/', $cleanedName, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($employeeList as $emp) {
        $empTokens = preg_split('/\s+/', $emp, -1, PREG_SPLIT_NO_EMPTY);
        $missing = array_diff($tokens, $empTokens);
        if (empty($missing)) {
            return $emp;
        }
    }
    return '';
}

foreach ($results as &$row) {
    if (empty($row['name'])) {
        $row['match'] = 'NO ENCONTRADO';
    } else {
        $match = findBestMatch($row['name'], $employees);
        $row['match'] = $match ? $match : 'NO ENCONTRADO';
    }
}
unset($row);

// --- HTML OUTPUT ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Resguardantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .match-found { color: green; font-weight: bold; }
        .match-not-found { color: red; font-style: italic; }
        .title-col { font-weight: bold; color: #0d6efd; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h2 class="mb-4">Reporte de Separación de Títulos y Coincidencias</h2>
        
        <div class="table-container">
            <table class="table table-striped table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Nombre Restante</th>
                        <th>Coincidencia en BD (Empleados)</th>
                        <th>Original (Validación)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1; 
                    foreach ($results as $row): 
                        $matchClass = ($row['match'] === 'NO ENCONTRADO') ? 'match-not-found' : 'match-found';
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="title-col"><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td class="<?= $matchClass ?>"><?= htmlspecialchars($row['match']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($row['original']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
