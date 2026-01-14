<?php
// app/models/Logger.php

class Logger
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Registrar accesos (Login/Logout)
    public function logAccess($usuario_id, $usuario_intentado, $accion, $details = "")
    {
        try {
            $query = "INSERT INTO bitacora_accesos (id_usuario, usuario_intentado, ip_origen, accion, detalles) 
                      VALUES (:uid, :usr, :ip, :act, :det)";

            $stmt = $this->conn->prepare($query);

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

            $stmt->bindParam(':uid', $usuario_id, PDO::PARAM_INT); // Puede ser null si falló login
            $stmt->bindParam(':usr', $usuario_intentado);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':act', $accion);
            $stmt->bindParam(':det', $details);

            return $stmt->execute();
        } catch (PDOException $e) {
            // Silencio o log a archivo si falla la BD de logs
            error_log("Error Logger Access: " . $e->getMessage());
            return false;
        }
    }

    // Registrar acciones de negocio (Altas, Bajas, Cambios)
    public function logAction($usuario_id, $modulo, $tipo_accion, $tabla, $id_registro, $old_data = null, $new_data = null)
    {
        try {
            $query = "INSERT INTO bitacora_acciones 
                      (id_usuario, modulo, tipo_accion, tabla_afectada, id_registro, datos_anteriores, datos_nuevos, ip_origen) 
                      VALUES (:uid, :mod, :tipo, :tbl, :idreg, :old, :new, :ip)";

            $stmt = $this->conn->prepare($query);

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // Codificar JSON si es array u objeto, sino null
            $json_old = $old_data ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null;
            $json_new = $new_data ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null;

            $stmt->bindParam(':uid', $usuario_id);
            $stmt->bindParam(':mod', $modulo);
            $stmt->bindParam(':tipo', $tipo_accion);
            $stmt->bindParam(':tbl', $tabla);
            $stmt->bindParam(':idreg', $id_registro);
            $stmt->bindParam(':old', $json_old);
            $stmt->bindParam(':new', $json_new);
            $stmt->bindParam(':ip', $ip);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error Logger Action: " . $e->getMessage());
            return false;
        }
    }
}
?>