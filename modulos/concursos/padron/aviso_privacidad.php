<div class="modal fade" id="modalAvisoPrivacidad" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAvisoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAvisoLabel"><i class="fas fa-user-shield me-2"></i>Aviso de Privacidad Simplificado</h5>
            </div>
            <div class="modal-content-scroll" style="max-height: 60vh; overflow-y: auto; padding: 20px;">
                <p><strong>Responsable del tratamiento de sus datos personales</strong></p>
                <p>La Secretaría de Comunicaciones y Obras Públicas (SECOPE) del Estado de Durango, con domicilio en Calle Constitución 214 Sur, Zona Centro, C.P. 34000, Durango, Dgo., es la responsable del tratamiento de los datos personales que nos proporcione.</p>
                
                <p><strong>Finalidades del tratamiento</strong></p>
                <p>Los datos personales que recabamos de usted, los utilizaremos para las siguientes finalidades que son necesarias para el servicio que solicita:</p>
                <ul>
                    <li>Integración y actualización del Padrón de Contratistas de Obras Públicas.</li>
                    <li>Validación de información y documentación legal, técnica y financiera.</li>
                    <li>Notificaciones relacionadas con el proceso de inscripción o refrendo.</li>
                    <li>Generación de certificados de registro al padrón.</li>
                </ul>

                <p><strong>Datos personales recabados</strong></p>
                <p>Para las finalidades antes mencionadas, recabamos datos de identificación, contacto, laborales y patrimoniales tanto de personas físicas como de representantes legales de personas morales.</p>

                <p><strong>Medidas de Seguridad y Auditoría</strong></p>
                <p>Le informamos que para garantizar la integridad de la información y prevenir accesos no autorizados, el sistema registra automáticamente datos técnicos de su conexión, incluyendo dirección IP, sistema operativo y tipo de navegador, así como la fecha y hora de sus actividades en el portal.</p>

                <p><strong>Derechos ARCO</strong></p>
                <p>Usted tiene derecho a conocer qué datos personales tenemos de usted, para qué los utilizamos y las condiciones del uso que les damos (Acceso). Asimismo, es su derecho solicitar la corrección de su información personal en caso de que esté desactualizada, sea inexacta o incompleta (Rectificación); que la eliminemos de nuestros registros o bases de datos cuando considere que la misma no está siendo utilizada conforme a los principios, deberes y obligaciones previstos en la normativa (Cancelación); así como oponerse al uso de sus datos personales para fines específicos (Oposición).</p>
            </div>
            <div class="modal-footer bg-light">
                <div class="form-check me-auto">
                    <input class="form-check-input" type="checkbox" id="checkAceptoAviso">
                    <label class="form-check-label fw-bold" for="checkAceptoAviso">
                        He leído y acepto los términos del aviso de privacidad
                    </label>
                </div>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnAceptarPrivacidad" disabled>Continuar con el Registro</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales para control
    window.modalAviso = new bootstrap.Modal(document.getElementById('modalAvisoPrivacidad'));
    const checkAcepto = document.getElementById('checkAceptoAviso');
    const btnAceptar = document.getElementById('btnAceptarPrivacidad');
    let onPrivacyAccepted = null;
    
    checkAcepto.addEventListener('change', function() {
        btnAceptar.disabled = !this.checked;
    });
    
    btnAceptar.addEventListener('click', function() {
        modalAviso.hide();
        if (typeof onPrivacyAccepted === 'function') {
            onPrivacyAccepted();
        }
    });

    // Función global para invocar la privacidad
    window.solicitarPrivacidad = function(callback) {
        onPrivacyAccepted = callback;
        // Reset state
        checkAcepto.checked = false;
        btnAceptar.disabled = true;
        modalAviso.show();
    };
});

</script>
