---
description: Blindaje de Seguridad Atómica (Protocolo de Permisos Granulares)
---

Este flujo asegura que cada parte del sistema respete estrictamente los permisos asignados en la base de datos (ver, crear, editar, eliminar, exportar).

### Pasos obligatorios para cada archivo:

1. **Definiciones Base**:
   - Incluir `auth.php` y `helpers.php`.
   - Ejecutar `requireAuth()`.

2. **Identificación del Módulo**:
   - Definir `MODULO_ID` basado estrictamente en el ID de la tabla `modulos`.

3. **Obtención de Permisos**:
   - Llamar a `$permisos_user = getUserPermissions(MODULO_ID);`.
   - Mapear variables booleanas: `$puedeCrear`, `$puedeEditar`, `$puedeEliminar`, `$puedeVer`.

4. **Protección de Acceso (Guardia)**:
   - Al inicio del archivo: Si no tiene permiso `ver`, denegar acceso y redirigir.
   - En acciones (POST/GET delete): Si no tiene el permiso específico (ej: `eliminar`), abortar la operación.

5. **Ajuste de Interfaz (UI)**:
   - Envolver botones de "Nuevo", "Editar" y "Eliminar" en condicionales PHP usando las variables de permiso.

6. **Consistencia de Sesión**:
   - Utilizar siempre `getCurrentUserId()` o `$_SESSION['usuario_id']` para auditoría o filtros (evitar `user_id` genérico).
