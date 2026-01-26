<?php
namespace SIC\Services;

use PDO;

class PdfDocumentoService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Genera el PDF final del documento con firmas y lo adjunta.
     * Retorna la ruta generada o null si no fue posible.
     */
    public function generar(int $documentoId, int $actorId): ?string
    {
        $detalle = \obtenerDocumentoDetalle($documentoId);
        if (!$detalle) {
            return null;
        }

        $html = $this->construirHtml($detalle);
        $dir = __DIR__ . '/../../storage/documentos';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $extension = 'pdf';
        $fileName = 'documento_' . $documentoId . '.' . $extension;
        $path = $dir . '/' . $fileName;

        $generado = false;
        if (class_exists('Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4');
            $dompdf->render();
            $output = $dompdf->output();
            $generado = file_put_contents($path, $output) !== false;
        }

        if (!$generado) {
            $extension = 'html';
            $fileName = 'documento_' . $documentoId . '.' . $extension;
            $path = $dir . '/' . $fileName;
            file_put_contents($path, $html);
        }

        $relativePath = 'storage/documentos/' . $fileName;

        $this->pdo->prepare('DELETE FROM documento_adjuntos WHERE documento_id = ? AND nombre_original LIKE ?')->execute([
            $documentoId,
            'documento_' . $documentoId . '.%'
        ]);
        $stmt = $this->pdo->prepare('INSERT INTO documento_adjuntos (documento_id, ruta, nombre_original, subido_por) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $documentoId,
            $relativePath,
            $fileName,
            $actorId,
        ]);

        return $relativePath;
    }

    private function construirHtml(array $detalle): string
    {
        $rechazos = array_filter($detalle['historial'], fn($item) => strtolower($item['accion']) === 'rechazar');
        $firmas = $detalle['firmas'];
        $pasos = $detalle['pasos'];

        ob_start();
        ?>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
                h1 { font-size: 20px; margin-bottom: 5px; }
                h2 { font-size: 16px; margin-top: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                th, td { border: 1px solid #333; padding: 6px; }
                .badge { padding: 4px 6px; border-radius: 4px; color: #fff; }
            </style>
        </head>
        <body>
            <h1><?php echo htmlspecialchars($detalle['titulo']); ?></h1>
            <p><strong>Tipo:</strong> <?php echo htmlspecialchars($detalle['tipo_documento']); ?> | <strong>Folio:</strong> <?php echo htmlspecialchars($detalle['folio']); ?></p>
            <p><strong>Creado por:</strong> <?php echo htmlspecialchars($detalle['creador_email'] ?? ''); ?> el <?php echo date('d/m/Y H:i', strtotime($detalle['fecha_creacion'])); ?></p>
            <p><strong>Estado final:</strong> <?php echo htmlspecialchars($detalle['estado_actual']); ?></p>

            <h2>Flujo de aprobación</h2>
            <table>
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Actor</th>
                        <th>Estado</th>
                        <th>Asignación</th>
                        <th>Resolución</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pasos as $paso): ?>
                    <tr>
                        <td><?php echo $paso['orden']; ?></td>
                        <td><?php echo htmlspecialchars($paso['actor_email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($paso['estatus']); ?></td>
                        <td><?php echo $paso['fecha_asignacion'] ? date('d/m/Y H:i', strtotime($paso['fecha_asignacion'])) : '-'; ?></td>
                        <td><?php echo $paso['fecha_resolucion'] ? date('d/m/Y H:i', strtotime($paso['fecha_resolucion'])) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Firmas electrónicas</h2>
            <?php if (empty($firmas)): ?>
                <p>No se registraron firmas.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Actor</th>
                            <th>Fecha</th>
                            <th>No. Certificado</th>
                            <th>Hash</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($firmas as $firma): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($firma['actor_email'] ?? ''); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($firma['fecha_firma'])); ?></td>
                            <td><?php echo htmlspecialchars($firma['numero_certificado'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($firma['hash_documento']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2>Bitácora</h2>
            <?php if (empty($detalle['historial'])): ?>
                <p>Sin registros.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Acción</th>
                            <th>Actor</th>
                            <th>Fecha</th>
                            <th>Comentarios</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detalle['historial'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['accion']); ?></td>
                            <td><?php echo htmlspecialchars($item['actor_email'] ?? ''); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($item['fecha'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($item['comentarios'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($rechazos)): ?>
                <h2>Comentarios de rechazo</h2>
                <ul>
                    <?php foreach ($rechazos as $rechazo): ?>
                        <li><strong><?php echo htmlspecialchars($rechazo['actor_email'] ?? ''); ?></strong> - <?php echo date('d/m/Y H:i', strtotime($rechazo['fecha'])); ?>:<br><?php echo nl2br(htmlspecialchars($rechazo['comentarios'] ?? '')); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
