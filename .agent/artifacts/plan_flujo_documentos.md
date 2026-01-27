# 🚀 Plan de Desarrollo: Sistema de Gestión Documental con Ciclo de Vida Completo

> **Versión:** 1.0  
> **Autor:** Antigravity AI  
> **Fecha:** 27 de Enero de 2026  
> **Cliente:** SECOPE - Sistema de Control de Proyectos y Expedientes

---

## 📋 Resumen Ejecutivo

Este plan describe la implementación de un **Sistema de Gestión Documental Inteligente** que transformará la manera en que SECOPE maneja sus documentos oficiales. El sistema proporcionará trazabilidad completa, flujos de aprobación dinámicos, firma electrónica integrada y una experiencia de usuario premium.

---

## 🎯 Objetivos del Proyecto

### Objetivos Principales
1. **Trazabilidad Total**: Cada documento tendrá un historial completo desde su creación hasta su resolución
2. **Flujos Dinámicos**: Cadenas de aprobación configurables por tipo de documento
3. **Firma Electrónica**: Integración con FIEL para validación jurídica
4. **Cero Papel**: Digitalización completa del proceso documental
5. **Tiempo Real**: Notificaciones y alertas instantáneas

### Indicadores de Éxito (KPIs)
| Indicador | Meta | Plazo |
|-----------|------|-------|
| Tiempo promedio de aprobación | -60% | 3 meses |
| Documentos extraviados | 0% | Inmediato |
| Adopción del sistema | 95% usuarios | 6 meses |
| Satisfacción del usuario | >4.5/5 | 6 meses |

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

### Modelo de Datos Principal

```sql
-- Tabla Maestra de Documentos
documentos (
    id, tipo_documento_id, folio_sistema, folio_oficial,
    titulo, contenido_json, archivo_pdf, hash_integridad,
    fase_actual, estatus, prioridad,
    usuario_generador_id, fecha_generacion,
    usuario_aprobador_id, fecha_aprobacion,
    fecha_resolucion, resultado_final,
    metadata_json, created_at, updated_at
)

-- Bitácora de Vida (Immutable Log)
documento_bitacora (
    id, documento_id, fase_anterior, fase_nueva,
    accion, descripcion, observaciones,
    usuario_id, ip_address, user_agent,
    firma_electronica, timestamp_evento
)

-- Flujo de Firmas Dinámico
documento_flujo_firmas (
    id, documento_id, orden, firmante_id,
    rol_firmante, estatus, fecha_asignacion,
    fecha_firma, tipo_respuesta, observaciones,
    firma_fiel_hash, certificado_serial
)

-- Cola de Trabajo por Usuario
usuario_bandeja_documentos (
    id, usuario_id, documento_id,
    tipo_accion_requerida, prioridad, fecha_asignacion,
    fecha_limite, leido, procesado
)
```

---

## 📅 Cronograma de Desarrollo

### Fase 1: Fundamentos (Semanas 1-2)
> **Objetivo:** Establecer la infraestructura base del sistema documental

| Tarea | Descripción | Prioridad | Estimación |
|-------|-------------|-----------|------------|
| 1.1 | Diseño y creación de tablas en BD | 🔴 Alta | 4 hrs |
| 1.2 | Modelo PHP para `Documento` con métodos CRUD | 🔴 Alta | 6 hrs |
| 1.3 | Servicio de Bitácora automática (eventos) | 🔴 Alta | 4 hrs |
| 1.4 | Catálogo de Tipos de Documento | 🟡 Media | 3 hrs |
| 1.5 | Sistema de Folios únicos por tipo | 🟡 Media | 2 hrs |
| 1.6 | API REST para operaciones documentales | 🟡 Media | 4 hrs |

**Entregables:**
- ✅ Modelo de datos implementado
- ✅ API funcional para crear/consultar documentos
- ✅ Bitácora registrando eventos automáticamente

---

### Fase 2: Flujo de Aprobación (Semanas 3-4)
> **Objetivo:** Implementar el sistema de validación interna

