# 📄 Oficio con Nombres Dinámicos de Firmantes

**Actualización:** 28 de Enero de 2026  
**Archivo modificado:** `modulos/recursos-financieros/generar-oficio.php`

---

## ✅ **Problema Solucionado**

**ANTES:**
- Los nombres en el oficio estaban **hardcodeados** (fijos)
- Siempre aparecían los mismos funcionarios sin importar quién firmaba
- No reflejaba la configuración real del flujo de firmas

**AHORA:**
- Los nombres se obtienen **dinámicamente** del flujo de firmas configurado
- Muestra los funcionarios que realmente están involucrados en el documento
- Es **congruente** con el sistema de firmas electrónicas

---

## 🎯 **Cómo Funciona Ahora**

### **1. Consulta del Documento y Firmantes**

Cuando se genera un oficio, el sistema:

```sql
1. Obtiene la solicitud de suficiencia
2. Busca el documento asociado
3. Consulta el flujo de firmas configurado
4. Extrae nombres y cargos de los firmantes
```

### **2. Asignación de Roles**

| Posición en Flujo | Rol en Oficio | Descripción |
|-------------------|---------------|-------------|
| **Primer firmante** | REMITENTE | Quien solicita/genera el oficio |
| **Último firmante** | DESTINATARIO | Quien autoriza la suficiencia |

**Ejemplo:**

Si configuraste este flujo:
```
1. Juan Pérez (SOLICITA) - Jefe de Departamento
2. María García (REVISA) - Coordinadora
3. Carlos López (AUTORIZA) - Director
```

El oficio mostrará:
```
Remitente: JUAN PÉREZ - JEFE DE DEPARTAMENTO
Destinatario: CARLOS LÓPEZ - DIRECTOR
```

---

## 📋 **Estructura del Oficio**

```
┌─────────────────────────────────────────┐
│    DIRECCIÓN DE CAMINOS                 │
│    Oficio No. DC/123/2026               │
├─────────────────────────────────────────┤
│                                         │
│ [DESTINATARIO - Último firmante]        │
│ CARLOS LÓPEZ                            │
│ DIRECTOR                                │
│ P R E S E N T E .                       │
│                                         │
├─────────────────────────────────────────┤
│                                         │
│ [CUERPO DEL OFICIO]                     │
│ Por medio de la presente me permito     │
│ solicitar suficiencia presupuestal...   │
│                                         │
├─────────────────────────────────────────┤
│                                         │
│        A T E N T A M E N T E            │
│  Victoria de Durango, Dgo., a [fecha]   │
│                                         │
│        [FIRMA DEL REMITENTE]            │
│                                         │
│ [REMITENTE - Primer firmante]           │
│ JUAN PÉREZ                              │
│ JEFE DE DEPARTAMENTO                    │
│                                         │
└─────────────────────────────────────────┘
```

---

## 🔍 **Lógica de Obtención de Nombres**

### **Prioridad de Fuentes:**

1. **Flujo de firmas del documento** (Principal)
   - Obtiene nombres de `documento_flujo_firmas`
   - Usa datos reales de la tabla `empleados`
   - Lee el campo `cargo` del empleado

2. **Parámetros GET** (Override manual)
   - Permite personalizar para casos especiales
   - Ejemplo: `?dest_nom=Nombre&dest_car=Cargo`

3. **Valores por defecto** (Fallback)
   - Si no hay flujo configurado
   - Si el documento aún no existe
   - Nombres hardcodeados como última opción

---

## 💾 **Datos Obtenidos de la Base de Datos**

```sql
-- Consulta principal
SELECT 
    dff.orden,                    -- Orden en el flujo
    dff.rol_firmante,             -- Rol: SOLICITA, REVISA, AUTORIZA
    e.nombres,                    -- Nombre(s) del empleado
    e.apellido_paterno,           -- Apellido paterno
    e.apellido_materno,           -- Apellido materno
    e.cargo,                      -- Cargo oficial
    u.usuario,                    -- Usuario del sistema
    dff.estatus                   -- Estado de la firma
FROM documento_flujo_firmas dff
JOIN empleados e ON e.id = dff.firmante_id
LEFT JOIN usuarios_sistema u ON u.id_empleado = e.id
WHERE dff.documento_id = ?
ORDER BY dff.orden ASC
```

---

## 🎨 **Formato de Nombres**

### **Nombre Completo:**
```php
$nombre = trim($nombres . ' ' . $apellido_paterno . ' ' . $apellido_materno);
```

**Ejemplo:**
- BD: `nombres="Juan Carlos"`, `apellido_paterno="Pérez"`, `apellido_materno="Gómez"`
- Oficio: `JUAN CARLOS PÉREZ GÓMEZ`

### **Cargo:**
```php
$cargo = $empleado['cargo'] ?: $empleado['rol_firmante'];
```

**Ejemplo:**
- Si hay cargo en BD: usa el cargo oficial
- Si no hay cargo: usa el rol del flujo ("AUTORIZA", "REVISA", etc.)

---

## 🧪 **Cómo Probar**

