<?php
/**
 * Servicio para la generación y control de folios de documentos.
 * Archivo: includes/services/FolioService.php
 */

namespace SIC\Services;

use PDO;
use RuntimeException;

class FolioService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Genera el siguiente folio para un tipo de documento específico.
     * Utiliza bloqueo SELECT FOR UPDATE para evitar colisiones.
     */
    public function generar(int $tipoId): string
    {
        $stmt = $this->pdo->prepare("
            SELECT prefijo_folio, ultimo_folio 
            FROM cat_tipos_documento 
            WHERE id = ? FOR UPDATE
        ");
        $stmt->execute([$tipoId]);
        $tipo = $stmt->fetch();

        if (!$tipo) {
            throw new RuntimeException("Tipo de documento #$tipoId no encontrado.");
        }

        $nuevoFolio = (int) $tipo['ultimo_folio'] + 1;

        // Formato: PREFIJO-AÑO-CORRELATIVO (Ej: SP-2026-00001)
        $folioFormateado = "{$tipo['prefijo_folio']}-" . date('Y') . "-" . str_pad($nuevoFolio, 5, '0', STR_PAD_LEFT);

        // Actualizar último folio en el catálogo
        $update = $this->pdo->prepare("UPDATE cat_tipos_documento SET ultimo_folio = ? WHERE id = ?");
        $update->execute([$nuevoFolio, $tipoId]);

        return $folioFormateado;
    }
}