| Tarea | Descripción | Prioridad | Estimación |
|-------|-------------|-----------|------------|
| 2.1 | UI de Generación de Documentos (Formulario Universal) | 🔴 Alta | 8 hrs |
| 2.2 | Panel de "Mis Documentos Pendientes" | 🔴 Alta | 6 hrs |
| 2.3 | Lógica de Auto-aprobación vs Escalamiento | 🔴 Alta | 4 hrs |
| 2.4 | Flujo de Rechazo con observaciones | 🟡 Media | 3 hrs |
| 2.5 | Notificaciones por correo y sistema | 🟡 Media | 4 hrs |
| 2.6 | Dashboard de estado de documentos | 🟡 Media | 5 hrs |

**Entregables:**
- ✅ Formulario universal de documentos
- ✅ Panel de trabajo con cola de pendientes
- ✅ Sistema de notificaciones activo

---

### Fase 3: Flujo de Firmas (Semanas 5-7)
> **Objetivo:** Cadena de firmas con doble modalidad: FIEL (Firma Electrónica Avanzada) y Firma Autógrafa con PIN

#### 🔐 Sistema Dual de Firma

El sistema soportará **dos modalidades de firma** según el nivel de validez jurídica requerido:

| Característica | 🔵 Firma con PIN | 🟢 Firma con FIEL |
|----------------|------------------|-------------------|
| **Validez Jurídica** | Interna/Administrativa | Plena (NOM-151) |
| **Requisitos** | Solo PIN personal | Certificado .cer + Llave .key + Contraseña |
| **Velocidad** | Instantánea | 3-5 segundos |
| **Uso Recomendado** | Aprobaciones internas, visto bueno, minutas | Oficios, contratos, suficiencias, licitaciones |
| **Almacenamiento** | Hash SHA-256 + timestamp | Hash + Cadena FIEL + Sello de tiempo |
| **Delegación** | Permitida | No permitida |

#### 📋 Casos de Uso por Tipo de Firma

**Firma con PIN (Autógrafa Digital):**
- ✓ Aprobación de borradores
- ✓ Visto bueno interno
- ✓ Autorización de viáticos
- ✓ Minutas de reunión
- ✓ Solicitudes internas
- ✓ Validación de reportes

**Firma con FIEL (Electrónica Avanzada):**
- ✓ Suficiencias presupuestales
- ✓ Oficios hacia dependencias externas
- ✓ Contratos y convenios
- ✓ Actas de entrega-recepción
- ✓ Documentos para auditoría
- ✓ Licitaciones y adjudicaciones

#### 📝 Tareas de Desarrollo

| Tarea | Descripción | Prioridad | Estimación |
|-------|-------------|-----------|------------|
| 3.1 | UI de Configuración de Cadena de Firmas | 🔴 Alta | 8 hrs |
| 3.2 | Widget de selección de firmantes (drag & drop) | 🔴 Alta | 6 hrs |
| 3.3 | **Sistema de Firma con PIN** | 🔴 Alta | 6 hrs |
| 3.3.1 | └─ Registro de PIN personal por usuario | 🔴 Alta | 2 hrs |
| 3.3.2 | └─ Modal de validación de PIN | 🔴 Alta | 2 hrs |
| 3.3.3 | └─ Generación de sello digital (hash + timestamp) | 🔴 Alta | 2 hrs |
| 3.4 | **Sistema de Firma con FIEL** | 🔴 Alta | 10 hrs |
| 3.4.1 | └─ Integración con sistema FIEL existente | 🔴 Alta | 4 hrs |
| 3.4.2 | └─ Validación de certificado y llave privada | 🔴 Alta | 3 hrs |
| 3.4.3 | └─ Generación de cadena de firma electrónica | 🔴 Alta | 3 hrs |
| 3.5 | Selector de tipo de firma por documento | 🔴 Alta | 3 hrs |
| 3.6 | Generación de Constancia de Firma (PDF) | 🟡 Media | 4 hrs |
| 3.7 | Sistema de delegación de firma PIN (vacaciones) | 🟢 Baja | 3 hrs |
| 3.8 | Alertas de documentos sin firmar (+24hrs) | 🟡 Media | 2 hrs |
| 3.9 | Histórico de firmas por usuario | 🟡 Media | 3 hrs |

