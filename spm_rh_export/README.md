# SPM Recursos Humanos - Módulo Standalone

Módulo de Recursos Humanos extraído del sistema PAO/SIC.

## Características

- **Organigrama Interactivo**: Visualización jerárquica de áreas con drill-down
- **Gestión de Áreas**: CRUD completo de áreas/dependencias
- **Gestión de Empleados**: Listado y gestión de empleados
- **Vista previa de impresión**: Ajuste de escala y orientación antes de imprimir
- **Sidebar colapsable**: Toggle para ocultar/mostrar menú lateral

## Instalación

### 1. Base de Datos
```sql
-- Crear la base de datos
CREATE DATABASE spm_rh CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;

-- Importar el schema
mysql -u root -p spm_rh < database/schema_rh.sql
```

### 2. Configuración
Editar `config/config.php` con tus credenciales de base de datos:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
define('DB_NAME', 'spm_rh');
```

### 3. Estructura de Archivos
```
spm_rh/
├── api/
│   └── organigrama.php      # API JSON para el organigrama
├── assets/
│   └── css/
│       └── styles.css       # Estilos del sistema
├── config/
│   └── config.php           # Configuración principal
├── database/
│   └── schema_rh.sql        # Schema de base de datos
├── img/
│   └── logo_secope.png      # Logo
├── includes/
│   └── rh_functions.php     # Funciones del módulo
├── modulos/
│   └── rh/
│       ├── areas.php        # Gestión de áreas
│       ├── empleados.php    # Gestión de empleados
│       └── organigrama.php  # Organigrama interactivo
└── README.md
```

## Notas de Integración

Los archivos en `modulos/rh/` usan rutas relativas como:
- `../../includes/header.php` → Ajustar según tu estructura
- `../../config/config.php` → Ajustar según tu estructura

Deberás crear tus propios archivos `header.php` y `footer.php` o adaptar los existentes.

## Dependencias

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Bootstrap 5.x
- Bootstrap Icons
- Font Awesome 6.x
