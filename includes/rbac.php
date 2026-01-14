<?php
/**
 * rbac.php - Funciones para Control de Acceso Basado en Roles (Jerárquico)
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Verifica si un usuario tiene acceso a un registro específico basado en Scope
 * 
 * @param string $modulo Nombre del módulo (ej. 'empleados', 'vehiculos')
 * @param int $registroAreaId ID del área a la que pertenece el registro
 * @param int $registroCreadorId ID del usuario creador (opcional, para scope PROPIO)
 * @return bool
 */
function tieneAccesoRegistro($modulo, $registroAreaId, $registroCreadorId = null) {
    // Asumimos que getCurrentUser() ya fue llamado o $_SESSION['user_id'] existe
    // En este sistema usamos $currentUser global a veces, o session.
    // Vamos a usar la sesión para ser robustos.
    if (!isset($_SESSION['user_id'])) return false;
    
    $userId = $_SESSION['user_id'];
    $nivelId = $_SESSION['nivel_usuario_id'] ?? 0;
    
    // 1. Obtener permiso base
    // Nota: Esta función ya existe en auth.php, vamos a usarla.
    // Pero necesitamos el 'alcance'. La función `getModulosDisponibles` trae alcance?
    // Probablemente no. Necesitamos una consulta directa aquí o modificar auth.php.
    // Consulta rápida para obtener alcance:
    $permiso = obtenerAlcanceModulo($nivelId, $modulo);
    
    if (!$permiso) return false; // No tiene permiso ni de ver
    
    $alcance = $permiso; // 'GLOBAL', 'JERARQUIA', 'AREA', 'PROPIO', 'ASIGNADO'
    
    // Si es GLOBAL, acceso total
    if ($alcance === 'GLOBAL') return true;
    
    // Obtener Area del Usuario
    // Asumimos que 'empleado_area_id' o 'dependencia_id' está en sesión o lo buscamos
    // En sync_users.php vimos que usuarios_sistema tiene empleado_id.
    // Empleados tiene dependencia_id (ahora areas.id).
    // Vamos a buscar el area del usuario si no está en sesión.
    $userAreaId = $_SESSION['area_id'] ?? obtenerAreaIdUsuario($userId);
    
    switch ($alcance) {
        case 'AREA':
            return $userAreaId == $registroAreaId;
            
        case 'JERARQUIA':
            return esAreaDescendiente($userAreaId, $registroAreaId);
            
        case 'PROPIO':
            return $userId == $registroCreadorId;
            
        case 'ASIGNADO':
            return verificarAccesoExplicito($userId, $registroAreaId, $modulo) || ($userAreaId == $registroAreaId); 
            // ASIGNADO usualmente incluye el propio + extras.
            
        default:
            return false;
    }
}

/**
 * Obtiene el alcance (string) de un nivel para un módulo
 */
function obtenerAlcanceModulo($nivelId, $moduloName) {
    global $pdo;
    $pdo = $pdo ?? conectarDB(); // Asegurar conexión
    
    $sql = "SELECT pm.alcance 
            FROM permisos_modulos pm
            JOIN modulos m ON pm.modulo_id = m.id
            WHERE pm.nivel_usuario_id = ? AND m.nombre = ? AND pm.puede_ver = 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nivelId, $moduloName]);
    return $stmt->fetchColumn(); // Returns scope string or false
}

/**
 * Obtiene el ID del área de un usuario
 */
function obtenerAreaIdUsuario($userId) {
    global $pdo;
    $pdo = $pdo ?? conectarDB();
    
    // Join usuarios_sistema -> empleados -> areas
    $sql = "SELECT e.dependencia_id 
            FROM usuarios_sistema us
            JOIN empleados e ON us.empleado_id = e.id
            WHERE us.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $areaId = $stmt->fetchColumn();
    
    // Cache in session optimization
    $_SESSION['area_id'] = $areaId;
    return $areaId;
}

/**
 * Verifica si $targetAreaId es descendiente o igual a $parentAreaId
 */
function esAreaDescendiente($parentAreaId, $targetAreaId) {
    if ($parentAreaId == $targetAreaId) return true;
    
    // Obtener todos los hijos (y nietos) de parentAreaId
    // Esto puede ser costoso si es muy profundo.
    // Mejor estrategia: ¿Es targetAreaId hijo de...? Subir desde target hasta encontrar parent o root.
    
    $currentId = $targetAreaId;
    while ($currentId != 0 && $currentId !== null) {
        if ($currentId == $parentAreaId) return true;
        $currentId = obtenerPadreArea($currentId);
    }
    return false;
}

