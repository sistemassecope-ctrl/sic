<?php
/**
 * Servicio para la generación del PDF final y archivo en el expediente digital.
 * Archivo: includes/services/ExpedienteDigitalService.php
 */

namespace SIC\Services;

use PDO;
use RuntimeException;
use Exception;

require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';

class ExpedienteDigitalService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Genera el PDF final del documento incluyendo la hoja de firmas legales.
     */
    public function generarPdfFinal(int $documentoId): string
    {
        // 1. Obtener datos del documento y sus firmas
        $stmt = $this->pdo->prepare("
            SELECT d.*, ctd.nombre as tipo_nombre, ctd.prefijo_folio
            FROM documentos d
            JOIN cat_tipos_documento ctd ON d.tipo_documento_id = ctd.id
            WHERE d.id = ?
        ");
        $stmt->execute([$documentoId]);
        $doc = $stmt->fetch();

        if (!$doc)
            throw new RuntimeException("Documento no encontrado.");

        $stmtFirmas = $this->pdo->prepare("
            SELECT df.*, e.nombre, e.apellido_paterno, e.apellido_materno, ef.firma_imagen
            FROM documento_flujo_firmas df
            JOIN usuarios_sistema us ON df.firmante_id = us.id
            JOIN empleados e ON us.id_empleado = e.id
            LEFT JOIN empleado_firmas ef ON e.id = ef.empleado_id
            WHERE df.documento_id = ? AND df.estatus = 'firmado'
            ORDER BY df.orden ASC
        ");
        $stmtFirmas->execute([$documentoId]);
        $firmas = $stmtFirmas->fetchAll();

        // 2. Configurar TCPDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('SIC-PAO');
        $pdf->SetAuthor('Sistema de Gestión Documental');
        $pdf->SetTitle('Documento Firmado - ' . $doc['folio_sistema']);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);

        // 3. Agregar Contenido del Documento
        $pdf->AddPage();

        // Encabezado Visual Premium
        $htmlHeader = '
            <table cellpadding="5" style="border-bottom: 2px solid #336699;">
                <tr>
                    <td width="70%">
                        <h1 style="color:#336699; font-size:18pt;">' . $doc['tipo_nombre'] . '</h1>
                        <span style="color:#666; font-size:10pt;">Folio Sistema: <b>' . $doc['folio_sistema'] . '</b></span>
                    </td>
                    <td width="30%" align="right">
                        <span style="font-size:9pt; color:#999;">Generado: ' . date('d/m/Y H:i') . '</span>
                    </td>
                </tr>
            </table>
            <br><br>
            <h2 style="font-size:14pt;">' . htmlspecialchars($doc['titulo']) . '</h2>
            <div style="font-size:11pt; line-height:1.5;">
                ' . nl2br(htmlspecialchars($doc['titulo'])) . ' 
                <!-- Aquí iría el contenido dinámico del JSON si tuviéramos una plantilla HTML -->
            </div>
        ';
        $pdf->writeHTML($htmlHeader, true, false, true, false, '');

        // 4. Agregar Hoja de Firmas (Nueva Página)
        $pdf->AddPage();
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetTextColor(51, 102, 153);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'HOJA DE FIRMAS ELECTRÓNICAS', 0, 1, 'C', 1);
        $pdf->Ln(5);

        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', '', 9);

        foreach ($firmas as $f) {
            $nombreFull = $f['nombre'] . ' ' . $f['apellido_paterno'] . ' ' . $f['apellido_materno'];

            $pdf->StartTransform();
            $pdf->SetLineWidth(0.1);
            $pdf->SetDrawColor(200);

            // Cuadro de firma
            $y = $pdf->GetY();

            // Imagen de la firma (Base64)
            if ($f['firma_imagen'] && $f['tipo_firma'] === 'pin') {
                $img = $f['firma_imagen'];
                if (strpos($img, 'data:image') !== false) {
                    $img = explode(',', $img)[1];
                }
                $pdf->Image('@' . base64_decode($img), 15, $y, 35, 0, 'PNG');
            }

            $pdf->SetX(55);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, $nombreFull, 0, 1);

            $pdf->SetX(55);
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->Cell(0, 5, strtoupper($f['rol_firmante'] ?? 'FIRMANTE AUTORIZADO'), 0, 1);

            $pdf->SetX(55);
            $pdf->SetFont('courier', '', 7);
            $pdf->SetTextColor(100);
            $pdf->MultiCell(0, 3, "Sello Digital (" . strtoupper($f['tipo_firma']) . "): " . ($f['firma_fiel_hash'] ?: $f['firma_pin_hash']), 0, 'L');
            $pdf->SetX(55);
            $pdf->Cell(0, 4, "Fecha: " . $f['fecha_firma'], 0, 1);

            $pdf->SetTextColor(0);
            $pdf->Ln(5);
            $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
            $pdf->Ln(5);
        }

        // QR de Validación
        $style = array(
            'border' => 2,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(51, 102, 153),
            'bgcolor' => false,
            'module_width' => 1,
            'module_height' => 1
        );
        $pdf->write2DBarcode('https://sicpao.gob.mx/validar/' . $doc['folio_sistema'], 'QRCODE,H', 160, $pdf->GetY(), 30, 30, $style, 'N');
        $pdf->SetY($pdf->GetY() + 10);
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->Cell(140, 5, 'Este documento cuenta con firmas electrónicas con validez legal interna.', 0, 0, 'R');

        // 5. Guardar Archivo
        $uploadDir = __DIR__ . '/../../storage/expedientes/' . date('Y/m');
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $fileName = $doc['folio_sistema'] . '_' . time() . '.pdf';
        $fullPath = $uploadDir . '/' . $fileName;
        $relativePath = 'storage/expedientes/' . date('Y/m') . '/' . $fileName;

        $pdf->Output($fullPath, 'F');

        // 6. Actualizar registro en BD
        $stmtUpdate = $this->pdo->prepare("UPDATE documentos SET archivo_pdf = ? WHERE id = ?");
        $stmtUpdate->execute([$relativePath, $documentoId]);

        return $relativePath;
    }
}
