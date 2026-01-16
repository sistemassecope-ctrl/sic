
# Plan de Trabajo: Sistema de Archivo Digital y Control Documental

## 1. Visión General
El objetivo es implementar un módulo centralizado de **Archivo Digital** que gestione el almacenamiento, versionado, y firma digital de documentos generados por distintos subsistemas (PAO, Recursos Financieros, Jurídico, etc.).

Este sistema está diseñado para ser escalable y desacoplado, permitiendo que cualquier módulo futuro interactúe con el archivo digital a través de una interfaz estandarizada.

---

## 2. Componentes Principales

### 2.1 Módulo `GestorDocumental` (Core)
Una clase o servicio central en PHP (`DigitalArchiveManager`) encargado de:
- **Almacenamiento Físico**: Guardar archivos en una estructura de carpetas organizada (ej. `/uploads/Y/M/ID/`).
- **Registro en BD**: Crear registros de metadatos únicos para cada archivo.
- **Hashing**: Generar y validar huellas digitales (SHA-256) para garantizar la integridad (que el archivo no ha sido modificado).
- **Control de Versiones**: Manejar múltiples versiones de un mismo documento lógico (v1.0 Borrador, v1.1 Revisado, v2.0 Final).

### 2.2 Sistema de Firmas Digitales
Un motor de firmas que soporta:
- **Firma Simple**: Registro de "Quién, Cuándo y Qué" autorizó en la base de datos.
- **Firma Criptográfica (Futuro)**: Posibilidad de integrar certificados digitales (.key/.cer) o e.firma (SAT).
- **Estampado Visual (Watermark)**: Generación de una nueva versión del PDF con la representación visual de las firmas y código QR de validación.

### 2.3 Interfaz de Validación Pública / Interna
- Un módulo para verificar la autenticidad de un documento impreso mediante **UUID** o **Código QR**.

---

## 3. Esquema de Base de Datos Propuesto

Se proponen las siguientes tablas nuevas en la base de datos `sic`:

### A. `archivo_documentos`
Tabla maestra de documentos.
| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_documento` | INT (PK) | Identificador interno. |
| `uuid` | VARCHAR(36) | Identificador único universal (público) y para QR. |
| `modulo_origen` | VARCHAR(50) | Ej: 'PAO_PROYECTOS', 'FUA', 'SUFICIENCIA'. |
| `referencia_id` | INT | ID del registro en la tabla origen (ej. `id_proyecto`). |
| `tipo_documento` | VARCHAR(50) | Ej: 'OFICIO_SOLICITUD', 'REPORTE_TECNICO'. |
| `nombre_archivo_original`| VARCHAR(255)| Nombre del archivo al subirlo. |
| `ruta_almacenamiento` | VARCHAR(255)| Ruta relativa en el servidor. |
| `hash_contenido` | CHAR(64) | Hash SHA-256 del contenido binario. |
| `estado` | ENUM | 'BORRADOR', 'FIRMADO', 'CANCELADO'. |
| `fecha_creacion` | DATETIME | Fecha de subida. |
| `id_usuario_creador` | INT | Usuario que subió el archivo. |

### B. `archivo_firmas`
Registro de firmas aplicadas a un documento.
| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_firma` | INT (PK) | Identificador de la firma. |
| `id_documento` | INT (FK) | Relación con el documento. |
| `id_usuario` | INT (FK) | Usuario firmante. |
| `rol_firmante` | VARCHAR(50) | Ej: 'DIRECTOR', 'REVISOR', 'ELABORÓ'. |
| `fecha_firma` | DATETIME | Momento exacto de la firma. |
| `hash_firma` | VARCHAR(255)| Hash resultante (firma criptográfica o hash documento + salt). |
| `metadata_firma`| JSON | Datos extra (IP, navegador, o datos del certificado). |
| `estado` | ENUM | 'VALIDA', 'REVOCADA'. |

---

## 4. Flujo de Trabajo (Workflow)

### Paso 1: Generación / Carga
1. El usuario genera un PDF (ej. Informe de Proyecto) desde el módulo PAO.
2. El sistema guarda el PDF físico en `uploads/2026/PAO/`.
3. Se inserta un registro en `archivo_documentos` con estado `BORRADOR` y su Hash SHA-256.

### Paso 2: Proceso de Firma
1. Los usuarios autorizados ven el documento pendiente de firma en su "Bandeja de Firmas".
2. Al firmar:
   - Se verifica que el Hash actual del archivo coincida con el registrado (integridad).
   - Se registra la firma en `archivo_firmas`.
   - Si es la última firma requerida, el documento pasa a estado `FIRMADO`.

### Paso 3: Estampado (Opcional)
1. Al completarse las firmas, el sistema genera una nueva versión del PDF.
2. Incrusta una hoja final o marcas de agua con los nombres, cargos, fechas y el QR de validación.
3. Se actualiza el registro o crea una versión final en `archivo_documentos`.

---

## 5. Estrategia de Implementación (Roadmap)

### Fase 1: Estructura Base (Semana 1)
- Crear tablas SQL (`archivo_documentos`, `archivo_firmas`).
- Crear clase PHP `DigitalArchiveService`.
- Implementar método `saveDocument($file, $metadata)`.

### Fase 2: Integración con PAO (Semana 2)
- Modificar módulo PAO para usar el servicio de archivo en lugar de guardar archivos sueltos.
- Migrar lógica de documentos adjuntos actuales.

### Fase 3: Módulo de Firmas (Semana 3)
- Interfaz UI para "Firmar Documento" (botón con confirmación y password).
- Lógica de backend para registrar firmas.
- Visualización de "Historial de Firmas" en el detalle del documento.

### Fase 4: Validación y QR (Semana 4)
- Generación de QR único por documento.
- Página pública de validación `verificar.php?uuid=...` que muestra si el documento es auténtico.

---

## 6. Consideraciones Técnicas

- **Rutas de Archivos**: Usar rutas absolutas configurables en un archivo `.env` o constante.
- **Seguridad**: La carpeta `uploads` NO debe estar en el root público web si es posible, o protegida con `.htaccess` para que solo PHP pueda servir los archivos tras verificar permisos.
- **Librerías**:
    - `TCPDF` o `FPDF`: Para generar los PDFs y estampar firmas.
    - `Endroid/QrCode`: Para generar códigos QR.
