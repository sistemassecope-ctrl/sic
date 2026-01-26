<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';

// FPDI manual loading for v2.6
// NOTE: FPDI v2 requires loading classes manually if no autoloader
require_once __DIR__ . '/../libs/fpdi/src/autoload.php'; // If it came with one, otherwise we need to require basics
if (!class_exists('Setasign\Fpdi\Tcpdf\Fpdi')) {
    // Manually require core files if autoload fails or is missing in zip dist
    // Trying a simpler approach: require key files. 
    // In FPDI 2, many files. Let's try to assume src/autoload.php exists or build a simple one.
    // If we just unzipped, 'src' is there.

    spl_autoload_register(function ($class) {
        if (strpos($class, 'Setasign\\Fpdi\\') === 0) {
            $filename = str_replace('\\', '/', substr($class, 14)) . '.php';
            $fullpath = __DIR__ . '/../libs/fpdi/src/' . $filename;
            if (file_exists($fullpath)) {
                require_once $fullpath;
            }
        }
    });
}

use Setasign\Fpdi\Tcpdf\Fpdi;

class StampingService
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Genera un NUEVO PDF que fusiona el original + la hoja de firmas.
     */
    public function generateSignatureSheet($idDocumento)
    {
        // 1. Obtener Datos
        $stmtDoc = $this->db->prepare("SELECT * FROM archivo_documentos WHERE id_documento = ?");
        $stmtDoc->execute([$idDocumento]);
        $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

        if (!$doc)
            throw new Exception("Documento no encontrado.");

        $stmtFirmas = $this->db->prepare("
            SELECT f.*, u.nombre_usuario, u.usuarios_nombre_completo, uc.ruta_firma_imagen 
            FROM archivo_firmas f
            JOIN usuarios u ON f.id_usuario = u.id_usuario
            LEFT JOIN usuarios_config_firma uc ON u.id_usuario = uc.id_usuario
            WHERE f.id_documento = ? AND f.estado = 'VALIDA'
            ORDER BY f.fecha_firma ASC
        ");
        $stmtFirmas->execute([$idDocumento]);
        $firmas = $stmtFirmas->fetchAll(PDO::FETCH_ASSOC);

        // 2. Preparar Rutas
        $originalPath = __DIR__ . '/../../uploads/archivo_digital/' . $doc['ruta_almacenamiento'];
        if (!file_exists($originalPath)) {
            throw new Exception("El archivo original no se encuentra en: $originalPath");
        }

        // 3. Iniciar FPDI (extiende TCPDF)
        try {
            $pdf = new Fpdi();

            // --- FASE A: IMPORTAR ORIGINAL ---
            $pageCount = $pdf->setSourceFile($originalPath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);

                // Get size of imported page
                $size = $pdf->getTemplateSize($templateId);

                // Create page with same orientation/size
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, array($size['width'], $size['height']));
                $pdf->useTemplate($templateId);
            }

            // --- FASE B: AGREGAR HOJA DE FIRMAS ---
            // Usamos formato carta vertical estándar para la hoja de firmas
            $pdf->AddPage('P', 'LETTER');

            // Meta y Estilos (Mismo código de antes)
            $pdf->SetCreator('SIS-PAO Digital Archive');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            $html = '
            <h1 style="text-align:center; font-family:helvetica; font-size:16pt;">HOJA DE FIRMAS ELECTRÓNICAS</h1>
            <p style="text-align:center;">Anexo al documento: <strong>' . $doc['nombre_archivo_original'] . '</strong></p>
            <p style="text-align:center; font-size:9pt; color:#555;">UUID: ' . $doc['uuid'] . ' | Hash Orig: ' . substr($doc['hash_contenido'], 0, 16) . '...</p>
            <hr><br><br>
            ';
            $pdf->writeHTML($html, true, false, true, false, '');

            // Renderizar Firmas v2 (Loop idéntico)
            $y = $pdf->GetY();
            $col = 0;

            foreach ($firmas as $firma) {
                $x = ($col == 0) ? 15 : 110;
                if ($col == 0 && $y > 250) {
                    $pdf->AddPage();
                    $y = 20;
                }

                $pdf->SetXY($x, $y);
                if (!empty($firma['ruta_firma_imagen'])) {
                    $imgAbsPath = __DIR__ . '/../../' . $firma['ruta_firma_imagen'];
                    if (file_exists($imgAbsPath)) {
                        $pdf->Image($imgAbsPath, $x + 15, $y, 40, 0, 'PNG');
                    }
                }

                $pdf->SetXY($x, $y + 25);
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(80, 5, strtoupper($firma['usuarios_nombre_completo']), 0, 1, 'C');

                $pdf->SetX($x);
                $pdf->SetFont('helvetica', '', 9);
                $pdf->Cell(80, 5, $firma['rol_firmante'], 'T', 1, 'C');

                $pdf->SetX($x);
                $pdf->SetFont('courier', '', 7);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(80, 4, 'Firma Digital: ' . substr($firma['hash_firma'], 0, 16) . '...', 0, 1, 'C');
                $pdf->Cell(80, 4, 'Fecha: ' . $firma['fecha_firma'], 0, 1, 'C');
                $pdf->SetTextColor(0, 0, 0);

                if ($col == 1) {
                    $col = 0;
                    $y += 50;
                } else {
                    $col = 1;
                }
            }

            // QR Final
            $style = array('border' => 0, 'vpadding' => 'auto', 'hpadding' => 'auto', 'fgcolor' => array(0, 0, 0), 'bgcolor' => false, 'module_width' => 1, 'module_height' => 1);
            $urlValidacion = "https://sistemassecope.gob.mx/validar/" . $doc['uuid'];

            $pdf->SetY(-40);
            $pdf->write2DBarcode($urlValidacion, 'QRCODE,H', 170, $pdf->GetY(), 25, 25, $style, 'N');
            $pdf->SetY(-35);
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->Cell(150, 0, 'Documento y firmas hash validados electrónicamente.', 0, 0, 'R');

            // --- FASE C: GUARDAR ---
            // Guardamos como "signed_UUID.pdf" para diferenciar o sobreescribir según política
            // En este caso, guardaremos como UNA NUEVA VERSIÓN VISUAL
            $outputName = 'signed_' . $doc['uuid'] . '.pdf';
            $outputPath = __DIR__ . '/../../uploads/archivo_digital/' . dirname($doc['ruta_almacenamiento']) . '/' . $outputName;

            $pdf->Output($outputPath, 'F');

            // Opcional: Actualizar BD para apuntar mostrar este archivo firmado visualmente en ciertos contextos?
            // Por ahora solo retornamos la ruta.
            return $outputPath;

        } catch (Exception $e) {
            // Si falla FPDI (ej. versión PDF muy nueva), fallback a solo hoja de firmas?
            // Por ahora lanzamos error.
            throw new Exception("Error al fusionar PDF con FPDI: " . $e->getMessage());
        }
    }
}
?>