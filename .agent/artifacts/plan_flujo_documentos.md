# 🚀 Plan de Desarrollo: Sistema de Gestión Documental con Ciclo de Vida Completo

> **Versión:** 2.0 (Actualizado)  
> **Autor:** Antigravity AI  
> **Fecha:** 28 de Enero de 2026  
> **Cliente:** SECOPE - Sistema de Control de Proyectos y Expedientes  
> **Estado:** ✅ ESTABLECIDO

---

## 📊 Estado Actual del Sistema PAO

### Componentes Ya Implementados ✅
| Componente | Ubicación | Estado |
|------------|-----------|--------|
| **FlujoDocumentosService** | `includes/services/FlujoDocumentosService.php` | ✅ Implementado |
| **PdfDocumentoService** | `includes/services/PdfDocumentoService.php` | ✅ Implementado |
| **Sistema de Firma Digital con PIN** | `database/firma_digital.sql` | ✅ Implementado |
| **Sistema FIEL Empleados** | `modulos/empleados/guardar-fiel.php` | ✅ Implementado |
| **Bandeja de Gestión** | `modulos/recursos-financieros/bandeja-gestion.php` | ✅ Implementado |
| **Solicitud Suficiencia** | `modulos/recursos-financieros/solicitud-suficiencia-form.php` | ✅ Implementado |
| **Sistema de Permisos Atómicos** | `includes/helpers/permissions.php` | ✅ Implementado |
| **Momentos de Gestión** | Tabla `momentos_gestion` | ✅ Implementado |

### Componentes Pendientes 🔄
| Componente | Prioridad | Dependencias |
|------------|-----------|--------------|
| Tabla `documentos` maestra | 🔴 Alta | Schema SQL |
| Tabla `documento_bitacora` | 🔴 Alta | Schema SQL |
| Tabla `documento_flujo_firmas` | 🔴 Alta | Schema SQL |
| Catálogo de Tipos de Documento | 🟡 Media | Schema SQL |
| Sistema de Folios | 🟡 Media | Tipos de Documento |
| UI de Generación Universal | 🔴 Alta | Servicios base |

---

## 📋 Resumen Ejecutivo

Este plan establece la implementación optimizada de un **Sistema de Gestión Documental Inteligente** aprovechando los componentes existentes del sistema PAO. El enfoque es **iterativo e incremental**, comenzando por los documentos de Suficiencia Presupuestal como piloto.

---

## 🎯 Objetivos del Proyecto

### Objetivos Principales
1. **Trazabilidad Total**: Cada documento tendrá un historial completo desde su creación hasta su resolución
2. **Flujos Dinámicos**: Cadenas de aprobación configurables por tipo de documento
3. **Firma Electrónica Dual**: Integración con PIN (interna) y FIEL (jurídica)
4. **Cero Papel**: Digitalización completa del proceso documental
5. **Tiempo Real**: Notificaciones y alertas instantáneas

### Indicadores de Éxito (KPIs)
| Indicador | Meta | Plazo | Baseline Actual |
|-----------|------|-------|-----------------|
| Tiempo promedio de aprobación | -60% | 3 meses | A medir |
| Documentos extraviados | 0% | Inmediato | A medir |
| Adopción del sistema | 95% usuarios | 6 meses | ~30% |
| Satisfacción del usuario | >4.5/5 | 6 meses | A medir |

---

## 🏗️ Arquitectura del Sistema

