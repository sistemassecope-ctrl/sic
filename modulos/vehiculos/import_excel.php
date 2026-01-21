<?php
// Script para importar vehículos desde Excel
require_once __DIR__ . '/../../config/db.php';

$excelFile = 'C:\Users\pc\Documents\Capturas del Sistema\formatos\MANTENIMIENTO\PADRON ACTUALIZADO 03 DE DIC.xlsx';

echo "=== IMPORTACIÓN DE VEHÍCULOS ===\n";

if (!file_exists($excelFile)) {
    die("ERROR: El archivo Excel no existe.\n");
}

try {
    $db = (new Database())->getConnection();
    
    // Limpiar tabla antes de importar (opcional, por ahora solo insertamos)
    // $db->exec("TRUNCATE TABLE vehiculos");
    
    $zip = new ZipArchive;
    if ($zip->open($excelFile) === TRUE) {
        
        // 1. Leer strings compartidos
        $sharedStrings = [];
        $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXML) {
            $xml = simplexml_load_string($sharedStringsXML);
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
        
        // 2. Leer hoja 1
        $sheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXML) {
            $xml = simplexml_load_string($sheetXML);
            $count = 0;
            $inserted = 0;
            
            $stmt = $db->prepare("INSERT INTO vehiculos (
                numero, numero_economico, numero_patrimonio, numero_placas, poliza,
                marca, tipo, modelo, color, numero_serie,
                secretaria_subsecretaria, direccion_departamento, resguardo_nombre,
                factura_nombre, observacion_1, observacion_2, telefono, kilometraje,
                region, con_logotipos, en_proceso_baja
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )");
            
            foreach ($xml->sheetData->row as $row) {
                $count++;
                
                // --- LÓGICA DE RANGOS SEGÚN NUEVO REQUERIMIENTO ---
                // Filas 5 a 85: SECOPE (Activos)
                // Filas 88 a 95: REGION LAGUNA (Activos)
                // Filas 98 a 99: SECOPE (En proceso de baja)
                
                $region = 'SECOPE';
                $baja = 'NO';
                $procesar = false;
                
                if ($count >= 5 && $count <= 85) {
                    $region = 'SECOPE';
                    $procesar = true;
                } elseif ($count >= 88 && $count <= 95) {
                    $region = 'REGION LAGUNA';
                    $procesar = true;
                } elseif ($count >= 98 && $count <= 99) {
                    $region = 'SECOPE';
                    $baja = 'SI';
                    $procesar = true;
                }
                
                if (!$procesar) continue; // Saltar filas fuera de rango o encabezados intermedios
                
                $cols = [];
                // Inicializar array de 18 columnas con vacíos
                for($i=0; $i<18; $i++) $cols[$i] = '';
                
                $idx = 0;
                foreach ($row->c as $cell) {
                    $cellAttrs = $cell->attributes();
                    
                    // Determinar índice real de columna
                    $val = '';
                    if (isset($cellAttrs['t']) && $cellAttrs['t'] == 's') {
                        $sIdx = (int)$cell->v;
                        $val = $sharedStrings[$sIdx] ?? '';
                    } else {
                        $val = (string)$cell->v;
                    }
                    
                    $cols[$idx] = trim($val);
                    $idx++;
                    if($idx >= 18) break; 
                }
                
                // Verificar si la fila tiene datos relevantes
                if (empty($cols[1]) && empty($cols[3])) continue; 
                
                // --- Lógica Con Logotipos ---
                // Excepciones (NO tienen logotipos)
                $patrimonio = $cols[2];
                $noLogos = ['03-4130', '03-4757', '03-2678', '03-3098', '03-3843'];
                $conLogotipos = in_array($patrimonio, $noLogos) ? 'NO' : 'SI';

                try {
                    $stmt->execute([
                        (int)$cols[0], // numero
                        $cols[1], // economico
                        $cols[2], // patrimonio
                        $cols[3], // placas
                        $cols[4], // poliza
                        $cols[5], // marca
                        $cols[6], // tipo
                        $cols[7], // modelo
                        $cols[8], // color
                        $cols[9], // serie
                        $cols[10], // secretaria
                        $cols[11], // direccion
                        $cols[12], // resguardo
                        $cols[13], // factura
                        $cols[14], // obs1
                        $cols[15], // obs2
                        $cols[16], // telefono
                        $cols[17],  // kilometraje
                        $region,
                        $conLogotipos,
                        $baja
                    ]);
                    $inserted++;
                } catch (PDOException $e) {
                    echo "Error en fila $count: " . $e->getMessage() . "\n";
                }
            }
            
            echo "✓ Importación completada.\n";
            echo "  - Total procesados: " . ($count - 3) . "\n";
            echo "  - Insertados: $inserted\n";
        }
        
        $zip->close();
    } else {
        echo "Error al abrir el archivo Excel.\n";
    }
    
} catch (Exception $e) {
    echo "Error General: " . $e->getMessage();
}
?>
