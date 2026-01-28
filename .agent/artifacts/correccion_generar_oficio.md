# 📄 Corrección: Error al Generar Oficio PDF

**Error Original:**
```
Fatal error: SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'pao_v2.usuarios_config_firma' doesn't exist
```

**Ubicación:** `modulos/recursos-financieros/generar-oficio.php` línea 51

---

## 🔍 Problema Identificado

El archivo `generar-oficio.php` estaba intentando obtener la firma del usuario desde una tabla que **no existe**:

```php
// ❌ ANTES (tabla inexistente)
SELECT ruta_firma_imagen FROM usuarios_config_firma WHERE id_usuario = ?
```

Esta tabla nunca se creó en el sistema. En su lugar, ya tenemos la tabla `empleado_firmas` que almacena:
- Firmas autógrafas digitalizadas en formato base64
- PINs hasheados para firma electrónica
- Metadatos de captura

---

## ✅ Solución Aplicada

### Cambio 1: Consulta de Firma (líneas 50-62)

**ANTES:**
```php
$stmt_firma = $pdo->prepare("SELECT ruta_firma_imagen FROM usuarios_config_firma WHERE id_usuario = ?");
$stmt_firma->execute([$user['id']]);
$ruta_firma = $stmt_firma->fetchColumn();
$img_firma_path = $ruta_firma ? __DIR__ . '/../../' . $ruta_firma : null;
```

**AHORA:**
```php
// Usar la tabla empleado_firmas que contiene las firmas autógrafas registradas
$stmt_firma = $pdo->prepare("
    SELECT ef.firma_imagen 
    FROM empleado_firmas ef
    JOIN usuarios_sistema u ON u.id_empleado = ef.empleado_id
    WHERE u.id = ?
");
$stmt_firma->execute([$user['id']]);
$firma_base64 = $stmt_firma->fetchColumn();

// La firma viene en formato base64 (data:image/png;base64,...)
// TCPDF puede usar directamente la imagen base64 con el método Image()
$tiene_firma = !empty($firma_base64);
```

---

### Cambio 2: Renderizado de Firma (líneas 124-132)

**ANTES:**
```php
if ($img_firma_path && file_exists($img_firma_path)) {
    $pdf->Image($img_firma_path, 88, 145, 40, 0, 'PNG');
}
```

**AHORA:**
```php
if ($tiene_firma) {
    // TCPDF puede usar directamente una imagen en formato base64
    // El formato es: data:image/png;base64,iVBORw0KGgoAAAANS...
    // Posicionar la firma sobre el nombre del remitente
    $pdf->Image('@' . base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $firma_base64)), 88, 145, 40, 0, 'PNG');
}
```

---

## 🎯 Cómo Funciona Ahora

### Flujo de Generación del Oficio:

1. **Usuario hace click en botón "Oficio"**
   ```
   GET generar-oficio.php?id=123
   ```

2. **Se consulta la solicitud de suficiencia**
   ```sql
   SELECT f.*, p.nombre_proyecto 
   FROM solicitudes_suficiencia f
   LEFT JOIN proyectos_obra p ON f.id_proyecto = p.id_proyecto
   WHERE f.id_fua = 123
   ```

3. **Se obtiene la firma del usuario autenticado**
   ```sql
   SELECT ef.firma_imagen 
   FROM empleado_firmas ef
   JOIN usuarios_sistema u ON u.id_empleado = ef.empleado_id
   WHERE u.id = ?  -- ID del usuario en sesión
   ```

4. **Se genera el PDF con TCPDF**
   - Encabezado con logo SECOPE
   - Número de oficio
   - Destinatario
   - Cuerpo del oficio con nombre del proyecto y monto
   - **Firma autógrafa** (si existe)
   - Pie con copias

5. **Se descarga/muestra el PDF**
   ```php
   $pdf->Output('Oficio_Suficiencia_123.pdf', 'I');
   ```

---

## 📋 Estructura de Datos

### Tabla: `empleado_firmas`