### Diagrama de Fases del Documento
```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        CICLO DE VIDA DEL DOCUMENTO                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   ┌─────────┐    ┌───────────┐    ┌─────────────┐    ┌──────────┐          │
│   │ 1. NACE │───▶│ 2. APRUEBA│───▶│ 3. SE FIRMA │───▶│ 4. GESTIÓN│         │
│   │Generación│    │ Validación│    │Flujo Firmas │    │ Int/Ext  │         │
│   └─────────┘    └─────┬─────┘    └──────┬──────┘    └────┬─────┘         │
│        │               │                  │                 │               │
│        │          ┌────▼────┐        ┌────▼────┐       ┌────▼────┐         │
│        │          │ RECHAZA │        │ RECHAZA │       │ 5. MUERE│         │
│        │          │ (Vuelve)│        │ (Vuelve)│       │Resolución│         │
│        │          └─────────┘        └─────────┘       └─────────┘         │
│        │                                                                    │
│        └──────────── BITÁCORA AUTOMÁTICA EN CADA PASO ──────────────────▶  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Integración con Componentes Existentes

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          ARQUITECTURA INTEGRADA                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  EXISTENTE                           NUEVO                                  │
│  ─────────                           ────                                   │
│  ┌───────────────────┐               ┌───────────────────┐                 │
│  │ FlujoDocumentos   │◄──────────────│ DocumentoService  │                 │
│  │ Service.php       │               │ (NUEVO)           │                 │
│  └───────────────────┘               └───────────────────┘                 │
│           │                                   │                             │
│           ▼                                   ▼                             │
│  ┌───────────────────┐               ┌───────────────────┐                 │
│  │ empleado_firmas   │◄──────────────│ documento_bitacora│                 │
│  │ (PIN/FIEL)        │               │ (NUEVO)           │                 │
│  └───────────────────┘               └───────────────────┘                 │
│           │                                   │                             │
│           ▼                                   ▼                             │
│  ┌───────────────────┐               ┌───────────────────┐                 │
│  │ bandeja-gestion   │◄──────────────│ Bandeja Universal │                 │
│  │ .php (Tabs)       │               │ de Documentos     │                 │
│  └───────────────────┘               └───────────────────┘                 │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Modelo de Datos Principal

```sql
-- ============================================
-- MIGRACIÓN: Sistema de Gestión Documental
-- Archivo: database/documentos_gestion.sql
-- ============================================

