# PAO v2 - Sistema de Permisos Atómicos para Organizaciones

## Plan de Trabajo Ejecutado ✅

### Objetivo
Crear un sistema de control granular de permisos donde los usuarios puedan:
- Acceder a ciertos módulos/formularios
- Realizar acciones específicas (crear, editar, eliminar, exportar, validar, firmar, aprobar)
- Ver información solo de las áreas asignadas

---

## Estructura Creada

### 1. Base de Datos (`database/`)

#### `schema.sql` - Tablas creadas:
| Tabla | Descripción |
|-------|-------------|
| `areas` | Áreas organizacionales |
| `puestos` | Puestos de trabajo con nivel jerárquico |
| `empleados` | Empleados con relación a área y puesto |
| `usuarios_sistema` | Usuarios con login, tipo y estado |
| `modulos` | Módulos del sistema (Dashboard, RH, Admin, etc.) |
| `permisos` | Permisos atómicos (ver, crear, editar, eliminar, etc.) |
| `usuario_modulo_permisos` | Relación: qué permisos tiene cada usuario en cada módulo |
| `usuario_areas` | Relación: a qué áreas tiene acceso cada usuario |

#### `seed_data.sql` - Datos de ejemplo:
- 5 áreas organizacionales
- 8 puestos de trabajo
- 10 empleados
- 6 usuarios del sistema
- 6 módulos
- 8 permisos atómicos
- Asignaciones de ejemplo

---

### 2. Configuración (`config/`)

- `database.php` - Conexión PDO a MySQL

---

### 3. Includes (`includes/`)

| Archivo | Función |
|---------|---------|
| `auth.php` | Sistema de autenticación completo |
| `helpers.php` | Funciones utilitarias |
| `header.php` | Cabecera HTML |
| `sidebar.php` | Menú lateral dinámico |
| `footer.php` | Pie de página |

#### Funciones de Permisos Disponibles:
```php
// Verificar autenticación
isAuthenticated()
getCurrentUser()
requireAuth()

// Verificar tipo de usuario
isAdmin()

// Verificar permisos por módulo
getUserPermissions($moduloId)
hasPermission($permisoClave, $moduloId)
requirePermission($permisoClave, $moduloId)

// Verificar acceso a áreas
getUserAreas()
hasAccessToArea($areaId)
getAreaFilterSQL($columnName)  // Para filtrar consultas SQL
```

---

### 4. Assets (`assets/`)

- `css/style.css` - Sistema de diseño completo (tema oscuro)
- `js/app.js` - JavaScript principal

---

### 5. Páginas Principales

| Archivo | Descripción |
|---------|-------------|
| `login.php` | Página de inicio de sesión |
| `logout.php` | Cerrar sesión |
| `index.php` | Dashboard principal |
| `admin/usuarios.php` | Gestión de usuarios |
| `admin/permisos.php` | **Módulo principal de permisos** |

---

## Cómo Usar el Sistema

### 1. Importar la base de datos:
```sql
-- Primero ejecutar el schema
source C:/wamp64/www/pao_v2/database/schema.sql;

-- Luego los datos de ejemplo
source C:/wamp64/www/pao_v2/database/seed_data.sql;
```

### 2. Acceder al sistema:
- URL: http://localhost/pao_v2/login.php
- Usuario: `admin`
- Contraseña: `password123`

### 3. Gestionar permisos:
1. Ir a "Gestión de Permisos" en el menú
2. Seleccionar un usuario
3. Marcar los permisos por módulo
4. Seleccionar las áreas accesibles
5. Guardar

---

## Ejemplo de Uso en Código

### Verificar permiso antes de una acción:
```php
// En cualquier página de módulo
requirePermission('editar', 3); // Módulo ID 3 = Administración

// O verificar manualmente
if (!hasPermission('eliminar', 2)) {
    die('No tienes permiso para eliminar');
}
```

### Filtrar datos por áreas del usuario:
```php
// El usuario solo ve empleados de sus áreas asignadas
$sql = "SELECT * FROM empleados WHERE " . getAreaFilterSQL('id_area');
$stmt = $pdo->query($sql);
```

---

## Próximos Pasos Sugeridos

1. **Crear módulo de empleados** con CRUD completo usando permisos
2. **Agregar roles/perfiles** para asignar conjuntos de permisos
3. **Implementar auditoría** de acciones (quién hizo qué y cuándo)
4. **Agregar permisos a nivel de campo** (opcional)
5. **Crear API REST** con verificación de permisos

---

*Creado: 2026-01-21 16:38*
*Estado: ✅ COMPLETADO*