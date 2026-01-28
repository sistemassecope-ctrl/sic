<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/services/DocumentoService.php';
require_once __DIR__ . '/includes/services/SignatureFlowService.php';

use SIC\Services\SignatureFlowService;

try {
    $pdo = conectarDB();
    $flowService = new SignatureFlowService($pdo);

    echo "--- REPARANDO FLUJOS DE FIRMA ---\n";

    // Buscar documentos que deberían tener flujo pero no lo tienen
    // Asumimos que todos los documentos tipo 1 (Suficiencia) deben tener flujo
    $docs = $pdo->query("
        SELECT d.id, d.titulo 
        FROM documentos d
        LEFT JOIN documento_flujo_firmas df ON d.id = df.documento_id
        WHERE d.tipo_documento_id = 1 
          AND df.id IS NULL
    ")->fetchAll();

    if (empty($docs)) {
        echo "✅ No se encontraron documentos sin flujo.\n";
    }

    foreach ($docs as $d) {
        echo "Reparando documento ID {$d['id']}: {$d['titulo']}... ";
        try {
            $flowService->iniciarFlujo($d['id']);
            echo "✅ ÉXITO\n";
        } catch (Exception $e) {
            echo "❌ FALLÓ: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR GLOBAL: " . $e->getMessage();
}
