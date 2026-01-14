<?php
require_once __DIR__ . '/functions.php';

if (!function_exists('obtenerNivelUsuarioEmpleadoBase')) {
    function obtenerNivelUsuarioEmpleadoBase() {
        return 6; // Nivel: Empleado
    }
}


/**
 * Obtener y cachear las columnas de usuarios_sistema
 */
function obtenerColumnasUsuarios(PDO $pdo, $forceRefresh = false) {
    static $columnCache = null;

    if ($forceRefresh || $columnCache === null) {
        try {
            $stmt = $pdo->query('SHOW COLUMNS FROM usuarios_sistema');
            $columnCache = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (PDOException $e) {
            error_log('Error obteniendo columnas de usuarios_sistema: ' . $e->getMessage());
            $columnCache = [];
        }
    }

    return $columnCache;
}

/**
 * Asegurar que exista la columna requiere_cambio_password
 */
function asegurarColumnaCambioPassword(PDO $pdo) {
    $columnas = obtenerColumnasUsuarios($pdo);

    if (!in_array('requiere_cambio_password', $columnas, true)) {
        try {
            $pdo->exec('ALTER TABLE usuarios_sistema ADD COLUMN requiere_cambio_password TINYINT(1) NOT NULL DEFAULT 1 AFTER password_hash');
            // Refrescar cache
            obtenerColumnasUsuarios($pdo, true);
        } catch (PDOException $e) {
            // Si la columna ya existe por condición de carrera, ignorar el error 1060
            if ($e->getCode() !== '42S21') {
                error_log('No se pudo agregar requiere_cambio_password: ' . $e->getMessage());
            }
        }
    }
}

/**
 * Asegurar que exista la columna username
 */
function asegurarColumnaUsername(PDO $pdo) {
    $columnas = obtenerColumnasUsuarios($pdo);

    if (!in_array('username', $columnas, true)) {
        try {
            $pdo->exec("ALTER TABLE usuarios_sistema ADD COLUMN username VARCHAR(191) NULL AFTER empleado_id");
            // Intentar crear índice único si no existe
            try {
                $pdo->exec('ALTER TABLE usuarios_sistema ADD UNIQUE KEY idx_username (username)');
            } catch (PDOException $e) {
                // Ignorar errores si el índice ya existe
                if ($e->getCode() !== '42000' && $e->getCode() !== '42S21') {
                    error_log('No se pudo crear índice único para username: ' . $e->getMessage());
                }
            }
            obtenerColumnasUsuarios($pdo, true);
        } catch (PDOException $e) {
            if ($e->getCode() !== '42S21') {
                error_log('No se pudo agregar username: ' . $e->getMessage());
            }
        }
    }
}

/**
 * Asegurar estructura requerida para usuarios del sistema
 */
function asegurarColumnasUsuarios(PDO $pdo) {
    asegurarColumnaCambioPassword($pdo);
    asegurarColumnaUsername($pdo);
}

/**
 * Determinar email preferido o generar uno alterno
 */
function obtenerEmailParaEmpleado(PDO $pdo, array $empleado) {
    $candidatos = [];

    foreach (['email', 'email_institucional', 'correo_institucional', 'correo_personal'] as $campo) {
        if (!empty($empleado[$campo])) {
            $candidatos[] = trim(strtolower($empleado[$campo]));
        }
    }

    foreach ($candidatos as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => asegurarEmailUnico($pdo, $email, $empleado['id']),
                'generado' => false,
            ];
        }
    }

    $base = $empleado['numero_empleado'] ?? $empleado['id'];
    $base = preg_replace('/[^a-zA-Z0-9]/', '', (string) $base);
    if ($base === '') {
        $base = 'usuario' . ($empleado['id'] ?? random_int(1000, 9999));
    }

    $emailGenerado = strtolower($base) . '@sic.local';
    return [
        'email' => asegurarEmailUnico($pdo, $emailGenerado, $empleado['id']),
        'generado' => true,
    ];
}

/**
 * Obtener username preferido basado en número de empleado
 */
