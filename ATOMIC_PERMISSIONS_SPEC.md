# Especificación del Sistema de Permisos Atómicos (SPA) - PAO v2

> **Documento de Referencia para Agentes Antigravity**
> Este documento define la arquitectura, reglas y patrones de implementación para el sistema de control de acceso en PAO v2.
> **Instrucción para Agentes:** Al implementar nuevas funcionalidades, MÓDULOS o CONSULTAS SQL, DEBES adherirte estrictamente a estas especificaciones.

---

## 1. Filosofía del Sistema

El sistema **no se basa únicamente en roles estáticos** (como "Editor" o "Visitante"). En su lugar, utiliza un enfoque **híbrido y atómico**:

1.  **Roles de Sistema (Macro)**: `SUPERADMIN`, `admin_global`. Tienen acceso total implícito.
2.  **Permisos Atómicos (Micro)**: Permisos granulares (`ver`, `crear`, `editar`, `eliminar`, `exportar`) asignados específicamente por **Módulo**.
3.  **Segregación por Áreas (Scope)**: Los usuarios pueden tener permisos sobre un módulo, pero **solo para los datos de las áreas** que tienen asignadas.

---

## 2. Esquema de Base de Datos Crítico

Cualquier modificación o consulta debe respetar estas relaciones:

| Tabla | Propósito | Clave para Agentes |
|-------|-----------|--------------------|
| `usuarios_sistema` | Autenticación | `tipo = 1` es Admin legacy. Preferir verificar `rol_sistema`. |
| `modulos` | Catálogo de funcionalidades | Cada nueva feature debe pertenecer a un `id` de módulo. |
| `permisos` | Catálogo de acciones | Acciones atómicas: `ver`, `crear`, `editar`, `eliminar`. |
| `usuario_modulo_permisos` | **La Tabla de la Verdad** | Relaciona `usuario` + `modulo` + `permiso`. |
| `usuario_areas` | **Scope de Datos** | Define QUÉ filas puede ver el usuario (filtro horizontal). |

---

## 3. Reglas de Implementación (GOLDEN RULES)

### Regla #1: Protección en Capas
Cada archivo PHP accesible directamente (controladores, vistas) debe validar permisos en este orden:
1.  **Autenticación**: `requireAuth();`
2.  **Autorización de Módulo**: `requirePermission('ver', $ID_MODULO);`
3.  **Scope de Datos (si aplica)**: Filtrar consultas SQL usando `getAreaFilterSQL()`.

### Regla #2: Nunca "Hardcodear" Roles
⛔ **INCORRECTO**:
```php
if ($user['rol'] == 'jefe_recursos_humanos') { ... }
```

✅ **CORRECTO (Semántico)**:
```php
if (hasPermission('aprobar_vacaciones', MODULO_RH)) { ... }
```

### Regla #3: El SQL es Sagrado (Row-Level Security)
Si un usuario tiene permiso de "ver empleados", **NO significa que pueda ver a TODOS**.
SIEMPRE concatena el filtro de áreas en las cláusulas `WHERE`.

---

## 4. Patrones de Código Estandarizados

Usa estos snippets tal cual para mantener consistencia.

### A. Verificación de Acceso (Inicio de Archivo)
```php
require_once 'includes/auth.php';

// 1. Validar sesión
requireAuth();

// 2. Definir ID del módulo actual (ver tabla `modulos`)
$MODULO_ID = 5; // Ejemplo: 5 = Finanzas

// 3. Bloquear acceso si no tiene permiso básico
requirePermission('ver', $MODULO_ID);
```

### B. Consulta Segura (Listados)
```php
// Obtener cláusula de seguridad
// 'e.area_id' es el nombre de la columna en tu tabla que referencia al área
$filtroAreas = getAreaFilterSQL('e.area_id');

$sql = "SELECT e.*, a.nombre_area 
        FROM empleados e 
        JOIN areas a ON e.area_id = a.id 
        WHERE e.activo = 1 
        AND $filtroAreas"; // <--- INYECCIÓN DE SEGURIDAD OBLIGATORIA

$stmt = $pdo->query($sql);
```

### C. Botones Condicionales (Frontend)
```php
<!-- Botón Editar -->
<?php if (hasPermission('editar', $MODULO_ID)): ?>
    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning">Editar</a>
<?php endif; ?>

<!-- Botón Eliminar -->
<?php if (hasPermission('eliminar', $MODULO_ID)): ?>
    <button onclick="deleteItem(<?= $row['id'] ?>)" class="btn btn-danger">Eliminar</button>
<?php endif; ?>
```

---

## 5. Protocolo para Nuevos Módulos

Si Antigravity necesita crear una nueva funcionalidad (ej: "Inventario"):

1.  **Registrar Módulo**: Insertar en tabla `modulos` (`nombre: Inventario`).
2.  **Definir Permisos**: ¿Requiere permisos especiales además del CRUD estándar? (ej: `aprobar_salida`).
3.  **Crear UI de Asignación**: El administrador debe poder asignar estos nuevos permisos en `admin/permisos.php` (esto suele ser automático si la UI es dinámica).
4.  **Codificar**: Usar el Patrón A y B descritos arriba.

---

## 6. Referencia de API (`includes/auth.php`)

| Función | Descripción |
|---------|-------------|
| `isAdmin()` | Devuelve `true` si es Superadmin. Bypasea todas las reglas. |
| `hasPermission($clave, $modulo_id)` | Devuelve `true/false`. Usar en `if`. |
| `requirePermission($clave, $modulo_id)` | Mata la ejecución (`die`) si falla. Usar al inicio para seguridad fuerte. |
| `getAreaFilterSQL($columna)` | Devuelve string SQL: `id_area IN (1, 2, 5)`. Maneja automáticamente el caso "sin acceso" (devuelve `IN (0)`). |

---

*Fin del Documento de Especificación*