function obtenerPadreArea($areaId) {
    global $pdo;
    $pdo = $pdo ?? conectarDB();
    // Columna confirmada: dependencia_padre_id
    $stmt = $pdo->prepare("SELECT dependencia_padre_id FROM areas WHERE id = ?");
    $stmt->execute([$areaId]);
    return $stmt->fetchColumn();
}

/**
 * Verifica acceso explícito en tabla auxiliar
 */
function verificarAccesoExplicito($userId, $areaId, $moduloName) {
    global $pdo;
    $pdo = $pdo ?? conectarDB();
    
    // Buscar si tiene permiso para esa area
    // modulo_id puede ser NULL (todos) o específico
    // Necesitamos modulo_id del nombre
    $sql = "SELECT COUNT(*) FROM usuario_areas_acceso uaa
            LEFT JOIN modulos m ON m.nombre = ?
            WHERE uaa.usuario_id = ? 
            AND uaa.area_id = ?
            AND (uaa.modulo_id IS NULL OR uaa.modulo_id = m.id)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$moduloName, $userId, $areaId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Genera el fragmento SQL para filtrar por alcance
 * @param string $modulo Nombre del módulo
 * @param string $campoArea Nombre de la columna de área (ej. 'e.area_id', 'area_id')
 * @param string $campoCreador Nombre de la columna creador (ej. 'e.creado_por', 'usuario_id')
 * @return string Fragmento SQL que empieza con AND (...)
 */
function getCondicionAlcance($modulo, $campoArea = 'area_id', $campoCreador = 'creado_por') {
    if (!isset($_SESSION['user_id'])) return " AND 0 "; // Sin sesión = 0 resultados
    
    $userId = $_SESSION['user_id'];
    $nivelId = $_SESSION['nivel_usuario_id'] ?? 0;
    
    $permiso = obtenerAlcanceModulo($nivelId, $modulo);
    if (!$permiso) return " AND 0 "; // Sin permiso
    
    $alcance = $permiso; // 'GLOBAL', 'JERARQUIA', 'AREA', 'PROPIO', 'ASIGNADO'
    
    // Si es GLOBAL, no filtramos nada (retorna vacío o 1=1)
    if ($alcance === 'GLOBAL') return "";
    
    $userAreaId = $_SESSION['area_id'] ?? obtenerAreaIdUsuario($userId);
    
    switch ($alcance) {
        case 'AREA':
            return " AND $campoArea = " . intval($userAreaId);
            
        case 'JERARQUIA':
            // Obtener jerarquía puede ser pesado.
            // Si hay muchos, usamos IN (...).
            // Helper para obtener hijos recursivos?
            $ids = obtenerIdsJerarquia($userAreaId);
            if (empty($ids)) return " AND $campoArea = " . intval($userAreaId);
            return " AND $campoArea IN (" . implode(',', $ids) . ")";
            
        case 'PROPIO':
            return " AND $campoCreador = " . intval($userId);
            
        case 'ASIGNADO':
            // Propia area + asignada
            $ids = [$userAreaId];
            // Buscar asignados
            global $pdo;
            $pdo = $pdo ?? conectarDB();
            $stmt = $pdo->prepare("SELECT area_id FROM usuario_areas_acceso 
                                   WHERE usuario_id = ? 
                                   AND (modulo_id IS NULL OR modulo_id = (SELECT id FROM modulos WHERE nombre = ?))");
            $stmt->execute([$userId, $modulo]);
            $extras = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $ids = array_merge($ids, $extras);
            $ids = array_unique($ids);
            
            return " AND $campoArea IN (" . implode(',', $ids) . ")";
            
        default:
            return " AND 0 ";
    }
}

/**
 * Obtiene IDs de la jerarquía (Padre + Hijos + Nietos)
 * Nota: Implementación simple, cuidado con recursión infinita
 */
function obtenerIdsJerarquia($rootAreaId) {
    global $pdo;
    $pdo = $pdo ?? conectarDB();
    
    $ids = [$rootAreaId];
    
    // Obtener hijos directos
    $sql = "SELECT id FROM areas WHERE dependencia_padre_id IN (" . implode(',', $ids) . ")";
    $stmt = $pdo->query($sql);
    $hijos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    while (!empty($hijos)) {
        $nuevosHijos = [];
        foreach ($hijos as $hijo) {
            if (!in_array($hijo, $ids)) {
                $ids[] = $hijo;
                $nuevosHijos[] = $hijo;
            }
        }
        if (empty($nuevosHijos)) break;
        
        // Buscar hijos de los nuevos hijos
        $sql = "SELECT id FROM areas WHERE dependencia_padre_id IN (" . implode(',', $nuevosHijos) . ")";
        $stmt = $pdo->query($sql);
        $hijos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    return $ids;
}

?>