function obtenerUsernameParaEmpleado(PDO $pdo, array $empleado) {
    $username = trim((string) ($empleado['numero_empleado'] ?? ''));
    if ($username === '') {
        $username = 'emp' . ($empleado['id'] ?? random_int(1000, 9999));
    }

    // Permitir solo caracteres seguros
    $username = preg_replace('/[^A-Za-z0-9._-]/', '', $username);
    if ($username === '') {
        $username = 'emp' . ($empleado['id'] ?? random_int(1000, 9999));
    }

    return asegurarUsernameUnico($pdo, $username, $empleado['id']);
}

/**
 * Garantizar que el username sea único
 */
function asegurarUsernameUnico(PDO $pdo, $username, $empleadoId) {
    $username = strtolower(trim($username));
    if ($username === '') {
        $username = 'emp' . $empleadoId;
    }

    $base = $username;
    $contador = 1;

    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios_sistema WHERE username = ? AND (empleado_id IS NULL OR empleado_id != ?) LIMIT 1');
        $stmt->execute([$username, $empleadoId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $username;
        }
        $username = $base . '-' . $contador;
        $contador++;
    }
}

/**
 * Garantizar que el email sea único dentro de usuarios_sistema
 */
function asegurarEmailUnico(PDO $pdo, $email, $empleadoId) {
    $email = strtolower(trim($email));
    if ($email === '') {
        $email = 'usuario' . $empleadoId . '@sic.local';
    }

    [$local, $dominio] = array_pad(explode('@', $email, 2), 2, 'sic.local');
    $contador = 1;

    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios_sistema WHERE email = ? AND empleado_id != ? LIMIT 1');
        $stmt->execute([$email, $empleadoId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $email;
        }
        $email = sprintf('%s+%d@%s', $local, $contador, $dominio);
        $contador++;
    }
}

/**
 * Crear usuario del sistema para un empleado específico
 */
function crearUsuarioSistemaParaEmpleado(PDO $pdo, array $empleado, array $options = []) {
    $autorId = $options['autor_id'] ?? null;
    $forzar = $options['forzar'] ?? false;
    $passwordPlano = $options['password'] ?? null;
    $passwordLength = $options['password_length'] ?? 12;

    asegurarColumnasUsuarios($pdo);
    $columnas = obtenerColumnasUsuarios($pdo);

    $stmt = $pdo->prepare('SELECT * FROM usuarios_sistema WHERE empleado_id = ? LIMIT 1');
    $stmt->execute([$empleado['id']]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioExistente && !$forzar) {
        $actualizaciones = [];
        $params = [];
        $usernameDeseado = null;

        // Asegurar nivel de usuario 6 si no está definido
        $nivelEmpleadoId = obtenerNivelUsuarioEmpleadoBase();
        if ($nivelEmpleadoId && (int) $usuarioExistente['nivel_usuario_id'] !== $nivelEmpleadoId) {
            $actualizaciones[] = 'nivel_usuario_id = ?';
            $params[] = $nivelEmpleadoId;
        }

        // Actualizar email si cambió en empleados
        $emailInfo = obtenerEmailParaEmpleado($pdo, $empleado);
        $emailDeseado = $emailInfo['email'];
        $emailActual = strtolower($usuarioExistente['email'] ?? '');
        if (!empty($emailDeseado) && ($emailInfo['generado'] === false || $emailActual === '')) {
            if ($emailDeseado !== $emailActual) {
                $actualizaciones[] = 'email = ?';
                $params[] = $emailDeseado;
            }
        }

        if (in_array('username', $columnas, true)) {
            $usernameDeseado = obtenerUsernameParaEmpleado($pdo, $empleado);
            $usernameActual = strtolower($usuarioExistente['username'] ?? '');
            if ($usernameDeseado !== '' && $usernameDeseado !== $usernameActual) {
                $actualizaciones[] = 'username = ?';
                $params[] = $usernameDeseado;
            }
        }

        if (!empty($actualizaciones)) {
            if (in_array('fecha_actualizacion', $columnas, true)) {
                $actualizaciones[] = 'fecha_actualizacion = NOW()';
            }
            $params[] = $usuarioExistente['id'];
            $sqlUpdate = 'UPDATE usuarios_sistema SET ' . implode(', ', $actualizaciones) . ' WHERE id = ?';
            $updStmt = $pdo->prepare($sqlUpdate);
            $updStmt->execute($params);
        }

        return [
            'status' => 'existing',
            'email' => $emailDeseado ?: $usuarioExistente['email'],
            'username' => in_array('username', $columnas, true) ? ($usernameDeseado ?? $usuarioExistente['username']) : null,
            'id' => $usuarioExistente['id'],
        ];
    }

    $emailInfo = obtenerEmailParaEmpleado($pdo, $empleado);
    $email = $emailInfo['email'];
    $username = in_array('username', $columnas, true) ? obtenerUsernameParaEmpleado($pdo, $empleado) : null;
    $nivelId = obtenerNivelUsuarioEmpleadoBase();
    if (!$nivelId) {
        throw new RuntimeException('No se encontró un nivel de usuario válido para empleados.');
    }

    $passwordPlano = $passwordPlano ?: generarPasswordTemporal($passwordLength);
    $passwordHash = password_hash($passwordPlano, PASSWORD_DEFAULT);

    $columnNames = ['empleado_id', 'email', 'password_hash', 'nivel_usuario_id'];
    $placeholders = ['?', '?', '?', '?'];
    $values = [$empleado['id'], $email, $passwordHash, $nivelId];

    if (in_array('username', $columnas, true)) {
        $columnNames[] = 'username';
        $placeholders[] = '?';
        $values[] = $username;
    }

    if (in_array('requiere_cambio_password', $columnas, true)) {
        $columnNames[] = 'requiere_cambio_password';
        $placeholders[] = '?';
        $values[] = 1;
    }
    if (in_array('activo', $columnas, true)) {
        $columnNames[] = 'activo';
        $placeholders[] = '?';
        $values[] = 1;
    }
    if (in_array('fecha_creacion', $columnas, true)) {
        $columnNames[] = 'fecha_creacion';
        $placeholders[] = 'NOW()';
    }
    if (in_array('fecha_actualizacion', $columnas, true)) {
        $columnNames[] = 'fecha_actualizacion';
        $placeholders[] = 'NOW()';
    }

    $sql = 'INSERT INTO usuarios_sistema (' . implode(', ', $columnNames) . ')
            VALUES (' . implode(', ', $placeholders) . ')';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    $nuevoId = (int) $pdo->lastInsertId();

    logActivity(
        'usuario_creado_auto',
        'Usuario generado automáticamente para empleado ID ' . $empleado['id'],
        $autorId
    );

    return [
        'status' => 'created',
        'id' => $nuevoId,
        'email' => $email,
        'username' => $username,
        'password' => $passwordPlano,
    ];
}