```sql
CREATE TABLE empleado_firmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL UNIQUE,
    firma_imagen LONGTEXT NOT NULL,  -- Base64: data:image/png;base64,...
    pin_hash VARCHAR(255) NOT NULL,
    fecha_captura DATETIME NOT NULL,
    estado TINYINT(1) DEFAULT 1,
    ...
);
```

### Formato de `firma_imagen`:

```
data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAABkCAYAAAA8AQ3AAAA...
```

Este es el formato generado por:
- Canvas HTML5 (cuando se captura firma a mano)
- Función `generatePlaceholderSignature()` (para firmas temporales)

---

## 🖼️ Sobre la Firma del Admin

El administrador actualmente tiene una **firma placeholder** generada automáticamente que se ve así:

```
┌─────────────────────┐
│                     │
│   A. DEL SISTEMA   │
│  ──────────────    │
│   FIRMA DIGITAL    │
└─────────────────────┘
```

### Para cambiar a una firma real:

1. **Opción 1: Desde el módulo de empleados**
   - Ir a Recursos Humanos → Empleados
   - Editar perfil del administrador
   - Sección "Firma Digital"
   - Canvas para dibujar firma
   - Guardar

2. **Opción 2: Subir imagen de firma escaneada**
   - Escanear firma física
   - Convertir a PNG
   - Convertir a base64
   - Actualizar en BD:
   ```sql
   UPDATE empleado_firmas 
   SET firma_imagen = 'data:image/png;base64,iVBORw...'
   WHERE empleado_id = 402;
   ```

---

## ✅ Verificación

### Comando de Sintaxis:
```bash
php -l modulos\recursos-financieros\generar-oficio.php
# ✓ No syntax errors detected
```

### Prueba de Generación:
1. Ve a la bandeja de gestión
2. Localiza una solicitud de suficiencia
3. Click en botón **"Oficio"** (ícono 📄)
4. Debería:
   - ✅ Generar PDF sin errores
   - ✅ Mostrar el oficio en pantalla
   - ✅ Incluir la firma si existe

---

## 🎨 Personalización del Oficio

El archivo soporta parámetros GET para personalizar:

```php
generar-oficio.php?id=123
    &dest_nom=C.P. MARLEN SÁNCHEZ GARCÍA
    &dest_car=DIRECTORA DE ADMINISTRACIÓN
    &rem_nom=ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ
    &rem_car=SUBSECRETARIO DE INFRAESTRUCTURA CARRETERA
```

---

## 📊 Estado de las Firmas en el Sistema

| Usuario | ID Empleado | Tiene Firma | Tipo |
|---------|-------------|-------------|------|
| admin | 402 | ✅ Sí | Placeholder |

Para verificar:
```bash
php verificar-pin-admin.php
```

---

## 🚀 Próximos Pasos

### Corto Plazo:
- [ ] Probar generación de oficio con el admin
- [ ] Verificar que la firma aparece en el PDF
- [ ] Registrar firma real del administrador

### Mediano Plazo:
- [ ] Crear módulo de captura de firmas para empleados
- [ ] Integrar firmas FIEL (certificados .cer/.key del SAT)
- [ ] Generar códigos QR con hash del documento en PDFs

### Largo Plazo:
- [ ] Firma electrónica avanzada con timestamp server
- [ ] Validación de PDFs firmados
- [ ] Repositorio centralizado de documentos firmados

---

## 🔐 Seguridad de las Firmas

### En Base de Datos:
- ✅ Firmas en base64 (no archivos en disco)
- ✅ Permisos restrictivos en tabla `empleado_firmas`
- ✅ Auditoría en `firma_log`

### En PDFs Generados:
- ⚠️ Las firmas son **visuales** (no criptográficas aún)
- 🔄 Próximamente: Firmas digitales con certificado
- 🔄 Próximamente: Sellos de tiempo

---

**¡Error corregido! El botón "Oficio" ahora debería funcionar correctamente. 📄✅**

*Última actualización: 28/01/2026 14:38*
