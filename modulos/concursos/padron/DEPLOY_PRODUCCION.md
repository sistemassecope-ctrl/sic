# Despliegue en Producción - padron.gusati.net

## Archivos a Subir al Servidor

### 1. Archivos PHP Principales
```
├── generar_certificado.php
├── generar_certificado_simple.php
├── validar_certificado.php
├── admin_certificados.php
├── config_produccion.php
├── crear_tabla_certificados.sql
└── DEPLOY_PRODUCCION.md
```

### 2. Librería TCPDF
```
├── tcpdf/
│   ├── tcpdf.php
│   ├── config/
│   ├── fonts/
│   ├── cache/
│   └── ...
```

## Pasos para Despliegue

### 1. Subir Archivos
- Subir todos los archivos PHP al directorio raíz del sitio
- Subir la carpeta `tcpdf/` completa al directorio raíz

### 2. Ejecutar SQL
```sql
-- Ejecutar el archivo crear_tabla_certificados.sql en la base de datos
-- Esto creará la tabla certificados con todos los campos necesarios
```

### 3. Configurar Permisos
```bash
# Dar permisos de escritura a la carpeta cache de TCPDF
chmod 755 tcpdf/cache/
chmod 644 tcpdf/cache/*
```

### 4. Verificar Configuración
- ✅ HTTPS funcionando correctamente
- ✅ Base de datos conectada
- ✅ TCPDF instalado y funcionando
- ✅ URLs generándose correctamente

## URLs del Sistema

### URLs Principales
- **Dashboard**: `https://padron.gusati.net/dashboard.php`
- **Admin Certificados**: `https://padron.gusati.net/admin_certificados.php`
- **Validador Público**: `https://padron.gusati.net/validar_certificado.php`
- **Generar PDF**: `https://padron.gusati.net/generar_certificado_simple.php`

### URLs de Validación (QR)
- **Formato**: `https://padron.gusati.net/validar_certificado.php?hash=HASH_UNICO`
- **Ejemplo**: `https://padron.gusati.net/validar_certificado.php?hash=a1b2c3d4e5f6...`

## Configuraciones de Producción

### HTTPS
- ✅ Protocolo detectado automáticamente
- ✅ URLs de validación usan HTTPS
- ✅ Redirección automática a HTTPS

### Base de Datos
- ✅ Usar las credenciales de producción
- ✅ Tabla `certificados` creada
- ✅ Relación con `persona_fisica` establecida

### Seguridad
- ✅ Headers de seguridad configurados
- ✅ Errores ocultos en producción
- ✅ Validación de certificados pública

## Pruebas Post-Despliegue

### 1. Crear Certificado de Prueba
1. Ir a `admin_certificados.php`
2. Seleccionar un registro existente
3. Crear un certificado con papelería correcta

### 2. Generar PDF
1. Ir al dashboard del usuario
2. Hacer clic en "IMPRIMIR CERTIFICADO"
3. Verificar que se genere el PDF correctamente

### 3. Validar Certificado
1. Escanear el QR del PDF generado
2. Verificar que redirija a la página de validación
3. Confirmar que muestre la información correcta

## Solución de Problemas

### Error TCPDF
- Verificar que la carpeta `tcpdf/` esté en el directorio raíz
- Verificar permisos de la carpeta `cache/`

### Error de Conexión
- Verificar credenciales de base de datos
- Verificar que la tabla `certificados` exista

### QR No Funciona
- Verificar que las URLs se generen con HTTPS
- Verificar que el validador esté accesible públicamente

## Mantenimiento

### Logs
- Errores se guardan en `error.log`
- Verificar logs regularmente

### Backup
- Hacer backup de la tabla `certificados` regularmente
- Backup de archivos PHP antes de actualizaciones
