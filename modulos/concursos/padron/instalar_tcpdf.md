# Instalación de TCPDF para Generación de Certificados - PRODUCCIÓN

## Para Servidor de Producción (padron.gusati.net)

1. **Descargar TCPDF desde GitHub:**
   ```
   https://github.com/tecnickcom/TCPDF/releases
   ```

2. **Subir al servidor de producción:**
   - Extraer el archivo ZIP
   - Subir la carpeta `tcpdf` al directorio raíz del proyecto
   - Ruta esperada: `/tcpdf/` (en el directorio raíz del sitio)

3. **Estructura de carpetas en producción:**
   ```
   / (directorio raíz del sitio)
   ├── tcpdf/
   │   ├── tcpdf.php
   │   ├── config/
   │   ├── fonts/
   │   └── ...
   ├── generar_certificado.php
   ├── validar_certificado.php
   ├── dashboard.php
   └── ...
   ```

## Configuración

1. **Verificar permisos de escritura** en la carpeta `tcpdf/cache/`

2. **Configurar la ruta** en `generar_certificado.php`:
   ```php
   require_once('tcpdf/tcpdf.php');
   ```

## Funcionalidades Implementadas

### ✅ Generador de PDF
- **Formato exacto** del certificado del padrón
- **Todos los campos** del certificado original
- **Código QR** con URL de validación
- **Firma digital** y datos de la autoridad

### ✅ Módulo Validador Público
- **Validación por número** de certificado
- **Validación por QR** (escaneo automático)
- **Verificación de vigencia** y autenticidad
- **Interfaz moderna** y responsive

### ✅ Seguridad
- **Hash SHA-256** para cada certificado
- **Validación de vigencia** automática
- **URL única** para cada certificado
- **Sin necesidad de login** para validar

## Pruebas

1. **Crear un certificado** usando `admin_certificados.php`
2. **Generar PDF** desde el dashboard del usuario
3. **Validar certificado** usando `validar_certificado.php`
4. **Escanear QR** con dispositivo móvil

## Notas Importantes

- El QR contiene la URL completa para validación
- Cada certificado tiene un hash único
- La validación es pública (sin login requerido)
- Los certificados vencidos se marcan claramente
