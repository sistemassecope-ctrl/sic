# Documentación: Módulos de Recursos Financieros y Programas Operativos (PAO)

Esta documentación describe la estructura, funcionalidad e integración de los módulos financieros dentro del marco de **Atomicidad de Permisos** de PAO v2.

---

## 1. Módulo: Recursos Financieros
Este módulo es el eje central para la gestión presupuestal y administrativa de las acciones de la organización.

### Sub-módulos Principales:
- **FUAs (Formatos Únicos de Atención)**: Gestión de trámites administrativos, suficiencias presupuestales y movimientos de saldo.
- **Catálogos Financieros**: Gestión de fuentes de financiamiento, ramos presupuestales y tipos de acción.

### Estructura de Datos (Tablas Clave):
| Tabla | Descripción |
|-------|-------------|
| `fuas` | Registro principal de trámites administrativos y financieros. |
| `cat_tipos_fua_accion` | Catálogo de tipos de obra o acción (Obra Nueva, Mantenimiento, etc.). |
| `cat_ramos` | Ramos presupuestales vinculados a los recursos. |

---

## 2. Módulo: Programas Operativos (PAO / POA)
Específicamente diseñado para la planeación anual y el seguimiento de proyectos físicos.

### Componentes:
- **Programas Anuales (POA)**: Definición del techo financiero por ejercicio fiscal.
- **Proyectos de Obra**: Desglose de acciones específicas dentro de un programa.

### Estructura de Datos (Tablas Clave):
| Tabla | Descripción |
|-------|-------------|
| `programas_anuales` | Registro de POAs por año (Ejercicio, Monto Autorizado, Estatus). |
| `proyectos_obra` | Proyectos vinculados a un POA con desglose de montos federal/estatal/municipal. |
| `cat_ejes` / `cat_objetivos` | Vinculación con el Plan Estatal/Municipal de Desarrollo. |

---

## 3. Integración con el Sistema de Permisos Atómicos
Siguiendo el **"Plan de Trabajo de Atomicidad de Permisos"**, estos módulos deben implementar las siguientes restricciones granulares:

### Permisos Sugeridos por Módulo:
| Permiso | Acción en Financieros / POA |
|---------|-----------------------------|
| `ver` | Visualizar listados de POAs, Proyectos y FUAs. |
| `crear` | Registrar nuevos POAs o dar de alta Proyectos. |
| `editar` | Modificar importes o datos técnicos de proyectos existentes. |
| `eliminar` | Borrado lógico de registros (solo si no tienen movimientos asociados). |
| `validar` | (Especial) Aprobar un FUA para que afecte el saldo del proyecto. |
| `exportar` | Generar reportes en Excel/PDF de la inversión programada. |

### Aplicación de Seguridad en Código (PHP):
Para cumplir con la arquitectura de PAO v2, cada archivo debe incluir:

#### A. Verificación de Permiso Atómico:
```php
requirePermission('ver', MODULO_FINANZAS_ID); // Al inicio de index.php
requirePermission('crear', MODULO_FINANZAS_ID); // En formularios de captura
```

#### B. Filtro por Área Institucional:
Dado que los proyectos pertenecen a unidades responsables (áreas), se debe usar la función de filtrado para que un usuario solo vea lo que le corresponde:
```php
// Ejemplo en listar_proyectos.php
$sql = "SELECT * FROM proyectos_obra WHERE " . getAreaFilterSQL('id_unidad_responsable');
```

---

## 4. Flujo Operativo y Semáforo de Gestión
El sistema implementa un indicador visual (Semáforo) en el listado de proyectos para facilitar la auditoría:

1.  **Gris (Sin Movimiento)**: El proyecto ha sido capturado en el POA pero no tiene trámites administrativos (FUAs) iniciados.
2.  **Rojo (En Gestión)**: El proyecto ya tiene al menos un FUA asociado (independientemente de su estatus), lo que indica que el recurso ya está siendo comprometido o gestionado.

---

## 5. Ubicaciones de Archivos (Arquitectura PAO v2)
- **Controladores y Vistas**: `modulos/recursos_financieros/`
- **Carga de Documentos**: `assets/uploads/fuas/` (Resguardos digitales de suficiencias).
- **Scripts de Instalación**: Localizados en `database/` para asegurar la integridad de la estructura jerárquica.

---
*Documento generado considerando el Plan de Atomicidad de Permisos - Enero 2026*
