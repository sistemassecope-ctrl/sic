<?php
require_once __DIR__ . '/config/database.php';
$pdo = conectarDB();

echo "--- DEBUG JOIN LOGIC ---\n";

// 1. Fetch raw documents content
$docs = $pdo->query("SELECT id, contenido_json FROM documentos WHERE tipo_documento_id = 1 LIMIT 5")->fetchAll();
foreach ($docs as $d) {
    echo "Doc ID: {$d['id']}, JSON: {$d['contenido_json']}\n";
    $json = json_decode($d['contenido_json'], true);
    echo "  -> PHP knows id_fua as: " . var_export($json['id_fua'] ?? 'N/A', true) . "\n";
}

// 2. Test the specific join query
echo "\n--- TESTING QUERY ---\n";
$sql = "
    SELECT 
        f.id_fua, 
        d.id as documento_id,
        JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) as extracted_id
    FROM solicitudes_suficiencia f
    LEFT JOIN documentos d ON d.tipo_documento_id = 1 
        AND JSON_UNQUOTE(JSON_EXTRACT(d.contenido_json, '$.id_fua')) = CAST(f.id_fua AS CHAR)
    WHERE f.estatus = 'ACTIVO' LIMIT 5
";
$res = $pdo->query($sql)->fetchAll();
foreach ($res as $r) {
    echo "FUA ID: {$r['id_fua']} -> Doc ID: " . ($r['documento_id'] ?? 'NULL') . " (Extracted: {$r['extracted_id']})\n";
}

// 3. Test without casting/unquote (Original Query)
echo "\n--- TESTING ORIGINAL QUERY (Expected to fail if types mismatch) ---\n";
$sqlOriginal = "
    SELECT 
        f.id_fua, 
        d.id as documento_id
    FROM solicitudes_suficiencia f
    LEFT JOIN documentos d ON d.tipo_documento_id = 1 
        AND JSON_EXTRACT(d.contenido_json, '$.id_fua') = f.id_fua
    WHERE f.estatus = 'ACTIVO' LIMIT 5
";
$resOrig = $pdo->query($sqlOriginal)->fetchAll();
foreach ($resOrig as $r) {
    echo "FUA ID: {$r['id_fua']} -> Doc ID: " . ($r['documento_id'] ?? 'NULL') . "\n";
}