#### 🎨 Diseño de UI para Firma

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

#### 💡 Innovaciones Propuestas
- 🌟 **Firma por Lotes**: Firmar múltiples documentos con una sola autenticación (PIN o FIEL)
- 🌟 **Vista Previa Inteligente**: Resaltado de cambios desde la última revisión
- 🌟 **Comentarios en Línea**: Anotaciones directas sobre el documento
- 🌟 **Firma Híbrida**: Algunos firmantes con PIN, otros con FIEL según su rol
- 🌟 **Verificador QR**: Código QR en el documento para validar autenticidad online

#### ✅ Entregables
- ✅ Flujo de firmas completamente funcional con ambas modalidades
- ✅ Documentos sellados electrónicamente (PIN o FIEL)
- ✅ Bandeja de firmas pendientes por usuario
- ✅ Constancia de firma con detalle de método utilizado
- ✅ Dashboard de firmas realizadas por usuario

---

### Fase 4: Gestión Interna/Externa (Semanas 8-9)
> **Objetivo:** Seguimiento de trámites dentro y fuera de la institución

| Tarea | Descripción | Prioridad | Estimación |
|-------|-------------|-----------|------------|
| 4.1 | Catálogo de Dependencias Externas | 🔴 Alta | 2 hrs |
| 4.2 | Registro de salida de documentos | 🔴 Alta | 4 hrs |
| 4.3 | Seguimiento con fechas de acuse | 🔴 Alta | 4 hrs |
| 4.4 | Timeline visual de gestión externa | 🟡 Media | 5 hrs |
| 4.5 | Alertas de documentos sin respuesta | 🟡 Media | 2 hrs |
| 4.6 | Vinculación con módulo de Correspondencia | 🟢 Baja | 4 hrs |

**Entregables:**
- ✅ Rastreo completo de documentos externos
- ✅ Integración con sistema de correspondencia

---

### Fase 5: Resolución y Cierre (Semanas 10-11)
> **Objetivo:** Finalización formal del ciclo de vida

| Tarea | Descripción | Prioridad | Estimación |
|-------|-------------|-----------|------------|
| 5.1 | Pantalla de cierre de documento | 🔴 Alta | 4 hrs |
| 5.2 | Captura de resultado final | 🔴 Alta | 2 hrs |
| 5.3 | Archivo digital (vault inmutable) | 🔴 Alta | 6 hrs |
| 5.4 | Generación de Expediente Electrónico | 🟡 Media | 5 hrs |
| 5.5 | Reportes de productividad documental | 🟡 Media | 6 hrs |
| 5.6 | Exportación para auditoría | 🟢 Baja | 3 hrs |

**Entregables:**
- ✅ Ciclo de vida completo implementado
- ✅ Archivado digital seguro
- ✅ Reportes gerenciales

---

### Fase 6: Experiencia de Usuario Premium (Semana 12)
> **Objetivo:** Pulir la interfaz y agregar funcionalidades WOW

| Tarea | Descripción | Prioridad | Estimación |
|-------|-------------|-----------|------------|
| 6.1 | Dashboard ejecutivo con gráficas | 🟡 Media | 8 hrs |
| 6.2 | Buscador universal de documentos | 🔴 Alta | 4 hrs |
| 6.3 | Historial visual (timeline) por documento | 🟡 Media | 4 hrs |
| 6.4 | Modo oscuro/claro consistente | 🟢 Baja | 2 hrs |
| 6.5 | Accesos directos y atajos de teclado | 🟢 Baja | 2 hrs |
| 6.6 | Tour guiado para nuevos usuarios | 🟢 Baja | 3 hrs |

---

## 💡 Ideas Innovadoras Adicionales