### **1. Crear Suficiencia con Flujo de Firmas**

```
1. Ve a: solicitud-suficiencia-form.php
2. Llena el formulario
3. Click en "GUARDAR Y CONFIGURAR FIRMAS"
4. Selecciona firmantes:
   - Primer firmante: Quien solicita
   - Último firmante: Quien autoriza
5. Guarda el flujo
```

### **2. Generar el Oficio**

```
1. Ve a: bandeja-gestion.php
2. Localiza la solicitud creada
3. Click en botón "Oficio" (📄)
4. Verifica que aparezcan:
   ✓ Nombres correctos
   ✓ Cargos correctos
   ✓ Congruente con el flujo configurado
```

### **3. Verificar el PDF**

El PDF debe mostrar:
- **Destinatario:** Nombre y cargo del último firmante
- **Remitente:** Nombre y cargo del primer firmante
- **Firma:** Del usuario que genera el oficio

---

## 🔄 **Override Manual (Casos Especiales)**

Si necesitas cambiar los nombres temporalmente (sin modificar el flujo):

```
generar-oficio.php?id=123
    &dest_nom=C.P. MARLEN SÁNCHEZ GARCÍA
    &dest_car=DIRECTORA DE ADMINISTRACIÓN
    &rem_nom=ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ
    &rem_car=SUBSECRETARIO
```

Esto sobrescribirá los nombres del flujo solo para este PDF.

---

## ⚙️ **Configuración Multi-Firmantes**

### **Escenario 1: Un solo firmante**
```
Firmante: Juan Pérez - AUTORIZA
```
**Resultado:**
- Destinatario: Juan Pérez
- Remitente: Juan Pérez

### **Escenario 2: Dos firmantes**
```
1. Juan Pérez - SOLICITA
2. María García - AUTORIZA
```
**Resultado:**
- Destinatario: María García (último)
- Remitente: Juan Pérez (primero)

### **Escenario 3: Tres o más firmantes**
```
1. Juan Pérez - SOLICITA
2. Carlos López - REVISA
3. María García - REVISA
4. Ana Torres - AUTORIZA
```
**Resultado:**
- Destinatario: Ana Torres (último)
- Remitente: Juan Pérez (primero)
- Los revisores intermedios no aparecen en el oficio

---

## 📊 **Tabla de Mapeo**

| Campo BD | Campo Oficio | Ubicación en PDF |
|----------|--------------|------------------|
| `primer_firmante.nombres + apellidos` | Remitente Nombre | Pie del oficio |
| `primer_firmante.cargo` | Remitente Cargo | Pie del oficio |
| `ultimo_firmante.nombres + apellidos` | Destinatario Nombre | Encabezado |
| `ultimo_firmante.cargo` | Destinatario Cargo | Encabezado |
| `usuario_actual.firma_imagen` | Firma Digital | Sobre nombre remitente |

---

## ✅ **Ventajas de Este Sistema**

1. **Dinámico:** Se adapta automáticamente al flujo configurado
2. **Congruente:** Los nombres coinciden con los firmantes reales
3. **Auditable:** Queda registro de quién firmó qué
4. **Flexible:** Permite override manual si es necesario
5. **Consistente:** Mismos nombres en todo el sistema

---

## 🔐 **Seguridad y Validación**

- ✅ Verifica que el documento exista
- ✅ Valida que haya firmantes configurados
- ✅ Usa valores por defecto si falta información
- ✅ Escapa caracteres especiales en nombres
- ✅ Trim de espacios extra

---

## 🚀 **Próximas Mejoras Sugeridas**

1. **Mostrar todos los firmantes intermedios**
   - Agregar sección de "Visto Bueno"
   - Listar revisores

2. **Incluir firmas de todos**
   - No solo del remitente
   - Firmas de autorizadores

3. **Fechas de firma**
   - Mostrar cuándo firmó cada uno
   - Timestamps en el documento

4. **Sello digital**
   - Código QR con hash
   - Validación en línea

---

## 📝 **Ejemplo Real**

### **Flujo Configurado:**
```
1. Ing. César Rodríguez - SOLICITA - Subsecretario
2. C.P. Marlen Sánchez - AUTORIZA - Directora
```

### **Oficio Generado:**
```
DIRECCIÓN DE CAMINOS
Oficio No. DC/0034/2026

C.P. MARLEN SÁNCHEZ GARCÍA
DIRECTORA DE ADMINISTRACIÓN
P R E S E N T E .

Por medio de la presente me permito solicitar 
suficiencia presupuestal para "CONSTRUCCIÓN DE 
CAMINO RURAL", por un importe de $1,500,000.00...

            A T E N T A M E N T E
    Victoria de Durango, Dgo., a 28 de enero de 2026

            [FIRMA DIGITAL]

       ING. CÉSAR OTHÓN RODRÍGUEZ GÓMEZ
    SUBSECRETARIO DE INFRAESTRUCTURA CARRETERA
```

---

**¡Sistema actualizado! Los oficios ahora muestran los nombres configurados en el flujo de firmas. ✅📄**

*Última actualización: 28/01/2026 15:15*