/**
 * Sincronizar todos los empleados activos con usuarios del sistema
 */
function sincronizarEmpleadosUsuarios(PDO $pdo, array $options = []) {
    $autorId = $options['autor_id'] ?? null;
    $limite = $options['limit'] ?? null;

    asegurarColumnasUsuarios($pdo);

    $sql = 'SELECT id, numero_empleado, email, email_institucional, nombres, apellido_paterno, apellido_materno
            FROM empleados WHERE activo = TRUE ORDER BY id';
    if ($limite) {
        $sql .= ' LIMIT ' . (int) $limite;
    }

    $stmt = $pdo->query($sql);
    $empleados = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $resultado = [
        'nuevos' => [],
        'existentes' => 0,
        'errores' => [],
    ];

    foreach ($empleados as $empleado) {
        try {
            $sync = crearUsuarioSistemaParaEmpleado($pdo, $empleado, ['autor_id' => $autorId]);
            if ($sync['status'] === 'created') {
                $resultado['nuevos'][] = [
                    'empleado_id' => $empleado['id'],
                    'nombre' => trim(($empleado['nombres'] ?? '') . ' ' . ($empleado['apellido_paterno'] ?? '') . ' ' . ($empleado['apellido_materno'] ?? '')),
                    'username' => $sync['username'],
                    'email' => $sync['email'],
                    'password' => $sync['password'],
                ];
            } else {
                $resultado['existentes']++;
            }
        } catch (Throwable $e) {
            $resultado['errores'][] = [
                'empleado_id' => $empleado['id'],
                'mensaje' => $e->getMessage(),
            ];
            error_log('Error sincronizando empleado ' . $empleado['id'] . ': ' . $e->getMessage());
        }
    }

    return $resultado;
}