### 🎨 Experiencia de Usuario
1. **Bandeja Inteligente con IA**: Priorización automática de documentos basada en urgencia, antigüedad y patrones del usuario
2. **Vista Kanban de Documentos**: Arrastrar documentos entre fases como en Trello
3. **Widgets de Escritorio**: Panel personalizable con los indicadores más relevantes para cada usuario
4. **Modo Concentración**: Ocultar distracciones al revisar documentos importantes

### 🔐 Seguridad y Cumplimiento
5. **Blockchain para Bitácora**: Hash encadenado que garantiza inmutabilidad del historial
6. **Marca de Agua Dinámica**: Nombre del usuario visualizando el documento en tiempo real
7. **Autodestrucción de Borradores**: Eliminar versiones no finales después de X días
8. **Auditoría de Accesos**: Quién vio qué documento y cuándo

### 📱 Movilidad
9. **App PWA**: Acceso desde celular para aprobar/firmar documentos urgentes
10. **Notificaciones Push**: Alertas instantáneas de documentos pendientes
11. **Firma por QR**: Escanear código para aprobar desde el teléfono

### 🤖 Automatización
12. **Plantillas Inteligentes**: El sistema sugiere el siguiente paso basado en el tipo de documento
13. **Recordatorios Escalonados**: 24hrs, 48hrs, 72hrs para documentos sin atender
14. **Auto-escalamiento**: Si el titular no firma en X tiempo, pasa al siguiente en la cadena
15. **Reportes Programados**: Envío semanal de documentos pendientes por área

### 📊 Analítica
16. **Cuello de Botella**: Identificar qué usuario/área retrasa más los trámites
17. **Predicción de Tiempos**: IA que estima cuándo se resolverá un documento
18. **Mapa de Calor de Actividad**: Visualizar horarios pico de trabajo documental

---

## 🛠️ Stack Tecnológico Recomendado

| Componente | Tecnología | Justificación |
|------------|------------|---------------|
| Backend | PHP 8.3 + PDO | Consistencia con sistema actual |
| Frontend | JavaScript Vanilla + Bootstrap 5 | Ligero, sin dependencias pesadas |
| Base de Datos | MySQL 8.0 | Soporte JSON, CTEs, mejor indexación |
| PDFs | TCPDF/FPDI | Generación y manipulación de PDFs |
| Firmas | OpenSSL + FIEL | Ya implementado en el sistema |
| Notificaciones | PHPMailer + WebSockets | Emails + tiempo real |
| Almacenamiento | Sistema de archivos + Hash SHA-256 | Integridad verificable |

---

## 📈 Métricas de Seguimiento

### Por Fase
- Porcentaje de avance
- Tareas completadas vs pendientes
- Bugs reportados vs resueltos
- Horas estimadas vs reales

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

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Resistencia al cambio | Alta | Alto | Capacitación progresiva, embajadores |
| Problemas con FIEL | Media | Alto | Pruebas exhaustivas, soporte dedicado |
| Volumen de datos | Baja | Medio | Índices optimizados, particionamiento |
| Pérdida de documentos | Muy Baja | Crítico | Backups diarios, replicación |

---

## 🎓 Plan de Capacitación

1. **Semana 1**: Administradores del sistema
2. **Semana 2**: Generadores de documentos (capturistas)
3. **Semana 3**: Aprobadores y firmantes
4. **Semana 4**: Usuarios de consulta
5. **Continuo**: Videos tutoriales en el sistema

---

## 📝 Próximos Pasos Inmediatos

1. [ ] **Validar** este plan con el equipo directivo
2. [ ] **Priorizar** las fases según necesidades actuales
3. [ ] **Definir** los tipos de documento que entrarán primero
4. [ ] **Asignar** recursos humanos y tiempos
5. [ ] **Iniciar** con Fase 1: Fundamentos

---

## 🏆 Visión Final

> *"Un sistema donde cada documento cuenta su propia historia, desde que nace hasta que cumple su propósito, dejando un rastro inmutable de quién lo tocó, cuándo y por qué. Un sistema que no solo gestiona papeles, sino que acelera decisiones."*

---

**¿Listo para transformar la gestión documental de SECOPE?** 🚀
