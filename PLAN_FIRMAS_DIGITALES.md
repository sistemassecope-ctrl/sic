# Plan de Acción: Módulo de Firmas Digitales Remotas y Estampado Seguro

## 1. Visión y Objetivos
Implementar un ecosistema de **Firmas Digitales** que permita a los funcionarios revisar y autorizar documentos oficiales desde cualquier ubicación y dispositivo (móvil/desktop), garantizando la identidad del firmante y la integridad del documento.

El sistema soportará una evolución escalonada:
1.  **Firma Electrónica Simple (Fase Inicial)**: Validez legal basada en usuario/contraseña + PIN de seguridad + Estampado visual de rúbrica.
2.  **Firma Electrónica Avanzada (Futuro)**: Integración con certificados criptográficos (e.firma SAT o interna).

---

## 2. Arquitectura de Seguridad

### 2.1 Identidad del Firmante
Para asegurar que "quien firma es quien dice ser", implementaremos:
-   **Doble Factor (Simplificado)**: Al momento de firmar, no basta con estar logueado. Se solicitará un **PIN DE FIRMA** (6 dígitos) exclusivo para autorizaciones, distinto a la contraseña de acceso.
-   **Registro de Auditoría**: Se guardará en la base de datos `archivo_firmas`:
    -   IP de origen.
    -   User Agent (Dispositivo/Navegador).
    -   Geolocalización aproximada (si el dispositivo lo permite).
    -   Timestamp exacto.

### 2.2 Integridad del Documento
-   Se usará el **Hash SHA-256** del documento original para asegurar que lo que se firma es exactamente lo que se ve.
-   Cualquier modificación posterior al documento invalidará las firmas previas (el hash cambiaría).

---

## 3. Módulo de Configuración de Firma (Perfil del Usuario)

Antes de poder firmar, cada usuario deberá configurar su **"Billetera de Identidad"**:

1.  **Carga de Rúbrica Visual**:
    -   Input para subir una imagen (PNG/JPG) de su firma autógrafa con fondo transparente.
    -   Soporte para "Dibujar en pantalla" (Canvas) desde una tablet o móvil si no tienen imagen.
    -   Esta imagen se protege en el servidor y SOLO el sistema puede estamparla.
2.  **Configuración de PIN**:
    -   Definir su PIN de 6 dígitos de seguridad personal.

---

## 4. Flujo de Trabajo Remoto (Mobile First)

El módulo se diseñará con interfaz **Responsive** para facilitar el uso en celulares.

### Paso 1: Notificación
-   El sistema detecta documentos en estado `EN_FIRMA`.
-   Envía correo electrónico/notificación: *"Tiene documentos pendientes de firma"*.

### Paso 2: Bandeja de Firmas (Móvil)
-   Lista limpia de documentos pendientes.
-   Botón **"Revisar"**: Abre el PDF en un visor compatible con móvil.

### Paso 3: Acción de Firma
-   Botón **"FIRMAR DOCUMENTO"**.
-   Modal de Seguridad:
    -   Muestra resumen del documento.
    -   Solicita **PIN DE FIRMA**.
    -   (Opcional) Checkbox: *"Declaro bajo protesta de decir verdad que..."*
-   Al confirmar:
    -   El backend valida el PIN.
    -   Registra la firma lógica en la BD (`archivo_firmas`).
    -   **Dispara el proceso de Estampado Visual (Ver punto 5).**

---

## 5. Motor de Estampado (Paso "Hardcore")

Una vez que todas las firmas requeridas están listas (o tras cada firma, según se defina), el sistema genera una **Nueva Versión del PDF** (`TCPDF` / `FPDF`).

### ¿Qué agrega el estampado?
1.  **Hoja de Firmas (Recomendado)**: Se agrega una página final al PDF.
2.  **Bloque de Firma**:
    -   Nombre del Funcionario.
    -   Cargo.
    -   **Rúbrica (Imagen)** procesada.
    -   Hash de la firma (cadena corta de validación).
    -   Fecha y Hora (UTC/Local).
3.  **Sello de Tiempo y QR**:
    -   Un código QR que lleva a la URL de validación pública (ej. `sistemas.gob.mx/validar?uuid=...`).
    -   Cadena de caracteres de autenticidad (Sello Digital del Sistema).

---

## 6. Hoja de Ruta de Implementación (Fases)

### Fase A: Preparación de Usuarios (Semana 1)
-   [x] Crear tabla `usuarios_config_firma` (PIN, Ruta Imagen Rúbrica).
-   [x] Crear vista "Mi Firma Digital" para que los usuarios suban su imagen y definan PIN.

### Fase B: Lógica de Firma (Semana 2)
-   [ ] Endpoint para validar PIN.
-   [ ] Servicio `SignatureService`:
    -   Método `signDocument($idDoc, $idUser, $pin)`.
    -   Validaciones de seguridad (Estado documento, Permisos).

### Fase C: Motor de Estampado PDF (Semana 3)
-   [ ] Integrar librería PDF (TCPDF sugerido por soporte de imágenes y HTML).
-   [ ] Crear plantilla visual para la "Hoja de Firmas".
-   [ ] Lógica para clonar el PDF original, agregar la hoja y guardar como nueva versión.

### Fase D: Interfaz Móvil "Bandeja de Firmas" (Semana 4)
-   [ ] Vista simplificada para celulares.
-   [ ] Visor de PDF embebido o descargable temporal.

---

## 7. Consideraciones Futuras (Firma Avanzada)

Para migrar a Firma Electrónica Avanzada (e.firma):
-   El sistema ya estará listo estructuralmente.
-   Solo se cambiará el **Método de Autenticación** en el paso 3: en lugar de pedir PIN, se pedirá subir el archivo `.key` y la contraseña de la clave privada, o firmar mediante un app externa.
-   El estampado visual dirá "Firmado electrónicamente con certificado SAT".
