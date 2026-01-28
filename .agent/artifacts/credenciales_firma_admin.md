# 🔐 Credenciales de Firma Digital - Administrador

**Fecha de generación:** 28 de Enero de 2026  
**Usuario:** admin  
**Empleado ID:** 402

---

## ✅ PIN Generado

```
PIN: 1234
```

> ⚠️ **IMPORTANTE:** Este es el PIN por defecto. Se recomienda cambiarlo después del primer uso.

---

## 📋 Información de la Firma Digital

| Campo | Valor |
|-------|-------|
| **Usuario** | admin |
| **Empleado** | Administrador del Sistema |
| **ID Empleado** | 402 |
| **Estado** | ACTIVO ✅ |
| **Tipo de Firma** | Placeholder temporal (cambiar por firma real) |
| **Método de Hash** | BCRYPT (seguro) |

---

## 🎯 Cómo Usar el PIN

### Para Firmar un Documento:

1. Ve a la **Bandeja de Gestión**
2. Localiza el documento con el badge pulsante de "Firma Pendiente"
3. Click en el botón **"Firmar"** (ícono de pluma)
4. Selecciona la pestaña **"Digital"** (PIN)
5. Ingresa el PIN: **1234**
6. Click en **"AUTORIZAR CON PIN"**
7. El documento se firmará automáticamente

---

## 🔄 Cómo Cambiar el PIN

### Método 1: Desde el módulo de empleados
1. Ve a **Recursos Humanos → Empleados**
2. Edita el empleado "Administrador del Sistema"
3. En la sección de **Firma Digital**, haz click en **"Cambiar PIN"**
4. Ingresa el PIN actual (1234)
5. Ingresa el nuevo PIN (4-6 dígitos)
6. Confirma el nuevo PIN
7. Guarda los cambios

### Método 2: Re-ejecutar el script
1. Edita el archivo `generar-pin-admin.php`
2. Cambia la línea: `$PIN_ADMIN = '1234';` por tu nuevo PIN
3. Ejecuta: `php generar-pin-admin.php`
4. El sistema preguntará si deseas actualizar (presiona ENTER)

---

## 🖼️ Registrar Firma Real

La firma actual es un **placeholder temporal**. Para registrar tu firma manuscrita real:

1. Ve a **Recursos Humanos → Empleados**
2. Edita tu perfil de empleado
3. En la sección **"Firma Digital"**, haz click en **"Capturar Firma"**
4. Usa el canvas para dibujar tu firma con el mouse/stylus
5. Ajusta el grosor y color si lo deseas
6. Click en **"Guardar Firma"**
7. La firma se guardará como imagen PNG en base64

---

## 🔒 Seguridad

### Características de Seguridad Implementadas:

- ✅ **Hash BCRYPT:** El PIN nunca se guarda en texto plano
- ✅ **Intentos Fallidos:** Se bloquea temporalmente después de 3 intentos fallidos
- ✅ **Auditoría Completa:** Cada uso del PIN se registra en `firma_log`
- ✅ **Bitácora Inmutable:** Las firmas se registran en `documento_bitacora`
- ✅ **Timestamp Preciso:** Microsegundos para prevenir duplicados
- ✅ **IP y User-Agent:** Se registra origen de cada firma

### Recomendaciones:

1. 🔐 **Cambia el PIN** inmediatamente después del primer uso
2. 🤫 **Nunca compartas** tu PIN con nadie
3. 📝 **Usa un PIN memorable** pero no obvio (evita: 1234, 0000, fecha de nacimiento)
4. 🔄 **Cambia el PIN periódicamente** (cada 3-6 meses)
5. 🖼️ **Registra tu firma real** para documentos oficiales

---

## 📊 Logs y Auditoría

Todas las acciones con tu firma se registran en:

### Tabla: `firma_log`
```sql
SELECT * FROM firma_log WHERE empleado_id = 402 ORDER BY created_at DESC;
```

### Tabla: `documento_bitacora`
```sql
SELECT * FROM documento_bitacora 
WHERE usuario_id = 1 AND firma_tipo != 'ninguna' 
ORDER BY timestamp_evento DESC;
```

---

## 🆘 Solución de Problemas

### "PIN incorrecto"
- Verifica que estás ingresando: **1234**
- Asegúrate de no tener CAPS LOCK activado
- Después de 3 intentos fallidos, espera 15 minutos

### "Cuenta bloqueada"
- Espera el tiempo indicado en el mensaje
- O ejecuta: `UPDATE empleado_firmas SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE empleado_id = 402;`

### "No se encuentra firma registrada"
- Verifica que el empleado ID 402 existe en `empleados`
- Re-ejecuta el script: `php generar-pin-admin.php`

---

## 📞 Contacto

Para cualquier duda o problema con la firma digital:
- **Email:** soporte@sistema.gob.mx
- **Extensión:** 1001
- **Horario:** Lunes a Viernes, 9:00 - 18:00

---

**¡Tu firma digital está lista para usar! 🎉**

*Última actualización: 28/01/2026 14:29*
