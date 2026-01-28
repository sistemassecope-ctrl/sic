# 🔧 Guía de Diagnóstico y Solución - Error de Conexión en Firma

**Problema reportado:** "Error de conexión" al ingresar PIN para firmar documento  
**Fecha:** 28 de Enero de 2026

---

## ✅ Correcciones Aplicadas

### 1. **Importaciones Faltantes**

#### `SignatureFlowService.php`
- ❌ **Antes:** No importaba `DocumentoService.php`
- ✅ **Ahora:** Agregado `require_once __DIR__ . '/DocumentoService.php';`

#### `procesar-firma.php`
- ❌ **Antes:** No importaba `DocumentoService.php`
- ✅ **Ahora:** Agregado `require_once __DIR__ . '/../../includes/services/DocumentoService.php';`

---

## 🧪 Cómo Verificar la Solución

### Método 1: Página de Prueba (Recomendado)

1. Abre en tu navegador:
   ```
   http://localhost/pao/test-firma-ajax.html
   ```

2. Verás un formulario con:
   - Campo "ID del Flujo de Firma" (puedes dejar 1)
   - Campo "PIN" (usa **1234**)

3. Click en **"Probar Firma"**

4. **Resultados esperados:**
   - ✅ **Éxito:** "ID de flujo inválido" o "Documento firmado"
   - ❌ **Error:** "Error de conexión" significa que aún hay un problema

---

### Método 2: Consola del Navegador

1. Abre la **bandeja de gestión:**
   ```
   http://localhost/pao/modulos/recursos-financieros/bandeja-gestion.php
   ```

2. Presiona **F12** para abrir Developer Tools

3. Ve a la pestaña **"Console"**

4. Intenta firmar un documento con PIN **1234**

5. Observa los mensajes en la consola:
   - Debe mostrar: `Enviando solicitud a: procesar-firma.php`
   - Debe mostrar: `Respuesta recibida: 200 OK` o el código de estado
   - Si hay error, mostrará el mensaje exacto

---

### Método 3: Comprobar Archivos PHP

Ejecuta en terminal PowerShell:

```powershell
cd c:\wamp64\www\pao

# Verificar sintaxis
php -l includes\services\SignatureFlowService.php
php -l includes\services\DocumentoService.php
php -l modulos\recursos-financieros\procesar-firma.php

# Deberías ver: "No syntax errors detected in..."
```

---

## 🔍 Diagnóstico de Errores Comunes

### Error 1: "Error de conexión"
**Causa:** El archivo PHP tiene un error de sintaxis o ruta incorrecta  
**Solución:**
```powershell
php test-endpoint-firma.php
```
Esto te dirá exactamente qué archivo está fallando.

---

### Error 2: "ID de flujo inválido"
**Causa:** No hay documentos con firmas pendientes  
**Solución:** Este es un error normal si no has creado un documento aún. Significa que el endpoint SÍ está funcionando.

---

### Error 3: "PIN incorrecto"
**Causa:** El PIN no coincide con el hash en la BD  
**Solución:** Verifica el PIN con:
```powershell
php verificar-pin-admin.php
```

---

### Error 4: "No se encuentra firma registrada"
**Causa:** El usuario no tiene PIN registrado  
**Solución:**
```powershell
php generar-pin-admin.php
```

---

## 📋 Checklist de Verificación

Marca cuando completes cada paso:

- [ ] Ejecutar `php test-endpoint-firma.php` sin errores
- [ ] Abrir `test-firma-ajax.html` en el navegador
- [ ] Probar con PIN 1234
- [ ] Ver respuesta JSON (aunque sea error de flujo inválido)
- [ ] Abrir consola del navegador (F12)
- [ ] Intentar firmar desde la bandeja real
- [ ] Verificar que no dice "Error de conexión"

---

## 🚀 Crear un Documento de Prueba

Si no tienes documentos con firma pendiente, crea uno:

### Opción A: Crear Suficiencia Presupuestal
1. Ve a: `http://localhost/pao/modulos/recursos-financieros/solicitud-suficiencia-form.php`
2. Llena el formulario
3. Click en **"GUARDAR Y CONFIGURAR FIRMAS"**
4. Selecciona al administrador como firmante
5. Asigna rol: "AUTORIZA"
6. Click en **"INICIAR FLUJO"**

### Opción B: Script SQL Directo
```sql
-- Crear un documento de prueba con firma pendiente
INSERT INTO documentos (
    tipo_documento_id, folio_sistema, titulo, 
    usuario_generador_id, fase_actual, estatus
) VALUES (
    1, 'TEST-2026-0001', 'Documento de Prueba',
    1, 'firmas', 'pendiente'
);

SET @doc_id = LAST_INSERT_ID();

-- Crear paso de firma para el admin
INSERT INTO documento_flujo_firmas (
    documento_id, orden, firmante_id, rol_firmante,
    estatus, tipo_firma, fecha_asignacion
) VALUES (
    @doc_id, 1, 1, 'AUTORIZA',
    'pendiente', 'pin', NOW()
);

-- Asignar a bandeja del admin
INSERT INTO usuario_bandeja_documentos (
    usuario_id, documento_id, tipo_accion_requerida,
    flujo_firma_id, prioridad
) VALUES (
    1, @doc_id, 'firmar', LAST_INSERT_ID(), 2
);
```

---

## 📞 Si el Problema Persiste

### 1. Verificar Logs de Apache/PHP
```powershell
# En WAMP, los logs están en:
notepad C:\wamp64\logs\php_error.log
notepad C:\wamp64\logs\apache_error.log
```

### 2. Activar Display Errors (temporal)
Edita `php.ini`:
```ini
display_errors = On
error_reporting = E_ALL
```
Reinicia Apache y prueba nuevamente.

### 3. Captura de Pantalla
Toma captura de:
- Consola del navegador (F12 → Console)
- Pestaña Network (F12 → Network → Click en `procesar-firma.php`)
- El mensaje de error exacto

---

## ✨ Archivos de Soporte Creados

1. **`test-endpoint-firma.php`**
   - Verifica que el endpoint existe
   - Comprueba sintaxis PHP
   - Prueba carga del archivo

2. **`test-firma-ajax.html`**
   - Interfaz visual para probar AJAX
   - Muestra errores detallados
   - No requiere autenticación

3. **`verificar-pin-admin.php`**
   - Confirma que el PIN está registrado
   - Prueba el hash
   - Muestra logs de firma

4. **`generar-pin-admin.php`**
   - Crea/actualiza el PIN
   - Genera firma placeholder
   - Registra en auditoría

---

## 🎯 Resumen

**Cambios aplicados:**
- ✅ Agregado `DocumentoService.php` a `SignatureFlowService.php`
- ✅ Agregado `DocumentoService.php` a `procesar-firma.php`
- ✅ Creadas herramientas de diagnóstico

**Siguiente paso:**
1. Ejecuta `php test-endpoint-firma.php`
2. Abre `test-firma-ajax.html` en el navegador
3. Prueba con PIN 1234
4. Si aún falla, revisa logs de PHP

---

**¡La solución está aplicada! Prueba  ahora con la página de test. 🚀**

*Última actualización: 28/01/2026 14:33*