-- Tabla Maestra de Documentos
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento_id INT NOT NULL,
    folio_sistema VARCHAR(50) NOT NULL UNIQUE COMMENT 'Folio automático del sistema',
    folio_oficial VARCHAR(50) NULL COMMENT 'Folio oficial (si aplica)',
    titulo VARCHAR(255) NOT NULL,
    contenido_json JSON NULL COMMENT 'Datos dinámicos del documento',
    archivo_pdf VARCHAR(255) NULL COMMENT 'Ruta al PDF generado',
    hash_integridad VARCHAR(64) NULL COMMENT 'SHA-256 del contenido',
    
    -- Ciclo de vida
    fase_actual ENUM('generacion', 'aprobacion', 'firmas', 'gestion', 'resuelto', 'cancelado') 
        DEFAULT 'generacion',
    estatus ENUM('borrador', 'pendiente', 'en_proceso', 'aprobado', 'rechazado', 'firmado', 'resuelto', 'cancelado') 
        DEFAULT 'borrador',
    prioridad ENUM('baja', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    
    -- Referencias
    usuario_generador_id INT NOT NULL,
    usuario_aprobador_id INT NULL,
    documento_padre_id INT NULL COMMENT 'Para vincular respuestas/anexos',
    
    -- Fechas clave
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion DATETIME NULL,
    fecha_primera_firma DATETIME NULL,
    fecha_ultima_firma DATETIME NULL,
    fecha_resolucion DATETIME NULL,
    fecha_limite DATETIME NULL COMMENT 'Fecha máxima para resolución',
    
    -- Resolución
    resultado_final ENUM('positivo', 'negativo', 'parcial', 'cancelado') NULL,
    observaciones_finales TEXT NULL,
    
    -- Metadatos
    metadata_json JSON NULL COMMENT 'Datos adicionales flexibles',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tipo_documento_id) REFERENCES cat_tipos_documento(id),
    FOREIGN KEY (usuario_generador_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (usuario_aprobador_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (documento_padre_id) REFERENCES documentos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para documentos
CREATE INDEX idx_doc_tipo ON documentos(tipo_documento_id);
CREATE INDEX idx_doc_fase ON documentos(fase_actual);
CREATE INDEX idx_doc_estatus ON documentos(estatus);
CREATE INDEX idx_doc_generador ON documentos(usuario_generador_id);
CREATE INDEX idx_doc_fecha ON documentos(fecha_generacion);
CREATE INDEX idx_doc_prioridad ON documentos(prioridad, fecha_limite);

-- Bitácora de Vida (Immutable Log)
CREATE TABLE IF NOT EXISTS documento_bitacora (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    
    -- Transición de estado
    fase_anterior ENUM('generacion', 'aprobacion', 'firmas', 'gestion', 'resuelto', 'cancelado') NULL,
    fase_nueva ENUM('generacion', 'aprobacion', 'firmas', 'gestion', 'resuelto', 'cancelado') NULL,
    estatus_anterior VARCHAR(50) NULL,
    estatus_nuevo VARCHAR(50) NULL,
    
    -- Acción
    accion VARCHAR(100) NOT NULL COMMENT 'Ej: CREAR, APROBAR, FIRMAR, RECHAZAR, DELEGAR',
    descripcion TEXT NOT NULL,
    observaciones TEXT NULL,
    
    -- Auditoría
    usuario_id INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    -- Firma electrónica (si aplica)
    firma_tipo ENUM('ninguna', 'pin', 'fiel') DEFAULT 'ninguna',
    firma_hash VARCHAR(255) NULL,
    certificado_serial VARCHAR(100) NULL,
    
    -- Timestamp inmutable
    timestamp_evento DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6),
    
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La bitácora es append-only, no se permite UPDATE/DELETE
-- Índices para bitácora
CREATE INDEX idx_bitacora_doc ON documento_bitacora(documento_id);
CREATE INDEX idx_bitacora_usuario ON documento_bitacora(usuario_id);
CREATE INDEX idx_bitacora_fecha ON documento_bitacora(timestamp_evento);
CREATE INDEX idx_bitacora_accion ON documento_bitacora(accion);

-- Flujo de Firmas Dinámico
CREATE TABLE IF NOT EXISTS documento_flujo_firmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    
    -- Secuencia
    orden TINYINT NOT NULL COMMENT 'Orden en la cadena de firmas',
    
    -- Firmante
    firmante_id INT NOT NULL COMMENT 'ID del usuario firmante',
    rol_firmante VARCHAR(100) NULL COMMENT 'Rol con el que firma',
    
    -- Estado de la firma
    estatus ENUM('pendiente', 'firmado', 'rechazado', 'delegado', 'vencido') DEFAULT 'pendiente',
    tipo_firma ENUM('pin', 'fiel') DEFAULT 'pin' COMMENT 'Método de firma requerido',
    
    -- Fechas
    fecha_asignacion DATETIME NULL,
    fecha_firma DATETIME NULL,
    fecha_limite DATETIME NULL COMMENT 'Tiempo máximo para firmar',
    
    -- Resultado
    tipo_respuesta ENUM('aprobado', 'aprobado_con_observaciones', 'rechazado', 'solicita_cambios') NULL,
    observaciones TEXT NULL,
    
    -- Firma electrónica
    firma_pin_hash VARCHAR(64) NULL COMMENT 'SHA-256 del sello PIN',
    firma_fiel_hash VARCHAR(255) NULL COMMENT 'Hash de firma FIEL',
    certificado_serial VARCHAR(100) NULL,
    sello_tiempo DATETIME NULL,
    
    -- Delegación
    delegado_a INT NULL,
    fecha_delegacion DATETIME NULL,
    motivo_delegacion TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (firmante_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (delegado_a) REFERENCES usuarios_sistema(id),
    
    UNIQUE KEY uk_doc_orden (documento_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para flujo de firmas
CREATE INDEX idx_flujo_doc ON documento_flujo_firmas(documento_id);
CREATE INDEX idx_flujo_firmante ON documento_flujo_firmas(firmante_id);
CREATE INDEX idx_flujo_estatus ON documento_flujo_firmas(estatus);
CREATE INDEX idx_flujo_pendiente ON documento_flujo_firmas(firmante_id, estatus, fecha_limite);

-- Cola de Trabajo por Usuario (Bandeja Universal)
CREATE TABLE IF NOT EXISTS usuario_bandeja_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    documento_id INT NOT NULL,
    
    -- Tipo de acción
    tipo_accion_requerida ENUM(
        'aprobar', 'firmar', 'revisar', 'responder', 
        'gestionar', 'informativo', 'vencido'
    ) NOT NULL,
    
    -- Prioridad y tiempos
    prioridad TINYINT DEFAULT 2 COMMENT '1=Baja, 2=Normal, 3=Alta, 4=Urgente',
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_limite DATETIME NULL,
    
    -- Estado
    leido TINYINT(1) DEFAULT 0,
    fecha_lectura DATETIME NULL,
    procesado TINYINT(1) DEFAULT 0,
    fecha_proceso DATETIME NULL,
    
    -- Metadatos
    origen VARCHAR(100) NULL COMMENT 'Módulo que generó la entrada',
    notas_internas TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id),
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    
    UNIQUE KEY uk_usuario_doc (usuario_id, documento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para bandeja
CREATE INDEX idx_bandeja_usuario ON usuario_bandeja_documentos(usuario_id, procesado, prioridad);
CREATE INDEX idx_bandeja_pendiente ON usuario_bandeja_documentos(usuario_id, procesado, fecha_limite);
CREATE INDEX idx_bandeja_tipo ON usuario_bandeja_documentos(tipo_accion_requerida);

-- Catálogo de Tipos de Documento
CREATE TABLE IF NOT EXISTS cat_tipos_documento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código único, ej: SUFPRE, OFICIO',
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    
    -- Configuración del flujo
    requiere_aprobacion TINYINT(1) DEFAULT 1,
    requiere_firma TINYINT(1) DEFAULT 1,
    tipo_firma_default ENUM('pin', 'fiel', 'ambas') DEFAULT 'pin',
    plantilla_flujo_id INT NULL COMMENT 'Plantilla predeterminada',
    
    -- Generación de folio
    prefijo_folio VARCHAR(10) NULL COMMENT 'Prefijo para folios',
    ultimo_folio INT DEFAULT 0,
    
    -- Plantilla PDF
    plantilla_pdf VARCHAR(255) NULL COMMENT 'Ruta a plantilla TCPDF',
    
    -- Control
    activo TINYINT(1) DEFAULT 1,
    orden_menu TINYINT DEFAULT 99,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plantillas de Flujo de Firmas
CREATE TABLE IF NOT EXISTS flujo_plantillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    tipo_documento_id INT NULL COMMENT 'NULL = plantilla genérica',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS flujo_plantilla_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plantilla_id INT NOT NULL,
    orden TINYINT NOT NULL,
    
    -- Puede ser un usuario específico o un rol
    actor_usuario_id INT NULL,
    actor_rol VARCHAR(50) NULL COMMENT 'Alternativa: asignar por rol',
    
    tipo_firma ENUM('pin', 'fiel') DEFAULT 'pin',
    tiempo_maximo_horas INT DEFAULT 48,
    puede_delegar TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (plantilla_id) REFERENCES flujo_plantillas(id),
    FOREIGN KEY (actor_usuario_id) REFERENCES usuarios_sistema(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

INSERT INTO cat_tipos_documento (codigo, nombre, descripcion, tipo_firma_default, prefijo_folio) VALUES
('SUFPRE', 'Suficiencia Presupuestal', 'Solicitud de suficiencia presupuestal para proyectos de obra', 'fiel', 'SP'),
('OFICIO', 'Oficio', 'Oficio oficial interno o externo', 'fiel', 'OF'),
('MEMO', 'Memorándum', 'Comunicación interna', 'pin', 'MEM'),
('MINUTA', 'Minuta de Reunión', 'Acta de reunión de trabajo', 'pin', 'MIN'),
('CONTRATO', 'Contrato', 'Contrato o convenio', 'fiel', 'CONT'),
('VIATICO', 'Solicitud de Viáticos', 'Solicitud de gastos de viaje', 'pin', 'VIA');
```

---

## 📅 Cronograma de Desarrollo (Optimizado)

### 🚀 Sprint 0: Preparación (Semana 1) - PRIORITARIO
> **Objetivo:** Establecer la base de datos y servicios core

| ID | Tarea | Descripción | Prioridad | Est. | Dependencia |
|----|-------|-------------|-----------|------|-------------|
| 0.1 | Migración SQL | Ejecutar `documentos_gestion.sql` | 🔴 Alta | 2 hrs | - |
| 0.2 | DocumentoService | Crear servicio PHP base | 🔴 Alta | 4 hrs | 0.1 |
| 0.3 | BitacoraService | Servicio de registro inmutable | 🔴 Alta | 3 hrs | 0.1 |
| 0.4 | FolioService | Generador de folios únicos | 🟡 Media | 2 hrs | 0.1 |
| 0.5 | Integrar con FlujoDocumentosService | Conectar servicios existentes | 🔴 Alta | 3 hrs | 0.2 |

**Entregables:**
- ✅ Base de datos migrada y funcional
- ✅ Servicios core operativos
- ✅ Tests básicos pasando

---

### Fase 1: Piloto con Suficiencias (Semanas 2-3)
> **Objetivo:** Implementar ciclo completo en documentos de Suficiencia Presupuestal

| ID | Tarea | Descripción | Prioridad | Est. |
|----|-------|-------------|-----------|------|
| 1.1 | Migrar Suficiencias | Conectar `solicitud-suficiencia-form.php` con nuevo modelo | 🔴 Alta | 6 hrs |
| 1.2 | Bitácora Automática | Registrar cada acción en suficiencias | 🔴 Alta | 3 hrs |
| 1.3 | Timeline Visual | Mostrar historial en detalle de suficiencia | 🟡 Media | 4 hrs |
| 1.4 | Bandeja Mejorada | Actualizar `bandeja-gestion.php` con nuevo modelo | 🔴 Alta | 5 hrs |
| 1.5 | Notificaciones | Correo al cambiar de fase | 🟡 Media | 3 hrs |

**Entregables:**
- ✅ Suficiencias funcionando con nuevo modelo
- ✅ Trazabilidad completa visible
- ✅ Usuarios notificados automáticamente

---

### Fase 2: Flujo de Aprobación Universal (Semanas 4-5)
> **Objetivo:** Sistema de validación interna para todos los tipos

| ID | Tarea | Descripción | Prioridad | Est. |
|----|-------|-------------|-----------|------|
| 2.1 | Formulario Universal | UI de creación de documentos genéricos | 🔴 Alta | 8 hrs |
| 2.2 | Selector de Tipo | Dropdown que carga plantilla correspondiente | 🔴 Alta | 3 hrs |
| 2.3 | Panel "Mis Pendientes" | Widget de documentos por aprobar | 🔴 Alta | 5 hrs |
| 2.4 | Flujo Rechazo | Regresar documento con observaciones | 🟡 Media | 3 hrs |
| 2.5 | Dashboard Estado | Vista general de documentos por fase | 🟡 Media | 5 hrs |

**Entregables:**
- ✅ Cualquier tipo de documento puede crearse
- ✅ Panel unificado de pendientes
- ✅ Dashboard gerencial operativo

---

### Fase 3: Sistema Dual de Firma (Semanas 6-8)
> **Objetivo:** Implementar firma con PIN y FIEL según tipo de documento

#### 🔐 Comparativa de Modalidades

| Característica | 🔵 Firma con PIN | 🟢 Firma con FIEL |
|----------------|------------------|-------------------|
| **Validez Jurídica** | Interna/Administrativa | Plena (NOM-151) |
| **Requisitos** | Solo PIN personal | Certificado .cer + Llave .key + Contraseña |
| **Velocidad** | Instantánea | 3-5 segundos |
| **Uso Recomendado** | Aprobaciones internas, visto bueno, minutas | Oficios, contratos, suficiencias, licitaciones |
| **Almacenamiento** | Hash SHA-256 + timestamp | Hash + Cadena FIEL + Sello de tiempo |
| **Delegación** | Permitida | No permitida |
| **Base en PAO** | `empleado_firmas` (EXISTENTE ✅) | `guardar-fiel.php` (EXISTENTE ✅) |

#### Tareas

| ID | Tarea | Descripción | Prioridad | Est. |
|----|-------|-------------|-----------|------|
| 3.1 | Configurador Cadena | UI drag & drop de firmantes | 🔴 Alta | 8 hrs |
| 3.2 | Widget Selección Firmantes | Búsqueda y asignación rápida | 🔴 Alta | 4 hrs |
| 3.3 | **Firma con PIN** | Integrar con `empleado_firmas` existente | 🔴 Alta | 6 hrs |
| 3.3.1 | └─ Modal de PIN | Validación de 4 dígitos | 🔴 Alta | 2 hrs |
| 3.3.2 | └─ Generación sello | Hash + timestamp + IP | 🔴 Alta | 2 hrs |
| 3.3.3 | └─ Registro en bitácora | Auditoría de firma PIN | 🔴 Alta | 2 hrs |
| 3.4 | **Firma con FIEL** | Integrar con sistema FIEL existente | 🔴 Alta | 8 hrs |
| 3.4.1 | └─ Modal FIEL | Solicitar .key + contraseña | 🔴 Alta | 3 hrs |
| 3.4.2 | └─ Validación certificado | Verificar vigencia y coincidencia | 🔴 Alta | 3 hrs |
| 3.4.3 | └─ Cadena de firma | Generar hash criptográfico | 🔴 Alta | 2 hrs |
| 3.5 | Selector Tipo Firma | Modal con opción PIN/FIEL | 🔴 Alta | 3 hrs |
| 3.6 | Constancia de Firma | PDF con detalles del firmado | 🟡 Media | 4 hrs |
| 3.7 | Delegación PIN | Permit delegar firma PIN | 🟢 Baja | 3 hrs |
| 3.8 | Alertas +24hrs | Notificar documentos sin firmar | 🟡 Media | 2 hrs |

#### 🎨 Diseño UI para Firma

```
┌─────────────────────────────────────────────────────────────┐
│                    FIRMAR DOCUMENTO                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📄 Suficiencia Presupuestal #SP-2026-0142                 │
│  Proyecto: Construcción de Biblioteca Municipal             │
│  Monto: $1,250,000.00                                       │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Seleccione el tipo de firma:                        │   │
│  │                                                       │   │
│  │  ┌───────────────┐    ┌───────────────┐             │   │
│  │  │  🔵 CON PIN   │    │  🟢 CON FIEL  │             │   │
│  │  │               │    │               │             │   │
│  │  │  Rápida       │    │  Validez      │             │   │
│  │  │  Interna      │    │  Jurídica     │             │   │
│  │  └───────────────┘    └───────────────┘             │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  [ Vista Previa ]  [ Rechazar ]  [ ✓ Firmar ]              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Entregables:**
- ✅ Flujo de firmas con ambas modalidades
- ✅ Documentos sellados electrónicamente
- ✅ Constancia de firma con QR verificable

---

### Fase 4: Gestión Externa (Semanas 9-10)
> **Objetivo:** Seguimiento de documentos hacia dependencias externas

| ID | Tarea | Descripción | Prioridad | Est. |
|----|-------|-------------|-----------|------|
| 4.1 | Catálogo Dependencias | CRUD de dependencias externas | 🔴 Alta | 2 hrs |
| 4.2 | Registro Salida | Marcar envío de documento | 🔴 Alta | 4 hrs |
| 4.3 | Seguimiento Acuse | Capturar fecha/hora de recepción | 🔴 Alta | 4 hrs |
| 4.4 | Timeline Externo | Visualizar gestión externa | 🟡 Media | 5 hrs |
| 4.5 | Alertas Sin Respuesta | Notificar documentos sin contestar | 🟡 Media | 2 hrs |
| 4.6 | Link Correspondencia | Vincular con módulo existente | 🟢 Baja | 4 hrs |

---

### Fase 5: Cierre y Archivo (Semanas 11-12)
> **Objetivo:** Finalización formal del ciclo de vida

| ID | Tarea | Descripción | Prioridad | Est. |
|----|-------|-------------|-----------|------|
| 5.1 | Pantalla Cierre | Formulario de resolución final | 🔴 Alta | 4 hrs |
| 5.2 | Resultado Final | Captura de resultado y observaciones | 🔴 Alta | 2 hrs |
| 5.3 | Vault Digital | Archivo inmutable con hash | 🔴 Alta | 6 hrs |
| 5.4 | Expediente Electrónico | Agrupar documentos relacionados | 🟡 Media | 5 hrs |
| 5.5 | Reportes Productividad | Tiempos, cuellos de botella, etc. | 🟡 Media | 6 hrs |
| 5.6 | Exportación Auditoría | Paquete para revisión externa | 🟢 Baja | 3 hrs |

---

### Fase 6: UX Premium (Semana 13)
> **Objetivo:** Pulir experiencia y agregar valor

| ID | Tarea | Descripción | Prioridad | Est. |
|----|-------|-------------|-----------|------|
| 6.1 | Dashboard Ejecutivo | Gráficas dinámicas con Chart.js | 🟡 Media | 8 hrs |
| 6.2 | Buscador Universal | Full-text search de documentos | 🔴 Alta | 4 hrs |
| 6.3 | Timeline Interactivo | Historial visual expandible | 🟡 Media | 4 hrs |
| 6.4 | Modo Oscuro | Toggle dark/light | 🟢 Baja | 2 hrs |
| 6.5 | Atajos Teclado | Ctrl+N, Ctrl+S, etc. | 🟢 Baja | 2 hrs |
| 6.6 | Tour Guiado | Intro.js para nuevos usuarios | 🟢 Baja | 3 hrs |

---

## 💡 Ideas Innovadoras

### 🎨 Experiencia de Usuario
1. **Bandeja Inteligente**: Priorización automática basada en urgencia y antigüedad
2. **Vista Kanban**: Drag & drop entre fases como Trello
3. **Widgets Personalizables**: Panel adaptable por usuario
4. **Modo Concentración**: Ocultar distracciones al revisar

### 🔐 Seguridad
5. **Hash Encadenado**: Bitácora tipo blockchain
6. **Marca de Agua Dinámica**: Nombre del visualizador en tiempo real
7. **Autodestrucción Borradores**: Eliminar versiones no finales
8. **Auditoría de Accesos**: Quién vio qué y cuándo

### 📱 Movilidad
9. **PWA**: Aprobar desde celular
10. **Notificaciones Push**: Alertas instantáneas
11. **Firma por QR**: Escanear para aprobar

### 🤖 Automatización
12. **Plantillas Inteligentes**: Sugerencias según contexto
13. **Recordatorios Escalonados**: 24h, 48h, 72h
14. **Auto-escalamiento**: Firma pasa al siguiente si no se atiende
15. **Reportes Programados**: Envío semanal automático

---

## 🛠️ Stack Tecnológico (Alineado con PAO)

| Componente | Tecnología | Estado |
|------------|------------|--------|
| Backend | PHP 8.3 + PDO | ✅ Ya en uso |
| Frontend | JavaScript Vanilla + Bootstrap 5 | ✅ Ya en uso |
| Base de Datos | MySQL 8.0 | ✅ Ya en uso |
| PDFs | TCPDF | ✅ Ya en uso |
| Firmas | OpenSSL + FIEL | ✅ Ya en uso |
| Notificaciones | PHPMailer | ✅ Ya en uso |
| Almacenamiento | Sistema de archivos + Hash | ✅ Ya en uso |

---

## 📈 Métricas de Seguimiento

### Por Fase
- [ ] Porcentaje de avance
- [ ] Tareas completadas vs pendientes
- [ ] Bugs reportados vs resueltos
- [ ] Horas estimadas vs reales

### Por Usuario
- Documentos generados
- Tiempo promedio de aprobación
- Documentos rechazados
- Tasa de resolución

### Por Documento
- Tiempo en cada fase
- Número de rechazos
- Firmantes involucrados
- Tiempo total de ciclo

---

## ⚠️ Riesgos y Mitigación

| Riesgo | Prob. | Impacto | Mitigación |
|--------|-------|---------|------------|
| Resistencia al cambio | Alta | Alto | Capacitación progresiva, piloto con suficiencias |
| Problemas con FIEL | Media | Alto | Pruebas exhaustivas, fallback a PIN |
| Volumen de datos | Baja | Medio | Índices optimizados, particionamiento |
| Pérdida de documentos | Muy Baja | Crítico | Backups diarios, replicación |

---

## 🎓 Plan de Capacitación

| Semana | Audiencia | Contenido |
|--------|-----------|-----------|
| 1 | Administradores | Configuración y gestión |
| 2 | Capturistas | Generación de documentos |
| 3 | Aprobadores/Firmantes | Flujo de firmas |
| 4 | Consulta | Búsqueda y reportes |
| Continuo | Todos | Videos tutoriales en sistema |

---

## 📝 Próximos Pasos Inmediatos

### ✅ Acciones Requeridas

1. - [ ] **Aprobar** este plan con el equipo directivo
2. - [ ] **Ejecutar** migración SQL `documentos_gestion.sql`
3. - [ ] **Crear** DocumentoService y BitacoraService
4. - [ ] **Integrar** con `solicitud-suficiencia-form.php` como piloto
5. - [ ] **Validar** funcionamiento con usuarios clave
6. - [ ] **Iterar** según feedback

### 📁 Archivos a Crear/Modificar

```
includes/services/
├── DocumentoService.php        # NUEVO - Servicio principal
├── BitacoraService.php         # NUEVO - Log inmutable
├── FolioService.php            # NUEVO - Generación de folios
├── FlujoDocumentosService.php  # MODIFICAR - Integrar con nuevo modelo
└── NotificadorService.php      # MODIFICAR - Eventos de documentos

database/
├── documentos_gestion.sql      # NUEVO - Migración completa
└── seed_tipos_documento.sql    # NUEVO - Catálogo inicial

modulos/recursos-financieros/
├── solicitud-suficiencia-form.php  # MODIFICAR - Usar nuevo modelo
├── bandeja-gestion.php             # MODIFICAR - Integrar bitácora
└── documento-timeline.php          # NUEVO - Vista de historial
```

---

## 🏆 Visión Final

> *"Un sistema donde cada documento cuenta su propia historia, desde que nace hasta que cumple su propósito, dejando un rastro inmutable de quién lo tocó, cuándo y por qué. Un sistema que no solo gestiona papeles, sino que acelera decisiones."*

---

## 📊 Resumen de Inversión

| Fase | Horas Est. | Semanas |
|------|------------|---------|
| Sprint 0: Preparación | 14 hrs | 1 |
| Fase 1: Piloto Suficiencias | 21 hrs | 2 |
| Fase 2: Aprobación Universal | 24 hrs | 2 |
| Fase 3: Sistema de Firma | 40 hrs | 3 |
| Fase 4: Gestión Externa | 21 hrs | 2 |
| Fase 5: Cierre y Archivo | 26 hrs | 2 |
| Fase 6: UX Premium | 23 hrs | 1 |
| **TOTAL** | **169 hrs** | **13 semanas** |

---

**🚀 ¿Listo para transformar la gestión documental de SECOPE?**

*Plan establecido el 28 de Enero de 2026 - v2.0*
